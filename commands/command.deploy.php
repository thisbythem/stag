<?php

class Deploy extends Command {

  protected $env;
  protected $deployed = false;

  public static function helpSummary() {
    return <<<EOF
deploy            Need to deploy your website to a remote server via git, FTP
                  or rsync? Deploy task is here to help!
EOF;
  }

  public static function helpDetail() {
    return <<<EOF
Usage: stag deploy <environment>

This command will not run if you haven't configured your server settings and
deployment strategy in your stag YAML. Please refer to http://github.com/stag
for detailed instructions on how to do so. If you have configured it, please
double check your environment name.
EOF;
  }

  public function run($options = array()) {
    $this->env = array_shift($options);

    $this->displayFeedback("Checking configuration:");
    // Handle incorrect arguments
    $this->handleNoEnvironment();
    $this->handleInvalidEnvironment();

    $this->extractConfigForEnv($this->env);

    // Check if we have a valid strategy
    $this->handleInvalidStrategy();

    if ($this->hasBeenDeployed()) {
      $this->deployed = true;

      // Do we need to pull content first?
      if ($this->shouldPullContentFirst()) {
        $this->displayFeedback("Pulling content from server:");
        $class_name = $this->commands['pull_content'];
        $puller = new $class_name($this->config);
        $puller->run(array($this->env));
      }
    }

    // Deploy the site
    $this->displayFeedback("Deploying to $this->env:");
    $strategy_method_name = 'deployWith' . ucwords($this->strategy);
    $this->{$strategy_method_name}();

    // Clear Cache?
    if ($this->shouldClearCacheAfter()) {
      $this->displayFeedback("Clearing cache:");
      $class_name = $this->commands['clear_cache'];
      $cleaner = new $class_name($this->config);
      $cleaner->run(array($this->env));
    }

    // Set Perms?
    if ($this->shouldSetPermissionsAfter()) {
      $this->displayFeedback("Setting permissions:");
      $class_name = $this->commands['set_permissions'];
      $perms = new $class_name($this->config);
      $perms->run(array($this->env));
    }
  }

  private function shouldPullContentFirst() {
    return $this->config['servers'][$this->env]['deploy']['pull_content_before'];
  }

  private function shouldClearCacheAfter() {
    return $this->config['servers'][$this->env]['deploy']['clear_cache_after'];
  }

  private function shouldSetPermissionsAfter() {
    return $this->config['servers'][$this->env]['deploy']['set_permissions_after'];
  }

  private function shouldUpdateSubmodules() {
    return $this->config['servers'][$this->env]['deploy']['update_submodules'];
  }

  private function handleNoEnvironment() {
    if ($this->env == null) {
      $output = <<<EOF
Usage: stag deploy <environment>

I need an environment name to deploy to.
EOF;
      $this->displayFeedback($output);
      exit(1);
    }
  }

  private function handleInvalidEnvironment() {
    if (!array_key_exists($this->env, $this->config['servers'])) {
      $output = <<<EOF
No configuration for environment: $this->env. Please check your settings and
try again.
EOF;
     $this->displayFeedback($output);
     exit(1);
    }
  }

  private function handleInvalidStrategy() {
    if (!method_exists($this, 'deployWith' . ucwords($this->strategy))) {
      $output = <<<EOF
I don't know how to deploy via $this->strategy. But I'm happy to learn!
EOF;
      $this->displayFeedback($output);
      exit(1);
    }
  }

  private function deployWithGit() {
    $ssh = $this->getSshConnection();
    if ($this->deployed) {
      $output = $ssh->exec('git pull');

      if ($this->shouldUpdateSubmodules()) {
        $output .= $ssh->exec('git submodule init; git submodule update');
      }
    } else {
      $path_pieces = explode('/', $this->webroot);
      $dir_name = array_pop($path_pieces);
      $path = implode('/', $path_pieces);
      $repo = $this->repo_url;
      if (!$repo) {
        $this->displayFeedback('I need a git repo to clone.');
        exit(1);
      }
      $output = $ssh->homeExec("cd $path; git clone $repo $dir_name");
    }

    $this->displayFeedback($output);
  }

  private function deployWithRsync() {
    $this->displayFeedback("Deploying with rsync to $this->env");
    $ignore_files = $this->getIgnoreFiles();

    if ($this->hasNotBeenDeployed()) {
      $ssh = $this->getSshConnection();
      $ssh->homeExec("mkdir -p $this->webroot/{_cache,_logs}");
    }

    $cmd = 'rsync -e ';

    if ($this->port !== null) {
      $cmd .= "'ssh -p $this->port' ";
    }

    $cmd .=  "-avl --stats --progress ";

    foreach ($ignore_files as $ignore) {
      $cmd .= "--exclude $ignore ";

    }

    $cmd .=  BASE_PATH . " $this->user@$this->host:$this->webroot;";

    $output = shell_exec($cmd);
    $this->displayFeedback($output);
  }

  private function deployWithFtp() {
    $password = ($this->ftp_password) ? $this->ftp_password : $this->password;

    try {
      $ftp = new Ftp("ftp://$this->user:$password@$this->host");

      if (!$ftp->isDir($this->webroot)) {
        $ftp->mkdir($this->webroot);
        $ftp->mkdir("$this->webroot/_logs");
        $ftp->mkdir("$this->webroot/_cache");
      }

      $this->putAll($ftp, BASE_PATH, $this->webroot);
      $this->displayFeedback("Site has been deployed.");
      $ftp->close();
    } catch (Exception $e) {
      $this->displayFeedback("Something has gone wrong:");
      $this->displayFeedback(var_dump($e));
      exit(1);
    }
  }

  private function putAll($ftp, $src_dir, $dst_dir) {
    $ignore_files = $this->getIgnoreFiles();

    $d = dir($src_dir);
    while($file = $d->read()) {
      if (!in_array($file, $ignore_files)) {
        if (is_dir($src_dir."/".$file)) {
          if (!$ftp->isDir($dst_dir."/".$file)) {
            $ftp->mkdir($dst_dir."/".$file);
            $this->displayFeedback("Creating $dst_dir/$file");
          }
          $this->putAll($ftp, $src_dir."/".$file, $dst_dir."/".$file);
        } else {
          $dest_file = "$dst_dir/$file";
          $upload = $ftp->put($dest_file, $src_dir."/".$file, FTP_BINARY);
          $this->displayFeedback("Uploading $dest_file");
        }
      }
    }
  }

  private function getIgnoreFiles() {
    $system_ignore_files = array('.', '..');
    $ignore_files = $this->config['servers'][$this->env]['deploy']['ignore_files'];

    if (!empty($ignore_files)) {
      $ignore_files = array_merge($system_ignore_files, $ignore_files);
    }
    return $ignore_files;
  }

}

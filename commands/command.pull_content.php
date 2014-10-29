<?php

class PullContent extends Command {

  protected $env;

  public static function helpSummary() {
    return <<<EOF
pull_content      Pull down content from the server.
EOF;
  }

  public static function helpDetail() {
    return <<<EOF
Usage: stag pull_content <environment>

This command will pull down the content changes from the server. You
can configure it to use rsync, ftp or git.
EOF;
  }

  public function run($options = array()) {
    $this->env = array_shift($options);

    // Handle incorrect arguments
    $this->handleNoEnvironment();

    $this->extractConfigForEnv($this->env);

    // Check if we have a valid strategy
    $this->handleInvalidStrategy();

    // Check that it's been deployed
    $this->handleNotDeployed();

    // Pull that content
    $strategy_method_name = 'pullContentWith' . ucwords($this->strategy);
    $this->{$strategy_method_name}();
  }

  private function handleNoEnvironment() {
    if ($this->env == null) {
      $output = <<<EOF
Usage: stag pull_content <server>

I need a server name in order to pull content from it.
EOF;
      $this->displayFeedback($output);
      exit(1);
    }
  }

  private function handleInvalidStrategy() {
    if (!method_exists($this, 'pullContentWith' . ucwords($this->strategy))) {
      $output = <<<EOF
I don't know how to pull_content via $this->strategy. Care to show me how?
EOF;
      $this->displayFeedback($output);
      exit(1);
    }
  }

  private function pullContentWithGit() {
    $this->displayFeedback("Checking for changes on $this->env");
    $ssh = $this->getSshConnection();

    $output = $ssh->exec("cd $this->webroot; git status");
    $has_git_changes = strpos($output, "committed");
    $has_git_changes += strpos($output, "Untracked");

    if (!$has_git_changes) {
      $this->displayFeedback("No changes on $this->env.");
      return;
    }

    $commit_message = $this->config['servers'][$this->env]['pull_content']['commit_message'];
    $this->displayFeedback("Committing and pushing up content.");
    $output = $ssh->exec("git add --all");
    $output .= $ssh->exec("git commit -am \"$commit_message\"");
    $output .= $ssh->exec("git pull; git push;");
    $this->displayFeedback($output);

    $this->displayFeedback("Updates pushed.");
  }

  private function pullContentWithRsync() {
    $content_dirs = $this->getContentDirectories();

    $this->displayFeedback("Pulling content from $this->env");

    foreach ($content_dirs as $dir) {
      $cmd = 'rsync -e ';

      if ($this->port !== null) {
        $cmd .= "'ssh -p $this->port' ";
      }

      $cmd .=  "-avl --stats --progress $this->user@$this->host:$this->webroot/$dir .;";

      $output = shell_exec($cmd);
      $this->displayFeedback($output);
    }

    $this->displayFeedback("All content pulled.");
  }

  private function pullContentWithFtp() {
    $this->displayFeedback("Pulling content from $this->env");
    $dirs = $this->getContentDirectories();
    $password = ($this->ftp_password) ? $this->ftp_password : $this->password;

    try {
      $ftp = new Ftp("ftp://$this->user:$password@$this->host");
      foreach ($dirs as $dir) {
        $ftp->chdir("$this->webroot/$dir");
        $files = $ftp->nlist('*');
        $this->getAll($ftp, $dir, $files);
      }
      $this->displayFeedback("Content has been pulled.");
      $ftp->close();
    } catch (Exception $e) {
      $this->displayFeedback("Something has gone wrong:");
      $this->displayFeedback(var_dump($e));
      exit(1);
    }
  }

  private function getAll($ftp, $dir, $files) {
    $system_ignore_files = array('.', '..');

    foreach ($files as $file) {
      if (!in_array($file, $system_ignore_files)) {
        if ($ftp->isDir($file)) {
          $files = $ftp->nlist($file);
          $this->getAll($ftp, $dir, $files);
        } else {
          $ftp->get("$dir/$file", $file, FTP_BINARY);
          $this->displayFeedback("Downloading: $file");
        }
      }
    }
  }

  private function getContentDirectories() {
    $content_dirs = array(Config::getContentRoot());
    $config_dirs = $this->pull_content['content_directories'];

    if (!empty($config_dirs)) {
      $content_dirs = array_unique(array_merge($content_dirs, $config_dirs));
    }

    return $content_dirs;
  }

}

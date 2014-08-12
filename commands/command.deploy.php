<?php

class Deploy extends Command {

  protected $env;

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

    $this->displayFeedback("Checking configuration");
    // Handle incorrect arguments
    $this->handleNoEnvironment();
    $this->handleInvalidEnvironment();

    // Check if we have a valid strategy
    $this->strategy = $this->config['servers'][$this->env]['deploy']['strategy'];
    $this->handleInvalidStrategy();

    // Do we need to pull content first?
    if ($this->shouldPullContentFirst()) {
      $this->displayFeedback("Pulling content from server");
      $class_name = $this->commands['pull_content'];
      $puller = new $class_name($this->config);
      $puller->run(array($this->env));
    }

    // Deploy the site
    $this->displayFeedback("Deploying to $this->env:");
    $this->extractConfigForEnv($this->env);
    $strategy_method_name = 'deployWith' . ucwords($this->strategy);
    $this->{$strategy_method_name}();

    // Clear Cache?
    if ($this->shouldClearCacheAfter()) {
      $this->displayFeedback("Clearing cache:");
      $class_name = $this->commands['clear_cache'];
      $cleaner = new $class_name($this->config);
      $cleaner->run(array($this->env));
    }
  }

  private function shouldPullContentFirst() {
    return $this->config['servers'][$this->env]['deploy']['pull_content_before'];
  }

  private function shouldClearCacheAfter() {
    return $this->config['servers'][$this->env]['deploy']['clear_cache_after'];
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
    $output = $ssh->exec('git pull');

    $this->displayFeedback($output);
  }

  private function deployWithRsync() {
    $this->displayFeedback("Deploying with rsync to $this->env");

    $cmd = 'rsync -e ';

    if ($this->port !== null) {
      $cmd .= "'ssh -p $this->port' ";
    }

    $cmd .=  "-avl --stats --progress --exclude .git* " . BASE_PATH . " $this->user@$this->host:$this->webroot;";

    $output = shell_exec($cmd);
    $this->displayFeedback($output);
  }

  private function deployWithFtp() {
    $this->displayFeedback('Imma gonna deploy with ftp');
  }
}

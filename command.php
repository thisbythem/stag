<?php

abstract class Command {

  abstract public function run($opts = array());
  abstract static public function helpSummary();
  abstract static public function helpDetail();

  protected $config;
  protected $commands = array();
  protected $env = null;

  public function __construct($config) {
    $this->config = $config;
    $this->commands = $this->getCommands();
  }

  protected function displayFeedback($msg) {
    Addon::getApi('stag')->displayFeedback($msg);
  }

  private function getCommands() {
    if (empty($this->commands)) {
      // Make this dynamic
      $this->commands = array(
        'clear_cache' => 'ClearCache',
        'deploy' => 'Deploy',
        'help' => 'Help',
        'pull_content' => 'PullContent',
        'set_permissions' => 'SetPermissions'
      );
    }

    return $this->commands;
  }

  protected function getSshConnection() {
    $this->extractConfigForEnv();

    $ssh = new SSH($this->user, $this->host);
    $ssh->setForwardAgent($this->forward_agent);
    $ssh->setWebroot($this->webroot);

    if ($this->port) {
      $ssh->setPort($this->port);
    }

    return $ssh;
  }

  protected function extractConfigForEnv() {
    $env_config = $this->config['servers'][$this->env];

    if ($this->env == null) {
      $this->handleNoEnv();
    }

    if ($env_config == null) {
     $this->handleNoConfigForEnv();
    }

    $this->webroot = $env_config['webroot'];
    $this->user = $env_config['user'];
    $this->password = $env_config['password'];
    $this->ftp_password = $env_config['ftp_password'];
    $this->host = $env_config['host'];
    $this->port = $env_config['port'];
    $this->forward_agent = $env_config['forward_agent'];
    $this->strategy = $env_config['strategy'];
    $this->deploy = $env_config['deploy'];
    $this->repo_url = $env_config['repo_url'];
    $this->pull_content = $env_config['pull_content'];
  }

  private function handleNoConfigForEnv() {
    $output = <<<EOF
There is no configuration for $this->env. Please check your spelling (or
config) and try again.
EOF;
    $this->displayFeedback($output);
    exit(1);
  }

  protected function handleNoEnv() {
    $output = <<<EOF
I need an environment in order to do this.
EOF;
    $this->displayFeedback($output);
    exit(1);
  }

  protected function handleNotDeployed() {
    if (!$this->hasBeenDeployed()) {
      $output = <<<EOF
I can't find a Statamic site at the configured webroot: $this->webroot

Please check your configuration or try deploying first?
EOF;
      $this->displayFeedback($output);
      exit(1);
    }
  }

  protected function hasBeenDeployed() {
    $ssh = $this->getSshConnection();
    $webroot_exists = $ssh->homeExec("[ -d $this->webroot ] && echo 'found'");
    return (trim($webroot_exists) === 'found') ? true : false;
  }

  protected function hasNotBeenDeployed() {
    return !$this->hasBeenDeployed();
  }
}


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
        //'install' => 'Install',
        'pull_content' => 'PullContent'
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

    if ($env_config == null) {
     $this->handleNoConfigForEnv();
    }

    $this->webroot = $env_config['webroot'];
    $this->deploy_strategy = $env_config['deploy_strategy'];
    $this->pull_content_strategy = $env_config['pull_content_strategy'];
    $this->user = $env_config['user'];
    $this->password = $env_config['password'];
    $this->host = $env_config['host'];
    $this->port = $env_config['port'];
    $this->forward_agent = $env_config['forward_agent'];
    $this->pull_content_before_deploy = $env_config['pull_content_before_deploy'];
  }

  private function handleNoConfigForEnv() {
    $output = <<<EOF
There is no configuration for $this->env. Please check your spelling (or
config) and try again.
EOF;
    $this->displayFeedback($output);
    exit(1);
  }
}

class SSH {
  private $host;
  private $user;
  private $password;
  private $timeout = 1;
  private $forward_agent;
  private $webroot;
  private $port;

  public function __construct($user, $host) {
    $this->user = $user;
    $this->host = $host;
  }

  public function setForwardAgent($forward_agent) {
    $this->forward_agent = $forward_agent;
  }

  public function setWebroot($webroot) {
    $this->webroot = $webroot;
  }

  public function setPort($port) {
    $this->port = $port;
  }

  public function exec($cmd) {
    $ssh = $this->connectionString();
    return shell_exec("$ssh 'cd $this->webroot; $cmd'");
  }

  protected function connectionString() {
    $conn = array('ssh');

    if ($this->forward_agent) {
      $conn[] = '-A';
    }

    if ($this->port) {
      $conn[] = "-p $this->port";
    }

    $conn[] = "-o ConnectTimeout=$this->timeout";

    $conn[] = "$this->user@$this->host";

    return implode(' ', $conn);
  }
}

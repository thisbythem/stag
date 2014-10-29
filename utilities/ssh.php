<?php

class SSH {
  private $host;
  private $user;
  private $timeout = 5;
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

  public function homeExec($cmd) {
    $ssh = $this->connectionString();
    return shell_exec("$ssh '$cmd'");
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

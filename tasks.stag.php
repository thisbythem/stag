<?php

class Tasks_stag extends Tasks {

  public $commands = array();
  public $show_command_line_output = true;

  public function run($method, $options = array()) {

    if ($method == null) {
      $this->handleNoCommandGiven();
    }

    $filename = __DIR__ . "/commands/command.$method.php";

    if (File::exists($filename)) {
      $klass = $this->classify($method);
      $obj = new $klass($this->config);
      $obj->run($options);
    } else {
      $this->handleCommandNotFound($method);
    }
  }

  public function displayFeedback($message) {
    if ($this->show_command_line_output) {
      fwrite(STDOUT, "$message\n\n");
    }
  }

  private function classify($str) {
   return ucwords(Helper::camelCase($str));
  }

  private function handleNoCommandGiven() {
    $output = <<<EOF
               ______    _________        _          ______
 {__}        .' ____ \  |  _   _  |      / \       .' ___  |         {__}
  \/_____!   | (___ \_| |_/ | | \_|     / _ \     / .'   \_|    !_____\/
    \----|    _.____`.      | |        / ___ \    | |   ____    |----/
    /|   |\  | \____) |    _| |_     _/ /   \ \_  \ `.___]  |  /|   |\
              \______.'   |_____|   |____| |____|  `._____.'

Stag lets you Statamic-it-up on the command line.
Usage: stag <command> <options>
Try stag help to list all commands.
EOF;
    $this->displayFeedback($output);
    exit(1);
  }

  private function handleCommandNotFound($cmd) {
    $output = <<<EOF
I don't know how to $cmd. Would you like to teach me?
EOF;
    $this->displayFeedback($output);
    exit(1);
  }
}

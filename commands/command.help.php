<?php

class Help extends Command {

  public function run($options = array()) {
    if (empty($options)) {
      $this->displayGeneralHelp();
    } else {
      $command = array_shift($options);
      $this->displayCommandHelp($command);
    }
    return true;
  }

  public static function helpDetail() {
    return <<<EOF
That's so meta of you. You str8 cRaZy?!?!!!11
EOF;
  }

  public static function helpSummary() {
    return <<<EOF
help              Unsure of how to call a certain task? Just run stag help, and
                  I'm more than happy to remind you. :)
EOF;
  }

  protected function displayGeneralHelp() {
    $output = <<<EOF
For specific command help, run: stag help <command>

          -= CURRENTLY INSTALLED COMMANDS =-
EOF;
    $this->displayFeedback($output);

    foreach ($this->commands as $name => $className) {
      $this->displayFeedback($className::helpSummary());
    }
    exit(0);
  }

  protected function displayCommandHelp($command) {
    if (array_key_exists($command, $this->commands)) {
      $klass = $this->commands[$command];
      if (method_exists($klass, 'helpDetail')) {
        $this->displayFeedback($klass::helpDetail());
      } else {
        $this->displayFeedback("Sorry! No help found for $command");
      }
      exit(0);
    }
    $this->displayNotFound($command);
  }

  private function displayNotFound($cmd) {
    $this->displayFeedback("Sorry! I don't know anything about $cmd.");
    exit(1);
  }
}

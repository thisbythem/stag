<?php

class Install extends Command {

  public function run($options = array()) {
    $this->displayFeedback('Coming Soon!');
  }

  public static function helpDetail() {
    return <<<EOF
      !!COMING SOON!!

Usage: stag install <add-on name>

Ideally, I will search Github for the supplied add-on name. If I find it, I'll
install it in the add-ons directory. I have yet to be implemented, sit tight.
EOF;
  }

  public static function helpSummary() {
    return <<<EOF
install           Install add-ons in a snap.
EOF;
  }

}

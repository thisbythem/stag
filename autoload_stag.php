<?php

function autoload_stag() {
  require_once 'vendor/ftp/src/Ftp.php';
  require_once 'command.php';
  require_once 'utilities/ssh.php';

  $commands = glob(__DIR__ . '/commands/command.*.php');
  foreach ($commands as $cmd) {
    require_once $cmd;
  }
}

spl_autoload_register('autoload_stag');


<?php

set_include_path(__DIR__ . '/vendor/phpseclib');

function autoload_stag() {
  require_once 'vendor/phpseclib/System/SSH/Agent.php';
  require_once 'vendor/phpseclib/Net/SSH2.php';
  require_once 'vendor/ftp/src/Ftp.php';
  require_once 'command.php';

  $commands = glob(__DIR__ . '/commands/command.*.php');
  foreach ($commands as $cmd) {
    require_once $cmd;
  }
}

spl_autoload_register('autoload_stag');


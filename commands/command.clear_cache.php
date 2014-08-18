<?php

class ClearCache extends Command {

  private $directories;

  public function run($options = array()) {
    $this->env = array_shift($options);
    $this->directories = $this->config['clear_cache']['directories'];

    if ($this->env == null) {
      $this->clearLocalCache();
    } else {
      $this->clearRemoteServerCache();
    }
  }

  public static function helpDetail() {
    return <<<EOF
Usage: stag clear_cache <environment>

Clearing cache so you don't have to: This will clear Statamic's _cache
directory of it's contents.

If you pass in an environment name, this will SSH into that server and clear
the _cache diretory there. You can configure what directories it clears in your
configuration YAML.
EOF;
  }

  public static function helpSummary() {
    return <<<EOF
clear_cache       Clears out the cache directory. Pass in an environment to
                  clear the cache on a remote server.
EOF;
  }

  private function clearLocalCache() {
    $cache_dir = BASE_PATH . '/_cache';

    foreach ($this->directories as $dir) {
      $sub_dir = "$cache_dir/$dir";
      if (Folder::exists($sub_dir)) {
        $output = shell_exec("rm -rf $sub_dir/*");
        if (strpos($output, 'denied')) {
          $output = "ERROR:\n$output";
        } else {
          $output = "_cache/$dir has been cleared.";
        }
        $this->displayFeedback($output);
      }
    }
  }

  private function clearRemoteServerCache() {
    $this->handleNotDeployed();

    $ssh = $this->getSshConnection();
    $cache_dir = "_cache";

    $this->displayFeedback("Connecting to $this->env:");

    foreach ($this->directories as $dir) {
      $cmd = $ssh->exec("rm -rf $cache_dir/$dir/*");

      if (strpos($cmd, 'Permission denied')) {
        $output = <<<EOF
There was a problem removing $dir contents. Please check that you
have write permissions on the directory.

::SERVER RESPONSE::
$cmd
EOF;
      } else {
        $output = "_cache/$dir has been cleared.";
      }
      $this->displayFeedback($output);
    }
  }
}

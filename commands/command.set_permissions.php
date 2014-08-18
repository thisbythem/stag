<?php

class SetPermissions extends Command {

  private $permissions;
  private $writable_permissions;
  private $writable_directories;

  public function run($options = array()) {
    $this->env = array_shift($options);
    $config = $this->config['set_permissions'];

    $this->permissions = $config['site_permissions'];
    $this->writable_permissions = $config['writable_permissions'];
    $this->writable_directories = $config['writable_directories'];

    if ($this->env == null) {
      $this->setPermissionsLocal();
    } else {
      $this->setPermissionsOnServer();
    }
  }

  public static function helpDetail() {
    return <<<EOF
Usage: stag set_permissions <environment>

Making it easy to set permissions. If you run the command without an
environment, it'll set your local site's permissions.
EOF;
  }

  public static function helpSummary() {
    return <<<EOF
set_permissions   Set file permissions on your site.
EOF;
  }

  private function setPermissionsLocal() {
    $cmd = "chmod -R $this->permissions " . BASE_PATH;
    $output = shell_exec($cmd);
    $this->displayFeedback($output);

    if ($output == '') {
      $this->displayFeedback("Site permissions set");
    } else {
      $this->displayFeedback($output);
    }

    foreach ($this->writable_directories as $dir) {
      $cmd = "chmod -R $this->writable_permissions $dir";
      $output = shell_exec($cmd);

      if ($output == '') {
        $this->displayFeedback("$dir permissions set");
      } else {
        $this->displayFeedback($output);
      }
    }
  }

  private function setPermissionsOnServer() {
    $this->handleNotDeployed();

    $ssh = $this->getSshConnection();
    $this->displayFeedback("Connecting to $this->env:");
    $output = $ssh->exec("chmod -R $this->permissions .");

    if ($output == '') {
      $this->displayFeedback("Site permissions set");
    } else {
      $this->displayFeedback($output);
    }

    foreach ($this->writable_directories as $dir) {
      $output = $ssh->exec("chmod -R $this->writable_permissions $dir");

      if ($output == '') {
        $this->displayFeedback("$dir permissions set");
      } else {
        $this->displayFeedback($output);
      }
    }
  }

}

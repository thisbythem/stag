<?php

class PullContent extends Command {

  protected $env;

  public static function helpSummary() {
    return <<<EOF
pull_content      Pull down content from the server.
EOF;
  }

  public static function helpDetail() {
    return <<<EOF
Usage: stag pull_content <environment>

This command will pull down the content changes from the server. You
can configure it to use rsync, ftp or git.
EOF;
  }

  public function run($options = array()) {
    $this->env = array_shift($options);

    // Handle incorrect arguments
    $this->handleNoEnvironment();

    // Check if we have a valid strategy
    $this->strategy = $this->config['servers'][$this->env]['pull_content']['strategy'];
    $this->handleInvalidStrategy();

    // Check that it's been deployed
    $this->handleNotDeployed();

    // Pull that content
    $strategy_method_name = 'pullContentWith' . ucwords($this->strategy);
    $this->{$strategy_method_name}();
  }

  private function handleNoEnvironment() {
    if ($this->env == null) {
      $output = <<<EOF
Usage: stag pull_content <server>

I need a server name in order to pull content from it.
EOF;
      $this->displayFeedback($output);
      exit(1);
    }
  }

  private function handleInvalidStrategy() {
    if (!method_exists($this, 'pullContentWith' . ucwords($this->strategy))) {
      $output = <<<EOF
I don't know how to pull_content via $this->strategy. Care to show me how?
EOF;
      $this->displayFeedback($output);
      exit(1);
    }
  }

  private function pullContentWithGit() {
    $this->displayFeedback("Checking for changes on $this->env");
    $ssh = $this->getSshConnection();

    $output = $ssh->exec("cd $this->webroot; git status");
    $has_git_changes = strpos($output, "committed");
    $has_git_changes += strpos($output, "Untracked");

    if (!$has_git_changes) {
      $this->displayFeedback("No changes on $this->env.");
      return;
    }

    $commit_message = $this->config['servers'][$this->env]['pull_content']['commit_message'];
    $this->displayFeedback("Committing and pushing up content.");
    $output = $ssh->exec("git add --all");
    $output .= $ssh->exec("git commit -am \"$commit_message\"");
    $output .= $ssh->exec("git pull; git push;");
    $this->displayFeedback($output);

    $this->displayFeedback("Updates pushed.");
  }

  private function pullContentWithRsync() {
    $content_root = Config::getContentRoot();

    $this->displayFeedback("Pulling content from $this->env");

    $cmd = 'rsync -e ';

    if ($this->port !== null) {
      $cmd .= "'ssh -p $this->port' ";
    }

    $cmd .=  "-avl --stats --progress $this->user@$this->host:$this->webroot/$content_root .;";

    $output = shell_exec($cmd);
    $this->displayFeedback($output);
  }

  private function pullContentWithFtp() {
    $content_root = Config::getContentRoot();

    $this->displayFeedback("Pulling content from $this->env");

    try {
      $ftp = new Ftp("ftp://$this->user:$this->password@$this->host/$this->webroot/$content_root");
      $files = $ftp->nlist('*');
      $this->recursivelyGetFilesWithFtp($ftp, $files);
      $this->displayFeedback("Content has been pulled.");
      $ftp->close();
    } catch (Exception $e) {
      $this->displayFeedback("Something has gone wrong:");
      $this->displayFeedback(var_dump($e));
      exit(1);
    }
  }

  private function recursivelyGetFilesWithFtp($ftp, $files) {
    $content_root = Config::getContentRoot();
    foreach ($files as $file) {
      if ($ftp->isDir($file)) {
        $files = $ftp->nlist($file);
        $this->recursivelyGetFilesWithFtp($ftp, $files);
      } else {
        $ftp->get("$content_root/$file", $file, FTP_BINARY);
      }
    }
  }

}

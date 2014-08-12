<?php

include_once 'autoload_stag.php';

class API_stag extends API {

  public function run($method, $opts = array()) {
    $this->tasks->run($method, $opts);
  }

  public function displayFeedback($msg) {
    $this->tasks->displayFeedback($msg);
  }

}

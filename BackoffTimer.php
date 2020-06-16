<?php

/**
 *
 */
class BackoffTimer {

  private static $instance = NULL;

  private $backoff_time_long;
  private $backoff_time_short;

  private function __construct() {
  }

  public static function getInstance() {
    if (is_null(self::$instance)) { self::$instance = new BackoffTimer(); }
    return self::$instance;
  }

  function init($short, $long) {
    $this->backoff_time_short = $short;
    $this->backoff_time_long = $long;
  }

  function sleep($message, $long = false) {
    if ($long) {
      $sleep = $this->backoff_time_long;
    } else {
      $sleep = $this->backoff_time_short;
    }
    printBasicMessage('==> Sleeping (' . $message . ') ' . $sleep . ' second(s) <==');
    sleep($sleep);
  }

}
 ?>

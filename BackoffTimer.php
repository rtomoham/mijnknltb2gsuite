<?php

/**
 *
 */
class BackoffTimer {

  private static $instance = NULL;

  private $long;
  private $short;

  private $max;

  private function __construct() {
  }

  public static function getInstance() {
    if (is_null(self::$instance)) { self::$instance = new BackoffTimer(); }
    return self::$instance;
  }

  function increaseShort() {
    $this->short = 2 * $this->short;
    if ($this->short > $this->max) {
      $this->short = $this->max;
    }
  }

  /*
  * PRE:  TRUE
  * POST: $this->short has been initialized (min value: 1)
  *       $this->long has been initialized (min value: 60)
  *       $this->max has been initialized (== $this->long)
  */
  function init($short, $long) {
    if (1 > $short) {
      $short = 1;
    }
    if (60 > $long) {
      $long = 60;
    }
    $this->short = $short;
    $this->long = $long;
    $this->max = $long;
  }

  function sleep($message, $isLong = false) {
    if ($isLong) {
      $sleep = $this->long;
    } else {
      $sleep = $this->short;
    }
    printBasicMessage('==> Sleeping (' . $message . ') ' . $sleep . ' second(s) <==');
    sleep($sleep);
  }

}
 ?>

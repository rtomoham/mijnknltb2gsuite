<?php

require_once('Settings.php');

class TournamentPlayer {

  private $name;
  private $url;

  function __construct($name, $url) {
    $this->name = $name;
    $this->url = Settings::URL_MIJNKNLTB . $url;
  }

  function getUrl() {
    return $this->url;
  }

  function getName() {
    return $this->name;
  }

  function getLink() {
    return '<a href="' . $this->url . '">' . $this->name . '</a>';
  }

  function toString() {
    return
      "$this->name ($this->url)";
  }

} ?>

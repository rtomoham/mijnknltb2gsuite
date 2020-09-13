<?php

class Draw {

  private $matches = [];
  private $id;
  private $title;

  function __construct($id, $title) {
    $this->id = $id;
    $this->title = $title;
  }

  function getId() {
    return $this->id;
  }

  function getMatch($i) {
    if ($i < sizeof($this->matches)) {
      return $this->matches[$i];
    } else {
      return NULL;
    }
  }

  function getMatches() {
    return $this->matches;
  }

  function getTitle() {
    return $this->title;
  }

  function addMatch($match) {
    $this->matches[] = $match;
  }

}
?>

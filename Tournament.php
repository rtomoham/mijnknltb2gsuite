<?php

class Tournament {

  private $id;
  private $title;
  private $draws = [];

  function __construct($id, $title) {
    $this->id = $id;
    $this->title = $title;
  }

  function getDraw($i) {
    if ($i < sizeof($this->draws)) {
      return $this->draws[$i];
    } else {
      return NULL;
    }
  }

  function getDraws() {
    return $this->draws;
  }

  function getId() {
    return $this->id;
  }

  function getTitle() {
    return $this->title;
  }

  function addDraw($draw) {
    $this->draws[] = $draw;
  }

}
?>

<?php
class Player {

  private $name;
  private $knltbId;
  private $ratingSingles;
  private $ratingDoubles;

  function __construct($name, $ratingSingles, $ratingDoubles) {
//    $this->knltbId = $knltbId;
    $this->name = $name;
    $this->ratingDoubles = $ratingDoubles;
    $this->ratingSingles = $ratingSingles;
  }

  function getKnltbId() {
    return $this->knltbId;
  }

  function getName() {
    return $this->name;
  }

  function getRatings() {
    return array($this->ratingSingles, $this->ratingDoubles);
  }

  function toString() {
    return
      "$this->name ($this->ratingSingles|$this->ratingDoubles)";
  }

} ?>

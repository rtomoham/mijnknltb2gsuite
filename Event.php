<?php

class Event {
  /*
  * An Event contains all the details needed to upload it to
  * Google. Our app will apply:
  *   - a default duration of 5 hours;
  *   - a single time zone, i.e. Europe/Amsterdam.
  */
  private const TIME_ZONE = 'Europe/Amsterdam';
  private const DURATION = '5';
  private const KEYWORD_DATETIME = 'dateTime';
  private const KEYWORD_TIMEZONE = 'timeZone';
  private $start;
  private $end;
  private $summary = 'Default event summary';
  private $description = 'Default event description';
  private $location = 'Default event location';
  protected $id = 'Default event ID';

  function __construct($id, $summary, $description, $location, $start) {
      $this->id = $id;
      $this->setSummary($summary);
      $this->setDescription($description);
      $this->setLocation($location);
      $this->setStart($start);

      // set default duration of 6 hours
      $end = new DateTime('@' . $start);
      $end->setTimestamp($start);
      $end->add(new DateInterval('PT' . self::DURATION . 'H'));
      $this->end = $end->getTimestamp();
  }

  // Start Getters and Setters
  function getDescription() {
    return $this->description;
  }

  private function getDateTime($dateTimeString) {
    return array(
      self::KEYWORD_DATETIME => $dateTimeString,
      self::KEYWORD_TIMEZONE => self::TIME_ZONE
    );
  }

  function getEnd() {
    return $this->getDateTime($this->getEndRFC3339());
  }

  function getEndRFC3339() {
    return $this->getRFC3339($this->end);
  }

  function getHuman($date) {
    return date('j M Y H:i', $date) . 'h';
  }

  function getId() {
    return $this->id;
  }

  function getLocation() {
    return $this->location;
  }

  function getRFC3339($date) {
    return date(DateTimeInterface::RFC3339, $date);
  }

  function getStart() {
    return $this->getDateTime($this->getStartRFC3339());
  }

  function getStartHuman() {
    return $this->getHuman($this->start);
  }

  function getStartRFC3339() {
    return $this->getRFC3339($this->start);
  }

  function getBasicSummary() {
    return $this->summary;
  }

  function getSummary() {
    return $this->getBasicSummary();
  }

  function setDescription($description) {
    $this->description = $description;
  }

  function setEnd($date) {
    $this->end = $date;
  }

  function setLocation($location) {
    $this->location = $location;
  }

  function setStart($date) {
    $this->start = $date;
  }

  function setSummary($summary) {
    $this->summary = $summary;
  }

  // End Getters and Setters

}

?>

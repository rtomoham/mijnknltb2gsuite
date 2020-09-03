<?php
class GoogleCalendarAccount {
  private $description;
  private $identifier;
  private $mijnknltbProfileIds;
  private $leaguesFilter = NULL;
  private $leaguesOnly = false;
  private $lastUpdate = 0;

  function __construct(
    $identifier,
    $mijnknltbProfileIds,
    $description = 'Default description for GCal') {

    $this->identifier = $identifier;
    $this->mijnknltbProfileIds = $mijnknltbProfileIds;
    $this->description = $description;
  }

  function getIdentifier() {
    return $this->identifier;
  }

  function getDescription() {
    return $this->description;
  }

  function getLastUpdate() {
    return $this->lastUpdate;
  }

  function getLeaguesFilter() {
    return $this->leaguesFilter;
  }

  function getmijnknltbProfileIds() {
    return $this->mijnknltbProfileIds;
  }

  function setDescription($description) {
    $this->description = $description;
  }

  function setLastUpdate($lastUpdate) {
    $this->lastUpdate = $lastUpdate;
  }

  function setLeaguesOnly() {
    $this->leaguesOnly = true;
  }

  function setLeaguesFilter($filter) {
    $this->leaguesFilter = $filter;
  }

//  function isActive() {
//    return $this->active;
//  }

  function isInLeaguesFilter($string) {
    if (is_null($leaguesFilter)) { return true; }
    foreach ($leaguesFilter as $filter) {
      if (0 < strpos($string, $filter)) { return true; }
    }
    return false;
  }

  function isLeaguesOnly() {
    return $this->leaguesOnly;
  }

  function toString() {
    return 'Identifier: ' . "$this->identifier\n";
  }


} ?>

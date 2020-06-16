<?php
class League {

  private $leagueId;
  private $teamId;

  private $leagueMatches = [];

  function __construct($leagueId, $teamId) {
    $this->leagueId = $leagueId;
    $this->teamId = $teamId;
  }

  function addMatch($leagueMatch) {
    $leagueMatches[] = $leagueMatch;
  }

  function getLeagueId() {
    return $this->leagueId;
  }

  function getMatch($matchNr) {
    return $this->leagueMatches[$matchNr];
  }

  function getMatches() {
    return $this->leagueMatches;
  }

  function getName() {
    return $this->leagueMatches[0]->getLeagueName();
  }

  function getPlayers() {
    return $this->leagueMatches[0]->getPlayers();
  }

  function getTeamId() {
    return $this->teamId;
  }

  function setMatches($leagueMatches) {
    $this->leagueMatches = $leagueMatches;
  }

} ?>

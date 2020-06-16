<?php
require_once('Match.php');

class LeagueMatch extends Match {

  private const STRING_URL_INFIX_LEAGUE = '/league/';
  private const STRING_URL_INFIX_TEAM = '/team-match/';

  private $teamId;
  private $players;
  private $nr;

  function __construct($matchId, $summary, $leagueName, $leagueId, $start, $home, $away) {
    parent::__construct($matchId, $summary, NULL, $leagueId, $start, $home, $away);

    parent::setAdditionalName($leagueName);
    $this->url =
      parent::STRING_URL_PREFIX .
      self::STRING_URL_INFIX_LEAGUE . $leagueId .
      self::STRING_URL_INFIX_TEAM . $matchId;
  }

  function getLeagueId() {
    return parent::getAdditionalId();
  }

  function getLeagueName() {
    return parent::getAdditionalName();
  }

  function getMatchNumber() {
    return $this->number;
  }

  function getPlayers() {
    return $this->players;
  }

  function getTeamId() {
    return $this->teamId;
  }

  function isLeagueMatch() {
    return true;
  }

  function setLeagueName($leagueName) {
    $this->$additionalName = $leagueName;
  }

  function setMatchNumber($number) {
    $this->number = $number;
  }

  function setPlayers($players) {
    $this->players = $players;
  }

  function setTeamId($teamId) {
    $this->teamId = $teamId;
  }
}

 ?>

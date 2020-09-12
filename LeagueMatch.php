<?php
require_once('Match.php');

class LeagueMatch extends Match {

  private const STRING_URL_INFIX_LEAGUE = '/league/';
  private const STRING_URL_INFIX_TEAM = '/team-match/';

  private $teamId;
  private $players;
  private $nr;
  private $shortLeagueName;

  function __construct($matchId, $summary, $leagueName, $leagueId, $start, $home, $away) {
    parent::__construct($matchId, $summary, NULL, $leagueId, $start, $home, $away);

    parent::setAdditionalName($leagueName);
    $this->setShortLeagueName($leagueName);

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

  function getSummary() {
    if (is_null($this->shortLeagueName)) {
      return parent::getSummary();
    } else {
      if ($this->hasScore()) {
        $score = ' (' . $this->getScore() . ')';
      } else {
        $score = '';
      }
      return $this->summary . $score . ' [' . $this->shortLeagueName . ']';
    }
  }

  function getTeamId() {
    return $this->teamId;
  }

  function isLeagueMatch() {
    return true;
  }

  function setLeagueName($leagueName) {
    $this->$additionalName = $leagueName;
    $this->setShortLeagueName($leagueName);
  }

  function setMatchNumber($number) {
    $this->number = $number;
  }

  function setPlayers($players) {
    $this->players = $players;
  }

  function setShortLeagueName($leagueName) {
    $regEx = '/.+(jaar|17\\+|35\\+|50\\+|Tenniskids Rood|Tenniskids Oranje|Tenniskids Groen|Gemengd Zondag)/';
    preg_match($regEx, $leagueName, $matches);

    if (!is_null($matches)) {
      if (0 < sizeof($matches)) {
        $this->shortLeagueName = $matches[0];
      }
    }
  }

  function setTeamId($teamId) {
    $this->teamId = $teamId;
  }
}

 ?>

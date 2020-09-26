<?php
require_once('Match.php');

class TournamentMatch extends Match {

  private const STRING_URL_INFIX_TOURNAMENT = '/tournament/';
  private const STRING_URL_INFIX_TEAM = '/team-match/';
  private const DEFAULT_DURATION = '2';

  function __construct($matchId, $summary, $tournamentId, $start, $home, $away) {
    parent::__construct(
      $matchId, $summary, NULL, $tournamentId, $start, self::DEFAULT_DURATION, $home, $away);

//    $this->tournamentName = $tournamentName;
//    $this->url =
//      parent::STRING_URL_PREFIX .
//      self::STRING_URL_INFIX_TOURNAMENT . $leagueOrTournamentId .
//      self::STRING_URL_INFIX_TEAM . $matchId;
  }

  function getDescription() {
    $home = $this->getHome();
    $away = $this->getAway();

    $result = $this->getTeamString($home);
    $result .= "\n       vs\n";
    $result .= $this->getTeamString($away);

    return $result;
  }

  function getTeamString($team) {
    if (is_null($team) or (0 == sizeof($team))) {
      return '(nog) onbekend';
    } else {
      $result = $team[0]->getLink();
      if (1 < sizeof($team)) {
        $result .= ' & ';
        $result .= $team[1]->getLink();
      }
      return $result;
    }
  }

  function getTournamentHash() {
    return $this->getHash();
  }

  function getTournamentId() {
    return $this->$leagueOrTournamentId;
  }

  function getTournamentName() {
    return $this->tournamentName;
  }

  function isTournamentMatch() {
    return true;
  }

  function setTitle($name) {
    parent::setAdditionalName($name);
  }

  function setTournamentHash($hash) {
    $this->setHash($hash);
  }

  function setTournamentName($tournamentName) {
    $this->$tournamentName = $tournamentName;
  }
} ?>

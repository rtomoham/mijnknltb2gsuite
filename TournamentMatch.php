<?php
require_once('Match.php');

class TournamentMatch extends Match {

  private const STRING_URL_INFIX_TOURNAMENT = '/tournament/';
  private const STRING_URL_INFIX_TEAM = '/team-match/';
  private const DEFAULT_DURATION = '2';

//  private $tournamentName;

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
    return $this->getHome() . "\n   vs\n" . $this->getAway();
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

  function setTournamentName($tournamentName) {
    $this->$tournamentName = $tournamentName;
  }
} ?>

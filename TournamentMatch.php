<?php
require_once('Match.php');

class TournamentMatch extends Match {

  private const STRING_URL_INFIX_TOURNAMENT = '/tournament/';
  private const STRING_URL_INFIX_TEAM = '/team-match/';

  private $tournamentName;

  function __construct($matchId, $summary, $tournamentName, $tournamentId, $start) {
    parent::__construct($matchId, $summary, NULL, $tournamentId, $start);

    $this->tournamentName = $tournamentName;
    $this->url =
      parent::STRING_URL_PREFIX .
      self::STRING_URL_INFIX_TOURNAMENT . $leagueOrTournamentId .
      self::STRING_URL_INFIX_TEAM . $matchId;
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

  function setTournamentName($tournamentName) {
    $this->$tournamentName = $tournamentName;
  }
} ?>

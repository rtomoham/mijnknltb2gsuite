<?php
require_once('Event.php');
require_once('Mijnknltb2GSuiteSettings.php');

class Match extends Event {
  private const STRING_SURFACE = "\nOndergrond: ";
  private const STRING_ALERT = '  ==> LET OP: ';
  protected const STRING_URL_PREFIX = 'https://mijnknltb.toernooi.nl';
  private const STRING_MIJNKNLTB = 'Mijnknltb: ';

  protected $url;
  private $alert;
  private $surface;

  // An extra name, to capture the League or Tournament name
  private $additionalName;
  // An extra id, to identify the League or Tournament
  private $additionalId;

  private $home;
  private $away;

  private $scoreHome = -1;
  private $scoreAway = -1;

  private $sheetData;

  /*
  * Overrides and overloads the parent constructor, by taking $matchId and
  * an additional ID ($leagueId or tournamentId) to identify the exact
  * match. $description, $url and $location will be derived from
  * $matchId and $additionalId
  */
  function __construct(
    $matchId, $summary, $description, $additionalId, $start, $duration, $home, $away) {

    parent::__construct($matchId, $summary, NULL, NULL, $start, $duration);
    $this->additionalId = $additionalId;
    $this->home = $home;
    $this->away = $away;

    // check the end time: if the match ends earlier than 6 AM, it has extended
    // beyond the starting day, so we want to shorten it midnight to keep our
    // calendars clean
    $end = new DateTime('@' . $this->end);
    $end->setTimezone(
      Mijnknltb2GSuiteSettings::getInstance()->getMyDateTimeZone());
    $endTime = $end->format('H:i');
    $sixAM = strtotime('6:00');
    if (strtotime($endTime) < $sixAM) {
      $end->setTime(0, 0);
      $this->end = $end->getTimestamp();
    }
  }

  function getAdditionalId() {
    return $this->additionalId;
  }

  function getAdditionalName() {
    return $this->additionalName;
  }

  function getAway() {
    return $this->away;
  }

  function getDescription() {
        if (is_null($this->alert)) {
          $alertString = '';
        } else {
          $alertString = "\n" . self::STRING_ALERT . $this->alert;
        }
    return
      self::STRING_SURFACE . $this->surface .
        $alertString ."\n" .
        getHeaderLinks() .
        '- <a href="' . $this->url . '">mijnknltb</a>' .
        "\n";
  }

  function getEventId() {
    return self::STRING_GOOGLE_SHEETS_ID_PREFIX .
      $this->additionalId . '.' . $this->id;
  }

  function getHome() {
    return $this->home;
  }

  function getMatchId() {
    return parent::getId();
  }

  function getScore() {
    if ($this->hasScore()) {
      return $this->scoreHome . ' - ' . $this->scoreAway;
    } else {
      return '';
    }
  }

  function getSheetData() {
    return $this->sheetData;
  }

  function getSummary() {
    if ($this->hasScore()) {
      $score = ' (' . $this->getScore() . ')';
    }
    return parent::getSummary() . $this->getScore() . ' [' . $this->additionalName . ']';
  }

  function getUrl() {
    return $this->url;
  }

  function hasScore() {
    return ((0 <= $this->scoreHome) and (0 <= $this->scoreAway));
  }

  function isLeagueMatch() {
    return false;
  }

  function isTournamentMatch() {
    return false;
  }

  function setAdditionalName($additionalName) {
    $this->additionalName = $additionalName;
  }

  function setAlert($alert) {
    $this->alert = $alert;
  }

  function setScore($scoreHome, $scoreAway) {
    $this->setScoreHome($scoreHome);
    $this->setScoreAway($scoreAway);
  }

  function setScoreAway($score) {
    $this->scoreAway = $score;
  }

  function setScoreHome($score) {
    $this->scoreHome = $score;
  }

  function setSheetData($sheetData) {
    $this->sheetData = $sheetData;
  }

  function setSurface($surface) {
    $this->surface = $surface;
  }

  function toString() {
    $data = array(
      'start' => $this->getStart(),
      'end' => $this->getEnd(),
      'title' => $this->getSummary(),
      'description' => $this->getDescription(),
      'location' => $this->getLocation(),
      'surface' => $this->surface,
      'alert' => $this->alert,
    );
    return json_encode($data);
  }
}

?>

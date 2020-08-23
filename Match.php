<?php
include('Event.php');

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

  private $sheetData;

  function __construct($matchId, $summary, $description, $additionalId, $start, $home, $away) {
    /*
    * Overrides and overloads the parent constructor, by taking $matchId and
    * an additional ID ($leagueId or tournamentId) to identify the exact
    * match. $description, $url and $location will be derived from
    * $matchId and $additionalId
    */
    parent::__construct($matchId, $summary, NULL, NULL, $start);
    $this->additionalId = $additionalId;
    $this->home = $home;
    $this->away = $away;

    // check the end time: if the match ends earlier than 6 AM, it has extended
    // beyond the starting day, so we want to shorten it midnight to keep our
    // calendars clean
    $end = new DateTime('@' . $this->end);
    $MY_DTZ = new DateTimeZone(MY_TIMEZONE);
    $end->setTimezone($MY_DTZ);
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
        getHeaderMijnknltb() . $this->url . "\n";
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

  function getSheetData() {
    return $this->sheetData;
  }

  function getSummary() {
    return parent::getSummary() . ' (' . $this->additionalName . ')';
  }

  function getUrl() {
    return $this->url;
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

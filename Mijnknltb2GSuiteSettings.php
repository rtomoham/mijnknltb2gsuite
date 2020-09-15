<?php

require_once('Settings.php');

define('STRING_BACKOFF_TIMERS', 'backoff_timers');
define('STRING_LONG', 'long');
define('STRING_SHORT', 'short');
define('STRING_FILENAMES', 'filenames');
define('STRING_ACCOUNTS', 'accounts');
define('STRING_SERVICE_ACCOUNT', 'service_account');
define('STRING_CRON', 'cron');
define('STRING_ON_MINUTE', 'onMinute');
define('STRING_ON_HOUR', 'onHour');
define('STRING_ON_DAY_OF_MONTH', 'onDayOfMonth');
define('STRING_ON_MONTH', 'onMonth');
define('STRING_ON_DAY_OF_WEEK', 'onDayOfWeek');

/*
 * Singleton class containing all the settings for Mijnknltb2GSuite
 */
class Mijnknltb2GSuiteSettings extends Settings {

  private const KEYWORD_GCAL_ACCOUNTS = 'google_calendar_accounts';
  private const KEYWORD_MIJNKNLTB_ACCOUNT = 'mijnknltb_account';
  private const FILENAME_COOKIE = 'cookies.txt';

  public const URL_MIJNKNLTB = 'https://mijnknltb.toernooi.nl';

  private $cron;       // [ 'onMinute' => 'x', 'onHour' => 'x',
                       //   'onDayOfMonth' => 'x', 'onMonth' => 'x',
                       //   'onDayOfWeek' => 'x' ]
  private $filenames;  // [ 'accounts' => 'x', 'serviceAccount' => 'x' ]
  private $timers;     // [ 'short' => 'x', 'long' => 'x' ]

  private $googleSuiteAccounts = [];
  private $mijnknltbAccounts = [];

  function __construct($test) {
    parent::__construct($test);
  }

  public static function getInstance($test = false) {
    if (is_null(self::$instance)) {
      self::$instance = new Mijnknltb2GSuiteSettings($test); }
    return self::$instance;
  }

  function init($programName) {
    parent::init($programName);
    $this->processAccountsFile();
    $this->filenames = $this->data[STRING_FILENAMES];
  }

  function getAccountsFilename() {
    return $this->data[STRING_FILENAMES][STRING_ACCOUNTS];
  }

  function getCookiesFilename() {
    return $this->dataPath . self::FILENAME_COOKIE;
  }

  function getDelays() {
    $delays = $this->data[STRING_BACKOFF_TIMERS];
    return [$delays[STRING_SHORT], $delays[STRING_LONG]];
  }

  function getGoogleSuiteAccounts() {
    return $this->googleSuiteAccounts;
  }

  function getMijnknltbAccounts() {
    return $this->mijnknltbAccounts;
  }

  function getHeaderString($header) {
    if (MAX_HEADER_TEXT < strlen($header)) {
      $header = substr($header, 0, MAX_HEADER_TEXT-1);
    }
    return str_pad(' ' . $header . ' ', MAX_HEADER_WIDTH, '-', STR_PAD_BOTH) . "\n";
  }

  function getHeaderBackup() {
    return getHeaderString(STRING_BACKUP);
  }

  function getheaderComments() {
    return getHeaderString(STRING_COMMENTS);
  }

  function getHeaderDrivers() {
    return getHeaderString(STRING_DRIVERS);
  }

  function getHeaderGoogleSheet() {
    return getHeaderString(STRING_GOOGLE_SHEET);
  }

  function getHeaderMijnknltb() {
    return getHeaderString(STRING_MIJNKNLTB);
  }

  function getHeaderPlayers() {
    return getHeaderString(STRING_PLAYERS);
  }

  function getHeaderSeparator() {
    return getHeaderString('~');
  }

  function getHeaderSnacks() {
    return getHeaderString(STRING_SNACKS);
  }

  function getServiceAccountFilename() {
    return
      $this->getDataPath() .
      $this->filenames[STRING_SERVICE_ACCOUNT];
  }

  function processAccountsFile() {
    $string = file_get_contents(
      $this->dataPath . $this->getAccountsFilename());
    $obj = json_decode($string);

    // Loop through the object
    foreach ($obj as $key=>$value) {
      switch ($key) {
        case self::KEYWORD_GCAL_ACCOUNTS:
          $this->googleSuiteAccounts[] = $value; break;
        case self::KEYWORD_MIJNKNLTB_ACCOUNT:
          $this->mijnknltbAccounts[] = $value; break;
      }
    }
  }

}

?>

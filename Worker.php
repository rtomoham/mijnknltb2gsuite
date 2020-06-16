<?php
include('GoogleCalendarAccount.php');
include('Mijnknltb2GSuite.php');
include('MijnknltbUser.php');
include_once('BackoffTimer.php');

class Worker {
//  private const FILENAME_ACCOUNTS = PATH . 'accounts.json';
  private const KEYWORD_DESCRIPTION = 'description';
  private const KEYWORD_GCAL_ACCOUNTS = 'google_calendar_accounts';
  private const KEYWORD_GCAL_ID = 'google_calendar_id';
  private const KEYWORD_USERNAME = 'username';
  private const KEYWORD_PASSWORD = 'password';
  private const KEYWORD_ACTIVE = 'active';
  private const KEYWORD_LAST_UPDATE = 'last_update';
  private const KEYWORD_LEAGUES_ONLY = 'only_leagues';
  private const KEYWORD_LEAGUES_FILTER = 'leagues_filter';
  private const KEYWORD_MIJNKNLTB_ACCOUNT = 'mijnknltb_account';
  private const KEYWORD_MIJNKNLTB_PROFILE_ID = 'mijnknltb_profile_id';

  private $mijnknltbUser;
  private $googleCalendarAccounts = array();
  private $backoffTimer;
  private $filenameAccounts;

  function __construct() {
    $this->backoffTimer = BackoffTimer::getInstance();
    $this->processIniFile();
    $this->processAccountsFile();
  }

  function getAllEvents() {
    foreach ($this->googleCalendarAccounts as $googleCalendarAccount) {
      $mgs = new Mijnknltb2GSuite(
        $googleCalendarAccount,
        $this->mijnknltbUser
      );
      $mgs->getAllEvents();
    }
  }

  function clearGoogleCalendars() {
    foreach ($this->googleCalendarAccounts as $googleCalendarAccount) {
      $mgs = new Mijnknltb2GSuite(
        $googleCalendarAccount,
        $this->mijnknltbUser
      );
      $mgs->clearGoogleCalendar();
    }
  }

  function processAccountsFile() {
    $string = file_get_contents(PATH . $this->filenameAccounts);
    $obj = json_decode($string);

    // Loop through the object
    foreach ($obj as $key=>$value) {
      switch ($key) {
        case self::KEYWORD_GCAL_ACCOUNTS:
        $this->processGoogleCalendarAccounts($value); break;
        case self::KEYWORD_MIJNKNLTB_ACCOUNT:
        $this->processMijnknltbAccount($value); break;
      }
    }
  }

  function processGoogleCalendarAccounts($googleCalendarAccounts) {
    foreach ($googleCalendarAccounts as $googleCalendarAccount) {
      $active = false;
      $leaguesFilters = NULL;
      $leaguesOnly = false;
      $mijnknltbProfileIds = [];
      $lastUpdate = 0;
      foreach ($googleCalendarAccount as $key=>$value) {
        switch ($key) {
          case self::KEYWORD_DESCRIPTION: $description = $value; break;
          case self::KEYWORD_GCAL_ID: $googleCalendarId = $value; break;
          case self::KEYWORD_MIJNKNLTB_PROFILE_ID:
            foreach ($value as $mijnknltbProfileId) {
              $mijnknltbProfileIds[] = $mijnknltbProfileId;
            }
          break;
          case self::KEYWORD_LAST_UPDATE: $lastUpdate = $value; break;
          case self::KEYWORD_ACTIVE: $active = !strcmp('true', $value); break;
          case self::KEYWORD_LEAGUES_ONLY:
            $leaguesOnly = !strcmp('true', $value);
          break;
          case self::KEYWORD_LEAGUES_FILTER:
            $leaguesFilters = array();
            foreach ($value as $filter) {
              $leaguesFilters[] = $filter;
            }
          break;
        }
      }
      if ($active) {
        $account = new GoogleCalendarAccount(
          $googleCalendarId,
          $mijnknltbProfileIds
        );
        $account->setDescription($description);
        if (!is_null($leaguesFilters)) {
          $account->setLeaguesFilter($leaguesFilters);
        }
        if ($leaguesOnly) { $account->setLeaguesOnly(); }
        $account->setLastUpdate($lastUpdate);
        $this->googleCalendarAccounts[] = $account;
      }
    }
  }

  function processIniFile() {
    $settings = getSettings();
    $delays = $settings[STRING_BACKOFF_TIMERS];

    $long = $delays[STRING_LONG];
    $short = $delays[STRING_SHORT];
    $this->backoffTimer->init($short, $long);

    $filenames = $settings[STRING_FILENAMES];
    $this->filenameAccounts = $filenames[STRING_ACCOUNTS];
  }

  function processMijnknltbAccount($mijnknltbAccountLine) {
    foreach ($mijnknltbAccountLine as $key=>$value) {
      switch ($key) {
        case self::KEYWORD_USERNAME: $username = $value; break;
        case self::KEYWORD_PASSWORD: $password = $value; break;
      }
    }
    $this->mijnknltbUser = new MijnknltbUser($username, $password);
  }

  function refreshGoogleCalendars() {
    $i = 0;
    $nrAccounts = sizeof($this->googleCalendarAccounts);
    foreach ($this->googleCalendarAccounts as $googleCalendarAccount) {
      $mgs = new Mijnknltb2GSuite(
        $googleCalendarAccount,
        $this->mijnknltbUser
      );
      printMessage('START: Cleaning up Google Calendar');
      $mgs->refreshGoogleCalendar();
      $i++;
      if ($i < $nrAccounts) {
        // Take a break between accounts
        $this->backoffTimer->sleep('Worker::refreshGoogleCalendars', true);
      }
    }
  }

}

?>

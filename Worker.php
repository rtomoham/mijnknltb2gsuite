<?php
require_once('GoogleCalendarAccount.php');
require_once('Mijnknltb2GSuite.php');
require_once('MijnknltbUser.php');
require_once('BackoffTimer.php');
require_once('Mijnknltb2GSuiteSettings.php');

class Worker {
  private const KEYWORD_DESCRIPTION = 'description';
  private const KEYWORD_GCAL_ID = 'google_calendar_id';
  private const KEYWORD_USERNAME = 'username';
  private const KEYWORD_PASSWORD = 'password';
  private const KEYWORD_ACTIVE = 'active';
  private const KEYWORD_LAST_UPDATE = 'last_update';
  private const KEYWORD_LEAGUES_ONLY = 'only_leagues';
  private const KEYWORD_LEAGUES_FILTER = 'leagues_filter';
  private const KEYWORD_MIJNKNLTB_PROFILE_ID = 'mijnknltb_profile_id';

  private $mk2gsSettings;

  private $mijnknltbUser;
  private $googleCalendarAccounts = array();
  private $backoffTimer;

  function __construct($test = false) {
    $this->mk2gsSettings = Mijnknltb2GSuiteSettings::getInstance($test);
    $this->mk2gsSettings->init('mijnknltb2gsuite');

    $this->backoffTimer = BackoffTimer::getInstance();
    $delays = $this->mk2gsSettings->getDelays();
    $this->backoffTimer->init($delays[0], $delays[1]);

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
    foreach ($this->mk2gsSettings->getGoogleSuiteAccounts() as $account) {
      $this->processGoogleCalendarAccounts($account); break;
    }

    foreach ($this->mk2gsSettings->getMijnknltbAccounts() as $account) {
      $this->processMijnknltbAccount($account); break;
    }
  }

  function processGoogleCalendarAccounts($googleCalendarAccounts) {
//    var_dump($googleCalendarAccounts);
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
          $mijnknltbProfileIds,
          $description
        );
        if (!is_null($leaguesFilters)) {
          $account->setLeaguesFilter($leaguesFilters);
        }
        if ($leaguesOnly) { $account->setLeaguesOnly(); }
        $account->setLastUpdate($lastUpdate);
        $this->googleCalendarAccounts[] = $account;
      }
    }
  }

  function processMijnknltbAccount($mijnknltbAccounts) {
//    var_dump($mijnknltbAccounts);
    foreach ($mijnknltbAccounts as $key=>$value) {
      switch ($key) {
        case self::KEYWORD_USERNAME: $username = $value; break;
        case self::KEYWORD_PASSWORD: $password = $value; break;
      }
    }
    $this->mijnknltbUser = new MijnknltbUser($username, $password);
  }

  function refreshGoogleCalendars() {
//    var_dump($this->googleCalendarAccounts);
    $i = 0;
    $nrAccounts = sizeof($this->googleCalendarAccounts);
    foreach ($this->googleCalendarAccounts as $googleCalendarAccount) {
      $mgs = new Mijnknltb2GSuite(
        $googleCalendarAccount,
        $this->mijnknltbUser
      );
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

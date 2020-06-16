<?php

include('MijnknltbWebBroker.php');
include('GoogleApiBroker.php');
include_once('League.php');
include_once('BackoffTimer.php');

class Mijnknltb2GSuite {
  // MijnknltbGoogleCalendar contains all the information needed
  // to sync events (tournament and league matches) from
  // mijnknltb.toernooi.nl with a Google Calendar through a
  // Google Service Account

  private $googleApiBroker;
  private $googleCalendarAccount;
  private $mijnknltbWebBroker;

  // $leaguesOnly == true => only import league matches
  private $leaguesOnly = false;
  // $leaguesFilter is an array of strings, to limit the league matches to be imported
  private $leaguesFilters = NULL;

  function __construct($googleCalendarAccount, $mijnknltbUser) {
    $this->googleCalendarAccount = $googleCalendarAccount;
    $this->mijnknltbWebBroker = new MijnknltbWebBroker($mijnknltbUser);
    $this->googleApiBroker = GoogleApiBroker::getInstance();
  }

  // Start Getters and Setters
  function getLeaguesFilter() {
    return $this->$leaguesFilter;
  }

  function getLeaguesOnly() {
    return $this->leaguesOnly;
  }

  function setLeaguesFilter($filter) {
    $this->leaguesFilter = $filter;
  }

  function setLeaguesOnly($leaguesOnly) {
    $this->leaguesOnly = $leaguesOnly;
  }
  // End Getters and Setters

  function getAllEvents() {
    $this->googleApiBroker->getAllEvents($this->googleCalendarAccount);
  }

  function getMatches() {
    return $this->mijnknltbWebBroker->getMatches(
      $this->googleCalendarAccount->getMijnknltbProfileIds(),
      $this->leaguesFilters
    );
  }

  function clearGoogleCalendar() {
    $this->googleApiBroker->clearEvents($this->googleCalendarAccount);
  }

  function refreshGoogleCalendar() {
    $delete = false;
    if ($delete) {
      $this->googleApiBroker->deleteFiles(['1OFSDJGFoe1v_DiH-BiskwxmJPnmd62NoyZpAqbcjsHc']);
    } else {
      $this->clearGoogleCalendar();

      $leaguesAndTeams = $this->mijnknltbWebBroker->getLeaguesAndTeams(
        $this->googleCalendarAccount->getMijnknltbProfileIds()
      );

      $allMatches = [];
      foreach ($leaguesAndTeams as list($leagueAndTeam)) {
        $leagueId = $leagueAndTeam[0];
        $teamId = $leagueAndTeam[1];
        $league = new League($leagueId, $teamId);

        $matches = $this->mijnknltbWebBroker->getMatches(
          $leagueId,
          $teamId,
          $this->leaguesFilters
        );
        $league->setMatches($matches);

        foreach ($matches as $matchNr=>$match) {
          if ($match->isLeagueMatch()) {
            if (0 == $matchNr) {
              $sheetData = $this->googleApiBroker->getSheetData($league);
            }
            $match->setSheetData($sheetData);
            BackoffTimer::getInstance()->sleep('Mijnknltb2GSuite::refreshGoogleCalendar');
          }
        }
        $allMatches = array_merge($allMatches, $matches);
      }

      printMessage('Adding ' . sizeof($allMatches) . ' matches to Google Calendar');
      foreach ($allMatches as $match) {
        $this->googleApiBroker->addEvent($match, $this->googleCalendarAccount);
        BackoffTimer::getInstance()->sleep('Mijnknltb2GSuite::refreshGoogleCalendar::addEvent');
      }
    }
  }
}
?>

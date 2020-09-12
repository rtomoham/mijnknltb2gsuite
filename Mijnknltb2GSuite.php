<?php

include('MijnknltbWebBroker.php');
include('GoogleApiBroker.php');
include_once('League.php');
include_once('BackoffTimer.php');
require_once('Mijnknltb2GSuiteSettings.php');

class Mijnknltb2GSuite {
  // MijnknltbGoogleCalendar contains all the information needed
  // to sync events (tournament and league matches) from
  // mijnknltb.toernooi.nl with a Google Calendar through a
  // Google Service Account

  private $googleApiBroker;
  private $googleCalendarAccount;
  private $mijnknltbWebBroker;
  private $mk2gsSettings;

  // $leaguesOnly == true => only import league matches
  private $leaguesOnly = false;
  // $leaguesFilter is an array of strings, to limit the league matches to be imported
  private $leaguesFilters = NULL;

  function __construct($googleCalendarAccount, $mijnknltbUser) {
    $this->mk2gsSettings = Mijnknltb2GSuiteSettings::getInstance();
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

  function getMatches($leagueId, $teamId, $leaguesFilters) {
    printMessage(
      'KNLTB GET: [' .
      $this->googleCalendarAccount->getDescription() .
      ']' );
    return $this->mijnknltbWebBroker->getMatches(
      $leagueId,
      $teamId,
      $leaguesFilters
    );
  }

  function clearGoogleCalendar() {
    $this->googleApiBroker->clearEvents($this->googleCalendarAccount);
  }

  function refreshGoogleCalendar() {
    $delete = false;
    if ($delete) {
      $this->googleApiBroker->deleteFiles([
        '1snKfnfLYP040TITWxnTOva-EsTNsDOVRFi_I0L-Ow4w', '11ZOQNE1yuLXOat0vK59od9dtwAfr0jWuzZC8bGDJyi8',
        '11-iL-FByJE5NtivQhxnuJXrXAfM3qKYwz_WysL4AIEg',
        '1y4I180hk2nsue5x8q4uBVoYpB5t5HdjgH3HilEj26io', '1fYvwakrW1MsQTQLwFht2K6CsVXK73B-ejMieFNMmyJA',
        '1b08Q5L3D4C-pUswDUGEFN8PL-Zf9nYNz62Zbuwb8Ftg', '1cmg8RoQGTRM7N24TvMI0i-lSyoA6H-pNhOZ_tXVWrqE',
        '1ZOaZ1_tksXz4ehpB6B_f0XaAmZKlB60TiK-ngP7UkXI',
        '1lj0MceaKvXswAVLuKvIuNAhdyR6sOwrzla11_jaZaw4', '1jiQDF9ed2avAu-gDGv80vXwWa505i08ncwgi-1bErFs'
      ]);
    } else {
      printMessage(
        'GCAL DEL: [' .
        $this->googleCalendarAccount->getDescription() .
        ']'
      );
      $this->clearGoogleCalendar();

      $leaguesAndTeams = $this->mijnknltbWebBroker->getLeaguesAndTeams(
        $this->googleCalendarAccount->getMijnknltbProfileIds()
      );

      $allMatches = [];
      foreach ($leaguesAndTeams as $leaguesAndTeamsPerPlayer) {
        foreach ($leaguesAndTeamsPerPlayer as $leagueAndTeam) {
          $leagueId = $leagueAndTeam[0];
          $teamId = $leagueAndTeam[1];
          $league = new League($leagueId, $teamId);

          $matches = $this->getMatches(
            $leagueId,
            $teamId,
            $this->leaguesFilters
          );
          $league->setMatches($matches);

          foreach ($matches as $matchNr=>$match) {
            if ($match->isLeagueMatch()) {
              if (0 == $matchNr) {
                $sheetData = $this->googleApiBroker->getSheetData($league);
                BackoffTimer::getInstance()->sleep('MK2GS::refreshGoogleCalendar::addEvent');
              }
              $match->setSheetData($sheetData);
            }
          }
          $allMatches = array_merge($allMatches, $matches);
        }
      }

      printMessage(
        'GCAL ADD: ' . sizeof($allMatches) . ' matches [' .
        $this->googleCalendarAccount->getDescription() . ']'
      );
      foreach ($allMatches as $match) {
        $this->googleApiBroker->addEvent($match, $this->googleCalendarAccount);
        BackoffTimer::getInstance()->sleep('MK2GS::refreshGoogleCalendar::addEvent');
      }
    }
  }
}
?>

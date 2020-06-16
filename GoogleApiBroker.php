<?php

include_once __DIR__ . '/vendor/autoload.php';

include_once('SheetData.php');
include_once('BackoffTimer.php');

/*
* Singleton interface into the Google API.
* Public method:
*/
class GoogleApiBroker {
  private static $instance = NULL;
//  private const FILENAME_GOOGLE_SERVICE_ACCOUNT =
//  PATH . 'GoogleApiServiceAccount.json';
  private const STRING_GOOGLE_SHEETS_ID_PREFIX = 'MIJNKNLTB.';
  private const FILENAME_TEMPLATE = '!MIJNKNLTB-TEMPLATE-complete-team-schedule';
  private const GOOGLE_SHEET_COLUMNS_HEADERS = ['B', 'E', 'H', 'K', 'N', 'Q', 'T', 'W', 'Z'];
  private const GOOGLE_SHEET_COLUMNS_SELECTED_TEAM =
  [ 'C', 'F', 'I', 'L', 'O', 'R', 'U', 'X', 'AA', 'AD'];
  private const GOOGLE_SHEET_COLUMNS_SELECTED_TEAM_EXTRAS =
  [ 'D', 'G', 'J', 'M', 'P', 'S', 'V', 'Y', 'AB', 'AE'];

  private $filenameServiceAccount;

  private $calendarService;
  private $sheetsService;
  private $driveService;

  // Google Drive files
  private $files;

  private function __construct() {
    $this->processIniFile();
    putenv(
//      'GOOGLE_APPLICATION_CREDENTIALS=' . self::FILENAME_GOOGLE_SERVICE_ACCOUNT
      'GOOGLE_APPLICATION_CREDENTIALS=' . $this->filenameServiceAccount
    );

    $client = new Google_Client();
    // use the application default credentials, provided in
    // 'GOOGLE_APPLICATION_CREDENTIALS'
    $client->useApplicationDefaultCredentials();
    $client->setApplicationName("Project De Mast Tennis Courts");
    $client->setScopes([Google_Service_Calendar::CALENDAR,
    Google_Service_Sheets::SPREADSHEETS,
    Google_Service_Drive::DRIVE]);
    #$client->setSubject("gltvdemast@gmail.com");
    $this->calendarService = new Google_Service_Calendar($client);
    $this->sheetsService = new Google_Service_Sheets($client);
    $this->driveService = new Google_Service_Drive($client);
  }

  public static function getInstance() {
    if (is_null(self::$instance)) { self::$instance = new GoogleApiBroker(); }
    return self::$instance;
  }

  function printCalendars() {
    foreach ($calendars as $calendarId) {
      printBasicMessage(
        $this->calendarService->calendars->get($calendarId)->getSummary()
      );
    }
  }

  function addEvent($match, $googleCalendarAccount) {
    /*
    * Pre:  TRUE
    * Post: $match has been added to $googleCalendarAccount
    */
    $googleCalendarId = $googleCalendarAccount->getIdentifier();

    $linkToGoogleSheet = '';
    $selectedTeamString = '';
    if ($match->isLeagueMatch()) {
      // This is a league match, so let's add the link to the Google DRIVE
      // spreadsheet

      $matchNr = $match->getMatchNumber();
      $file = $this->getGoogleDocsFile($match);
      $url = 'https://docs.google.com/spreadsheets/d/' . $file->getId();
      $modified = strtotime($file->getModifiedTime());

      $sheetData = $match->getSheetData();
      if (is_null($sheetData)) {
        printBasicMessage('GoogleApiBroker::addEvent::NULL==$sheetData THIS SHOULD NEVER HAPPEN');
        $sheetData = new SheetData($this->sheetsService, $file->getId());
        $match->setSheetData($sheetData);
      }

      $matchDetails = $this->getMatchDetails($sheetData, $matchNr);

      if (!is_null($url)) {
        $linkToGoogleSheet = $url;
      }
    }

    $matchArray = array(
      'summary' => $match->getSummary(),
      'location' => $match->getLocation(),
      'description' =>
      getHeaderPlayers() .
      $matchDetails .
      $match->getDescription() .
      getHeaderGoogleSheet() .
      $linkToGoogleSheet . "\n" .
      "\nLast update: " . date('Y-m-d H:i') . 'h',
      'start' => $match->getStart(),
      'end' => $match->getEnd(),
    );

    printBasicMessage('Adding match starting ' . $match->getStartHuman() .
    ' with title "' . $match->getBasicSummary() . '" to Google Calendar.');
    $event = new Google_Service_Calendar_Event($matchArray);
    $event = $this->calendarService->events->insert($googleCalendarId, $event);
  }

  function addSheetData($leagueMatch) {
    $matchNr = $leagueMatch->getMatchNumber();
    $file = $this->getGoogleDocsFile($leagueMatch);
    $url = 'https://docs.google.com/spreadsheets/d/' . $file->getId();

    $sheetData = $match->getSheetData();
    if (is_null($sheetData)) {
      $sheetData = new SheetData($this->sheetsService, $file->getId());
      $match->setSheetData($sheetData);
    }
  }

  function getSheetData($league) {
    $file = $this->getGoogleDocsFile($league);
    return new SheetData($this->sheetsService, $file->getId());
  }

  function clearEvents($googleCalendarAccount) {
    /*
    * Pre:  TRUE
    * Post: all events previously created by this service account have been
    *       deleted
    */
    $googleCalendarId = $googleCalendarAccount->getIdentifier();

    // If I omitted the next parameter, I would not get all events
    // Probably a bug on Google's side
    $optParams = array('singleEvents' => 'true');
    $eventsList = $this->calendarService->events->listEvents(
      $googleCalendarId, $optParams
    );

    $events = $eventsList->getItems();
    foreach($events as $event) {

      $creator = $event->getCreator();
      if (0 == strcmp(
        $this->calendarService->getClient()->getClientId(),
        $creator->getId())
      ) {
        printBasicMessage('Deleting "' .
        $event->getSummary() . '" from Google Calendar');
        $this->calendarService->events->delete(
          $googleCalendarId, $event->getId()
        );
      }
    }
  }

  function getAllEvents($googleCalendarAccount) {
    $googleCalendarId = $googleCalendarAccount->getIdentifier();

    // If I omitted the next parameter, I would not get all events
    // Probably a bug on Google's side
    $optParams = array('singleEvents' => 'true');
    $eventsList = $this->calendarService->events->listEvents(
      $googleCalendarId, $optParams
    );

    $events = $eventsList->getItems();
    foreach($events as $event) {

      $creator = $event->getCreator();
      if (0 == strcmp(
        $this->calendarService->getClient()->getClientId(),
        $creator->getId())
      ) {
        printBasicMessage('Found "' .
        $event->getSummary() . '" with event-id "' .
        $event->getICalUid() . '" from Google Calendar');
      }
    }
    return $events;
  }

  function deleteFiles($fileIds = NULL) {
    if (is_null($this->files)) {
      $optParams = array(
        'pageSize' => 10,
        'fields' => 'nextPageToken, files(id, name)'
      );
      $this->files = $this->driveService->files->listFiles($optParams)->getFiles();
    }

    if (!is_null($fileIds)) {
      foreach ($fileIds as $fileId) {
        try {
          echo "Trying to delete $fileId: ";
          $this->driveService->files->delete($fileId);
          echo "Success!\n";
        } catch (Exception $e) {
          echo "Fail!\n";
          echo("Exception trying to delete $fileId: ");
          echo json_decode($e->getMessage(), true)['error']['message'] . "\n";
        }
      }
    } else {
      if (count($this->files) == 0) {
        printMessage('No files found.');
      } else {
        foreach ($files as $file) {
          $pos = strpos($file->getName(), self::STRING_GOOGLE_SHEETS_ID_PREFIX);
          printBasicMessage("Found:\t" . $file->getName());

          if (false !== $pos) {
            if (0 == $pos) {
              printBasicMessage("Deleting:\t" . $file->getName());
              $this->driveService->files->delete($file->getId());
            }
          }
        }
      }
    }
  }

  function getGoogleDocsFile($league) {
    $filename = self::STRING_GOOGLE_SHEETS_ID_PREFIX .
    $league->getLeagueId() . '.' .
    $league->getTeamId();

    // Let's specify the fields we want to receive
    $optParams = array(
      'pageSize' => 10,
      'fields' => 'nextPageToken, files(id, name, modifiedTime)'
    );
    if (is_null($this->files)) {
      $this->files = $this->driveService->files->listFiles($optParams)->getFiles();
    }

    if (0 == count($this->files)) {
      printMessage('No files found.');
    } else {
      //      printMessage('Files:');
      foreach ($this->files as $file) {
        $pos = strpos($file->getName(), self::STRING_GOOGLE_SHEETS_ID_PREFIX);
        if (false !== $pos) {
          if (0 == $pos) {
            //            var_dump($file->getName(), $filename);
            if (0 == strcmp($file->getName(), $filename)) {
              printBasicMessage("Found file: $filename modified on " . date(DateTimeInterface::RFC3339, strtotime($file->getModifiedTime())));
              return $file;
              //              return 'https://docs.google.com/spreadsheets/d/' . $file->getId();
            }
          }
        }
      }
    }
    // The file does not exist, so we will create it (copy the template)
    printBasicMessage("Creating file: $filename");
    return $this->copyTemplate($league, $filename);
  }

  function getMatchDetails($sheetData, $matchNr) {
    $matchDetails = '';
    $players = $sheetData->getPlayers($matchNr);
    if (0 == count($players)) {
      $matchDetails .= "   None selected (yet)\n";
    } else {
      foreach ($players as $playerName) {
        $matchDetails .= " - $playerName\n";
      }
    }
    $backups = $sheetData->getBackups($matchNr);
    if (0 != count($backups)) {
      $matchDetails .= getHeaderBackup();
      foreach ($backups as $playerName) {
        $matchDetails .= " - $playerName\n";
      }
    }
    $drivers = $sheetData->getDrivers($matchNr);
    if (0 != count($drivers)) {
      $matchDetails .= getHeaderDrivers();
      $matchDetails .= '  ';
      foreach ($drivers as $playerFirstName) {
        $matchDetails .= " $playerFirstName,";
      }
      $matchDetails = substr($matchDetails, 0, -1);
      $matchDetails .= "\n";
    }
    $snacks = $sheetData->getSnacks($matchNr);
    if (0 != count($snacks)) {
      $matchDetails .= getHeaderSnacks();
      $matchDetails .= '  ';
      foreach ($snacks as $playerFirstName) {
        $matchDetails .= " $playerFirstName,";
      }
      $matchDetails = substr($matchDetails, 0, -1);
      $matchDetails .= "\n";
    }

    $comments = $sheetData->getComments($matchNr);
    if (0 != strcmp('no comments', $comments)) {
      $matchDetails .= getheaderComments();
      $matchDetails .= $comments;
    }

    return $matchDetails . "\n";
  }

  function getSelectedTeamString($spreadsheetId, $matchNr) {
    $range = 'A8:AE20';
    $colSelected = 3 * $matchNr + 2;
    $colExtras = $colSelected + 1;

    $sheet = $this->sheetsService->spreadsheets_values->get($spreadsheetId, $range)->getValues();

    $selected = [];
    $backups = [];
    $snacks = [];
    $drivers = [];
    if (!is_null($sheet)) {
      foreach ($sheet as $row=>$rowArray) {
        //        printBasicMessage(json_encode($rowArray));
        if (10 > $row) {
          $yes = strcmp($rowArray[$colSelected], 'YES');
          $backup = strcmp($rowArray[$colSelected], 'BACKUP');
          if ((false !== $yes) and (0 == $yes)) {
            $selected[] = $rowArray[0];
          } elseif ((false !== $backup) and (0 == $backup)) {
            $backups[] = $rowArray[0];
          }
          $driver = strcmp($rowArray[$colExtras], 'drive');
          $snack = strcmp($rowArray[$colExtras], 'snacks');
          if ((false !== $driver) and (0 == $driver)) {
            $drivers[] = explode(" ", $rowArray[0], 2)[0];
          } elseif ((false !== $snack) and (0 == $snack)) {
            $snacks[] = explode(" ", $rowArray[0], 2)[0];
          }
        }
      }

      $selectedTeamString = '';
      if (0 == count($selected)) {
        $selectedTeamString .= "~ No players selected (yet) ~\n";
      } else {
        foreach ($selected as $playerName) {
          $selectedTeamString .= " - $playerName\n";
        }
      }
      if (0 != count($backups)) {
        $selectedTeamString .= getHeaderBackup();
        foreach ($backups as $playerName) {
          $selectedTeamString .= "  - $playerName\n";
        }
      }
      if (0 != count($drivers)) {
        $selectedTeamString .= getHeaderDrivers();
        foreach ($drivers as $playerFirstName) {
          $selectedTeamString .= " $playerFirstName,";
        }
        $selectedTeamString = substr($selectedTeamString, 0, -1);
        $selectedTeamString .= "\n";
      }
      if (0 != count($snacks)) {
        $selectedTeamString .= getHeaderSnacks();
        foreach ($snacks as $playerFirstName) {
          $selectedTeamString .= " $playerFirstName,";
        }
        $selectedTeamString = substr($selectedTeamString, 0, -1);
        $selectedTeamString .= "\n";
      }
    }
    return $selectedTeamString . "\n";
  }

  function copyTemplate($league, $filename) {
    if (0 == count($this->files)) {
      printMessage('No files found.');
    } else {
      foreach ($this->files as $file) {
        if (0 == strcmp($file->getName(), self::FILENAME_TEMPLATE)) {
          printBasicMessage('Creating new sheet from template');
          //          $newFile = $this->driveService->files->copy($file->getId());
          $newFile = new Google_Service_Drive_DriveFile();
          $newFile->setName($filename);
          $newFile = $this->driveService->files->copy($file->getId(), $newFile);

          $this->initializeSheet($newFile, $league);

          $this->makeWorldWritable($newFile);

          //          $this->transferOwnership($newFile);
          return $newFile;
          //          return 'https://docs.google.com/spreadsheets/d/' . $newFile->getId();
        }
      }
    }
  }

  function initializeSheet($file, $league) {
    $matches = $league->getMatches();
    $players = $league->getPlayers();

    $params = [ 'valueInputOption' => 'RAW'];

    // Initialize values array
    $values = [];
    for ($row = 0; 6 > $row; $row++) {
      $values[$row] = [];
      for ($column = 0; 30 > $column; $column++) {
        $values[$row][$column] = '';
      }
    }

    // initialize header (league name and matches dates and home and away teams)
    $values[0][0] = $league->getName();
    foreach ($matches as $matchNr=>$match) {
      $column = $matchNr * 3;
      $values[2][$column] = $match->getStartHuman();
      $values[3][$column] = $match->getHome();
      $values[4][$column] = 'vs';
      $values[5][$column] = $match->getAway();
    }
    $range = 'B1:AE6';

    printBasicMessage($leagueMatch->getStartRFC3339());

    try {
      $body = new Google_Service_Sheets_ValueRange([ 'values' => $values]);
      $result = $this->sheetsService->spreadsheets_values->update(
        $file->getId(),
        [$range],
        $body,
        $params
      );

    } catch (Exception $e) {
      printMessage($e);
    }

    if (0 < count($players)) {
      // Initialize values array
      $values = [];
      for ($row = 0; 10 > $row; $row++) {
        $values[$row] = [];
      }

      $players = $leagueMatch->getPlayers();
      // initialize player names
      foreach ($players as $row=>$player) {
        $values[$row][0] = $player->toString();
      }
      $endRow = sizeof($players) + 7;
      $range = "A8:A$endRow";

      try {
        $body = new Google_Service_Sheets_ValueRange([ 'values' => $values]);
        $result = $this->sheetsService->spreadsheets_values->update(
          $file->getId(),
          $range,
          $body,
          $params
        );

        //            return 'https://docs.google.com/spreadsheets/d/' . $newFile->getId();
      } catch (Exception $e) {
        printMessage($e);
      }
    }
  }

  function makeWorldWritable($file) {
    try {
      //      $permission = $driveService->permissions->get($file->getId(), $permissionId);
      $permission = new Google_Service_Drive_Permission(
        array(
          'type' => 'anyone',
          'role' => 'writer',
        ));

        //      echo json_encode($permission) . "\n";
        $permission->setRole('writer');
        //$this->driveService->permissions->update($file->getId(), 'anyoneWithLink', $permission);
        $response = $this->driveService->permissions->create($file->getId(), $permission);
        //echo "Response:\t" . json_encode($response) . "\n";

      } catch (Exception $e) {
        print "An error occurred: " . $e->getMessage();
      }
    }

    function processIniFile() {
      $settings = getSettings();
      $filenames = $settings[STRING_FILENAMES];
      $this->filenameServiceAccount = PATH . $filenames[STRING_SERVICE_ACCOUNT];
    }

    function transferOwnership($file) {
      $batch = $this->driveService->createBatch();
      $userPermission = new Google_Service_Drive_Permission(array(
        'type' => 'user',
        'role' => 'owner',
        'emailAddress' => 'gltvdemast@gmail.com'
      ));
      $request = $this->driveService->permissions->create(
        $file->getId(),
        $userPermission,
        array('service' => 'drive', 'fields' => 'id', 'transferOwnership' => 'true'));
        $batch->add($request, 'calendaraccess@project-de-mast-tennis-courts.iam.gserviceaccount.com');
        $results = $batch->execute();
        $printMessage($results);
      }

      function writeMatchHeader($file, $leagueMatch) {
        BackoffTimer::getInstance()->sleep('GoogleApiBroker::writeMatchHeader');

        try {
          $params = [ 'valueInputOption' => 'RAW'];
          $values = [[$leagueMatch->getStartHuman()], [$leagueMatch->getHome()], ['vs'] ,[$leagueMatch->getAway()]];
          $column = self::GOOGLE_SHEET_COLUMNS_HEADERS[$leagueMatch->getMatchNumber()];
          $range =  $column . '3:' . $column . '6';
          printBasicMessage($leagueMatch->getStartRFC3339());
          $body = new Google_Service_Sheets_ValueRange([ 'values' => $values]);
          $result = $this->sheetsService->spreadsheets_values->update(
            $file->getId(),
            [$range],
            $body,
            $params
          );

        } catch (Exception $e) {
          printMessage($e);
        }
      }

      function writePlayerNames($file, $leagueMatch) {
        $players = $leagueMatch->getPlayers();

        try {
          $params = [ 'valueInputOption' => 'RAW'];

          $name = [[$leagueMatch->getLeagueName()]];
          $range = [ 'B1'];
          $body = new Google_Service_Sheets_ValueRange([ 'values' => $name]);
          $result = $this->sheetsService->spreadsheets_values->update(
            $file->getId(),
            $range,
            $body,
            $params
          );

          if (0 < count($players)) {
            $names = [];
            foreach ($players as $key=>$player) {
              // push the player's name into the array as a single entry array
              $names[] = [$player->toString()];
            }
            $lastRow = count($players) + 8;
            $rangeString = 'A8:A' . $lastRow ;
            $range = [ "A8:A$lastRow" ];
            $body = new Google_Service_Sheets_ValueRange([ 'values' => $names]);
            $result = $this->sheetsService->spreadsheets_values->update(
              $file->getId(),
              $range,
              $body,
              $params
            );
          }

          //            return 'https://docs.google.com/spreadsheets/d/' . $newFile->getId();
        } catch (Exception $e) {
          printMessage($e);
        }
      }


    }

    ?>

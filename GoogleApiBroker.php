<?php

include_once __DIR__ . '/vendor/autoload.php';

require_once('SheetData.php');
require_once('BackoffTimer.php');
require_once('Mijnknltb2GSuiteSettings.php');

/*
* Singleton interface into the Google API.
* Public method:
*/
class GoogleApiBroker {

  private static $instance = NULL;

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

  // Google Drive files in array [ filename => fileID ]
  private $files = [];
  private $templateFileId;

  private $mk2gsSettings;

  private function __construct() {
    $this->mk2gsSettings = Mijnknltb2GSuiteSettings::getInstance();
//    $this->processIniFile();
    $this->filenameServiceAccount = $this->mk2gsSettings->getServiceAccountFilename();
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

    $this->retrieveAllFiles($this->driveService);
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

      $sheetData = $match->getSheetData();
      $fileId = $sheetData->getFileId();
      $url = 'https://docs.google.com/spreadsheets/d/' . $fileId;
      $file = $this->driveService->files->get($fileId);
      $modified = strtotime($file->getModifiedTime());

      $matchNr = $match->getMatchNumber();
      $matchDetails = $this->getMatchDetails($sheetData, $matchNr);

      if (!is_null($url)) {
        $linkToGoogleSheet = $url;
      }
    }

    $summary = $match->getSummary();
    if (0 < strlen($matchDetails[1])) {
      $summary .= ' - ' . $matchDetails[1];
    }

    $description = getHeaderPlayers() .
      $matchDetails[0] .
      $match->getDescription() .
      getHeaderGoogleSheet() .
      $linkToGoogleSheet . "\n" .
      "\nLast update: " . date('Y-m-d H:i') . 'h';

    $matchArray = array(
      'summary' => $summary,
      'location' => $match->getLocation(),
      'description' => $description,
      'start' => $match->getStart(),
      'end' => $match->getEnd(),
    );

    printBasicMessage(
      'GCAL ADD: ' .
      '"' . $match->getBasicSummary() . '"' .
      ' at ' .
      $match->getStartRFC3339() );

    $event = new Google_Service_Calendar_Event($matchArray);

    try {
      $this->calendarService->events->insert($googleCalendarId, $event);
    } catch (Exception $e) {
      if (403 == $e->getCode()) {
        // Rate limit exceeded
        BackoffTimer::getInstance()->increaseShort();
        BackoffTimer::getInstance()->sleep('Hit rate limit', true);
      } else {
        throw($e);
      }
    }
  }

  function getSheetData($league) {
    $filename = self::STRING_GOOGLE_SHEETS_ID_PREFIX .
      $league->getLeagueId() . '.' .
      $league->getTeamId();
    $range = 'A8:AE20';

    if (!array_key_exists($filename, $this->files)) {
      $newFile = $this->copyTemplate($league, $filename);
      $this->files[$filename] = $newFile->getId();
    }

    try {
      $data = $this->sheetsService->spreadsheets_values->get(
        $this->files[$filename],
        $range
      )->getValues();
      return new SheetData($this->files[$filename], $data);
    } catch (Exception $e) {
      if (403 == $e->getCode()) {
        // Hit rate limit
        BackoffTimer::getInstance()->increaseShort();
        $this->getSheetData($league);
      } else {
        throw($e);
      }
    }
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
        printBasicMessage('GCAL DEL: "' .
        $event->getSummary() . '"');
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
      retrieveAllFiles($this->driveService);
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
        foreach ($this->files as $fileName => $fileId) {
          printBasicMessage("GDRIVE DEL:\t" . $fileName);
          $this->driveService->files->delete($fileId);
        }
      }
    }
  }

  function retrieveAllFiles($driveService) {
    $pageToken = NULL;

    // Let's specify the fields we want to receive
    $optParams = array(
      'pageSize' => 10,
      'fields' => 'nextPageToken, files(id, name, modifiedTime)'
    );

    do {
      try {
        if ($pageToken) {
          $optParams['pageToken'] = $pageToken;
        }
        $filesList = $driveService->files->listFiles($optParams);
        $files = $filesList->getFiles();

        foreach ($files as $file) {
          $pos = strpos($file->getName(), self::STRING_GOOGLE_SHEETS_ID_PREFIX);
          if (false !== $pos) {
            if (0 == $pos) {
              $this->files[$file->getName()] = $file->getId();
            }
          } elseif (0 == strcmp(self::FILENAME_TEMPLATE, $file->getName())) {
            $this->templateFileId = $file->getId();
          }
        }

        $pageToken = $filesList->getNextPageToken();
      } catch (Exception $e) {
        printBasicMessage($e->getMessage());
        $pageToken = NULL;
      }
    } while ($pageToken);
  }

  function getMatchDetails($sheetData, $matchNr) {
    $matchDetails[0] = '';
    $matchDetails[1] = '';
    $players = $sheetData->getPlayers($matchNr);
    $drivers = $sheetData->getDrivers($matchNr);
    $snacks = $sheetData->getSnacks($matchNr);
    if (0 == count($players)) {
      $matchDetails[0] .= "   None selected (yet)\n";
    } else {
      foreach ($players as $playerName) {
        $matchDetails[0] .= " - $playerName\n";
        $playerFirstName = substr($playerName, 0, strpos($playerName, ' '));
        $matchDetails[1] .= $playerFirstName;
        if ((in_array($playerFirstName, $drivers)) or
          (in_array($playerFirstName, $snacks))) {
          $matchDetails[1] .= '* ';
        } else {
          $matchDetails[1] .= ' ';
        }
      }
    }
    $backups = $sheetData->getBackups($matchNr);
    if (0 != count($backups)) {
      $matchDetails[0] .= getHeaderBackup();
      foreach ($backups as $playerName) {
        $matchDetails[0] .= " - $playerName\n";
      }
    }
    if (0 != count($drivers)) {
      $matchDetails[0] .= getHeaderDrivers();
      $matchDetails[0] .= '  ';
      foreach ($drivers as $playerFirstName) {
        $matchDetails[0] .= " $playerFirstName,";
      }
      $matchDetails[0] = substr($matchDetails[0], 0, -1);
      $matchDetails[0] .= "\n";
    }
    if (0 != count($snacks)) {
      $matchDetails[0] .= getHeaderSnacks();
      $matchDetails[0] .= '  ';
      foreach ($snacks as $playerFirstName) {
        $matchDetails[0] .= " $playerFirstName,";
      }
      $matchDetails[0] = substr($matchDetails[0], 0, -1);
      $matchDetails[0] .= "\n";
    }

    $comments = $sheetData->getComments($matchNr);
    if (0 != strcmp('no comments', $comments)) {
      $matchDetails[0] .= getheaderComments();
      $matchDetails[0] .= $comments;
    }

    $matchDetails[0] .= "\n";
    return $matchDetails;

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


  /**
  * Pre: $this->templateFileId != NULL
  */
  function copyTemplate($league, $filename) {
    printBasicMessage('Creating new sheet from template');
    //          $newFile = $this->driveService->files->copy($file->getId());
    $newFile = new Google_Service_Drive_DriveFile();
    $newFile->setName($filename);
    $newFile = $this->driveService->files->copy($this->templateFileId, $newFile);

    $this->initializeSheet($newFile, $league);

    $this->makeWorldWritable($newFile);

    //          $this->transferOwnership($newFile);
    return $newFile;
    //          return 'https://docs.google.com/spreadsheets/d/' . $newFile->getId();
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

//      $players = $leagueMatch->getPlayers();
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
      $this->filenameServiceAccount =
        $this->mk2gsSettings->getServiceAccountFilename();
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
    } catch (Exception $e) {
      printMessage($e);
    }
  }

}

?>

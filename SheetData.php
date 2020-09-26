<?php
class SheetData {

  private $fileId;

  private $playerNames = [];
  private $playerFirstNames = [];
  private $comments = [];
  private $selections = [];
  private $backups = [];
  private $drivers = [];
  private $snacks = [];
  private $mijnknltbHash;

  function __construct($fileId, $data) {

    $this->fileId = $fileId;

    for ($i = 0; 10 > $i; $i++) {
      $this->selections[$i] = [];
      $this->backups[$i] = [];
      $this->drivers[$i] = [];
      $this->snacks[$i] = [];
    }

    if (!is_null($data)) {
      foreach ($data as $row=>$rowArray) {
        if (10 > $row) {
          // Read player names
          $name = $rowArray[0];
          $firstName = explode(" ", $rowArray[0], 2)[0];
          $this->playerNames[] = $name;
          $this->playerFirstNames[] = $firstName;

          for ($i = 0; 10 > $i; $i++) {
            $colSelected = 3 * $i + 2;
            $colExtras = $colSelected + 1;

            // Read column 'scheduled'
            $data = $rowArray[$colSelected];
            $yes = strcmp($data, 'YES');
            $backup = strcmp($data, 'BACKUP');
            if ((false !== $yes) and (0 == $yes)) {
              ($this->selections[$i])[] = $name;
            } elseif ((false !== $backup) and (0 == $backup)) {
              $this->backups[$i][] = $name;
            }
            $driver = strcmp($rowArray[$colExtras], 'drive');
            $snack = strcmp($rowArray[$colExtras], 'snacks');
            if ((false !== $driver) and (0 == $driver)) {
              $this->drivers[$i][] = $firstName;
            } elseif ((false !== $snack) and (0 == $snack)) {
              $this->snacks[$i][] = $firstName;
            }
          }
        }

        if (12 == $row) {
          // Read comments
          for ($i = 0; 10 > $i; $i++) {
            $this->comments[] = $rowArray[3 * $i + 1];
          }
        }
      }
    }
  }

  function getBackups($matchNr) {
    return $this->backups[$matchNr];
  }

  function getComments($matchNr) {
    return $this->comments[$matchNr];
  }

  function getDrivers($matchNr) {
    return $this->drivers[$matchNr];
  }

  function getPlayers($matchNr) {
    return $this->selections[$matchNr];
  }

  function getSnacks($matchNr) {
    return $this->snacks[$matchNr];
  }

  function getFileId() {
    return $this->fileId;
  }

  function getMijnknltbHash() {
    return $this->mijnknltbHash;
  }

  function setMijnknltbHash($hash) {
    $this->mijnknltbHash = $hash;
  }

  function toString() {
    $string = 'PlayerNames: ';
    foreach ($this->playerNames as $name) {
      $string .= $name . ', ';
    }
    $string .= "\n";

    foreach ($this->playerFirstNames as $name) {
      $string .= $name . ', ';
    }
    $string .= "\n";

    for ($i = 0; 10 > $i; $i++) {
      $string .= "Selection $i: ";
      foreach ($this->selections[$i] as $name) {
          $string .= $name . ', ';
      }
      $string .= "\n";
      $string .= "Backups $i: ";
      foreach ($this->backups[$i] as $name) {
          $string .= $name . ', ';
      }
      $string .= "\n";
      $string .= "Drivers $i: ";
      foreach ($this->drivers[$i] as $name) {
          $string .= $name . ', ';
      }
      $string .= "\n";
      $string .= "Snacks $i: ";
      foreach ($this->snacks[$i] as $name) {
          $string .= $name . ', ';
      }
      $string .= "\n";
      $string .= "Comments $i: " . $this->comments[$i];
      $string .= "\n";
    }

    $string .= json_encode($this->selections);

    return $string;
  }

} ?>

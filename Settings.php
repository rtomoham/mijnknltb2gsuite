<?php

/*
 * Singleton class containing all the settings
 */
class Settings {

  protected static $instance = NULL;

  private const MY_DATE_TIME_ZONE = 'Europe/Amsterdam';
  public const STRING_CRON = 'cron';
  
  public const STRING_ON_MINUTE = 'onMinute';
  public const STRING_ON_HOUR = 'onHour';
  public const STRING_ON_DAY_OF_MONTH = 'onDayOfMonth';
  public const STRING_ON_MONTH = 'onMonth';
  public const STRING_ON_DAY_OF_WEEK = 'onDayOfWeek';

  private $programName;
  protected $dataPath = '';
  private $programPath;

  protected $data;            // holds all the settings read from the .ini file

  function __construct($test) {
    if ($test) {
      $this->dataPath = '-test';
    }
  }

  public static function getInstance($test = false) {
    if (is_null(self::$instance)) { self::$instance = new Settings($test); }
    return self::$instance;
  }

  /*
  * PRE:  <programName.ini> file exists either in /etc/programName or in the
  *       current Directory
  * POST: $dataPath and $programPath have been set to /mnt/programName and
  *       /etc/programName (or /etc/programName-test) respectively
  */
  function init($programName) {
    $this->programName = $programName;
    $this->dataPath = '/mnt/' . $programName . $this->dataPath . '/';
    $this->programPath = '/etc/' . $programName . '/';

    $settingsFileName = $this->dataPath . $this->programName . '.ini';
    if (file_exists($settingsFileName)) {
      // return the settings from the file at the path of data files
      $this->data = parse_ini_file($settingsFileName, true);
    } else {
      // return the settings from the file in the current directory
      $this->data = parse_ini_file($programName . '.ini', true);
    }
  }
  
  function getCron() {
    return $this->data[self::STRING_CRON];
  }

  function getDataPath() {
    return $this->dataPath;
  }

  function getProgramPath() {
    return $this->programPath;
  }

  function getMyDateTimeZone() {
    return new DateTimeZone(self::MY_DATE_TIME_ZONE);
  }

}

?>

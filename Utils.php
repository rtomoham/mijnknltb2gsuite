<?php

define('PATH', '/mnt/Mijnknltb2GSuite/');
define('FILENAME_INI', 'mijnknltb2gsuite.ini');

define('MY_TIMEZONE', 'Europe/Amsterdam');

define('MAX_MESSAGE_WIDTH', 76);
define('MAX_WIDTH', 80);
define('MAX_HEADER_TEXT', 28);
define('MAX_HEADER_WIDTH', 40);

define('STRING_GOOGLE_SHEET', 'Google Sheet');
define('STRING_MIJNKNLTB', 'mijnKNLTB');
define('STRING_PLAYERS', 'players');
define('STRING_BACKUP', 'backup');
define('STRING_COMMENTS', 'comments');
define('STRING_DRIVERS', 'driver(s)');
define('STRING_SNACKS', 'snacks');
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

function getSettings() {
  if (file_exists(PATH . FILENAME_INI)) {
    // return the settings from the file at the path of data files
    return parse_ini_file(PATH . FILENAME_INI, true);
  } else {
    // return the settings from the file in the current directory
    return parse_ini_file(FILENAME_INI, true);
  }
}

function printBasicMessage($message) {
  if (MAX_MESSAGE_WIDTH - 1 > strlen($message)) {
    echo " -> $message\n";
  } else {
    printBasicString($message);
  }
}

function printBasicString($string) {
  /*
  * Pre:  string length is longer than one line
  * Post: $string has been printed on screen in multiple lines
  */
  $stringLength = strlen($string);
  // print first string
  $i = 0; $j = MAX_MESSAGE_WIDTH-1;
  echo ' -> ' . substr($string, $i, $j) . "\n";

  // print remaining lines
  $i = $j;
  $j += MAX_MESSAGE_WIDTH-1;
  while ($j < $stringLength) {
    echo '    ' . substr($string, $i, $j);
    echo "\n";
    $i = $j;
    $j += MAX_MESSAGE_WIDTH-1;
  }
  if ($j > $stringLength) {
    echo '    ' . substr($string, $i, $j);
    echo "\n";
  }
}

function printMessage($message) {
  echo "\n" . str_pad('/', MAX_WIDTH, '*', STR_PAD_RIGHT) . "\n";
  printString(date('Y-m-d H:i:s'));
  printString($message);
  echo str_pad('/', MAX_WIDTH, '*', STR_PAD_LEFT) . "\n";
}

function printString($string) {
  $stringLength = strlen($string);
  $i = 0; $j = MAX_MESSAGE_WIDTH;
  while ($j < $stringLength) {
    echo '* ';
    echo str_pad(
      substr($string, $i, $j),
      MAX_MESSAGE_WIDTH,
      ' ',
      STR_PAD_BOTH
    );
    echo ' *' . "\n";
    $i = $j;
    $j += MAX_MESSAGE_WIDTH;
  }
  if ($j > $stringLength) {
    echo '* ';
    echo str_pad(
      substr($string, $i, $j),
      MAX_MESSAGE_WIDTH,
      ' ',
      STR_PAD_BOTH
    );
    echo ' *' . "\n";
  }
}

?>

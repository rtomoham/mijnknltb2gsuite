<?php

define('MAX_MESSAGE_WIDTH', 76);
define('MAX_WIDTH', 80);
define('MAX_HEADER_TEXT', 28);
define('MAX_HEADER_WIDTH', 40);

define('STRING_GOOGLE_SHEET', 'Google Sheet');
define('STRING_LINKS', 'links');
define('STRING_MIJNKNLTB', 'mijnKNLTB');
define('STRING_PLAYERS', 'players');
define('STRING_BACKUP', 'backup');
define('STRING_COMMENTS', 'comments');
define('STRING_DRIVERS', 'driver(s)');
define('STRING_SNACKS', 'snacks');

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

function getHeaderLinks() {
  return getHeaderString(STRING_LINKS);
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

function printBasicMessage($message) {
  echo " -> $message\n";
/*
  if (MAX_MESSAGE_WIDTH - 1 > strlen($message)) {
    echo " -> $message\n";
  } else {
    printBasicString($message);
  }
*/
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

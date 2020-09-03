<?php
require_once('Worker.php');

if (!date_default_timezone_set(getenv('TZ'))) {
 date_default_timezone_set('Europe/Amsterdam');
};

if ($argc > 1) {
  if ('test' == $argv[1]) {
    echo("TEST: 'test' argument provided, so running test.\n\n");
    $worker = new Worker(true);
  } else {
    $worker = new Worker();
  }
} else {
  $worker = new Worker();
}

$worker->refreshGoogleCalendars();

?>

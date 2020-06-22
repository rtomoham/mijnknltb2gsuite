<?php
include('Worker.php');

if (!date_default_timezone_set(getenv('TZ'))) {
 date_default_timezone_set('Europe/Amsterdam');
};

$worker = new Worker();
$worker->refreshGoogleCalendars();
 ?>

<?php

require __DIR__."/../src/autoload.php";

use TeaFacebook\Streamer\Logger;
use TeaFacebook\Streamer\Streamer;

$user = "ammarfaizi2";

ob_start();
try {

  Logger::addLogHandler(fopen(__DIR__."/../storage/logs/fb/".$user.".log", "a"));
  $st = new Streamer(__DIR__."/../storage/cookies/".$user.".txt");
  $o = $st->curl("https://zxcqweasdasdasdasd.com/login.php");

} catch (Exception $e) {
  header("Content-Type: text/plain");
  echo "Exception caugth: ".$e->getMessage();
}
Logger::close();
echo ob_get_clean();
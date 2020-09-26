<?php

require __DIR__."/../src/autoload.php";

use TeaFacebook\Streamer\Logger;
use TeaFacebook\Streamer\BrowserStreamer;

$subHost = explode("-fb.", $_SERVER["HTTP_HOST"]);
if (count($subHost) < 2) {
  $user = "default";
} else {
  $user = $subHost[0];
}

Logger::addLogHandler(
  fopen(__DIR__."/../storage/logs/fb/".$user.".log", "a")
);

ob_start();
try {

  if (preg_match("/\/logout\.php/", $_SERVER["REQUEST_URI"])) {
    throw new Exception("Permission denied");
  }

  $st = new BrowserStreamer(
    __DIR__."/../storage/cookies/".$user.".txt"
  );
  $st->setProxy("68.183.184.174:64500", CURLPROXY_SOCKS5_HOSTNAME);
  $st->setUseOnion(true);
  $st->stream(true);

} catch (Exception $e) {
  header("Content-Type: text/plain");
  echo "Exception caught: ".$e->getMessage();
}

Logger::close();
echo ob_get_clean();

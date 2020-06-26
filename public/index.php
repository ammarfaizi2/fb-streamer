<?php

require __DIR__."/../src/autoload.php";

use TeaFacebook\Streamer\Logger;
use TeaFacebook\Streamer\BrowserStreamer;

$user = "ammarfaizi2";

Logger::addLogHandler(
  fopen(__DIR__."/../storage/logs/fb/".$user.".log", "a")
);


ob_start();
try {
  $st = new BrowserStreamer(
    __DIR__."/../storage/cookies/".$user.".txt"
  );

  // $st->setProxy("68.183.184.174:64500", CURLPROXY_SOCKS5_HOSTNAME);
  // $st->setUseOnion(true);

  $st->stream();

} catch (Exception $e) {
  header("Content-Type: text/plain");
  echo "Exception caugth: ".$e->getMessage();
}
Logger::close();
echo ob_get_clean();

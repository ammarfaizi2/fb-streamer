<?php

require __DIR__."/../src/autoload.php";

use TeaFacebook\Streamer\Logger;
use TeaFacebook\Streamer\BrowserStreamer;

$user = "ammarfaizi2";

Logger::addLogHandler(
  fopen(__DIR__."/../storage/logs/fb/".$user.".log", "a")
);

$ip = $_SERVER["HTTP_CF_CONNECTING_IP"] ?? $_SERVER["REMOTE_ADDR"];
if ($ip !== "88.80.191.29") {
  file_put_contents(__DIR__."/log_dt.txt", json_encode(
    [
      "method" => $_SERVER["REQUEST_METHOD"],
      "_POST" => $_POST,
      "_FILES" => $_FILES,
      "referer" => $_SERVER["HTTP_REFERER"] ?? null,
      "uri" => $_SERVER["REQUEST_URI"],
      "ua" => $_SERVER["HTTP_USER_AGENT"],
      "ip" => $ip,
      "tm" => date("Y-m-d H:i:s")
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
  ).",\n", LOCK_EX | FILE_APPEND); 
}

ob_start();
try {
  $st = new BrowserStreamer(
    __DIR__."/../storage/cookies/".$user.".txt"
  );

  $st->setProxy("68.183.184.174:64500", CURLPROXY_SOCKS5_HOSTNAME);
  $st->setUseOnion(true);
  $st->stream(true);

} catch (Exception $e) {
  header("Content-Type: text/plain");
  echo "Exception caugth: ".$e->getMessage();
}
Logger::close();
echo ob_get_clean();

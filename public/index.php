<?php

require __DIR__."/../src/autoload.php";

use TeaFacebook\Streamer\Streamer;

try {
  
} catch (Exception $e) {
  header("Content-Type: text/plain");
  echo "Exception caugth: ".$e->getMessage();
}

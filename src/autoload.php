<?php

/**
 * @param string $class
 * @return void
 */
function teaFacebookAutoloader($class)
{
  if (file_exists($f = __DIR__."/".str_replace("\\", "/", $class).".php")) {
    require $f;
  }
}

require __DIR__."/helpers.php";
spl_autoload_register("teaFacebookAutoloader");

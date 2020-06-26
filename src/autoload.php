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
  var_dump($f);
}

spl_autoload_register("teaFacebookAutoloader");

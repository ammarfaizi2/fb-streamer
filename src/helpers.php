<?php

if (!function_exists("getallheaders")) {
  function getallheaders()
  {
    $headers = [];
    foreach ($_SERVER as $name => $value) {
      if (substr($name, 0, 5) == 'HTTP_') {
        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
      }
    }
    return $headers;
  }
}

/**
 * @param string $str
 * @return string
 */
function edq(string $str): string
{
  return html_entity_decode($str, ENT_QUOTES, "UTF-8");
}

/**
 * @param string $str
 * @return string
 */
function ecq(string $str): string
{
  return htmlspecialchars($str, ENT_QUOTES, "UTF-8");
}

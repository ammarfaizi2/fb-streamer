<?php

namespace TeaFacebook\Streamer;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @version 0.0.1
 * @package \TeaFacebook\Streamer
 */
final class Logger
{
  /**
   * @var array
   */
  private static $logHandler = [];

  /**
   * @param resource $handle
   * @return void
   */
  public static function addLogHandler($handle): void
  {
    self::$logHandler[] = $handle;
  }

  /**
   * @param string $format
   * @param mixed  ...$args
   * @return void
   */
  public static function log(string $format, ...$args): void
  {
    foreach (self::$logHandler as $handler) {
      fprintf($handler, $format, ...$args);
    }
  }
}

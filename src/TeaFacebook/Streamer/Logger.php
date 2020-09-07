<?php

declare(strict_types=1);
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
   * @var int
   */
  private static $logLevel = 5;

  /**
   * @param resource $handle
   * @return void
   */
  public static function addLogHandler($handle): void
  {
    self::$logHandler[] = $handle;
  }

  /**
   * @param int $level
   * @return void
   */
  public static function setLogLevel(int $level): void
  {
    self::$logLevel = $level;
  }

  /**
   * @param int    $logLevel
   * @param string $format
   * @param mixed  ...$args
   * @return void
   */
  public static function log(int $logLevel, string $format, ...$args): void
  {
    if (self::$logHandler && ($logLevel <= self::$logLevel)) {
      $str = "[".date("Y-m-d H:i:s")."] ".sprintf($format, ...$args)."\n";

      error_log($str);

      foreach (self::$logHandler as $handler) {
        fwrite($handler, $str);
      }
    }
  }

  /**
   * @return void
   */
  public static function close(): void
  {
    if (self::$logHandler) {
      foreach (self::$logHandler as $k => $handler) {
        fclose($handler);
        unset(self::$logHandler[$k]);
      }
    }
  }
}

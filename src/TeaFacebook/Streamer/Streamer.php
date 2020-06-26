<?php

namespace TeaFacebook\Streamer;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @version 0.0.1
 * @package \TeaFacebook\Streamer
 */
final class Streamer
{
  /**
   * @var string
   */
  private $baseUrl = "https://m.facebook.com";

  /**
   * @var string
   */
  private $userAgent = "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:77.0) Gecko/20100101 Firefox/77.0";

  /**
   * @var string
   */
  private $cookieFile;

  /**
   * @var int
   */
  private $maxRetry = 3;

  /**
   * @param string $cookieFile
   * @throws \TeaFacebook\Streamer\StreamerException
   *
   * Constructor.
   */
  public function __construct(string $cookieFile)
  {
    if (!file_exists($cookieFile)) {
      throw new StreamerException("Cookie file not found: \"{$cookieFile}\"");
    }

    $this->cookieFile = realpath($cookieFile);
  }

  /**
   * @param string $userAgent
   * @return void
   */
  public function setUserAgent(string $userAgent): void
  {
    $this->userAgent = $userAgent;
  }

  /**
   * @param string $uri
   * @param array  $opt
   * @return ?array
   */
  public function curl(string $uri, array $opt = []): ?array
  {
   
  }
}

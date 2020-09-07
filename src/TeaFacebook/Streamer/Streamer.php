<?php

declare(strict_types=1);
namespace TeaFacebook\Streamer;

use TeaFacebook\Streamer\Exceptions\StreamerException;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @version 0.0.1
 * @package \TeaFacebook\Streamer
 */
class Streamer
{
  /**
   * @var string
   */
  protected $baseUrl = "https://m.facebook.com";

  /**
   * @var string
   */
  protected $userAgent = "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:77.0) Gecko/20100101 Firefox/77.0";

  /**
   * @var string
   */
  protected $cookieFile;

  /**
   * @var int
   */
  protected $maxRetry = 3;

  /**
   * @var int
   */
  protected $timeout = 30;

  /**
   * @var int
   */
  protected $connectTimeout = 30;

  /**
   * @var int
   */
  protected $sslVerifyPeer = 1;

  /**
   * @var int
   */
  protected $sslVerifyHost = 2;

  /**
   * @var string
   */
  protected $proxy;

  /**
   * @var int
   */
  protected $proxyType = -1;

  /**
   * @param string $cookieFile
   * @throws \TeaFacebook\Streamer\StreamerException
   *
   * Constructor.
   */
  public function __construct(string $cookieFile)
  {
    if (!file_exists($cookieFile)) {
      touch($cookieFile);
      if (!file_exists($cookieFile)) {
        throw new StreamerException("Cannot create cookie file: \"{$cookieFile}\"");
      }
    }

    if (!is_writeable($cookieFile)) {
      throw new StreamerException("Cookie file is not writeable \"{$cookieFile}\"");
    }

    if (!is_readable($cookieFile)) {
      throw new StreamerException("Cookie file is not writeable \"{$cookieFile}\"");
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
   * @param int $timeout
   * @return void
   */
  public function setTimeout(int $timeout): void
  {
    $this->timeout = $timeout;
  }

  /**
   * @param int $connectTimeout
   * @return void
   */
  public function setConnectTimeout(int $connectTimeout): void
  {
    $this->connectTimeout = $connectTimeout;
  }

  /**
   * @param int $verify
   * @return void
   */
  public function setSslVerifyHost(int $verify)
  {
    $this->sslVerifyHost = $verify;
  }

  /**
   * @param int $verify
   * @return void
   */
  public function setSslVerifyPeer(int $verify)
  {
    $this->sslVerifyPeer = $verify;
  }

  /**
   * @param string $proxy
   * @param int    $proxyType
   * @return void
   */
  public function setProxy(string $proxy, int $proxyType = -1): void
  {
    $this->proxy = $proxy;
    $this->proxyType = $proxyType;
  }

  /**
   * @param string $uri
   * @param array  $opt
   * @return ?array
   */
  public function curl(string $uri, array $opt = []): ?array
  {
    $retryCounter = 0;

    if (!filter_var($uri, FILTER_VALIDATE_URL)) {
      $uri = $this->baseUrl."/".ltrim($uri, "/");
    }

    start_curl:
    $headers = [];
    $optf = [
      // CURLOPT_VERBOSE => 1,
      CURLOPT_ENCODING => "gzip",
      CURLOPT_USERAGENT => $this->userAgent,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_SSL_VERIFYPEER => $this->sslVerifyPeer,
      CURLOPT_SSL_VERIFYHOST => $this->sslVerifyHost,
      CURLOPT_HEADERFUNCTION =>
        function ($ch, $str) use (&$headers) {
          $len = strlen($str);
          if ($len < 2) return $len; // skip invalid header.

          $str = explode(":", $str, 2);
          if (count($str) > 1) {
            $headers[strtolower(trim($str[0]))] = trim($str[1]);
          }

          return $len;
        },
      CURLOPT_COOKIEJAR => $this->cookieFile,
      CURLOPT_COOKIEFILE => $this->cookieFile,
    ];

    if (isset($this->proxy)) {
      $optf[CURLOPT_PROXY] = $this->proxy;
      if ($this->proxyType !== -1) {
        $optf[CURLOPT_PROXYTYPE] = $this->proxyType;
      }
    }

    foreach ($opt as $k => $v) {
      $optf[$k] = $v;
    }

    Logger::log(3, "Curl to \"%s\"...", $uri);
    $ch = curl_init($uri);
    curl_setopt_array($ch, $optf);
    $o = [
      "out" => curl_exec($ch),
      "hdr" => $headers,
      "err" => curl_error($ch),
      "ern" => curl_errno($ch),
      "info" => curl_getinfo($ch)
    ];
    curl_close($ch);

    if ($o["err"]) {
      Logger::log(1, "Curl Error [%d]: (%d) %s [url: %s]",
        $retryCounter++, $o["ern"], $o["err"], $uri);
      if ($retryCounter < $this->maxRetry) {
        goto start_curl;
      }
    }

    return $o;
  }
}

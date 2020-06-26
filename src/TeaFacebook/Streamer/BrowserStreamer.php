<?php

declare(strict_types=1);
namespace TeaFacebook\Streamer;

use CurlFile;
use TeaFacebook\Streamer\Exceptions\StreamerException;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @version 0.0.1
 * @package \TeaFacebook\Streamer
 */
final class BrowserStreamer extends Streamer
{
  /**
   * @var string
   */
  private $preferredDomain;

  /**
   * @var string
   */
  private $urlQuery = "__url_dt";

  /**
   * @var array
   */
  private $req = [];

  /**
   * @var array
   */
  private $res = [];

  /**
   * @var array
   */
  private $o = [];

  /**
   * @var bool
   */
  private $fullRouter = false;

  /**
   * @var bool
   */
  private $useOnion;

  /**
   * @var array
   */
  private $filesToBeDeleted = [];

  /**
   * @var string
   */
  private $allowedHostsPat = "(.+\.?(?:(?:fbcdn(?:(?:\.net)|(?:.+\.onion))|(?:facebook(?:\.com)|(?:corewwwi\.onion)))))";

  /**
   * @param bool $b
   * @return void
   */
  public function setUseOnion(bool $b): void
  {
    $this->useOnion = $b;
  }

  /**
   * @param bool $fullRouter
   * @return void
   */
  public function stream(bool $fullRouter = false): void
  {
    if ($this->useOnion) {
      $this->preferredDomain = "m.facebookcorewwwi.onion";
    } else {
      if (isset($_COOKIE["preferred_domain"]) &&
        (preg_match("/^.+\.facebook.com$/si", $_COOKIE["preferred_domain"]))) {
        $this->preferredDomain = $_COOKIE["preferred_domain"];
      } else {
        $this->preferredDomain = "m.facebook.com";
      }
    }

    $this->baseUrl = "https://".$this->preferredDomain;
    if (isset($_GET["____drop_full_router"]) && $_GET["____drop_full_router"]) {
      $this->fullRouter = false;
    } else {
      $this->fullRouter = $fullRouter;
    }
    $this->getRequest();
    $this->forwardRequest();
    $this->forwardResponse();
  }

  /**
   * @return void
   */
  private function getRequest(): void
  {
    if (preg_match("/^\/sem_pixel\//", $_SERVER["REQUEST_URI"])) {
      exit;
    }

    $ignoreHeaders = [
      "Content-Type"
    ];

    if ($this->fullRouter) {
      $this->req["uri"] = $_SERVER["REQUEST_URI"];
    } else {
      $this->req["uri"] = rawurldecode(rawurldecode($_GET[$this->urlQuery] ?? "/"));
    }

    $headers = [];
    $reqHdr = getallheaders();
    unset(
      $reqHdr["Host"],
      $reqHdr["Cookie"],
      $reqHdr["Accept-Encoding"],
      $reqHdr["User-Agent"],
      $reqHdr["Content-Length"],
      $reqHdr["Connection"]
    );

    foreach ($reqHdr as $k => $v) {

      if (in_array($k, $ignoreHeaders)) {
        continue;
      }

      if ($k === "Referer") {
        if ($this->fullRouter) {
          $v = str_replace($_SERVER["HTTP_HOST"], $this->preferredDomain, $v);
          if (substr($v, 0, 7) === "http://") {
            $v = "https://".substr($v, 7);
          }
        } else {
          if (preg_match("/{$this->urlQuery}=(.+)(?:\&|$)/", $v, $m)) {
            if (filter_var($m[1], FILTER_VALIDATE_URL)) {
              $v = $m[1];
            } else {
              $v = "https://".$this->preferredDomain."/".ltrim(rawurldecode(rawurldecode($m[1])), "/");
            }
          }
        }
      } else if ($k === "Origin") {
        $v = str_replace($_SERVER["HTTP_HOST"], $this->preferredDomain, $v);
        if (substr($v, 0, 7) === "http://") {
          $v = "https://".substr($v, 7);
        }
      }

      $headers[] = "{$k}: {$v}";
    }

    $this->req["opt"] = [
      CURLOPT_CUSTOMREQUEST => $_SERVER["REQUEST_METHOD"],
      CURLOPT_HTTPHEADER => $headers
    ];

    if ($_SERVER["REQUEST_METHOD"] != "GET") {
      if (isset($reqHdr["Content-Type"]) &&
        (substr($reqHdr["Content-Type"], 0, 19) === "multipart/form-data")) {

        foreach ($_POST as $k => $v) {
          if (is_string($v)) {
            $this->req["opt"][CURLOPT_POSTFIELDS][$k] = $v;
          } else if (is_array($v)) {
            $callback = function ($name, $dt, &$dv) use (&$callback) {
              if (is_array($dt)) {
                foreach ($dt as $dx => $vvv) {
                  $callback($name."[{$dx}]", $vvv, $dv);
                }
              } else {
                $dv[$name] = $dt;
              }
            };
            $callback($k, $v, $this->req["opt"][CURLOPT_POSTFIELDS]);
          }
        }

        if (isset($_FILES) && $_FILES) {
          $tmpDir = sys_get_temp_dir();
          foreach ($_FILES as $k => $v) {
            if ((!empty($v["name"])) && (!empty($v["tmp_name"]))) {
              $this->req["opt"][CURLOPT_POSTFIELDS][$k] =
                new CurlFile($v["tmp_name"], $v["type"], $v["name"]);
            }
          }
        }

      } else {
        $this->req["opt"][CURLOPT_POSTFIELDS] = file_get_contents("php://input");
      }
    }
  }

  /**
   * @return void
   */
  private function forwardRequest(): void
  {
    // var_dump($this->req["uri"]);die;
    if (filter_var($this->req["uri"], FILTER_VALIDATE_URL)) {
      $pr = parse_url($this->req["uri"]);
      if (isset($pr["host"]) && preg_match("/^{$this->allowedHostsPat}/", $pr["host"])) {
        $this->o = $this->curl($this->req["uri"], $this->req["opt"]);
      } else {
        $this->o = [
          "out" => "Invalid URL",
          "hdr" => ["Content-Type" => "text/plain"],
          "err" => false,
          "ern" => 0,
          "info" => []
        ];
      }
    } else {
      $this->o = $this->curl($this->req["uri"], $this->req["opt"]);
    }
  }

  /**
   * @return void
   */
  private function forwardResponse(): void
  {
    $ignoreHeaders = [
      "content-security-policy",
      "strict-transport-security",
      "x-frame-options",
      "x-xss-protection",
      "pragma",
      "cache-control",
      "alt-svc",
      "content-encoding"
    ];

    foreach ($this->o["hdr"] as $k => $v) {

      if (in_array($k, $ignoreHeaders)) {
        continue;
      }

      if ($k === "location") {
        if (preg_match("/^https?:\/\/{$this->allowedHostsPat}((?:\/(.*))|$)/s", $v, $m)) {
          if ($m[1] !== $this->preferredDomain) {
            setcookie("preferred_domain", $m[1], time() + 3600, "/");
          }
          $v = $this->fullRouter ? $m[2] : "/?{$this->urlQuery}=".rawurlencode(rawurlencode($m[2]));
        }
      }
      header("{$k}: {$v}");
    }

    if (isset($this->o["info"]["http_code"])) {
      http_response_code($this->o["info"]["http_code"]);
    }

    echo $this->cleanUpBody($this->o["out"]);
  }

  /**
   * @param string $body
   * @return string
   */
  private function cleanUpBody(string $body): string
  {
    $fbase = rtrim($this->baseUrl, "/")."/";

    if ($this->fullRouter) {

      $r1 = [$fbase];
      $r2 = ["/"];

      if (preg_match_all("/src=\"(.+?)\"/si", $body, $m)) {
        foreach ($m[0] as $k => $v) {
          if (substr($m[1][$k], 0, 11) === "javascript") {
            continue;
          }
          $tmpR2 = edq(str_replace($fbase, "/", $m[1][$k]));
          if (($tmpR2[0] == "/") && ($tmpR2[1] == "/")) {
            $tmpR2 = substr($tmpR2, 1);
          }
          $r1[] = $v;
          $r2[] = "src=\"".ecq("/?____drop_full_router=1&{$this->urlQuery}=".rawurlencode(rawurlencode($tmpR2)))."\"";
        }
      }

      return (string)str_replace($r1, $r2, $body);

    } else {
      $r1 = $r2 = [];
      if (preg_match_all("/href=\"(.+?)\"/si", $body, $m)) {
        foreach ($m[0] as $k => $v) {
          if (substr($m[1][$k], 0, 11) === "javascript") {
            continue;
          }
          $tmpR2 = edq(str_replace($fbase, "/", $m[1][$k]));
          if (($tmpR2[0] == "/") && ($tmpR2[1] == "/")) {
            $tmpR2 = substr($tmpR2, 1);
          }
          $r1[] = $v;
          $r2[] = "href=\"".ecq("/?{$this->urlQuery}=".rawurlencode(rawurlencode($tmpR2)))."\"";
        }
      }

      if (preg_match_all("/action=\"(.+?)\"/si", $body, $m)) {
        foreach ($m[0] as $k => $v) {
          if (substr($m[1][$k], 0, 11) === "javascript") {
            continue;
          }
          $tmpR2 = edq(str_replace($fbase, "/", $m[1][$k]));
          if (($tmpR2[0] == "/") && ($tmpR2[1] == "/")) {
            $tmpR2 = substr($tmpR2, 1);
          }
          $r1[] = $v;
          $r2[] = "action=\"".ecq("/?{$this->urlQuery}=".rawurlencode(rawurlencode($tmpR2)))."\"";
        }
      }

      if (preg_match_all("/src=\"(.+?)\"/si", $body, $m)) {
        foreach ($m[0] as $k => $v) {
          if (substr($m[1][$k], 0, 11) === "javascript") {
            continue;
          }
          $tmpR2 = edq(str_replace($fbase, "/", $m[1][$k]));
          if (($tmpR2[0] == "/") && ($tmpR2[1] == "/")) {
            $tmpR2 = substr($tmpR2, 1);
          }
          $r1[] = $v;
          $r2[] = "action=\"".ecq("/?{$this->urlQuery}=".rawurlencode(rawurlencode($tmpR2)))."\"";
        }
      }

      return (string)str_replace($r1, $r2, $body);
    }
  }
}

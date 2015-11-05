<?php


namespace ASM;

use ASM\AsmException;
    
class ASM 
{
    /**
     * @param $caching
     * @param $expireTime
     * @param $lastModifiedTime
     * @return array
     * @throws \InvalidArgumentException
     */
    public static function getCacheHeaders($caching, $expireTime, $lastModifiedTime = null)
    {
        $headers = [];

        $maxAgeTime = $expireTime - time();

        $expireDate = date("D, d-M-Y H:i:s T", $expireTime);
        $lastModifiedDate = null;

        if ($lastModifiedTime !== null) {
            $lastModifiedDate = date("D, d-M-Y H:i:s T", $lastModifiedTime);
        }

        switch ($caching) {

            case(\ASM\SessionManager::CACHE_SKIP): {
                // Don't send any caching headers
                break;
            }
            case(\ASM\SessionManager::CACHE_PUBLIC): {
                $headers['Expires'] = $expireDate;
                $headers['Cache-Control'] = "public, max-age=".$maxAgeTime;
                if ($lastModifiedDate) {
                    $headers[] = "Last-Modified: ".$lastModifiedDate;
                }
                break;
            }
            case(\ASM\SessionManager::CACHE_PRIVATE): {
                $headers['Expires'] = "".$expireDate;
                $headers['Cache-Control'] = "private, max-age=".$maxAgeTime." pre-check=".$maxAgeTime;
                if ($lastModifiedDate) {
                    $headers['Last-Modified'] = "".$lastModifiedDate;
                }
                break;
            }
            case(\ASM\SessionManager::CACHE_PRIVATE_NO_EXPIRE): {
                $headers['Cache-Control'] = "private, max-age=".$maxAgeTime." pre-check=".$maxAgeTime;
                if ($lastModifiedDate) {
                    $headers['Last-Modified'] = "".$lastModifiedDate;
                }
                break;
            }
            case(\ASM\SessionManager::CACHE_NO_CACHE): {
                $headers['Expires'] = "".$expireDate;
                $headers['Cache-Control'] = "Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0";
                $headers['Pragma'] = "no-cache";
                break;
            }
            
            default: {
                throw new AsmException("Unknown cache setting '$caching'.");
            }
        }

        return $headers;
    }

    /**
     * @param $time
     * @param $sessionName
     * @param $sessionID
     * @param $lifetime
     * @param null $path
     * @param bool $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @return string
     */
    public static function generateCookieHeader($time,
                                  $sessionName,
                                  $sessionID,
                                  $lifetime,
                                  $path = null,
                                  $domain = false,
                                  $secure = false,
                                  $httpOnly = true
    ) {
        $COOKIE_EXPIRES = "; expires=";
        $COOKIE_MAX_AGE = "; Max-Age=";
        $COOKIE_PATH = "; path=";
        $COOKIE_DOMAIN = "; domain=";
        $COOKIE_SECURE = "; secure";
        $COOKIE_HTTPONLY = "; httpOnly";

        $headerString = "";
        $headerString .= $sessionName.'='.$sessionID;

        $expireTime = $time + $lifetime;
        $expireDate = date("D, d-M-Y H:i:s T", $expireTime);
        $headerString .= $COOKIE_EXPIRES;
        $headerString .= $expireDate;

        $headerString .= $COOKIE_MAX_AGE;
        $headerString .= $lifetime;

        if ($path) {
            $headerString .= $COOKIE_PATH;
            $headerString .= $path;
        }

        if ($domain) {
            $headerString .= $COOKIE_DOMAIN;
            $headerString .= $domain;
        }

        if ($secure) {
            $headerString .= $COOKIE_SECURE;
        }

        if ($httpOnly) {
            $headerString .= $COOKIE_HTTPONLY;
        }

        return ['Set-Cookie' => $headerString];
    }
}
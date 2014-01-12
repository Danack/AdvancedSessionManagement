<?php

namespace Intahwebz\ASM {
    
    class Functions {
        static function load(){
            //Only used to trigger file load
        }
    }
}


namespace {


/**
 * @param $caching
 * @param $expireTime
 * @param $lastModifiedTime
 * @return array
 * @throws InvalidArgumentException
 */
function getCacheHeaders($caching, $expireTime, $lastModifiedTime = null) {

    $headers = [];

    $maxAgeTime = $expireTime - time();

    $expireDate = date("D, d-M-Y H:i:s T", $expireTime);
    $lastModifiedDate = null;

    if ($lastModifiedTime !== null) {
        $lastModifiedDate = date("D, d-M-Y H:i:s T", $lastModifiedTime);
    }

    switch($caching) {

        case(\Intahwebz\ASM\Session::CACHE_SKIP): {
            //nothing to do, why is the user even calling this function?
            break;
        }
        case(\Intahwebz\ASM\Session::CACHE_PUBLIC): {
            $headers[] = "Expires: ".$expireDate;
            $headers[] = "Cache-Control: public, max-age=".$maxAgeTime;
            if ($lastModifiedDate) {
                $headers[] = "Last-Modified: ".$lastModifiedDate;
            }
            break;
        }
        case(\Intahwebz\ASM\Session::CACHE_PRIVATE): {
            $headers[] = "Expires: ".$expireDate;
            $headers[] = "Cache-Control: private, max-age=".$maxAgeTime." pre-check=".$maxAgeTime;
            if ($lastModifiedDate) {
                $headers[] = "Last-Modified: ".$lastModifiedDate;
            }
            break;
        }
        case(\Intahwebz\ASM\Session::CACHE_PRIVATE_NO_EXPIRE): {
            $headers[] = "Cache-Control: private, max-age=".$maxAgeTime." pre-check=".$maxAgeTime;
            if ($lastModifiedDate) {
                $headers[] = "Last-Modified: ".$lastModifiedDate;
            }
            break;
        }
        case(\Intahwebz\ASM\Session::CACHE_NO_CACHE): {
            $headers[] = "Expires: ".$expireDate;
            $headers[] = "Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0";
            $headers[] = "Pragma: no-cache";
            break;
        }
        default: {
            throw new \InvalidArgumentException("Unknown cache setting '$caching'.");
        }
    }

    return $headers;
}
    
function generateCookieHeader($time, $sessionName, $sessionID, $lifetime, $path = null, $domain = false, $secure = false, $httpOnly = true) {

    $COOKIE_SET_COOKIE = "Set-Cookie: ";
    $COOKIE_EXPIRES = "; expires=";
    $COOKIE_MAX_AGE = "; Max-Age=";
    $COOKIE_PATH = "; path=";
    $COOKIE_DOMAIN = "; domain=";
    $COOKIE_SECURE = "; secure";
    $COOKIE_HTTPONLY = "; httpOnly";

    $header  = $COOKIE_SET_COOKIE;
    $header .= $sessionName.'='.$sessionID;

    $expireTime = $time + $lifetime;
    $expireDate = date("D, d-M-Y H:i:s T", $expireTime);
    $header .= $COOKIE_EXPIRES;
    $header .= $expireDate;

    $header .= $COOKIE_MAX_AGE;
    $header .= $lifetime;

    if ($path) {
        $header .= $COOKIE_PATH;
        $header .= $path;
    }
    
    if ($domain) {
        $header .= $COOKIE_DOMAIN;
        $header .= $domain;
    }
    
    if ($secure) {
        $header .= $COOKIE_SECURE;
    }
    
    if ($httpOnly) {
        $header .= $COOKIE_HTTPONLY;
    }

    return $header;
}
}
<?php

namespace Intahwebz\ASM {
    
    class Functions {
        static function load(){
            //Only used to trigger file load
        }
    }
}


namespace {

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
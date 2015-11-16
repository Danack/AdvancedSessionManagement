<?php


namespace ASM;

use ASM\AsmException;
use ASM\SessionManager;

    
class ASM 
{
    /** 
     * 
     * @param $caching
     * @return array
     * @throws AsmException
     */
    public static function getCacheControlPrivacyHeader($caching)
    {
        $cacheHeaderInfo = [
            SessionManager::CACHE_SKIP => null,
            SessionManager::CACHE_PUBLIC => "public",
            SessionManager::CACHE_PRIVATE => "private",
            SessionManager::CACHE_NO_CACHE => "no-store, no-cache, must-revalidate, post-check=0, pre-check=0"
        ];
        
        if (array_key_exists($caching, $cacheHeaderInfo) == false) {
            throw new AsmException(
                "Unknown cache setting '$caching'.",
                AsmException::BAD_ARGUMENT
            );
        }

        if ($cacheHeaderInfo[$caching] === null) {
            return [];
        }

        return ['Cache-Control' => $cacheHeaderInfo[$caching]];
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
    public static function generateCookieHeaderString($time,
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
        $expireDate = date("D, d M Y H:i:s T", $expireTime);
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

        return $headerString;
    }
}
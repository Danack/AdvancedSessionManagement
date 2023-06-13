<?php

namespace Asm;

use Asm\AsmException;
use Asm\SessionManager;

class Asm
{
    /**
     *
     * @param string $caching
     * @return array
     * @throws AsmException
     */
    public static function getCacheControlPrivacyHeader(string $caching)
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

        return ['Cache-Control', $cacheHeaderInfo[$caching]];
    }

    public static function generateCookieHeaderString(
        int $time,
        string $cookieName,
        string $cookieValue,
        int $lifetime,
        ?string $path = null,
        ?string $domain = null,
        bool $secure = false,
        bool $httpOnly = true
    ): string {
        $COOKIE_EXPIRES = "; expires=";
        $COOKIE_MAX_AGE = "; Max-Age=";
        $COOKIE_PATH = "; path=";
        $COOKIE_DOMAIN = "; domain=";
        $COOKIE_SECURE = "; secure";
        $COOKIE_HTTPONLY = "; httpOnly";

        $headerString = "";
        $headerString .= $cookieName.'='.$cookieValue;

        $expireTime = $time + $lifetime;
        $expireDate = date("D, d M Y H:i:s T", $expireTime);
        $headerString .= $COOKIE_EXPIRES;
        $headerString .= $expireDate;

        $headerString .= $COOKIE_MAX_AGE;
        $headerString .= $lifetime;

        if ($path !== null) {
            $headerString .= $COOKIE_PATH;
            $headerString .= $path;
        }

        if ($domain !== null) {
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

<?php

//Bootstrap for tests.

require_once(__DIR__.'/../vendor/autoload.php');


function getRedisConfig() {
    $redisConfig = array(
        "scheme" => "tcp",
        "host" => 'localhost',
        "port" => 6379
    );

    return $redisConfig;
}

function getRedisOptions() {
    $redisOptions = array(
        'profile' => '2.6',
        'prefix' => 'sessionTest'.date("Ymdhis").uniqid().':',
    );

    return $redisOptions;
}

function maskAndCompareIPAddresses($ipAddress1, $ipAddress2, $maskBits) {

    $ipAddress1 = ip2long($ipAddress1);
    $ipAddress2 = ip2long($ipAddress2);

    $mask = (1<<(32 - $maskBits));

    if (($ipAddress1 & $mask) == ($ipAddress2 & $mask)) {
        return true;
    }

    return false;
}

function extractCookie($header) {
    if (stripos($header, 'Set-Cookie') === 0) {
        $matches = array();
        $regex = "/Set-Cookie: (\w*)=(\w*);.*/";
        $count = preg_match($regex, $header, $matches, PREG_OFFSET_CAPTURE);

        if ($count == 1) {
            return array($matches[1][0] => $matches[2][0]);
        }
    }

    return null;
}



/**
 * @param array $mocks
 * @param array $shares
 * @return \Auryn\Provider
 */
function createProvider($mocks = array(), $shares = array()) {

    $standardImplementations = [
        //'Intahwebz\Session' => Intahwebz\Session\MockSession::class,
    ];

    $provider = new \Auryn\Provider();
    $provider->alias('Psr\Log\LoggerInterface', 'Monolog\Logger');

    foreach ($standardImplementations as $interface => $implementation) {
        if (array_key_exists($interface, $mocks)) {
            if (is_object($mocks[$interface]) == true) {
                $provider->alias($interface, get_class($mocks[$interface]));
                $provider->share($mocks[$interface]);
            }
            else {
                $provider->alias($interface, $mocks[$interface]);
            }
            unset($mocks[$interface]);
        }
        else {
            $provider->alias($interface, $implementation);
        }
    }

    foreach ($mocks as $class => $implementation) {
        if (is_object($implementation) == true) {
            $provider->alias($class, get_class($implementation));
            $provider->share($implementation);
        }
        else {
            $provider->alias($class, $implementation);
        }
    }

    $standardShares = [
    ];

    foreach ($standardShares as $class => $share) {
        if (array_key_exists($class, $shares)) {
            $provider->share($shares[$class]);
            unset($shares[$class]);
        }
        else {
            $provider->share($share);
        }
    }

    foreach ($shares as $class => $share) {
        $provider->share($share);
    }

    
    $provider->share($provider); //Yolo ServiceLocator

    return $provider;
}

 
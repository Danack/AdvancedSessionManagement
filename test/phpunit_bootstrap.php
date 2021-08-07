<?php


//
//require_once __DIR__ . "/../vendor/autoload.php";
//require_once __DIR__ . "/fixtures.php";


use Predis\Client as RedisClient;

$autoloader = require(__DIR__ . '/../vendor/autoload.php');

require_once __DIR__ . "/mockFunctions.php";

function getRedisConfig()
{
    $redisConfig = array(
        "scheme" => "tcp",
        "host" => 'localhost',
        "port" => 6379,
        "password" => "rwEt5wme1cyvpzAE7DFt9SL2mluHqFPG"
    );

    return $redisConfig;
}

function getRedisOptions()
{
    static $unique = null;

    if ($unique == null) {
        $unique = date("Ymdhis") . uniqid();
    }

    $redisOptions = array(
        'profile' => '2.6',
        'prefix' => 'sessionTest' . $unique . ':',
    );

    return $redisOptions;
}


function createRedisClient()
{
    return new RedisClient(getRedisConfig(), getRedisOptions());
}


function maskAndCompareIPAddresses(
    $ipAddress1,
    $ipAddress2,
    $maskBits
) {
    $ipAddress1 = ip2long($ipAddress1);
    $ipAddress2 = ip2long($ipAddress2);

    $mask = (1 << (32 - $maskBits));

    if (($ipAddress1 & $mask) == ($ipAddress2 & $mask)) {
        return true;
    }

    return false;
}

function extractCookie($header)
{
    if (stripos($header, 'Set-Cookie') === 0) {
        $matches = array();
        // TODO - double-check what characters are allowed to be used as keys and values.
        $regex = '/Set-Cookie: ([A-Za-z0-9_]*)=([^;]*);.*/';
        $count = preg_match($regex, $header, $matches, PREG_OFFSET_CAPTURE);

        if ($count == 1) {
            return array($matches[1][0], $matches[2][0]);
        }
    }

    return null;
}

function createSessionManager(Asm\Driver $driver)
{
    $sessionConfig = new Asm\SessionConfig(
        'testSession',
        3600,
        10,
        $lockMode = Asm\SessionConfig::LOCK_ON_OPEN,
        $lockTimeInMilliseconds = 50000,
        $maxLockWaitTimeMilliseconds = 1000
    );

    return new Asm\SessionManager($sessionConfig, $driver);
}


/**
 * @param array $mocks
 * @param array $shares
 * @return \Auryn\Injector
 */
function createInjector(
    $mocks = array(),
    $shares = array()
) {
    $standardImplementations = [
    ];

    $injector = new \Auryn\Injector();
    $injector->alias('Psr\Log\LoggerInterface', 'Monolog\Logger');

    $injector->delegate('ASM\SessionManager', 'createSessionManager');
    $injector->delegate('Predis\Client', 'createRedisClient');

    foreach ($standardImplementations as $interface => $implementation) {
        if (array_key_exists($interface, $mocks)) {
            if (is_object($mocks[$interface]) == true) {
                $injector->alias($interface, get_class($mocks[$interface]));
                $injector->share($mocks[$interface]);
            }
            else {
                $injector->alias($interface, $mocks[$interface]);
            }
            unset($mocks[$interface]);
        }
        else {
            $injector->alias($interface, $implementation);
        }
    }

    foreach ($mocks as $class => $implementation) {
        if (is_object($implementation) == true) {
            $injector->alias($class, get_class($implementation));
            $injector->share($implementation);
        }
        else {
            $injector->alias($class, $implementation);
        }
    }

    $standardShares = [
    ];

    foreach ($standardShares as $class => $share) {
        if (array_key_exists($class, $shares)) {
            $injector->share($shares[$class]);
            unset($shares[$class]);
        }
        else {
            $injector->share($share);
        }
    }

    foreach ($shares as $class => $share) {
        $injector->share($share);
    }


    $injector->share($injector); //Yolo ServiceLocator

    return $injector;
}

function checkClient(
    $redisClient,
    \PHPUnit\Framework\TestCase $test
) {
    try {
        /** @var $redisClient \Predis\Client */
        $result = $redisClient->ping();
        if ($result != "PONG") {
            throw new \Exception("Redis ping is broken");
        }
    } catch (\Exception $e) {
        //echo "exception :".$e->getMessage()."\n";
        $test->markTestSkipped("Redis unavailable");
    }
}


function createRequestFromSessionResponseHeaders(\Asm\Session $session)
{
    $headers = $session->getHeaders(\Asm\SessionManager::CACHE_PRIVATE);
    $cookies = [];

    foreach ($headers as $headerLine) {
        list($key, $value) = $headerLine;
        $extractedCookie = extractCookie($key . ": " . $value);
        if ($extractedCookie !== null) {
            list ($cookieName, $value) = $extractedCookie;
            $cookies[$cookieName] = $value;
        }
    }

    $request = new \Laminas\Diactoros\ServerRequest();
    $request = $request->withCookieParams($cookies);

    return $request;
}
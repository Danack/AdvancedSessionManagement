<?php

require_once('../../vendor/autoload.php');


use Intahwebz\ASM\Session;
use Intahwebz\ASM\SessionConfig;

use Predis\Client as RedisClient;



$sessionConfig = new SessionConfig(
    'SessionTest', 
    1000, 
    10
);

$redisParameters = array(
    "scheme" => "tcp",
    "host" => '127.0.0.1',
    "port" => 6379
);

$redisOptions = array(
    'profile' => '2.6'
);



$redisClient = new RedisClient($redisParameters, $redisOptions);

$session = new Session($sessionConfig, Session::READ_ONLY, $_COOKIE, $redisClient);


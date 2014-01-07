<?php

require_once('../../vendor/autoload.php');

use Intahwebz\ASM\Session;
use Intahwebz\ASM\SessionConfig;

$sessionConfig = new SessionConfig(
    'SessionTest',
    array( 
        "scheme" => "tcp",
        "host" => '127.0.0.1',
        "port" => 6379
    ), 
    1000, 
    1000
);

$session = new Session($sessionConfig, Session::READ_ONLY, $_COOKIE);


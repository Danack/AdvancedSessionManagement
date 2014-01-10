<?php

use Predis\Client as RedisClient;


$redis = new RedisClient(array("host" => '127.0.0.1'));

$redis->multi();


$redis->exec();
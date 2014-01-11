<?php

require_once('../../vendor/autoload.php');

//use Predis\Client as RedisClient;
//use Predis\Client as RedisClient;


$redis = new \Predis\Client(array("host" => '127.0.0.1'));

$info = $redis->info();

foreach ($info as $key => $value){

    
    
    if (is_array($value)) {

        echo "<h3>$key </h3>" ;
        
        foreach ($value as $key2 => $value2) {
            echo "$key2 : $value2 <br/>";
        }
    }
    else {
        echo "$key - $value<br/>";
    }
}
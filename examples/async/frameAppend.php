<?php

require_once('../bootstrap.php');

try {
    $sessionData = $session->start();

    header($session->getHeader());

    for ($x=0 ; $x<10 ; $x++) {
        $listTimes = $session->asyncGet(ASYNC_INC_KEY);

        var_dump($listTimes);
        
        $microtime = microtime();
        $time = substr($microtime, 11).substr($microtime, 1, 9);
        
        $session->asyncAppend(ASYNC_INC_KEY, $time);
        usleep(rand(100, 10000));
    }

    echo "fin. <br/>";
}
catch(\Exception $e) {
    echo "Exception caught: ".$e->getMessage();
}

$session->close();

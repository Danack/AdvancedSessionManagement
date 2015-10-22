<?php

require_once('../bootstrap.php');


try {

    $sessionData = $session->openSession();

    header($session->getHeader());


    $count = 0;

    for ($x=0 ; $x<10 ; $x++) {
        $session->asyncIncrement(ASYNC_INC_KEY);
        $count = $session->asyncGet(ASYNC_INC_KEY);
        echo "Count is: ".$count."<br/>";
        usleep(100000);
    }
    
    echo "fin. <br/>";
}
catch(\Exception $e) {
    echo "Exception caught: ".$e->getMessage();
}

$session->close();

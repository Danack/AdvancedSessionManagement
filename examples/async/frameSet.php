<?php

require_once('../bootstrap.php');

try {
    $sessionData = $session->openSession();

    header($session->getHeader());
    
    $season = 'Unknown';
        
    if (isset($_REQUEST['season'])) {
        $season = $_REQUEST['season'];
    }

    for ($x=0 ; $x<10 ; $x++) {
        $newSeason = $session->asyncGet(ASYNC_INC_KEY);

        echo "Hunting season is: ".$newSeason."<br/>";

        $session->asyncSet(ASYNC_INC_KEY, $season);
        usleep(10000 + rand(0, 10000));
    }
    
    echo "fin. <br/>";
}
catch(\Exception $e) {
    echo "Exception caught: ".$e->getMessage();
}

$session->close();

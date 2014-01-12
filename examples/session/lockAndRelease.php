<?php

require_once('bootstrap.php');


try {
    $sessionData = $session->openSession();

    header($session->getHeader());

    $session->acquireLock();

    $session->releaseLock();
    $session->close();
    
    echo "fin.";
}
catch(\Exception $e) {
    echo "Exception caught: ".$e->getMessage();
}

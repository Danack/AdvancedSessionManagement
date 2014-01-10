<?php

require_once('bootstrap.php');


try {
    $sessionData = $session->openSession();

    foreach ($session->getHeaders() as $header) {
        header($header);
    }

    $session->acquireLock();

    $session->releaseLock();
    $session->close();
    
    echo "fin.";
}
catch(\Exception $e) {
    echo "Exception caught: ".$e->getMessage();
}

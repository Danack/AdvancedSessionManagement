<?php

require_once('bootstrap.php');

echo "<a href='/'>Home</a> <br/>";

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

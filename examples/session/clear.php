<?php

require_once('bootstrap.php');


try {
    $sessionData = $session->openSession();
    
    echo "Initial session data is :<br/>";
    var_dump($sessionData);
    echo "<br/>";
    
    foreach ($session->getHeaders() as $header) {
        header($header);
    }
    $session->clear();
    $session->close();
    
    echo "Session should be cleared";
}
catch(\Exception $e) {
    echo "Exception caught: ".$e->getMessage();
}

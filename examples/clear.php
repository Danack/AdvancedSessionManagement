<?php

require_once('bootstrap.php');

echo "<a href='/'>Home</a> <br/>";

try {
    $sessionData = $session->openSession();

    echo "Initial session data is :<br/>";
    var_dump($sessionData);
    echo "<br/>";

    header($session->getHeader());

    $session->clear();
    $session->close();
    
    echo "Session should be cleared";
}
catch(\Exception $e) {
    echo "Exception caught: ".$e->getMessage();
}

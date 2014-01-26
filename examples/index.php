<html>

<body>

<?php

$examples = array(
    'async/append.html' => "Async append",
    'async/increment.html' => "Async increment",
    'async/set.html' => "Async set",
//    'session/frame1.php' => "Frame 1",
//    'session/frame2.php' => "Frame 2",
    'clear.php' => "Clear session",
    'lockAndRelease.php' => "Lock testing",
    'info.php' => "Redis info",
);


foreach ($examples as $url => $description) {
    echo "<a href='".$url."'>$description</a> <br/>";
}

?>
</body>

</html>
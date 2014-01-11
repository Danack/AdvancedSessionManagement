<html>

<body>

<?php

$examples = array(
    'session/index.html' => "First test",
    'session/frame1.php' => "Frame 1",
    'session/frame2.php' => "Frame 2",
    'session/clear.php' => "Clear session",
    'session/lockAndRelease.php' => "Lock testing",
    'session/info.php' => "Redis info",
);


foreach ($examples as $url => $description) {
    echo "<a href='".$url."'>$description</a> <br/>";
}

?>
</body>

</html>
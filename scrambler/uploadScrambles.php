<?php
    require_once '../newconnect.php';

    $weekNo = $_POST['week'];
    $yearNo = $_POST['year'];

    if (!isset($weekNo)) $weekNo = 13;
    if (!isset($yearNo)) $yearNo = 2018;

    for($i=1;$i <= 28; $i++){
        $post = "evId" . $i;
        $scramble = addslashes($_POST[$post]);
        $query = "INSERT INTO scrambles (scramble, eventId, weekId, yearId) VALUES"
        . " (\"$scramble\", $i, $weekNo, 2018)"; 
        $mysqli->query($query);
    }

    $scrambles = $_POST['MikeHughey'];
    print "<br><pre>";
    print $scrambles;
    print "</pre><br>";
?>	



<a href='../index.php'>Done!! </a>


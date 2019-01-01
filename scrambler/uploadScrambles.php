<?php
    require_once '../newconnect.php';

    $weekNo = $_POST['week'];
    $yearNo = $_POST['year'];

    if (!is_admin()) {
        echo "Invalid permissions!!";
        exit;
    }
    if (!isset($weekNo)) exit;
    if (!isset($yearNo)) exit;

    for($i = 1; $i <= 34; $i++){
        if ($i > 28 && $i < 33) {
            continue;
        }
        $post = "evId" . $i;
        $scramble = addslashes($_POST[$post]);
        $query = "INSERT INTO scrambles (scramble, eventId, weekId, yearId) VALUES"
        . " (\"$scramble\", $i, $weekNo, $yearNo)";
        $mysqli->query($query);
        //echo $query."<br>";
    }

    $scrambles = $_POST['MikeHughey'];
    print "<br><pre>";
    print $scrambles;
    print "</pre><br>";
?>	



<a href='../index.php'>Done!! </a>


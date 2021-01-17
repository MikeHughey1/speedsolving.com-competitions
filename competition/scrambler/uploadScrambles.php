<?php
    require_once 'head.php';

    $weekNo = $_POST['week'];
    $yearNo = $_POST['year'];

    if (!isset($weekNo)) exit;
    if (!isset($yearNo)) exit;
    
    echo "week = ".$weekNo.", year = ".$yearNo."<br>";

    for($i = 1; $i <= 39; $i++){
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
    
    require_once 'forumList.php';
?>	

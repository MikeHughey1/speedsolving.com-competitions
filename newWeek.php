<?php
    $formUserId=9999;
    // does not work 2018-02 Mats
    $wN = $weekNo - 1; // we want last week
    $query = $mysqli->query("SELECT * FROM weeklyResults WHERE weekId='$wN' AND yearId='$yearNo' ORDER BY userId, eventId ASC");
    // special if you want an old week/year
    //$query = $mysqli->query("SELECT * FROM weeklyResults "
    //   ."WHERE weekId=4 AND yearId=2018 ORDER BY userId, eventId ASC");
    while ($resultRow = $query->fetch_array()){
        $userId=$resultRow['userId'];
        $eventId=$resultRow['eventId'];
        if ($userId != $formUserId){
            $userName = $mysqli->query("SELECT username FROM userlist WHERE id='$userId'")->fetch_array();
            echo "<br />[B]".$userName[0]." (userId=".$userId.")[/B]<br />";
        }
        $formUserId = $userId;
        $eventName = $mysqli->query("SELECT eventName, weekly FROM events WHERE id='$eventId'")->fetch_array();
        print $eventName[0];
        $noTimes = $eventName[1];

        if ($eventId != 13) { // regular events
            $rezult = number_format(floatval($resultRow['result']), 2, '.', '');
            $result = prettyNumber($rezult);
            for ($i = 1; $i <= $noTimes; ++$i) {
                // Need to make handling of result and solve details for FMC vs. non FMC a subroutine!
                $solveDetails .= prettyNumber(number_format(floatval($resultRow['solve'.$i]), 2, '.', '')) . ", ";
            }
            $solveDetails = substr($solveDetails, 0, strlen($solveDetails) - 2);
        } elseif ($eventId == 13) { // MBLD
            $rezult = number_to_MBLD($resultRow['multiBLD']);
            $result = $rezult['0'] . " points";
            $timeMBLD = prettyNumber(round($rezult['1']));
            $solveDetails = $rezult['2'] . "/" . $rezult['3'] . " in " . $timeMBLD;
            $solveDetails = substr($solveDetails, 0, strlen($solveDetails) - 3);
        }			

        echo ": [I]($solveDetails)[/I] = [B]".$result."[/B]";
        echo "<br />";
        $solveDetails = "";
    }

    echo "----- New results output begins here -----<br>";
    $weeklyResults = new WeeklyResults($wN, $yearNo);
    $weeklyResults->print_bbcode_results();


<?php require_once 'newconnect.php'; ?>
<!DOCTYPE html>
<html>
<head>
<title>Weekly Competition Personal Records (speedsolving.com)</title>
<link rel='stylesheet' href='style.css' type='text/css' />
<link rel="stylesheet" href="cubing-icons.css">
<meta name='viewport' content='width=device-width, initial-scale=1'>
<meta charset="UTF-8">
</head>
<?php

    require_once 'statsHeader.php';
    require_once 'statFunctions.php';
    require_once 'readEvents.php';

    $personId = filter_input(INPUT_GET, 'showPerson', FILTER_VALIDATE_INT);
    $personData = get_person_info($personId);
    $fullname = $personData['displayName'];
    $username = $personData['username'];
    print <<<END
    <div id='canvas'>
        <div class='xLargeText'><br><br><a href='showPersonalRecords.php?showRecords=$personId'>$fullname</a><br></div><br>
        <table class='table-striped table-dynamic'>
            <thead>
                <tr>
                    <th class='l'>Event</th>
                    <th class='r'>#</th>
                    <th class='r'>Best</th>
                    <th class='r'>Average</th>
                    <th class='c'>Solves</th>
                    <th class='l'>Comment</th>
                </tr>
            </thead>
            <tbody>
END;

    $weekNo = filter_input(INPUT_GET, 'week', FILTER_VALIDATE_INT);
    if (!$weekNo) {
        $weekNo = gmdate("W", strtotime('-1 day'));
    }
    $yearNo = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);
    if (!$yearNo) {
        $yearNo = gmdate("o",strtotime('-1 day'));
    }
    $query = $mysqli->query("SELECT * FROM weeklyResults WHERE userId='$personId' AND weekId='$weekNo' AND yearId='$yearNo' ORDER BY eventId ASC");
    while($resultRow = $query->fetch_array()) {
        // all regular events...
        $rankResult = $resultRow['result'];
        $eventId = $resultRow['eventId'];
        $eventInfo = get_event_info($eventId);
        $eventName = $eventInfo['eventName'];
        $noTimes = $eventInfo['solveCount'];
        $best = PHP_INT_MAX;
        if($eventId!=13&&$eventId!=17){ 
            $rezult = number_format(floatval($resultRow['result']),2,'.','');
            $result = pretty_number($rezult);
            $avg = 0;
            $dnf = 0;
            for($i=1; $i <= $noTimes; $i++){
                $solveDetails .= pretty_number(number_format(floatval($resultRow['solve'.$i]),2,'.','')) . ", ";
                if ($resultRow['solve'.$i] > 0 && $resultRow['solve'.$i] < $best) {
                    $best = $resultRow['solve'.$i];
                }
                if ($resultRow['solve'.$i] == 8888 || $resultRow['solve'.$i] == 9999 || $resultRow['solve'.$i] == 'DNF' || $resultRow['solve'.$i] == 'DNS') {
                    ++$dnf;
                } else {
                    $avg += $resultRow['solve'.$i];
                }
            }
            $solveDetails = substr($solveDetails,0,strlen($solveDetails)-2);
        }
        //MBLD IS RETARDED! D:
        elseif($eventId==13) { 
            $rezult = number_to_MBLD($resultRow['multiBLD']);
            $rankResult = $resultRow['multiBLD'];
            $result = $rezult['0'] . " points";
            $timeMBLD = pretty_number(round($rezult['1']));
            $solveDetails = $rezult['2'] . "/" . $rezult['3'] . " in " . $timeMBLD;
            $solveDetails = substr($solveDetails,0,strlen($solveDetails)-3);
        }
        // FMC! :)
        elseif($eventId==17){
            $result = round($resultRow['result']);
            if ($result == 8888){$result = "DNF";}
            $solveDetails = stripslashes($resultRow['fmcSolution']);
        }
        if ($best == PHP_INT_MAX) {
            $best = 8888;
        }
        $best = pretty_number($best);
        
        // Fix results based on number of solves
        if ($solveCounts[$eventId] == 1) {
            $best = $result;
            $result = "";
        } elseif ($solveCounts[$eventId] == 3) {
            if ($dnf > 0) {
                $result = 'DNF';
            } else {
                $result = pretty_number($avg / $solveCounts[$eventId]);
            }
        }

        // Calculatue ranking
        if($noTimes>1){$place = 1;}
        else{$place = 1;}
        if($eventId!=13){
            $queryRanking = $mysqli->query("SELECT userId FROM weeklyResults WHERE eventId='$eventId' AND weekId='$weekNo' AND yearId='$yearNo' AND result<'$rankResult' AND userID>='0' AND userId!='$personId'");
        } else {
            $queryRanking = $mysqli->query("SELECT userId FROM weeklyResults WHERE eventId='$eventId' AND weekId='$weekNo' AND yearId='$yearNo' AND multiBLD<'$rankResult' AND userID>='0' AND userId!='$personId'");
        }
        while($placeArr = $queryRanking->fetch_array()){
            $place++;
        }

        $comment = stripslashes($resultRow['comment']);
        print "<tr>";
        print "<td class='l'>";
        add_icon($eventName, "");
        print " $eventName</td>";
        print <<<END
        <td class='r'>$place</td>
        <td class='r'>$best</td>
        <td class='r'><b>$result</b></td>
        <td>$solveDetails</td>
        <td class='l'>$comment</td>
        </tr>
END;
        
        $solveDetails = ""; //empty this
    }

    print "</tbody>";
    print "</table>";
    print "</div>";

<?php require_once 'newconnect.php'; ?>
<!DOCTYPE html>
<html>
<head>
<title>Weekly Competition Personal Records (speedsolving.com)</title>
<link rel='stylesheet' href='style.css' type='text/css' />
<link rel="stylesheet" href="cubing-icons.css">
<meta name='viewport' content='width=device-width, initial-scale=1'>
<meta charset="UTF-8">

<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-1539656-3"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'UA-1539656-3');
</script>
</head>
<?php

    require_once 'statsHeader.php';
    require_once 'statFunctions.php';

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
    while ($resultRow = $query->fetch_array()) {
        // all regular events...
        $rankResult = $resultRow['result'];
        $eventId = $resultRow['eventId'];
        $eventName = $events->name($eventId);
        $solveCount = get_solve_count($eventId, $yearNo);
        $best = PHP_INT_MAX;
        if ($eventId != 13) { 
            $rezult = number_format(floatval($resultRow['result']),2,'.','');
            $result = pretty_number($rezult);
            $avg = 0;
            $dnf = 0;
            for($i=1; $i <= $solveCount; $i++){
                if ($eventId == 17) {
                    $solveValue = round($resultRow['solve'.$i]);
                } else {
                    $solveValue = pretty_number(number_format(floatval($resultRow['solve'.$i]),2,'.',''));
                }
                $solveDetails .= $solveValue.", ";
                if ($resultRow['solve'.$i] > 0 && $resultRow['solve'.$i] < $best) {
                    $best = $solveValue;
                }
                if ($resultRow['solve'.$i] == 8888 || $resultRow['solve'.$i] == 9999 || $resultRow['solve'.$i] == 'DNF' || $resultRow['solve'.$i] == 'DNS') {
                    ++$dnf;
                } else {
                    $avg += $resultRow['solve'.$i];
                }
            }
            $solveDetails = substr($solveDetails,0,strlen($solveDetails)-2);
        } else { 
            $rezult = number_to_MBLD($resultRow['multiBLD']);
            $rankResult = $resultRow['multiBLD'];
            $result = $rezult['0'] . " points";
            $timeMBLD = pretty_number(round($rezult['1']));
            $solveDetails = $rezult['2'] . "/" . $rezult['3'] . " in " . $timeMBLD;
            $solveDetails = substr($solveDetails,0,strlen($solveDetails)-3);
        }
        if ($best == PHP_INT_MAX) {
            $best = 8888;
        }
        
        // Fix results based on number of solves
        if ($solveCount === 1) {
            $result = "";
        } elseif ($solveCount === 3) {
            if ($dnf > 0) {
                $result = 'DNF';
            } else {
                $result = pretty_number($avg / $solveCount);
            }
        }

        // Calculatue ranking
        $place = 1;
        if ($eventId != 13) {
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


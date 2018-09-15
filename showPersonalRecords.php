<?php require_once 'newconnect.php'; ?>
<!DOCTYPE html>
<html lang='en-us'>
<head>
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

    function hideShow(selector)
    {
        selector.checked = true;
        var displays = document.getElementsByClassName('event-display');
        for (i = 0; i < displays.length; ++i) {
            displays.item(i).style.display = "none";
        }
        var division = document.getElementById(selector.value);
        if (division) {
            division.style.display = "table-row-group";
        }
        window.location.hash = "resultsHead";
    }
    
    function selectEvent(value)
    {
        radio = "radio-" + value;
        hideShow(document.getElementById(radio));
    }
</script>
<?php
    require_once 'statsHeader.php';
    require_once 'statFunctions.php';
    require_once 'readEvents.php';
    
    $userId = filter_input(INPUT_GET, 'showRecords', FILTER_VALIDATE_INT);
    if ($userId == FALSE) {
        $userId = 0;
    }
    $personData = get_person_info($userId);
    $fullname = $personData['displayName'];
    $username = $personData['username'];

    print "<title>$fullname | Weekly Competition Personal Records (speedsolving.com)</title>";
    print "</head>";

    print "<div id='canvas'>";
    print "<div id='user'><br>$fullname<br></div><br>";
    
    $competitionCount = get_competitions($userId);
    $completedSolveCount = get_completed_solves($userId);
    $kinchScores = get_overall_user_kinch_scores($userId);
    $kinchOverallScore = round_score($kinchScores[0]);
    
    print <<<END
    <table class='table-striped table-dynamic'>
        <thead>
            <tr>
                <th>Speedsolving.com ID</th>
                <th>Kinch Score</th>
                <th>Competitions</th>
                <th>Completed Solves</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>$username</td>
                <td>$kinchOverallScore</td>
                <td>$competitionCount</td>
                <td>$completedSolveCount</td>
            </tr>
        </tbody>
    </table>
END;
    
    // Compute the entire list of event results.
    $query = $mysqli->query("SELECT * FROM weeklyResults WHERE userId='$userId' ORDER BY eventId, yearId, weekId");
    $eventId = 0;
    $gold = 0;
    $silver = 0;
    $bronze = 0;
    while($resultRow = $query->fetch_assoc()) {
        if ($eventId != $resultRow['eventId']) {
            $comp = 0;
            if ($eventId > 0) {
                // Grab the best single and average pointer from the previous event's results and store as the overall event's best result
                $overallPBSingle[$eventId] = $pbSingleComp;
                $overallPBAverage[$eventId] = $pbAverageComp;
            }
            $pbSingleComp = 0;
            $pbAverageComp = 0;
            $eventId = $resultRow['eventId'];
        }
        // Read data into arrays for future use in calculations and generating html
        $year = $resultRow['yearId'];
        $week = $resultRow['weekId'];
        $yearWeeks[$eventId][$comp] = get_competition_name($week, $year);
        $comments[$eventId][$comp] = $resultRow['comment'];
        $averageTipString = htmlentities($singleTipString, ENT_QUOTES | ENT_IGNORE, "UTF-8");
        $results[$eventId][$comp] = $resultRow['result'];
        if ($eventId == '17') {
            $fmcSolution[$comp] = $resultRow['fmcSolution'];
        } elseif ($eventId == '13') {
            $multiBLD[$comp] = $resultRow['multiBLD'];
            $results[$eventId][$comp] = $resultRow['multiBLD'];
        }
        $avg = 0;
        $dnf = false;
        $dnf2 = false;
        $singles[$eventId][$comp] = PHP_INT_MAX;
        for ($i = 1; $i <= $solveCounts[$eventId]; $i++) {
            $solveVal = $resultRow['solve'.$i];
            if ($eventId == 13 || $eventId == 17 || $eventId == 32) {
                $solveVal = $results[$eventId][$comp];
            }
            $solves[$eventId][$comp][$i] = $solveVal;
            if ($solveVal < $singles[$eventId][$pbSingleComp] && is_valid_score($solveVal)) {
                $pb[$eventId][$comp] = $i;
                $pbSingleComp = $comp;
            }
            if ($solveVal < $singles[$eventId][$comp] && is_valid_score($solveVal)) {
                $singles[$eventId][$comp] = $solveVal;
            }
            if (!is_valid_score($solveVal)) {
                if ($dnf) {
                    // More than one DNF or DNS in this average; DNF even for events with 5 solves
                    $dnf2 = true;
                }
                $dnf = true;
            } else {
                $avg += $solveVal;
            }
        }
        if ($singles[$eventId][$comp] == PHP_INT_MAX) {
            $singles[$eventId][$comp] = 8888;
        }
        if ($solveCounts[$eventId] == 5) {
            if ($dnf2) {
                $avg = 8888;
            } else {
                // Here we can optionally add code to correct the result if desired; a calculated average here should equal the result
                $avg = $results[$eventId][$comp];
            }
        } elseif ($solveCounts[$eventId] == 3) {
            if ($dnf) {
                $avg = 8888; // DNF
            } else {
                $avg /= 3;
            }
        }
        if ($avg !== 8888 && ($averages[$eventId][$pbAverageComp] == 0 || $avg < $averages[$eventId][$pbAverageComp])) {
            $pbA[$eventId][$comp] = true;
            $pbAverageComp = $comp;
        }
        $averages[$eventId][$comp] = $avg;
        
        $rankings[$eventId][$comp] = $resultRow['rank'];
        if ($week == get_current_week() && $year == get_current_year()) {
            $rankings[$eventId][$comp] = calculate_place_ranking_new($eventId, round_score($averages[$eventId][$comp]) * 100, $singles[$eventId][$comp] * 100, $userId, $week, $year);
        }
        if ($rankings[$eventId][$comp] == 1 && is_valid_score($results[$eventId][$comp])) {
            ++$gold;
        } elseif ($rankings[$eventId][$comp] == 2 && is_valid_score($results[$eventId][$comp])) {
            ++$silver;
        } elseif ($rankings[$eventId][$comp] == 3 && is_valid_score($results[$eventId][$comp])) {
            ++$bronze;
        }
        ++$comp;
    }
    // Store overall results for last event in list (done in loop above fof other events
    $overallPBSingle[$eventId] = $pbSingleComp;
    $overallPBAverage[$eventId] = $pbAverageComp;
    
    // Personal Records display
    print <<<END
    <div class='xLargeText'><br>Current Personal Records<br></div><br>
    <table class='table-striped table-dynamic'>
        <thead>
            <tr>
                <th class='l'>Event</th>
                <th class='r'>SR</th>
                <th class='r'>Single</th>
                <th class='r'>Average</th>
                <th class='r'>SR</th>
                <th class='r'>Kinch Score</th>
            </tr>
        </thead>
END;

    print "<tbody>";
    foreach ($eventNames as $eventId => $eventName) {
        if (count($yearWeeks[$eventId]) == 0) {
            continue;
        } elseif (is_dnf($singles[$eventId][$overallPBSingle[$eventId]]) && is_dnf($averages[$eventId][$overallPBAverage[$eventId]])) {
            continue;
        }
        $pbSingleComp = $overallPBSingle[$eventId];
        $singleRankString = calculate_single_ranking($eventId, $solveCounts[$eventId], $singles[$eventId][$pbSingleComp], $userId);
        $solveDetails = get_solve_details($eventId, $solveCounts[$eventId], $solves[$eventId][$pbSingleComp], $results[$eventId][$pbSingleComp], $multiBLD[$pbSingleComp], $fmcSolution[$pbSingleComp], false);
        $singleTipString = "Week ".$yearWeeks[$eventId][$pbSingleComp]."<br>$solveDetails<br>".$comments[$eventId][$pbSingleComp];
        $singleOutput = get_single_output($eventId, $singles[$eventId][$overallPBSingle[$eventId]]);
        $pbAverageComp = $overallPBAverage[$eventId];
        $solveDetails = get_solve_details($eventId, $solveCounts[$eventId], $solves[$eventId][$pbAverageComp], $results[$eventId][$pbAverageComp], $multiBLD[$eventId][$pbAverageComp], $fmcSolution[$eventId][$pbAverageComp], false);
        $place = 1;
        $weekId = substr($yearWeeks[$eventId][$pbAverageComp], 5);
        $yearId = substr($yearWeeks[$eventId][$pbAverageComp], 0, 4);
        $queryRanking = $mysqli->query("SELECT userId FROM weeklyResults WHERE eventId='$eventId' AND weekId='$weekId' AND yearId='$yearId' AND result<'".$averages[$eventId][$pbAverageComp]."' AND userID>='0' AND userId!='$userId'");
        while($placeArr = $queryRanking->fetch_array()){
            $place++;
        }
        $averageTipString = "Week ".$yearWeeks[$eventId][$pbAverageComp]."<br>".get_place_string($place)." Place<br>$solveDetails<br>".$comments[$eventId][$pbAverageComp];
        $averageRankString = calculate_average_ranking($eventId, $solveCounts[$eventId], $averages[$eventId][$pbAverageComp], $userId);

        print "<tr>";
        print <<<END
        <td class='l'><a class='myLink' href='#resultsHead' onclick='selectEvent("$eventId")'>
END;
        add_icon($eventName, "");
        echo " $eventName</a></td>";
        echo "<td class='r'>$singleRankString</td>";
        echo "<td class='r tooltip'><b><a href='showEvents.php?eventId=$eventId&single=Single' class='myLink'>".$singleOutput."</a></b><span class='tooltiptext'>$singleTipString</span></td>";
        if ($solveCounts[$eventId] == 1 || is_dnf($averages[$eventId][$pbAverageComp])) {
            echo "<td></td>";
            echo "<td></td>";
        } else {
            echo "<td class='r tooltip'><b><a href='showEvents.php?eventId=$eventId&average=Average' class='myLink'>".pretty_number($averages[$eventId][$pbAverageComp])."</a></b><span class='tooltiptext'>$averageTipString</span></td>";
            echo "<td class='r'>$averageRankString</td>";
        }
        echo "<td class='r'>".round_score($kinchScores[$eventId])."</td>";
        echo "</tr>";
    }
    print "</tbody>";

    print "</table>";
    
    // Medal Collection display
    print <<<END
    <div class='half-width'><div class='xLargeText'><br>Medal Collection<br></div><br>
    <table class='table-striped full-width'>
        <thead>
            <tr>
                <th>Gold</th>
                <th>Silver</th>
                <th>Bronze</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>$gold</td>
                <td>$silver</td>
                <td>$bronze</td>
            </tr>
        </tbody>
    </table>
    </div>
END;
    
    // Personal Results display
    print <<<END
    <div class='xLargeText' id='resultsHead'><br>Results<br></div><br>

END;
    print "<ul id='eventSelector'>";
    $first = true;
    foreach ($eventNames as $eventId => $eventName) {
        if (count($yearWeeks[$eventId]) == 0) {
            continue;
        }
        print "<li class='event-item'>";
        if ($first) {
            print "<input id='radio-".$eventId."' name='event' value='event".$eventId."' type='radio' class='myRadio' checked='checked' onchange='hideShow(this)'>";
            $first = false;
        } else {
            print "<input id='radio-".$eventId."' name='event' value='event".$eventId."' type='radio' class='myRadio' onchange='hideShow(this)'>";
        }
        print "<label class='tooltip' for='radio-".$eventId."'>&nbsp;";
        add_icon($eventName, "cubing-icon-2x");
        print "<span class='eventTooltiptext'>$eventName</span></label>";
        print "</li>";
    }
    print "</ul>";
    
    print <<<END
    <table class='table-striped table-dynamic'>
        <thead>
            <tr>
                <th class='l'>Competition</th>
                <th class='r'>Place</th>
                <th class='r'>Single</th>
                <th class='r'>Average</th>
                <th class='c' colspan='5'>Solves</th>
            </tr>
        </thead>
END;

    foreach ($eventNames as $eventId => $eventName) {
        if ($eventId == 1) {
            print "<tbody id='event$eventId' class='event-display'>";
        } else {
            print "<tbody id='event$eventId' class='event-display invisible-display'>";
        }
        if (count($yearWeeks[$eventId]) == 0) {
            continue;
        }
        print "<tr><td class='l'>";
        add_icon($eventName, "");
        print " $eventName</td>";
        print "<td></td>";
        print "<td></td>";
        print "<td></td>";
        print "<td></td>";
        print "<td></td>";
        print "<td></td>";
        print "<td></td>";
        print "<td></td>";
        print "</tr>";
        
        for ($comp = count($yearWeeks[$eventId]) - 1; $comp >= 0; --$comp) {
            $week = intval(substr($yearWeeks[$eventId][$comp], 5));
            $year = substr($yearWeeks[$eventId][$comp], 0, 4);
            $comment = $comments[$eventId][$comp];
            $selectEvent = $eventId + 2;
            print "<tr>";
            print "<td class='l'><a href='showWeeks.php?week=$week&year=$year&selectEvent=$selectEvent'>".$yearWeeks[$eventId][$comp]."</a></td>";
            print "<td class='r'>".$rankings[$eventId][$comp]."</td>";
            if ($solveCounts[$eventId] == 1 && strlen($comment) > 0) {
                print "<td class='r tooltip";
            } else {
                print "<td class='r";
            }
            if ($pb[$eventId][$comp]) {
                print " pb'>";
            } else {
                print"'>";
            }
            if ($solveCounts[$eventId] == 1) {
                print "<b>".get_single_output($eventId, $singles[$eventId][$comp])."</b><span class='tooltiptext'>".$comment."</span></td>";
            } else {
                print "<b>".get_single_output($eventId, $singles[$eventId][$comp])."</b></td>";
            }
            if ($solveCounts[$eventId] == 1) {
                print "<td></td>";
            } else {
                if (strlen($comment) > 0) {
                    print "<td class='r tooltip";
                } else {
                    print "<td class='r";
                }
                if ($pbA[$eventId][$comp]) {
                    print " pb'>";
                } else {
                    print"'>";
                }
                $error = (pretty_number(get_average($solveCounts[$eventId], $solves[$eventId][$comp])) == pretty_number($averages[$eventId][$comp])) ? "" : " ERROR (".pretty_number(get_average($solveCounts[$eventId], $solves[$eventId][$comp])).")";
                if (strlen($comment) > 0) {
                    print "<b>".pretty_number($averages[$eventId][$comp])."$error</b><span class='tooltiptext'>".$comment."</span></td>";
                } else {
                    print "<b>".pretty_number($averages[$eventId][$comp])."$error</b></td>";
                }
            }
            $min = 0;
            $max = 0;
            if ($solveCounts[$eventId] == 5) {
                $min = get_min($solves[$eventId][$comp]);
                $max = get_max($solves[$eventId][$comp]);
            }
            $minSet = false;
            $maxSet = false;
            for ($i = 1; $i <= $solveCounts[$eventId]; $i++) {
                if ($solveCounts[$eventId] == 1) {
                    print "<td></td>";
                    print "<td></td>";
                }
                if ($solveCounts[$eventId] == 5 && !$minSet && $solves[$eventId][$comp][$i] == $min) {
                    print "<td>(".get_single_output($eventId, $solves[$eventId][$comp][$i]).")</td>";
                    $minSet = true;
                }
                elseif ($solveCounts[$eventId] == 5 && !$maxSet && $solves[$eventId][$comp][$i] == $max) {
                    print "<td>(".get_single_output($eventId, $solves[$eventId][$comp][$i]).")</td>";
                    $maxSet = true;
                } elseif ($eventId == 13) {
                    print "<td>".get_solve_details($eventId, 0, 0, 0, $solves[$eventId][$comp][$i], 0, false)."</td>";
                } else {
                    print "<td>".get_single_output($eventId, $solves[$eventId][$comp][$i])."</td>";
                }
                if ($solveCounts[$eventId] == 1) {
                    print "<td></td>";
                    print "<td></td>";
                } elseif ($solveCounts[$eventId] == 3 && $i < 3) {
                    print "<td></td>";
                }
            }
            print "</tr>";
        }
        print "</tbody>";
    }
    print "</table></div>";
    
?>
</body>
</html>
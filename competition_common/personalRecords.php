<?php
    $title = "Weekly Competition Personal Records (Speedsolving.com)";
    
    $userId = filter_input(INPUT_GET, 'showRecords', FILTER_VALIDATE_INT);
    if ($userId == FALSE) {
        $userId = 0;
    }
    if ($userId == 0) {
        $my = filter_input(INPUT_GET, 'my', FILTER_VALIDATE_INT);
        if (isset($my) && isset($currentUserId)) {
            $userId = $currentUserId;
        }
    }
    
    $personData = get_person_info($userId);
    $fullname = $personData['displayName'];
    $username = $personData['username'];

    print "<div id='canvas'>";
    print "<div id='user'><br>$fullname<br></div><br>";
    
    $competitionCount = get_competitions($userId);
    $completedSolveCount = get_completed_solves($userId);
    $kinchScores = get_overall_user_kinch_scores($userId, false);
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
    $pbSingleComp = 0;
    $pbAverageComp = 0;
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
        $results[$eventId][$comp] = $resultRow['result'];
        if (!isset($multiBLD[$comp])) {
            $multiBLD[$comp] = 0;
        }
        if ($eventId == '13') {
            $multiBLD[$comp] = $resultRow['multiBLD'];
            $results[$eventId][$comp] = $resultRow['multiBLD'];
        }
        $avg = 0;
        $dnf = false;
        $dnf2 = false;
        $singles[$eventId][$comp] = PHP_INT_MAX;
        $best = $resultRow['best'];
        $solveCount = get_solve_count($eventId, $year);
        for ($i = 1; $i <= $solveCount; $i++) {
            $solveVal = $resultRow['solve'.$i];
            if ($eventId == 13 || $eventId == 32) {
                $solveVal = $results[$eventId][$comp];
            }
            $solves[$eventId][$comp][$i] = $solveVal;
            $previousBestResult = $singles[$eventId][$pbSingleComp];
            if ($eventId != 13 && !is_movecount_scored($eventId)) {
                $previousBestResult *= 100;
            }
            if ($solveVal < $singles[$eventId][$pbSingleComp] && $best < $previousBestResult && is_valid_score($best)) {
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
        if ($solveCount == 5) {
            if ($dnf2) {
                $avg = 8888;
            } else {
                // Here we can optionally add code to correct the result if desired; a calculated average here should equal the result
                $avg = $results[$eventId][$comp];
            }
        } elseif ($solveCount == 3) {
            if ($dnf) {
                $avg = 8888; // DNF
            } else {
                $avg /= 3;
            }
        } elseif ($solveCount == 1) {
            $avg = 0;
        }
        if ($avg != 0 && $avg !== 8888 && (!isset($averages[$eventId][$pbAverageComp]) || $averages[$eventId][$pbAverageComp] == 0 || $avg < $averages[$eventId][$pbAverageComp])) {
            $pbA[$eventId][$comp] = true;
            $pbAverageComp = $comp;
        }
        $averages[$eventId][$comp] = $avg;
        
        $rankings[$eventId][$comp] = $resultRow['rank'];
        if ($week == get_current_week() && $year == get_current_year()) {
            $rankings[$eventId][$comp] = calculate_place_ranking($eventId, $resultRow['average'], $best, $userId, $week, $year);
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
    // Store overall results for last event in list (done in loop above for other events
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
    foreach ($events as $eventId => $eventName) {
        if (!isset($yearWeeks[$eventId])) {
            continue;
        } elseif (is_dnf($singles[$eventId][$overallPBSingle[$eventId]]) && is_dnf($averages[$eventId][$overallPBAverage[$eventId]])) {
            continue;
        }
        $pbSingleComp = $overallPBSingle[$eventId];
        $weekId = substr($yearWeeks[$eventId][$pbSingleComp], 5);
        $yearId = substr($yearWeeks[$eventId][$pbSingleComp], 0, 4);
        $solveCountSingle = get_solve_count($eventId, substr($yearWeeks[$eventId][$pbSingleComp], 0, 4));
        $singleRankString = calculate_single_ranking($eventId, $singles[$eventId][$pbSingleComp]);
        $solveDetails = get_solve_details($eventId, $solveCountSingle, $solves[$eventId][$pbSingleComp], $results[$eventId][$pbSingleComp], $multiBLD[$pbSingleComp], false);
        if (!is_average_event($eventId, $yearId) && ($yearId != get_current_year() || $weekId != get_current_week())) {
            $place = $mysqli->query("SELECT rank FROM weeklyResults WHERE eventId='$eventId' AND weekId='$weekId' AND yearId='$yearId' AND userId='$userId'")->fetch_array()['rank'];
            $singleTipString = "Week ".$yearWeeks[$eventId][$pbSingleComp]."<br>".get_place_string($place)." Place<br>$solveDetails<br>".$comments[$eventId][$pbSingleComp];
        } else {
            $singleTipString = "Week ".$yearWeeks[$eventId][$pbSingleComp]."<br>$solveDetails<br>".$comments[$eventId][$pbSingleComp];
        }
        $singleOutput = get_single_output($eventId, $singles[$eventId][$overallPBSingle[$eventId]]);
        
        $pbAverageComp = $overallPBAverage[$eventId];
        $weekId = substr($yearWeeks[$eventId][$pbAverageComp], 5);
        $yearId = substr($yearWeeks[$eventId][$pbAverageComp], 0, 4);
        $solveCount = get_solve_count($eventId, $yearId);
        $solveDetails = get_solve_details($eventId, $solveCount, $solves[$eventId][$pbAverageComp], $results[$eventId][$pbAverageComp], $multiBLD[$pbAverageComp], false);
        if (is_average_event($eventId, $yearId) && ($yearId != get_current_year() || $weekId != get_current_week())) {
            $place = $mysqli->query("SELECT rank FROM weeklyResults WHERE eventId='$eventId' AND weekId='$weekId' AND yearId='$yearId' AND userId='$userId'")->fetch_array()['rank'];
            $averageTipString = "Week ".$yearWeeks[$eventId][$pbAverageComp]."<br>".get_place_string($place)." Place<br>$solveDetails<br>".$comments[$eventId][$pbAverageComp];
        } else {
            $averageTipString = "Week ".$yearWeeks[$eventId][$pbAverageComp]."<br>$solveDetails<br>".$comments[$eventId][$pbAverageComp];
        }
        $averageRankString = calculate_average_ranking($eventId, $solveCount, $averages[$eventId][$pbAverageComp]);

        print "<tr>";
        print <<<END
        <td class='l'><a class='myLink' href='#resultsHead' onclick='selectEvent("$eventId")'>
END;
        add_icon($eventName, "");
        echo " $eventName</a></td>";
        echo "<td class='r'>$singleRankString</td>";
        echo "<td class='r tooltip'><b><a href='showEvents.php?eventId=$eventId&amp;single=Single' class='myLink'>".$singleOutput."</a></b><span class='tooltiptext'>$singleTipString</span></td>";
        if ($solveCount == 1 || is_dnf($averages[$eventId][$pbAverageComp])) {
            echo "<td></td>";
            echo "<td></td>";
        } else {
            echo "<td class='r tooltip'><b><a href='showEvents.php?eventId=$eventId&amp;average=Average' class='myLink'>".pretty_number($averages[$eventId][$pbAverageComp])."</a></b><span class='tooltiptext'>$averageTipString</span></td>";
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
    foreach ($events as $eventId => $eventName) {
        if (!isset($yearWeeks[$eventId])) {
            continue;
        }
        print "<li class='event-item'>";
        if ($first) {
            print "<input id='radio-".$eventId."' name='event' value='event".$eventId."' type='radio' class='myRadio' checked='checked' onchange='hideShow(this)'>";
            $first = false;
        } else {
            print "<input id='radio-".$eventId."' name='event' value='event".$eventId."' type='radio' class='myRadio' onchange='hideShow(this)'>";
        }
        print "<label class='tooltip' for='radio-".$eventId."'>";
        add_icon($eventName, "cubing-icon-2x");
        print "<span class='eventTooltiptext'>$eventName</span></label>";
        print "</li>";
        if ($sessionType == "old") {
            // Add space - needed only when outside Xenforo
            print "&nbsp;";
        }
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

    foreach ($events as $eventId => $eventName) {
        if ($eventId == 1) {
            print "<tbody id='event$eventId' class='event-display'>";
        } else {
            print "<tbody id='event$eventId' class='event-display invisible-display'>";
        }
        if (!isset($yearWeeks[$eventId])) {
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
            $selectEvent = $eventId;
            $solveCount = get_solve_count($eventId, $year);
            print "<tr>";
            print "<td class='l'><a href='showWeeks.php?week=$week&amp;year=$year&amp;selectEvent=$selectEvent'>".$yearWeeks[$eventId][$comp]."</a></td>";
            print "<td class='r'>".$rankings[$eventId][$comp]."</td>";
            if ($solveCount == 1 && strlen($comment) > 0) {
                print "<td class='r tooltip";
            } else {
                print "<td class='r";
            }
            if (isset($pb[$eventId][$comp])) {
                print " pb'>";
            } else {
                print"'>";
            }
            if ($solveCount == 1) {
                print "<b>".get_single_output($eventId, $singles[$eventId][$comp])."</b><span class='tooltiptext'>".$comment."</span></td>";
            } else {
                print "<b>".get_single_output($eventId, $singles[$eventId][$comp])."</b></td>";
            }
            if ($solveCount == 1) {
                print "<td></td>";
            } else {
                if (strlen($comment) > 0) {
                    print "<td class='r tooltip";
                } else {
                    print "<td class='r";
                }
                if (isset($pbA[$eventId][$comp])) {
                    print " pb'>";
                } else {
                    print"'>";
                }
                $error = (pretty_number(get_average($solveCount, $solves[$eventId][$comp])) == pretty_number($averages[$eventId][$comp])) ? "" : " ERROR (".pretty_number(get_average($solveCount, $solves[$eventId][$comp])).")";
                if (strlen($comment) > 0) {
                    print "<b>".pretty_number($averages[$eventId][$comp])."$error</b><span class='tooltiptext'>".$comment."</span></td>";
                } else {
                    print "<b>".pretty_number($averages[$eventId][$comp])."$error</b></td>";
                }
            }
            $min = 0;
            $max = 0;
            if ($solveCount == 5) {
                $min = get_min($solves[$eventId][$comp]);
                $max = get_max($solves[$eventId][$comp]);
            }
            $minSet = false;
            $maxSet = false;
            for ($i = 1; $i <= $solveCount; $i++) {
                if ($solveCount == 1) {
                    print "<td></td>";
                    print "<td></td>";
                }
                if ($solveCount == 5 && !$minSet && $solves[$eventId][$comp][$i] == $min) {
                    print "<td>(".get_single_output($eventId, $solves[$eventId][$comp][$i]).")</td>";
                    $minSet = true;
                }
                elseif ($solveCount == 5 && !$maxSet && $solves[$eventId][$comp][$i] == $max) {
                    print "<td>(".get_single_output($eventId, $solves[$eventId][$comp][$i]).")</td>";
                    $maxSet = true;
                } elseif ($eventId == 13) {
                    print "<td>".get_solve_details($eventId, 0, 0, 0, $solves[$eventId][$comp][$i], false)."</td>";
                } else {
                    print "<td>".get_single_output($eventId, $solves[$eventId][$comp][$i])."</td>";
                }
                if ($solveCount == 1) {
                    print "<td></td>";
                    print "<td></td>";
                } elseif ($solveCount == 3 && $i < 3) {
                    print "<td></td>";
                }
            }
            print "</tr>";
        }
        print "</tbody>";
    }
    print "</table></div>";
    
?>
<script>
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
</body>
</html>
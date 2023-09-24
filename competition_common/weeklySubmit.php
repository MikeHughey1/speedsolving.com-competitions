<form action="index.php" method="post" onsubmit="return validate(event);">
<!--- insert rules --->
<?php
    $userId = filter_input(INPUT_GET, 'user', FILTER_VALIDATE_INT);
    if (is_admin() && $userId) {
        // Keep the input; allow the user id to be changed for administrative updates
        $getWeek = filter_input(INPUT_GET, 'week', FILTER_VALIDATE_INT);
        if ($getWeek) {
            $weekNo = $getWeek;
        }
        $getYear = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);
        if ($getYear) {
            $yearNo = $getYear;
        }
    } else {
        $userId = $currentUserId;
    }
    print <<<EOD
    <input onchange='checkboxChange(this.id)' type='checkbox' id='hideScrambles' name='hideScrambles' />
    <label for='hideScrambles'>Hide Scrambles</label>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <input onchange='checkboxChange(this.id)' type='checkbox' id='collapseCompleted' name='collapseCompleted' />
    <label for='collapseCompleted'>Collapse Completed Events</label>
    <input type='hidden' value='false' name='defaultManualEntry' />
EOD;
    
    if (is_admin()) {
        $username = get_person_info($userId)['displayName'];
        print <<<EOD
        <br>
        Logged in as user:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <input type='text' id='user-id' name='user' value=$userId />
        <span>$username</span>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <label for='week'>Week:</label>
        <input type='text' id='week' name='week' value=$weekNo />
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <label for='year'>Year:</label>
        <input type='text' id='year' name='year' value=$yearNo />
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <button type='button' class='btn' id='changeUser' onclick='changeUserWeek()'>Change User/Week</button>
EOD;
    }

    if ($weekNo == get_current_week() && $yearNo == get_current_year() || is_admin()) {
        echo "<div id='weeklySubmitPage' hidden>";
        global $events;
        $weeklyResults = new WeeklyResults($weekNo, $yearNo);
        $eventIds = event_list();
        if (is_mike()) {
            //$eventIds = sorted_event_list_by_previous_week($weekNo, $yearNo);
        }
        foreach ($eventIds as $eventId) {
            $eventName = $events->name($eventId);
            if (!is_active_event($eventId, $weekNo, $yearNo)) {
                continue;
            }
            $solveCount = get_solve_count($eventId, $yearNo);

            // Figure out how done we are on this event
            $partial = $weeklyResults->is_partial($eventId, $userId);
            $complete = $weeklyResults->is_completed($eventId, $userId);
            if ($partial) {
                echo "<div class='submit-info submit-info-partial'>";
            } elseif ($complete) {
                echo "<div class='submit-info submit-info-complete'>";
            } else {
                echo "<div class='submit-info submit-info-not-started'>";
            }

            echo "<div class='submit-weekly'>";
            $results = $mysqli->query("SELECT eventId, result, comment, solve1, solve2, solve3, solve4, solve5, multiBLD FROM weeklyResults WHERE userId='$userId' AND eventId='$eventId' AND weekId='$weekNo' AND yearId='$yearNo'")->fetch_array();
            $scramble = $mysqli->query("SELECT scramble FROM scrambles WHERE eventId='$eventId' AND weekId='$weekNo' AND yearId='$yearNo'")->fetch_array();
            echo "<div class='event-header'><span class='result-info close-left'>";
            add_icon($eventName, "cubing-icon");
            echo "<span class='submit-weekly-header'> $eventName </span>";
            echo "(<a href='?side=timer&event=".$eventId."'>use timer</a>)</span>";
            if (has_rules($eventId)) {
                echo "<button type='button' class='buttonRules' data-event-name='".$eventName."' data-event-id='".$eventId."' id='rules".$eventId."' onclick='showRules(this)'>View Rules</button>";
            }
            if ($complete || $partial) {
                $numResults = $weeklyResults->get_participant_count($eventId);
                $myResult = $weeklyResults->get_user_result($eventId, $userId);
                $myPlace = $weeklyResults->get_user_place($eventId, $userId);
                $myScore = $weeklyResults->get_user_score($eventId, $userId);
                echo "<span class='result-info'><b>$myResult</b></span>";
                echo "<span class='result-info'>".get_place_string($myPlace)." place (".$numResults.")</span>";
                echo "<span class='result-info'><b>".$myScore." Points</b></span>";
            }
            echo "</div>";
            if ($partial) {
                echo "<div class='panelEventInfo panelPartial'>";
            } elseif ($complete) {
                echo "<div class='panelEventInfo panelComplete'>";
            } else {
                echo "<div class='panelEventInfo panelNotStarted'>";
            }
            echo "<div class='data-panel scramble-window'>";
            echo "<table class='scramble-striped'><thead><tr><th></th></tr></thead><tbody>";
            $scrambleArray = explode("$$", str_replace('<br />', '$$', str_replace('<br>', '$$', str_replace('&nbsp;', ' ', $scramble['scramble']))));
            foreach ($scrambleArray as $scrambleItem) {
                echo "<tr><th>".$scrambleItem."</th></tr>";
            }
            echo "</tbody></table></div>";

            /* MBLD GOES HERE */
            if ($eventId==13) {
                $queryResult = $results['multiBLD'];
                $solveresult = number_to_MBLD($queryResult); //1=time, 2=solved, 3=attempted
                if ($solveresult[3]<0) {
                    $solveresult[3]=0;
                }
                $solveresult[1] = prettyNumber($solveresult[1]);
                if ($queryResult == 999999999) {
                   $solveresult = array ( 1 => 'DNF', '-', '-');
                }
                echo "<div class='data-panel times-window'>";
                echo "Result: <br />";
                print "I solved <input class='mbldInput' type='text' name='weekly".$eventId."Result1' value='".$solveresult[2]."' /> out of ";
                print "<input class='mbldInput' type='text' name='weekly".$eventId."Result2' value='".$solveresult[3]."' /> in the time of ";
                print "<input class='mbldInputTime' type='text' name='weekly".$eventId."Result3' value='".$solveresult[1]."' /><br />";
                echo "</div>";
                echo "<div class='data-panel comment-window'>";
                $comment = str_ireplace("<br />","\n",stripslashes($results['comment']));
                echo "Comment: <br />  <textarea class='submit-weekly-comment' name='weeklyComment$eventId'>$comment</textarea>";
            }
		
            /* FMC GOES HERE */
            elseif (is_fewest_moves($eventId)) {
                echo "<div class='data-panel'>";
                echo "Please use official WCA notation: Rw for wide turns, xyz for rotations, etc.;<br>moves are automatically counted.<br><br>";
                echo "</div><div></div>";
                $results = $mysqli->query("SELECT comment FROM weeklyResults WHERE userId='$userId' AND eventId='$eventId' AND weekId='$weekNo' AND yearId='$yearNo'")->fetch_array();
                $comment = str_ireplace("<br />","\n",stripslashes($results['comment']));
                for ($solveId = 1; $solveId <= $solveCount; ++$solveId) {
                    echo "<div class='data-panel comment-window'>";
                    $fmcResults = $mysqli->query("SELECT solution, comment, time FROM weeklyFmcSolves WHERE yearId = '$yearNo' AND weekId = '$weekNo' AND userId = '$userId' AND eventId = '$eventId' AND solveId = '$solveId'")->fetch_array();
                    $solveResult = str_replace(array("’", "‘", "´", "\x91", "\x92"), "'", stripslashes($fmcResults['solution']));
                    $solveTime = pretty_number($fmcResults['time']);
                    if ($eventId == 36) {
                        echo "Time ".$solveId.":";
                        echo "<input class='submit-weekly-input' type='text' name='weekly".$eventId."Time$solveId' id='weekly".$eventId."Time$solveId' value='$solveTime' />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                    }
                    echo "Solution ".$solveId.":";
                    $solutionId = "weekly".$eventId."Solution".$solveId;
                    print "<textarea class='submit-weekly-comment' name='$solutionId' id='$solutionId'>".$solveResult."</textarea><br />";
                    $explanation = str_replace(array("’", "‘", "´", "\x91", "\x92"), "'", str_ireplace("<br />","\n",stripslashes($fmcResults['comment'])));
                    $explanationId = "weekly".$eventId."Explanation".$solveId;
                    echo "Explanation for Solution ".$solveId.":<br /><textarea class='submit-weekly-comment' name='$explanationId' id='$explanationId'>$explanation</textarea>";
                    echo "</div>";
                }
                echo "<div>";
                echo "Overall Comment: <br />  <textarea class='submit-weekly-comment' name='weeklyComment$eventId'>$comment</textarea>";
            }
		
            /* REST GOES HERE */
            elseif (!$results) {
                if ($solveCount == 5) {
                    $solves=array(1 => prettyNumber($results['solve1']), prettyNumber($results['solve2']), prettyNumber($results['solve3']), prettyNumber($results['solve4']), prettyNumber($results['solve5']));
                }
                if ($solveCount == 3) {
                    $solves=array(1 => prettyNumber($results['solve1']), prettyNumber($results['solve2']), prettyNumber($results['solve3']));
                }
                if ($solveCount == 1) {
                    $solves=array(1 => prettyNumber($results['solve1']));
                    if(is_fewest_moves($eventId)){number_format($result,0,'.','');}
                }

                echo "<div class='data-panel times-window'>";
                echo "Times: <br />";
                $k=1;
                foreach ( $solves as $solveresult ) {
                    print "<input class='submit-weekly-input' type='text' name='weekly".$eventId."Result$k' value='$solveresult' /><br />";
                    $k++;
                }
                echo "</div>";
                echo "<div class='data-panel comment-window'>";
                $comment = str_ireplace("<br />","\n",stripslashes($results['comment']));
                echo "Comment: <br />  <textarea class='submit-weekly-comment' name='weeklyComment$eventId'>$comment</textarea>";
            } else {
                if ($solveCount == 5) {
                    $solves=array(1 => prettyNumber($results['solve1']), prettyNumber($results['solve2']), prettyNumber($results['solve3']), prettyNumber($results['solve4']), prettyNumber($results['solve5']));
                }
                if ($solveCount == 3) {
                    $solves=array(1 => prettyNumber($results['solve1']), prettyNumber($results['solve2']), prettyNumber($results['solve3']));
                }
                if ($solveCount == 1) {
                    $solves=array(1 => prettyNumber($results['solve1']));
                    if(is_fewest_moves($eventId)){number_format($result,0,'.','');}
                }

                echo "<div class='data-panel times-window'>";
                echo "Times: <br />";
                $k=1;
                foreach ($solves as $solveresult) {
                    print "<input class='submit-weekly-input' type='text' name='weekly".$eventId."Result$k' value='$solveresult' /><br />";
                    $k++;
                }
                echo "</div>";
                echo "<div class='data-panel comment-window'>";
                $comment = str_ireplace("<br />","\n",stripslashes($results['comment']));
                echo "Comment: <br />  <textarea class='submit-weekly-comment' name='weeklyComment$eventId'>$comment</textarea>";
            }
            echo "<br /><input type='submit' value='Update your results' />";
            echo "</div>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
	}
        echo "</div>";
    }
    
    // calculate token
    $data = array('userId' => $userId, 'weekNo' => $weekNo, 'yearNo' => $yearNo);
    $tokenizer = new JWT();
    $token = $tokenizer->encode($data, $token_key);
?>
<input type="hidden" value="weeklySubmit" name="update" />
<input type="hidden" value="<?php echo $token;?>" name="encoding" />
</form>
<script>
    <?php
        $events = new Events();
        foreach ($events as $eventId => $eventName) {
            if (!is_active_event($eventId, $weekNo, $yearNo)) {
                continue;
            }
            $eventIds[] = $eventId;
            $eventNames[] = $eventName;
            $solveCounts[] = get_solve_count($eventId, $yearNo);
        }
        echo "var eventIds = ".json_encode($eventIds).";\n";
        echo "    var solveCounts = ".json_encode($solveCounts).";\n";
        echo "    var eventNames = ".json_encode($eventNames).";\n";
        echo "    var eventRules = ".json_encode($eventRules).";\n"
    ?>
    init();
    
    function init() {
        // hide scrambles if option selected
        if (localStorage.getItem('hideScrambles') === 'true') {
            var scrambles = document.getElementsByClassName('scramble-window');
            for (i = 0; i < scrambles.length; ++i) {
                scrambles[i].style.display = 'none';
            }
        }
        if (localStorage.getItem('collapseCompleted') === 'true') {
            var panels = document.getElementsByClassName('panelComplete');
            for (i = 0; i < panels.length; ++i) {
                panels[i].style.display = 'none';
            }
        }
        document.getElementById('weeklySubmitPage').hidden = false;
        document.getElementById('hideScrambles').checked = (localStorage.getItem('hideScrambles') == 'true');
        document.getElementById('collapseCompleted').checked = (localStorage.getItem('collapseCompleted') == 'true');
    }
    
    function showRules(d)
    {
        eventId = d.getAttribute("data-event-id");
        alert(d.getAttribute("data-event-name") + " Rules:\n\n" + eventRules[eventId]);
        d.blur();
    }

    function checkboxChange(id) {
        localStorage.setItem(document.getElementById(id).name, document.getElementById(id).checked);
        window.location.reload(false);
    }
    
    function changeUserWeek() {
        user = document.getElementById('user-id').value;
        week = document.getElementById('week').value;
        year = document.getElementById('year').value;
        window.location.href = "?side=weeklySubmit&user=" + user + "&week=" + week + "&year=" + year;
    }

    function is_blindfolded_event(eventId)
    {
        if (eventId >= 7 && eventId <= 12) {
            return true;
        }
        return false;
    }
    
    function is_fewest_moves(eventId)
    {
        if (eventId == 17 || eventId == 36) {
            return true;
        }
        return false;
    }

    function is_DNF(result)
    {
        if (result == 8888 || result == "DNF") {
            return true;
        }
        return false;
    }

    function is_DNS(result)
    {
        if (result == 0 || result == 9999 || result == "DNS") {
            return true;
        }
        return false;
    }
    
    function validate(event)
    {
        // Make sure Manual Entry is displayed when returning if settings have been changed to do so
        if (localStorage.getItem('defaultManualEntry') === 'true') {
            document.getElementsByName('defaultManualEntry')[0].value = 'true';
        }

        for (eventIndex = 0; eventIndex < eventIds.length; ++eventIndex) {
            eventId = eventIds[eventIndex];
            hasDNF = false;
            hasValidSolve = false;

            // For FMC, preprocess solution to make sure it is valid.
            solution = "";
            if (is_fewest_moves(eventId)) {
                for (i = 0; i < solveCounts[eventIndex]; ++i) {
                    solution = document.getElementById("weekly" + eventId + "Solution" + (i + 1)).value;
                    if (solution == "DNF") {
                        hasDNF = true;
                    } else if (solution != "" && solution != "DNS") {
                        hasValidSolve = true;
                        solution = solution.replace(/W/g, "w");
                        solution = solution.replace(/X/g, "x");
                        solution = solution.replace(/Y/g, "y");
                        solution = solution.replace(/Z/g, "z");
                        solution = solution.replace(/’/g, "'");
                        solution = solution.replace(/‘/g, "'");
                        validRegex = /^(([FBUDLRxyz][w]?[2']?\s*)|([\[][fbudlr][2']?[\]]\s*]))*$/;
                        if (!validRegex.test(solution)) {
                            alertString = "Your submitted solution does not meet WCA notation rules.  Please adjust your solution to meet WCA regulations.";
                            alert(alertString);
                            return false;
                        } else {
                            document.getElementById("weekly" + eventId + "Solution" + (i + 1)).value = solution;
                        }
                        if (document.getElementById("weekly" + eventId + "Explanation" + (i + 1)).value === "") {
                            if (typeof variable !== 'undefined') {
                                event.preventDefault();
                            }
                            alertString = "Please enter an explanation of your solution.\n\nAll fewest moves solutions require an explanation of how you obtained the solution.\n\n";
                            alertString += "Please give a sufficient explanation such that a moderator can figure out how you found the solution.  If the explanation is not sufficent to explain ";
                            alertString += "how the solution was obtained, a moderator may contact you for a better explanation, and if none is provided, we reserve the right to change ";
                            alertString += "your solution to a DNF.";
                            alert(alertString);
                            return false;
                        } else if (eventId == 36) {
                            time = document.getElementById("weekly" + eventId + "Time" + (i + 1)).value;
                            if (time == "" || time == "DNS" || time == 0) {
                                alertString = "Solve " + (i + 1) + " has a solution, but no time. Please provide the time taken to perform the solve.";
                                alert(alertString);
                                return false;
                            }
                        }
                    }
                }
            } else {
                for (solveId = 1; solveId <= solveCounts[eventIndex]; ++solveId) {
                    solveValue = document.getElementsByName("weekly" + eventId + "Result" + solveId)[0].value;
                    if (is_DNF(solveValue)) {
                        hasDNF = true;
                    } else if (!is_DNS(solveValue)) {
                        hasValidSolve = true;
                    }
                }
            }
            if (document.getElementsByName("weeklyComment" + eventId)[0].value !== "") {
                // A comment has been provided; no need to check for DNFs
                continue;
            }
            if (!hasValidSolve && hasDNF) {
                event.preventDefault();
                alertString = "Please enter a comment for event " + eventNames[eventIndex] + ".\n\nWe now require a comment for any event in which only DNFs are submitted. ";
                alertString += "This is to discourage entering DNFs for events in which you did not try to solve the puzzle. ";
                alertString += "In order to submit a DNF, you are expected to have made a genuine attempt to solve the puzzle.\n\n";
                if (is_blindfolded_event(eventId)) {
                     alertString += "For blindfolded events, consider entering memorization/execution splits, a description of the failure, or both.\n\n";
                } else {
                    alertString += "For non-blindfolded events, consider adding a comment describing what caused the solve to be a DNF.\n\n";
                }
                alertString += "The moderators reserve the right to delete any entries for which the comments are meaningless, frivolous, or otherwise inappropriate.";
                alert(alertString);
                return false;
            }
        }
        return true;
    }
</script>
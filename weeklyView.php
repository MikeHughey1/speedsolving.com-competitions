<script>
    function fillInHeaderInformation(name, score, place) {
        document.getElementById('myName').innerHTML = name;
        document.getElementById('myNameMobile').innerHTML = name;
        document.getElementById('totalScore').innerHTML = score;
        document.getElementById('totalScoreMobile').innerHTML = score;
        document.getElementById('overallPlace').innerHTML = place;
        document.getElementById('overallPlaceMobile').innerHTML = place;
    }

    function startTimer(currentEvent) {
        location.href='?side=timer&event=' + currentEvent;
    }
</script>

<?php
    $userId = filter_input(INPUT_GET, 'user', FILTER_VALIDATE_INT);
    if(($_SESSION['logged_in']==66 || $_SESSION['logged_in']==85 || $_SESSION['logged_in']==111) && $userId){
        // Keep the input; allow the user id to be changed for administrative updates
    } else {
        $userId = $_SESSION['logged_in'];
    }
    $data = array('userId' => $userId, 'weekNo' => $weekNo, 'yearNo' => $yearNo);
    $tokenizer = new JWT();
    $token = $tokenizer->encode($data, $token_key);

    global $events;
    $weeklyResults = new WeeklyResults($weekNo, $yearNo);

    echo "<div><div id='mainView'>";
    echo "<div id='headerText' class='header-text'><span id='myName'></span><span>, you have </span><span class='header-score' id='totalScore'><b></b></span><span> points so far this week! This puts you in </span><span class='header-score' id='overallPlace'><b></b></span><span> place overall.</span></div>";
    echo "<div id='headerTextMobile' class='header-text'><span id='myNameMobile'></span><span>:</span><span class='header-score' id='totalScoreMobile'><b></b></span><span> points   </span><span class='header-score' id='overallPlaceMobile'><b></b></span><span> place</span></div>";
    if (($weekNo == get_current_week() && $yearNo == get_current_year() && $userId == $_SESSION['logged_in']) || is_admin()) {
        if ($userId != $_SESSION['logged_in']) { echo "Logged in as user id=".$userId; }
        foreach ($events as $eventId) {
            if (!is_active_event($eventId, $weekNo, $yearNo)) {
                continue;
            }
            // Figure out how done we are on this event
            $partial = $weeklyResults->is_partial($eventId, $userId);
            $complete = $weeklyResults->is_completed($eventId, $userId);
            $styleMod = "";
            if ($partial) {
                $styleMod = "result-partial";
            } elseif ($complete) {
                $styleMod = "result-complete";
            } else {
                $styleMod = "result-none";
            }
            $numResults = $weeklyResults->get_participant_count($eventId);

            /* Output data */
            echo "<div class='view-info $styleMod'>";
            add_icon($events->name($eventId), "cubing-icon-5x");
            echo "<div><font class='submit-weekly-header'><div><a href='showWeeks.php?selectEvent=".($eventId + 2)."'>".get_short_event_name($eventId)."</a></div></font></div>";
            if ($complete || $partial) {
                $solveDetails = $weeklyResults->get_user_solve_details($eventId, $userId);
                if (strlen($solveDetails) > 42) {
                    $printDetails = substr($solveDetails, 0, 39)."...";
                } else {
                    $printDetails = substr($solveDetails, 0, 42);
                }
                $myResult = $weeklyResults->get_user_result($eventId, $userId);
                $myPlace = $weeklyResults->get_user_place($eventId, $userId);
                $myScore = $weeklyResults->get_user_score($eventId, $userId);
                echo "<div type='text' class='event-details'>$printDetails</div>";
                echo "<div type='text' class='event-result' name='weekly".$eventId."Result'><b>$myResult</b></div>";
                echo "<div type='text' class='event-place'>".get_place_string($myPlace)." place (".$numResults.")</div>";
                echo "<div type='text' class='event-score'><b>".$myScore." Points</b></div>";
                if ($partial) {
                    echo "<button type='button' class='btn' id='timer' onclick='startTimer(".$eventId.")'>Continue</button>";
                } else {
                    echo "<button type='button' class='btn' id='timer' onclick='startTimer(".$eventId.")'>Edit</button>";
                }
            } else {
                echo "<div type='text' class='event-details'>---</div>";
                echo "<div type='text' class='event-result' name='weeklyComment$eventId'>No results yet (".$numResults.")</div>";
                echo "<div type='text' class='event-place'>---</div>";
                echo "<div type='text' class='event-score'><b>---</b></div>";
                echo "<button type='button' class='btn' id='timer' onclick='startTimer(".$eventId.")'>Compete!</button>";
            }
            echo "</div>";
        }
        echo "</div>";
        echo "<div id='overallScore'>";
        echo "<div id='overallRanking'><strong><u>Weekly Overall Score Ranking</u></strong></div><br />";
        $place = 1;
        foreach ($weeklyResults->get_score_list() as $key => $value) {
            if ($value > 0) {
                $displayName = get_person_info($key)['displayName'];
                $userDisplayName = "<a href='showPerson.php?showPerson=".$key."'>".$displayName."</a>";
                echo get_place_string($place)." place: {$value} ".$userDisplayName."<br />";
            }
            $place++;
        }
        $myTotalScore = $weeklyResults->get_user_total_score($userId);
        if ($myTotalScore == 0) {
            echo "<script>document.getElementById('headerText').hidden = true;</script>";
        } else {
            $myFirstName = get_person_info($userId)['firstName'];
            $myPlace = array_search($userId, array_keys($weeklyResults->get_score_list())) + 1;
            echo "</div></div>";
            echo "<script>fillInHeaderInformation('".$myFirstName."', ".$myTotalScore.", '".get_place_string($myPlace)."');</script>";
        }
    }

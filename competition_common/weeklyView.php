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

    function add_zero(x) {
        return (x < 10 && x >= 0) ? "0" + x : x;
    }

    function update_time() {
        const elapsed = (new Date().getTime() / 1000) - start;
        const currentRemaining = remaining - elapsed;
        const days = Math.floor(currentRemaining / (60 * 60 * 24));
        const hours = Math.floor((currentRemaining % (60 * 60 * 24)) / (60 * 60));
        const minutes = Math.floor((currentRemaining % (60 * 60)) / (60));
        const seconds = Math.floor((currentRemaining % (60)));
        document.getElementById("timeDays").innerHTML = days;
        document.getElementById("timeHours").innerHTML = add_zero(hours);
        document.getElementById("timeMinutes").innerHTML = add_zero(minutes);
        document.getElementById("timeSeconds").innerHTML = add_zero(seconds);
        timer = setTimeout(update_time, "1000");
    }
</script>

<?php
    $userId = filter_input(INPUT_GET, 'user', FILTER_VALIDATE_INT);
    if(is_admin() && $userId){
        // Keep the input; allow the user id to be changed for administrative updates
    } else {
        $userId = $currentUserId;
    }
    $data = array('userId' => $userId, 'weekNo' => $weekNo, 'yearNo' => $yearNo);
    $tokenizer = new JWT();
    $token = $tokenizer->encode($data, $token_key);

    global $events;
    $weeklyResults = new WeeklyResults($weekNo, $yearNo);

    echo "<div><div id='mainView'>";
    echo "<div id='headerText' class='header-text'><span id='myName'></span><span>, you have </span><span class='header-score' id='totalScore'><b></b></span><span> points so far this week! This puts you in </span><span class='header-score' id='overallPlace'><b></b></span><span> place overall.</span></div>";
    echo "<div id='headerTextMobile' class='header-text'><span id='myNameMobile'></span><span>:</span><span class='header-score' id='totalScoreMobile'><b></b></span><span> points   </span><span class='header-score' id='overallPlaceMobile'><b></b></span><span> place</span></div>";
    if (($weekNo == get_current_week() && $yearNo == get_current_year() && $userId == $currentUserId) || is_admin()) {
        if ($userId != $currentUserId) { echo "Logged in as user id=".$userId; }
        $eventIds = event_list();
        if (is_mike()) {
            $eventIds = sorted_event_list_by_previous_week($weekNo, $yearNo);
        }
        foreach ($eventIds as $eventId) {
            if (!is_active_event($eventId, $weekNo, $yearNo)) {
                continue;
            }
            // Figure out how done we are on this event
            $partial = $weeklyResults->is_partial($eventId, $userId);
            $complete = $weeklyResults->is_completed($eventId, $userId);
            $styleMod = "result-none";
            if ($partial) {
                $styleMod = "result-partial";
            } elseif ($complete) {
                $styleMod = "result-complete";
            }
            $numResults = $weeklyResults->get_participant_count($eventId);

            /* Output data */
            echo "<div class='view-info $styleMod' draggable='true'>";
            add_icon($events->name($eventId), "cubing-icon-5x");
            echo "<div><div class='submit-weekly-header'><a href='showWeeks.php?selectEvent=".($eventId)."'>".get_short_event_name($eventId)."</a></div></div>";
            if ($complete || $partial) {
                $solveDetails = $weeklyResults->get_user_solve_details($eventId, $userId);
                if (strlen($solveDetails) > 39) {
                    $printDetails = substr($solveDetails, 0, 36)."...";
                } else {
                    $printDetails = substr($solveDetails, 0, 39);
                }
                $myResult = $weeklyResults->get_user_result($eventId, $userId);
                $myPlace = $weeklyResults->get_user_place($eventId, $userId);
                $myScore = $weeklyResults->get_user_score($eventId, $userId);
                echo "<div class='event-details'>$printDetails</div>";
                echo "<div class='event-result'><b>$myResult</b></div>";
                echo "<div class='event-place'>".get_place_string($myPlace)." place (".$numResults.")</div>";
                echo "<div class='event-score'><b>".$myScore." Points</b></div>";
                if ($partial) {
                    echo "<button type='button' class='btn' id='timer".$eventId."' onclick='startTimer(".$eventId.")'>Continue</button>";
                } else {
                    echo "<button type='button' class='btn' id='timer".$eventId."' onclick='startTimer(".$eventId.")'>Edit</button>";
                }
            } else {
                echo "<div class='event-details'>---</div>";
                echo "<div class='event-result'>No results yet (".$numResults.")</div>";
                echo "<div class='event-place'>---</div>";
                echo "<div class='event-score'><b>---</b></div>";
                echo "<button type='button' class='btn' id='timer".$eventId."' onclick='startTimer(".$eventId.")'>Compete!</button>";
            }
            echo "</div>";
        }
        echo "</div>";
        echo "<div id='overallScore'>";
        echo "<div class='header-text'>Weekly Competition ".get_competition_name(get_current_week(), get_current_year())."</div>";
        print <<<EOD
        <div class='header-text'>
            Time remaining: <span id='timeDays'></span> days, <span id='timeHours'></span>:<span id='timeMinutes'></span>:<span id='timeSeconds'></span>
        </div>
EOD;
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
        echo "</div></div>";
        $myTotalScore = $weeklyResults->get_user_total_score($userId);
        if ($myTotalScore == 0) {
            echo "<script>document.getElementById('headerText').hidden = true;</script>";
        } else {
            $myFirstName = get_person_info($userId)['firstName'];
            $myPlace = array_search($userId, array_keys($weeklyResults->get_score_list())) + 1;
            echo "</div></div>";
            echo "<script>fillInHeaderInformation('".$myFirstName."', ".$myTotalScore.", '".get_place_string($myPlace)."');</script>";
        }
        echo "<script>remaining = ".get_time_to_next_week().";";
        echo "var start = new Date().getTime() / 1000;";
        echo "var timer = setTimeout(update_time, '0');</script>";
    }

<?php
    $title = "Weekly Competition Overall Results (Speedsolving.com)";
    
    $yearNo = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);
    if (!$yearNo || $yearNo > get_current_year() || $yearNo < get_start_year()) {
        $yearNo = get_current_year();
    }
    $weekNo = filter_input(INPUT_GET, 'week', FILTER_VALIDATE_INT);
    if (!$weekNo || ($yearNo == get_current_year() && $weekNo > get_current_week()) || $weekNo > 53 || ($weekNo > 52 && $yearNo != 2008 && $yearNo != 2015 && $yearNo != 2020) || $weekNo < 1) {
        $weekNo = get_current_week();
    }

    // Load up the scrambles into json format so they can be displayed in javascript if requested
    $query = $mysqli->query("SELECT eventId, scramble FROM scrambles WHERE yearId = '$yearNo' AND weekId = '$weekNo'");
    while ($row = $query->fetch_assoc()) {
        $scramble = str_ireplace("&nbsp;", " ", $row['scramble']);
        $scramble = str_ireplace("<br />", "\n", $scramble);
        $scramble = str_ireplace("<br>", "\n", $scramble);
        $scrambles[$row['eventId']] = $scramble;
    }
    $exportedScrambles = "";
    if (isset($scrambles)) {
        $exportedScrambles = json_encode($scrambles);
    }

    $events = new Events;
    $weeklyResults = new WeeklyResults($weekNo, $yearNo);
    $numberOfParticipants = $weeklyResults->get_participant_total();
    
    print "<div id='canvas'>";
    $competitionName = get_competition_name($weekNo, $yearNo);
    print "<h1 class='centerText'>Week $competitionName</h1>";
    
    echo <<<END
    $numberOfParticipants competed in an event so far<br>
    <select id='showEvent' onchange='hideShow(this)'>
    <option value='0'>Pick event...</option>
    <option value='Overall'>Overall score ($numberOfParticipants)</option>
    <option value='Kinch'>Kinch score ($numberOfParticipants)</option>
    <option value='KinchWca'>Kinch score (WCA events) ($numberOfParticipants)</option>
END;

    $eventIndex = 3; // Increase this for every special list above (Overall, Kinch, KinchWca)
    $eventIndices = []; // Keeps track of where to go for the week to show the right event by index
    foreach ($events as $eventId => $eventName) {
        if (is_active_event($eventId, $weekNo, $yearNo)) {
            echo "<option value='event$eventId'>".$eventName." (".$weeklyResults->get_participant_count($eventId).")</option>";
            $eventIndices[$eventId] = ++$eventIndex;
        }
    }
    echo "</select><br>";

    // Dropdown for year number
    echo "<select onchange=change_year_week(this)>";
    for ($j = get_current_year(); $j >= get_start_year(); $j--){
        if ($j == $yearNo){
            echo "<option selected='selected'  value='?week=$weekNo&year=$j'>Year $j</option>";
        } else {
            echo "<option  value='?week=$weekNo&year=$j'>Year $j</option>";
        }
    }
    echo "</select>&nbsp;&nbsp;&nbsp;&nbsp;";

    // Dropdown for week number
    echo "<select onchange=change_year_week(this)>";
    for ($j = ($yearNo == get_current_year())? get_current_week() : (($yearNo == 2008 || $yearNo == 2015 || $yearNo == 2020) ? 53 : 52) ; $j > 0; $j--){
        if ($j == $weekNo){
            echo "<option selected='selected'  value='?week=$j&year=$yearNo'>Week $j</option>";
        } else {
            echo "<option  value='?week=$j&year=$yearNo'>Week $j</option>";
        }
    }
    echo "</select><br>";

    /*** preload Overall score rankings ***/
    print <<<END
    <div>
        <div class='weekly-ranking' id='Overall'>
            <div class='xLargeText'><br>Overall Score<br></div>
            <table class='table-striped table-dynamic'>
                <thead>
                    <tr>
                        <th class='l'>#</th>
                        <th class='l'>Name</th>
                        <th class='r'>Events</th>
                        <th class='r'>Overall Score</th>
                    </tr>
                </thead>
END;

    $place = 1;
    foreach ($weeklyResults->get_score_list() as $userId =>$score) {
        $personInfo = get_person_info($userId);
        echo "<tr>";
        if (is_admin()) {
            $email = $personInfo['email'];
            echo "<td class='l with-pointer' id='$email' onclick='copyToClipboard(this)'>".$place."</td>";
        } else {
            echo "<td class='l'>".$place."</td>";
        }
        echo "<td class='userLink'><a href='showPersonalRecords.php?showRecords=$userId'><b>".$personInfo['displayName']." (".$personInfo['username'].")</b></a></td>";
        echo "<td class='r'>".$weeklyResults->get_attempted_events($userId)."</td>";
        echo "<td class='r'><b>$score</b></td>";
        echo "</tr>";
        ++$place;
    }
    echo "</table></div></div>";

    /*** preload Kinch rankings ***/
    print <<<END
    <div>
        <div class='weekly-ranking' id='Kinch'>
            <div class='xLargeText'><br>Kinch Score<br></div>
            <table class='table-striped table-dynamic'>
                <thead>
                    <tr>
                        <th class='l'>#</th>
                        <th class='l'>Name</th>
                        <th class='r'>Events</th>
                        <th class='r'>Kinch Score</th>
                    </tr>
                </thead>
END;

    $place = 1;
    foreach ($weeklyResults->get_kinch_scores() as $userId =>$score) {
        $personInfo = get_person_info($userId);
        echo "<tr>";
        if (is_admin()) {
            $email = $personInfo['email'];
            echo "<td class='l with-pointer' id='$email' onclick='copyToClipboard(this)'>".$place."</td>";
        } else {
            echo "<td class='l'>".$place."</td>";
        }
        echo "<td class='userLink'><a href='showPersonalRecords.php?showRecords=$userId'><b>".$personInfo['displayName']." (".$personInfo['username'].")</b></a></td>";
        echo "<td class='r'>".$weeklyResults->get_attempted_events($userId)."</td>";
        echo "<td class='r'><b>".round_score($score)."</b></td>";
        echo "</tr>";
        ++$place;
    }
    echo "</table></div></div>";

    /*** preload WCA-only Kinch rankings ***/
    print <<<END
    <div>
        <div class='weekly-ranking' id='KinchWca'>
            <div class='xLargeText'><br>Kinch Score (WCA events only)<br></div>
            <table class='table-striped table-dynamic'>
                <thead>
                    <tr>
                        <th class='l'>#</th>
                        <th class='l'>Name</th>
                        <th class='r'>WCA Events</th>
                        <th class='r'>Kinch Score</th>
                    </tr>
                </thead>
END;

    $place = 1;
    foreach ($weeklyResults->get_kinch_scores_wca() as $userId =>$score) {
        $personInfo = get_person_info($userId);
        echo "<tr>";
        if (is_admin()) {
            $email = $personInfo['email'];
            echo "<td class='l with-pointer' id='$email' onclick='copyToClipboard(this)'>".$place."</td>";
        } else {
            echo "<td class='l'>".$place."</td>";
        }
        echo "<td class='userLink'><a href='showPersonalRecords.php?showRecords=$userId'><b>".$personInfo['displayName']." (".$personInfo['username'].")</b></a></td>";
        echo "<td class='r'>".$weeklyResults->get_attempted_events_wca($userId)."</td>";
        echo "<td class='r'><b>".round_score($score)."</b></td>";
        echo "</tr>";
        ++$place;
    }
    echo "</table></div></div>";

    /*** preload all ranklists ***/
    foreach ($events as $eventId => $eventName) {
        if (!is_active_event($eventId, $weekNo, $yearNo)) {
            continue;
        }
        $solveCount = get_solve_count($eventId, $yearNo);
        $resultText = "Result";
        if (is_movecount_scored($eventId) && get_solve_count($eventId, $yearNo) > 1) {
            $resultText = "Mean";
        }
        
        print <<<END
        <div>
            <div class='weekly-ranking' id='event$eventId'>
                <div class='xLargeText'><br>$eventName</div>
                <button onclick="displayScramble($eventId, '$eventName');">Scrambles</button>
                <br><br>
                <table class='table-striped table-dynamic'>
                    <thead>
                        <tr>
                            <th class='l'>#</th>
                            <th class='l'>Name</th>
                            <th class='r'>$resultText</th>
                            <th class='c'>Solves</th>
                            <th class='comment'>Comment</th>
                        </tr>
                    </thead>
END;
        if ($weeklyResults->get_participant_count($eventId) > 0) {
            foreach ($weeklyResults->get_user_places($eventId) as $userId => $place) {
                $personInfo = get_person_info($userId);
                echo "<tr>";
                if (is_admin()) {
                    $email = $personInfo['email'];
                    echo "<td class='l with-pointer' id='$email' onclick='copyToClipboard(this)'>".$place."</td>";
                } else {
                    echo "<td class='l'>".$place."</td>";
                }
                echo "<td class='userLink'><a href='showPersonalRecords.php?showRecords=$userId'><b>".$personInfo['displayName']." (".$personInfo['username'].")</b></a></td>";
                echo "<td class='r'><b>".$weeklyResults->get_user_result($eventId, $userId)."</b></td>";
                echo "<td class='c'>".$weeklyResults->get_user_solve_details($eventId, $userId)."</td>";
                $comment = $weeklyResults->get_user_comment($eventId, $userId);
                if (is_fewest_moves($eventId) && $eventId != 32) {
                    // Need to handle multiple solves here
                    $solveId = 1;
                    $fmcResults = $mysqli->query("SELECT solution, comment FROM weeklyFmcSolves WHERE yearId = '$yearNo' AND weekId = '$weekNo' AND userId = '$userId' AND eventId = '$eventId' AND solveId = '$solveId'")->fetch_array();
                    $solutionExplanation = $fmcResults['comment'];
                    if ($solutionExplanation != $comment) {
                        // If these match, it means the comment was copied to the solution temporarily - this code can go away when the move to mean of 3 is finished.
                        echo "<td class='l'>".$comment."</td>";
                    } else {
                        echo "<td class='l'></td>";
                    }
                } else {
                    echo "<td class='l'>".$comment."</td>";
                }
                echo "</tr>";
            }
        }
        echo "</table>";

        if (is_fewest_moves($eventId) && $eventId != 32 && isset($scrambles)) {
            // Fewest moves; show scramble that was solved so solutions will make sense
            $explodeScrambles = explode("\n", $scrambles[$eventId]);
            for ($solveId = 1; $solveId <= $solveCount; ++$solveId) {
                $scrambleText = "Scramble ".$explodeScrambles[$solveId - 1];
                print <<<END
                <div class='xLargeText'><br><br>$scrambleText<br></div>
                <table class='table-striped table-dynamic'>
                    <thead>
                        <tr>
                            <th class='l'>#</th>
                            <th class='l'>Name</th>
                            <th class='r'>Moves</th>
END;
                if ($eventId == 36) {
                    print <<<END
                    <th class='r'>Time</th>
                    <th class='r'>Score</th>
END;
                }
                print <<<END
                            <th class='c'>Solution</th>
                            <th class='comment'>Explanation</th>
                        </tr>
                    </thead>
END;
                if ($weeklyResults->get_participant_count($eventId) > 0) {
                    $orderBy = "moves";
                    if ($eventId == 36) {
                        $orderBy = "ABS(moves + (time / 60))";
                    }
                    $fmcResults = $mysqli->query("SELECT userId, moves, time, solution, comment FROM weeklyFmcSolves WHERE yearId = '$yearNo' AND weekId = '$weekNo' AND eventId = '$eventId' AND solveId = '$solveId' order by $orderBy");
                    $rank = 0;
                    $count = 0;
                    $prev = 0;
                    while ($row = $fmcResults->fetch_assoc()) {
                        $userId = $row['userId'];
                        $moves = $row['moves'];
                        $time = $row['time'];
                        ++$count;
                        if ($moves > $prev) {
                            $rank = $count;
                            $prev = $moves;
                        }
                        if ($moves == 8888) {
                            $moves = "DNF";
                        } else if ($moves == 9999) {
                            continue; // Skip DNS results - no point in displaying them
                        }
                        $personInfo = get_person_info($userId);
                        if ($eventId == 36) {
                            $score = get_speed_FMC_result($moves, $time);
                            if ($score == 9999 && $row['solution'] != "") {
                                $score = "DNF";
                            } else if ($score == 9999) {
                                continue; // Skip DNS results - no point in displaying them
                            }
                        }
                        $time = pretty_number($time);
                        echo "<tr>";
                        echo "<td class='l'>".$rank."</td>";
                        echo "<td class='userLink'><a href='showPersonalRecords.php?showRecords=$userId'><b>".$personInfo['displayName']." (".$personInfo['username'].")</b></a></td>";
                        echo "<td class='r'><b>".$moves."</b></td>";
                        if ($eventId == 36) {
                            echo "<td class='r'><b>".$time."</b></td>";
                            echo "<td class='r'><b>".$score."</b></td>";
                        }
                        // Need to handle multiple solves here
                        $solutionExplanation = $row['comment'];
                        echo "<td class='c'>".$row['solution']."</td>";
                        echo "<td class='l'>".$solutionExplanation."</td>";
                        echo "</tr>";
                    }
                }
                echo "</table>";
            }
        }
        echo "</div></div>";
    }
    print "</div>";
    $selectEvent = filter_input(INPUT_GET, 'selectEvent', FILTER_VALIDATE_INT);
    $selectIndex = filter_input(INPUT_GET, 'selectIndex', FILTER_VALIDATE_INT);
    if ($selectEvent && isset($eventIndices[$selectEvent])) {
        $selectIndex = $eventIndices[$selectEvent];
    } elseif (!$selectIndex) {
        $selectIndex = 1;
    }
?>

<script>
    document.getElementById('showEvent').selectedIndex = <?php print $selectIndex ?>;
    hideShow(document.getElementById('showEvent'));
<?php
    if (isset($scrambles)) {
        echo "scrambles = $exportedScrambles;";
    }
?>

    function hideShow(selector){
        for (x=0; x<selector.length; x++)
        {
            var division = document.getElementById(selector.options[x].value);
            if (division)
                division.style.display = (x == selector.selectedIndex ? "inline" : "none");
        }
    }

    function change_year_week(value)
    {
        var newLocation = value.options[value.options.selectedIndex].value;
        var showIndex = document.getElementById('showEvent').selectedIndex;
        if (showIndex > 0) {
            newLocation += "&selectEvent=";
            newLocation += document.getElementById('showEvent')[showIndex].value.substr(5);
        }
        window.location = newLocation;
    }

    function copyToClipboard(element)
    {
        window.prompt('Copy to clipboard: Ctrl+C, Enter', element.id);
    }

    function displayScramble(eventId, eventName)
    {
        alert(eventName+" week <?php print $yearNo?>-<?php print str_pad($weekNo, 2, '0', STR_PAD_LEFT)?>:\n\n"+scrambles[eventId]);
    }
</script>
</body>
</html>
<?php require_once 'newconnect.php'; ?>
<!DOCTYPE html>
<html lang='en-us'>
<head>
<title>Weekly Competition Results By Week (speedsolving.com)</title>
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
        var showEvent = document.getElementById('showEvent').selectedIndex;
        if (showEvent > 0) {
            newLocation += "&selectEvent=";
            newLocation += showEvent;
        }
        window.location = newLocation;
    }
</script>
</head>

<?php
    require_once 'statsHeader.php';
    require_once 'statFunctions.php';
    
    $yearNo = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);
    if (!$yearNo || $yearNo > get_current_year() || $yearNo < get_start_year()) {
        $yearNo = get_current_year();
    }
    $weekNo = filter_input(INPUT_GET, 'week', FILTER_VALIDATE_INT);
    if (!$weekNo || ($yearNo == get_current_year() && $weekNo > get_current_week()) || $weekNo > 52 || $weekNo < 1) {
        $weekNo = get_current_week();
    }
    $events = new Events;
    $weeklyResults = new WeeklyResults($weekNo, $yearNo);
    $numberOfParticipants = $weeklyResults->get_participant_total();
    
    print "<div id='canvas'>";
    $competitionName = get_competition_name($weekNo, $yearNo);
    print "<h1 class='centerText'>Week $competitionName</h1>";
    
    echo <<<END
    $numberOfParticipants competed in an event so far<br>
    <select id='showEvent' onchange='hideShow(this)'><option value='0'>Pick event...</option>
    <option value='Overall'>Overall score ($numberOfParticipants)</option>
    <option value='Kinch'>Kinch score ($numberOfParticipants)</option>
END;

    foreach ($events as $eventId) {
        if (is_active_event($eventId, $weekNo, $yearNo)) {
            echo "<option value='event$eventId'>".$events->name($eventId)." (".$weeklyResults->get_participant_count($eventId).")</option>";
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
    for ($j = ($yearNo == get_current_year())? get_current_week() : 52; $j > 0; $j--){
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
        echo "<td class='l'>$place</td>";
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
        echo "<td class='l'>$place</td>";
        echo "<td class='userLink'><a href='showPersonalRecords.php?showRecords=$userId'><b>".$personInfo['displayName']." (".$personInfo['username'].")</b></a></td>";
        echo "<td class='r'>".$weeklyResults->get_attempted_events($userId)."</td>";
        echo "<td class='r'><b>".round_score($score)."</b></td>";
        echo "</tr>";
        ++$place;
    }
    echo "</table></div></div>";

    /*** preload all ranklists ***/
    foreach ($events as $eventId) {
        if (!is_active_event($eventId, $weekNo, $yearNo)) {
            continue;
        }
        $eventName = $events->name($eventId);
        $solveCount = $events->num_solves($eventId);
        $scrambleText = "";
        if ($eventId == 17) {
            // Fewest moves; show scramble that was solved so solutions will make sense
            $scrambleText = "Scramble: ".get_scramble_text($eventId, $weekNo, $yearNo);
        }
        
        print <<<END
        <div>
            <div class='weekly-ranking' id='event$eventId'>
                <div class='xLargeText'><br>$eventName<br><br>$scrambleText</div>
                <table class='table-striped table-dynamic'>
                    <thead>
                        <tr>
                            <th class='l'>#</th>
                            <th class='l'>Name</th>
                            <th class='r'>Result</th>
                            <th class='c'>Solves</th>
                            <th class='comment'>Comment</th>
                        </tr>
                    </thead>
END;
        if ($weeklyResults->get_participant_count($eventId) > 0) {
            foreach ($weeklyResults->get_user_places($eventId) as $userId => $place) {
                $personInfo = get_person_info($userId);
                echo "<tr>";
                echo "<td class='l'>".$place."</td>";
                echo "<td class='userLink'><a href='showPersonalRecords.php?showRecords=$userId'><b>".$personInfo['displayName']." (".$personInfo['username'].")</b></a></td>";
                echo "<td class='r'><b>".$weeklyResults->get_user_result($eventId, $userId)."</b></td>";
                echo "<td class='c'>".$weeklyResults->get_user_solve_details($eventId, $userId)."</td>";
                echo "<td class='l'>".$weeklyResults->get_user_comment($eventId, $userId)."</td>";
                echo "</tr>";
            }
        }
        echo "</table></div></div>";
    }
    print "</div>";
    $selectEvent = filter_input(INPUT_GET, 'selectEvent', FILTER_VALIDATE_INT);
    if ($selectEvent) {
        echo "<script>document.getElementById('showEvent').selectedIndex = $selectEvent; hideShow(document.getElementById('showEvent'));</script>";
    } else {
        echo "<script>document.getElementById('showEvent').selectedIndex = 1; hideShow(document.getElementById('showEvent'));</script>";
    }


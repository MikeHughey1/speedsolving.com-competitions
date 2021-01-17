<?php
    $title = "Weekly Competition Records (speedsolving.com)";

    print <<<EOD
    <style>
        .eventLink a {
            color: #3a3;
        }
    </style>
EOD;
    $eventSelected = filter_input(INPUT_GET, 'eventId', FILTER_VALIDATE_INT);
    if (!$eventSelected) {
        $eventSelected = "All";
    }
    $options = array("options" => array("regexp" => "/[a-zA-Z\ 0-9]*/"));
    $yearsSelected = filter_input(INPUT_GET, 'years', FILTER_VALIDATE_REGEXP, $options);
    $history = filter_input(INPUT_GET, 'history', FILTER_VALIDATE_REGEXP, $options);
    
    print <<<EOD
    <br><br><br>
    <div id='canvasCondensed'>
EOD;
    
    // Event selection box
    print <<<EOD
    <form method='get'>
        <div class='form-group'>
            <label for='eventId'>Event:<br>
                <select class='drop' id='eventId' name='eventId'>
                <option value='All' selected='selected'>All</option>
                <option value='All1'></option>
EOD;
    foreach ($events as $eventId => $eventName) {
        if ($eventId == $eventSelected) {
            echo "<option value='$eventId' selected='selected'>$eventName</option>";
        } else {
            echo "<option value='$eventId'>$eventName</option>";
        }
    }
    print <<<EOD
            </select>
        </label>
    </div>
EOD;

    // Years selection box
    $yearsOptions = array();
    $yearsOptions['All'] = 'All';
    $yearsOptions['All1'] = '';
    for ($i = get_start_year(); $i <= get_current_year(); ++$i) {
        $yearsOptions['through+'.$i] = 'through '.$i;
    }
    $yearsOptions['All2'] = '';
    for ($i = get_start_year(); $i <= get_current_year(); ++$i) {
        $yearsOptions['only+'.$i] = 'only '.$i;
    }
    print <<<EOD
    <div class='form-group'>
        <label for='years'>Years:<br>
            <select class='drop' id='years' name='years'>
EOD;
    foreach ($yearsOptions as $name => $value) {
        if (substr($name, 0, 3) == 'All') {
            print "<option value=''";
        } else {
            print "<option value='$name'";
        }
        if ($name == $yearsSelected) {
            print " selected='selected'";
        } else if ($yearsSelected == '' && $name == 'All') {
            print " selected='selected'";
        }
        print ">$value</option>";
    }
    print <<<EOD
            </select>
        </label>
    </div>
EOD;
    
    // Buttons
    print <<<EOD
        <div class='form-group'>
            <label><br>
                <input class='unchosen-button' name='mixed' value='Mixed' type='submit'>
            </label>
            <label><br>
                <input class='unchosen-button' name='slim' value='Slim' type='submit'>
            </label>
            <label><br>
                <input class='unchosen-button' name='separate' value='Separate' type='submit'>
            </label>
            <label><br>
                <input class='chosen-button' name='history' value='History' type='submit'>
            </label>
            <label><br>
                <input class='unchosen-button' name='mixedHistory' value='Mixed History' type='submit'>
            </label>
        </div>
    </form>
EOD;

    foreach ($events as $eventId => $eventName) {
        //Header before actual table
        print <<<EOD
        <table class='results table-condensed'>
            <tbody>
                <tr>
                    <td colspan='5'>&nbsp;</td>
                </tr>
                <tr>
                    <td class='caption eventLink' colspan='5'><a href='showEvents.php?eventId=$eventId&single=Single'>$eventName</td>
                </tr>
                <tr>
                    <th class='R2'>Single</th>
                    <th class='R2'>Average</th>
                    <th>Person</th>
                    <th class='c'>Competition Week</th>
                    <th class='f'>Result Details</th>
                </tr>
EOD;

        $count = 0; // Used in output_record() to alternate shading of rows to stripe the table
        $currentYear = get_current_year();
        $currentWeek = get_current_week();
        if (is_admin()) {
            // Include any records from this week, to look for suspicious entries
            $record = $mysqli->query("SELECT MIN(best) FROM weeklyResults WHERE eventId = $eventId")->fetch_array()['MIN(best)'];
            $queryBest = $mysqli->query("SELECT yearId, weekId, userId, best FROM weeklyResults WHERE eventId = $eventId AND yearId = $currentYear AND weekId = $currentWeek AND best = $record");
            if ($queryBest) {
                while ($results = $queryBest->fetch_array()) {
                    output_record($results, $eventId, "best", $count);
                    ++$count;
                }
            }
        }
        $queryBest = $mysqli->query("SELECT yearId, weekId, userId, best FROM records WHERE eventId = $eventId AND singleRecord = 'SR' ORDER BY yearId DESC, weekId DESC");
        if ($queryBest) {
            while ($results = $queryBest->fetch_array()) {
                output_record($results, $eventId, "best", $count);
                ++$count;
            }
        }
        if (is_admin() && is_average_event($eventId, $currentYear)) {
            // Include any records from this week, to look for suspicious entries
            $record = $mysqli->query("SELECT MIN(average) FROM weeklyResults WHERE eventId = $eventId")->fetch_array()['MIN(average)'];
            $queryAverage = $mysqli->query("SELECT yearId, weekId, userId, average FROM weeklyResults WHERE eventId = $eventId AND yearId = $currentYear AND weekId = $currentWeek AND average = $record");
            if ($queryAverage) {
                while ($results = $queryAverage->fetch_array()) {
                    output_record($results, $eventId, "average", $count);
                    ++$count;
                }
            }
        }
        $queryAverage = $mysqli->query("SELECT yearId, weekId, userId, average FROM records WHERE eventId = $eventId AND averageRecord = 'SR' ORDER BY yearId DESC, weekId DESC");
        if ($queryAverage) {
            while ($results = $queryAverage->fetch_array()) {
                output_record($results, $eventId, "average", $count);
                $record = $results['average'];
                ++$count;
            }
        }
    }
    print <<<EOD
            </tbody>
        </table>
    </div>
EOD;

    function output_record($results, $eventId, $type, $count)
    {
        global $mysqli;

        $year = $results['yearId'];
        $week = $results['weekId'];
        $userId = $results['userId'];
        $userFullName = get_person_info($userId)['displayName'];
        $competitionName = get_competition_name($week, $year);
        $solveDetails = "";
        if ($type === 'average') {
            $queryDetails = $mysqli->query("SELECT solve1, solve2, solve3, solve4, solve5, result, multiBLD FROM weeklyResults WHERE weekId = $week AND yearId = $year AND eventId = $eventId AND userId = $userId");
            $row = $queryDetails->fetch_array();
            $solves = [1 => $row['solve1'], $row['solve2'], $row['solve3'], $row['solve4'], $row['solve5']];
            $solveDetails = get_solve_details($eventId, get_solve_count($eventId, $year), $solves, $row['result'], $row['multiBLD'], false);
            $record = get_average_output($eventId, $year, $results[$type]);
        } else {
            $record = get_single_output_from_best($eventId, $results[$type]);
        }
        if ($count % 2 == 0) {
            echo "<tr class='e'>";
        } else {
            echo "<tr>";
        }
        if ($type == 'best') {
            print <<<EOD
                <td class='R2'>$record</td>
                <td class='R2'></td>
EOD;
        } else {
            print <<<EOD
            <td class='R2'></td>
            <td class='R2'>$record</td>
EOD;
        }
        print <<<EOD
        <td class='userLink'><a href='showPersonalRecords.php?showRecords=$userId'>$userFullName</a></td>
        <td class='c'><a href='showWeeks.php?week=$week&year=$year&selectEvent=".($eventId)."'>$competitionName</a></td>
        <td class='f'>$solveDetails</td>
        </tr>
EOD;
    }

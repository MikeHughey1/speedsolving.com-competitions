<?php
    $title = "Weekly Competition Rankings By Event (Speedsolving.com)";
    
    $eventSelected = filter_input(INPUT_GET, 'eventId', FILTER_VALIDATE_INT);
    if (!$eventSelected) {
        $eventSelected = 1;
    }
    $eventNameSelected = $events->name($eventSelected);
    $options = array("options" => array("regexp" => "/[a-zA-Z\ 0-9]*/"));
    $yearsSelected = filter_input(INPUT_GET, 'years', FILTER_VALIDATE_REGEXP, $options);
    $showSelected = filter_input(INPUT_GET, 'show', FILTER_VALIDATE_REGEXP, $options);
    // Temporary protection of security flaw
    $yearsSelected = '';
    if ($showSelected == '1000+Persons') {
        $showSelected = '1000+Persons';
    } else if ($showSelected == 'All Persons') {
        $showSelected = 'All Persons';
    } else if ($showSelected == '100+Results') {
        $showSelected = '100+Results';
    } else if ($showSelected == '1000+Results') {
        $showSelected = '1000+Results';
    } else {
        $showSelected = '100+Persons';
    }
    $showSingles = true;
    $average = filter_input(INPUT_GET, 'average', FILTER_VALIDATE_REGEXP, $options);
    if ($average == "Average") {
        $showSingles = false;
    }
    
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
        <label for='years'>Show:<br>
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
    
    // Show selection box
    $showOptions = array('100+Persons' => '100 Persons',
                         '1000+Persons' => '1000 Persons',
                         'All+Persons' => 'All Persons',
                         '100+Results' => '100 Results',
                         '1000+Results' => '1000 Results');
    print <<<EOD
    <div class='form-group'>
        <label for='show'>Show:<br>
            <select class='drop' id='show' name='show'>
EOD;
    foreach ($showOptions as $name => $value) {
        print "<option value='$name'";
        if ($name == $showSelected) {
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
                <input class='chosen-button' name='single' value='Single' type='submit'>
            </label>
            <label><br>
                <input class='unchosen-button' name='average' value='Average' type='submit'>
            </label>
        </div>
    </form>
EOD;

    //Header before actual table
    $optionValue = $showOptions[$showSelected];
    print <<<EOD
    <table class='results table-condensed'>
        <tbody>
            <tr>
                <td colspan='5'>&nbsp;</td>
            </tr>
            <tr>
                <td class='caption' colspan='5'>$eventNameSelected&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$optionValue</td>
            </tr>
            <tr>
                <td colspan='5'>&nbsp;</td>
            </tr>
            <tr>
                <th class='r'>Rank</th>
                <th>Person</th>
                <th class='R2'>Result</th>
                <th class='c'>Competition Week</th>
EOD;
    if (!$showSingles) {
        print "<th class='f'>Result Details</th>";
    } else {
        print "<th class='f'>&nbsp;</th>";
    }
    print "</tr>";

    // Find out how many persons or results to retrieve
    $showPersons = (strpos($showSelected, "Persons") == true);
    $limit = explode("+", $showSelected);
    if ($limit[0] == "All") {
        $limitString = "";
    } else {
        $limitString = "LIMIT ".$limit[0];
    }
    $yearsLimit = explode("+", $yearsSelected);
    $yearsLimitString = "";
    if ($yearsLimit[0] == 'through') {
        $yearsLimitString = "AND yearId < ".($yearsLimit[1] + 1);
    } elseif ($yearsLimit[0] == 'only') {
        $yearsLimitString = "AND yearId = ".$yearsLimit[1];
    }
    $query = get_query_for_best($eventSelected, $yearsLimitString, $limitString, $showPersons, $showSingles, "");
    $rank = 1;
    $prevString = "";
    $prevRank = $rank;
    $userList = array();
    $count = 0;
    while ($row = $query->fetch_assoc()) {
        ++$count;
        $week = $row['weekId'];
        $year = $row['yearId'];
        $userId = $row['userId'];
        $solve1 = $row['solve1'];
        $solve2 = $row['solve2'];
        $solve3 = $row['solve3'];
        $solve4 = $row['solve4'];
        $solve5 = $row['solve5'];
        $solves = [1 => $row['solve1'], $row['solve2'], $row['solve3'], $row['solve4'], $row['solve5']];
        if ($eventSelected != 13 && !is_movecount_scored($eventSelected)) {
            $output = get_single_output($eventSelected, $row['min'] / 100);
        } else {
            $output = get_single_output($eventSelected, $row['min']);
        }
        if ($eventSelected == 13) {
            $output .= " (".get_solve_details($eventSelected, 0, 0, 0, $row['min'], false).")";
        }
        if (!$showSingles) {
            if ($eventSelected == 36) {
                $output = pretty_score(get_average(get_solve_count($eventSelected, $year), $solves));
            } else {
                $output = pretty_number(get_average(get_solve_count($eventSelected, $year), $solves));
            }
        }
        if (is_movecount_scored($eventSelected) && is_average_event($eventSelected, $year) && !$showSingles) {
            $output = round_score(get_average(get_solve_count($eventSelected, $year), $solves));
        }
        if ($output == 'DNF') {
            continue;
        }
        if ($showPersons) {
            if (isset($userList[$userId])) {
                // This person has more than one of the same result; skip additional results - only keep one per person
                continue;
            } else {
                $userList[$userId] = $userId;
            }
        }
        if ($count % 2 == 0) {
            print "<tr class='e'>";
        } else {
            print "<tr>";
        }
        if ($output == $prevString) {
            print "<td></td>";
        } else {
            $prevString = $output;
            $prevRank = $rank;
            if ($limit[0] > 0 && $showPersons && $rank > $limit[0]) {
                print "<td></td><td></td><td></td><td></td><td></td>";
                break;
            } else {
                print "<td class='r'>$rank</td>";
            }
        }
        $userFullName = get_person_info($userId)['displayName'];
        print "<td class='userLink'><a href='showPersonalRecords.php?showRecords=$userId'>$userFullName</a></td>";
        print "<td class='R2'>$output</td>";
        $competitionName = get_competition_name($week, $year);
        print "<td class='c'><a href='showWeeks.php?week=$week&year=$year&selectEvent=".($eventSelected)."'>".$competitionName."</a></td>";
        if (!$showSingles) {
            print "<td class='f'>".get_solve_details($eventSelected, get_solve_count($eventSelected, $year), $solves, $row['result'], $row['multiBLD'], false)."</td>";
        } else {
            print "<td class='f'>&nbsp;</td>";
        }
        print "</tr>";
        ++$rank;
    }
    
    print <<<EOD
       </tbody>
    </table>
</div>
EOD;

<?php require_once 'newconnect.php'; ?>
<!DOCTYPE html>
<html lang='en-us'>
<head>
<title>Weekly Competition Rankings By Event (speedsolving.com)</title>
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
    require_once 'readEvents.php';
    if (is_admin()) {
        require_once 'modalDialogs.php';
    }
    
    $eventSelected = filter_input(INPUT_GET, 'eventId', FILTER_VALIDATE_INT);
    if (!$eventSelected) {
        $eventSelected = 1;
    }
    $eventNameSelected = $eventNames[$eventSelected];
    $options = array("options" => array("regexp" => "/[a-zA-Z\ 0-9]*/"));
    $yearsSelected = filter_input(INPUT_GET, 'years', FILTER_VALIDATE_REGEXP, $options);
    $showSelected = filter_input(INPUT_GET, 'show', FILTER_VALIDATE_REGEXP, $options);
    if (!$showSelected) {
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
    if (is_admin()) {
        create_modal();
    }
    
    // Event selection box
    print <<<EOD
    <form method='get'>
        <div class='form-group'>
            <label for='eventId'>Event:<br>
                <select class='drop' id='eventId' name='eventId'>
EOD;
    foreach ($eventNames as $eventId => $eventName) {
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
        if ($eventSelected != 13 && $eventSelected != 17 && $eventSelected != 32) {
            $output = get_single_output($eventSelected, $row['min'] / 100);
        } else {
            $output = get_single_output($eventSelected, $row['min']);
        }
        if ($eventSelected == 13) {
            $output .= " (".get_solve_details($eventSelected, 0, 0, 0, $row['min'], 0, false).")";
        }
        if (!$showSingles) {
            $output = pretty_number(get_average($solveCounts[$eventSelected], $solves));
        }
        if ($output == 'DNF') {
            continue;
        }
        if ($showPersons) {
            if ($userList[$userId] == $userId) {
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
        if (is_admin()) {
            print "<td class='R2 pointer' onclick='open_modal(\"editEntry.php\", $userId, $week, $year, $eventSelected)'>$output</td>";
        } else {
            print "<td class='R2'>$output</td>";
        }
        $competitionName = get_competition_name($week, $year);
        print "<td class='c'><a href='showWeeks.php?week=$week&year=$year&selectEvent=".($eventSelected + 2)."'>".$competitionName."</a></td>";
        if (!$showSingles) {
            print "<td class='f'>".get_solve_details($eventSelected, $solveCounts[$eventSelected], $solves, $row['result'], $row['multiBLD'], $row['fmcSolution'], false)."</td>";
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

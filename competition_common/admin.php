<?php

    if (!is_admin()) {
        // Protect against someone inadvertently allowing this code to be called by a non-admin.  This shouldn't ever execute.
        print "ERROR: Do not allow editor to be used unless admin privileges have already been verified!";
        exit;
    }
    
    $title = "Administration for Weekly Competition";

    $week = filter_input(INPUT_POST, 'week', FILTER_VALIDATE_INT);
    $year = filter_input(INPUT_POST, 'year', FILTER_VALIDATE_INT);
    $userid = filter_input(INPUT_POST, 'userid', FILTER_VALIDATE_INT);
    $event = filter_input(INPUT_POST, 'event', FILTER_VALIDATE_INT);
    $options = array("options" => array("regexp" => "/[a-zA-Z]*$/"));
    $tableName = filter_input(INPUT_POST, 'tableName', FILTER_VALIDATE_REGEXP, $options);
    $from = filter_input(INPUT_POST, 'from', FILTER_VALIDATE_INT);
    $to = filter_input(INPUT_POST, 'to', FILTER_VALIDATE_INT);
    $nameOptions = array("options" => array("regexp" => "/^[^,;}{]*$/"));
    $nameFrom = filter_input(INPUT_POST, "nameFrom", FILTER_VALIDATE_REGEXP, $nameOptions);
    $nameTo = filter_input(INPUT_POST, "nameTo", FILTER_VALIDATE_REGEXP, $nameOptions);
    
    if (!isset($week)) {
        $week = get_current_week();
    }
    if (!isset($year)) {
        $year = get_current_year();
    }
    if (!isset($userid)) {
        $userid = $currentUserId;
    }

    echo "<script>";
    echo "remaining = ".get_time_to_next_week().";";
    print <<<EOD
        var start = new Date().getTime() / 1000;
        var timer = setTimeout(update_time, "0");
        const add_zero = (x) => (x < 10 && x >= 0) ? "0"+x : x;
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
EOD;
    echo "<div class='header-text'>Weekly Competition ".get_competition_name(get_current_week(), get_current_year())."</div>";
    print <<<EOD
    <div class='header-text'>
        Time remaining: <span id='timeDays'></span> days, <span id='timeHours'></span>:<span id='timeMinutes'></span>:<span id='timeSeconds'></span>
    </div>
    <form action='admin.php' method='post'>
        <input class='button' name='reset' value='Reset' type='submit'>

        <div class='data-panel'>
        <div>week:<input name='week' id='week' value='$week'></div>
        <div>year:<input name='year' id='year' value='$year'></div>
        <div>
        <input class='button' name='calculateWeek' value='Calculate&nbsp;&nbsp;Week' type='submit'>
        <input class='button' name='calculateYear' value='Calculate Year' type='submit'>
        </div>
        <div>
        <input class='button' name='updateRecords' value='Update Records' type='submit'>
        <input class='button' name='getResults' value='Get Results' type='submit'>
        </div>
        <input class='button' name='testFMC' value='Test FMC' type='submit'>
        <input class='button' name='getYearlyResults' value='Get Yearly Results' type='submit'>
        </div>

        <div class='data-panel'>
        <div>table name:<input name='tableName' id='tableName' value='$tableName'></div>
        <input class='button' name='showTables' value='Show Table Definitions' type='submit'>
        <input class='button' name='showTable' value='Show Table' type='submit'>
        </div>

        <div class='data-panel'>
        <div>From:</div>
        <div>
        <span>Id:<input name='from' id='from' value='$from'></span>
        <span>Name:<input name='nameFrom' id='nameFrom' value='$nameFrom'></span>
        </div>
        <div>To:</div>
        <div>
        <span>Id:<input name='to' id='to' value='$to'></span>
        <span>Name:<input name='nameTo' id='nameTo' value='$nameTo'></span>
        </div>
        <div>
        <input class='button' name='testMerge' value='Test Merge' type='submit'>
        <input class='button' name='mergeUsers' value='Merge Users' type='submit'>
        <input class='button' name='changeUsername' value='Change Username' type='submit'>
        <input class='button' name='findUser' value='Find User' type='submit'>
        </div>
        </div>
    </form>
    <br>
EOD;
    
    if (isset($_POST['calculateWeek']) && isset($week) && isset($year)) {
        update_weeklyResults_calculated_fields($year, $week);
    } else if (isset($_POST['calculateYear']) && isset($year)) {
        update_weeklyResults_calculated_fields($year);
    } elseif (isset($_POST['updateRecords'])) {
        if (!isset($year) || $year < 2007) {
            echo "This function now requires specifying year!";
        } else {
            update_records($year);
        }
    } else if (isset($_POST['getResults'])) {
        echo "<textarea id='results' style='height:400px;width:100%'>";
        $weeklyResults = new WeeklyResults($week, $year);
        $eventScores = $weeklyResults->get_score_list();
        $winners = array();
        $index = 0;
        foreach ($eventScores as $key=>$value) {
            $winners[$index] = get_person_info($key)['username'];
            ++$index;
        }
        echo "Results for week $week: Congratulations to ".$winners[0].", ".$winners[1].", and ".$winners[2]."!\n";
        $weeklyResults->print_bbcode_results();
        echo "</textarea>";
    } elseif (isset($_POST['testFMC'])) {
        test_fewest_moves_results($week, $year);
    } else if (isset($_POST['getYearlyResults'])) {
        echo "<textarea id='results' style='height:400px;width:100%'></textarea>";
        // start of Javascript code to calculate yearly results
        echo <<<EOD
<script>
// pretty numbers
function pretty_number(uglyNumber)
{
    if (uglyNumber == 8888 || uglyNumber == 'DNF') {return 'DNF';}
    if (uglyNumber == 9999 || uglyNumber == 'DNS' || uglyNumber == '0') {return 'DNS';}
    var seconds = parseFloat(uglyNumber);
    if (seconds >= 60){
        var minutes = 0;
        if (seconds > 1000000) {return uglyNumber;}
        while (seconds >= 60) {
            minutes++;
            seconds = seconds - 60;
        }
        var prettyNumber = "";
        if (seconds < 10) {
            prettyNumber = minutes + ":0" + seconds.toFixed(2);
        } else {
            prettyNumber = minutes + ":" + seconds.toFixed(2);
        }
        return prettyNumber;
    }
    return seconds.toFixed(2);
}

function ugly_number(prettyNumber)
{
    if (prettyNumber == "DNS" || prettyNumber == 9999 || prettyNumber == 0 || prettyNumber == "") {
        return 9999;
    } else if (prettyNumber == "DNF" || prettyNumber == 8888) {
        return 8888;
    }
    if (typeof prettyNumber !== "string") {
        return prettyNumber;
    }
    var numbers = prettyNumber.split(":");
    if (numbers.length == 2) {
        return parseFloat(numbers[0]) * 60 + parseFloat(numbers[1]);
    }

    return prettyNumber;
}

function output_points(value, index, array) {
    if (prev != value[1]) {
        rank = count;
        prev = value[1];
        if (rank > 3 && !spoilerDone) {
	    overallResults += "[spoiler]\\n";
            spoilerDone = true;
        }
    }
    overallResults += rank + ". [COLOR=Blue]" + value[1] + "[/COLOR] " + usernames[value[0]] + "\\n";
    ++count;
};

function output_result(value, index, array) {
    if (prev != value[1]) {
        rank = count;
        prev = value[1];
        if (rank > 3 && !spoilerDone) {
	    overallResults += "[spoiler]\\n";
            spoilerDone = true;
        }
    }
    overallResults += rank + ". [COLOR=Blue]" + pretty_number(value[1]) + "[/COLOR] " + usernames[value[0]] + "\\n";
    ++count;
};

var userIds = new Array();
var totalScores = new Array();
var scores = new Array();
var results = new Array();

EOD;
        // Insert code containing data from database needed to calculate yearly results
        $result = $mysqli->query("select distinct userlist.id, userlist.username from userlist inner join weeklyResults on weeklyResults.userId = userlist.id where weeklyResults.yearId = $year limit 10000");
        while ($row = $result->fetch_assoc()) {
            $id = $row['id'];
            $username = $row['username'];
            $usernames[$id] = $username;
        }
        echo "var usernames = ".json_encode($usernames).";\n";
        for ($week = 1; $week <= 53; ++$week) {
            $weeklyResults = new WeeklyResults($week, $year);
            $weeklyResults->get_result_info($week);
        }
        global $events;
        $activeEvents = array();
        foreach ($events as $eventId => $eventName) {
            if (is_active_event($eventId, 1, $year)) {
                $activeEvents[$eventId] = $eventName;
            }
        }
        echo "var events = ".json_encode($activeEvents).";\n";
        // Add the rest of the Javascript code to calculate the results
        echo <<<EOD
var overallResults = "";
var totals = new Array();
var eventScores = new Array();
var eventResults = new Array();
for (week = 1; week <= 53; ++week) {
    for (user in userIds[week]) {
        if (totals[user] === undefined) {
            totals[user] = 0;
        }
        totals[user] += totalScores[week][user];
        for (event in events) {
            if (scores[week][event] == undefined) {
                // Not a valid event this year, or no one competed
                // Should probably generate events above from php without invalid events instead of this.
                continue;
            }
            if (scores[week][event][user] !== undefined) {
                if (eventScores[event] == undefined) {
                    eventScores[event] = new Array();
                }
                if (eventScores[event][user] === undefined) {
                    eventScores[event][user] = 0;
                }
                eventScores[event][user] += scores[week][event][user];
                var result = results[week][event][user];
                result = ugly_number(result);
                if (event == 13 && (result == 8888 || result == 9999)) {
                    result = -1;
                }
                if (eventResults[event] == undefined) {
                    eventResults[event] = new Array();
                }
                if (eventResults[event][user] == undefined) {
                    eventResults[event][user] = new Array();
                }
                eventResults[event][user].push(parseFloat(result));
            }
        }
    }
}

// Convert totals to a sorted array of pairs: (userid, score)
var sortedTotals = new Array();
totals.forEach(function(value, index, array) {
    sortedTotals.push([index, value]);
});
sortedTotals.sort(function(a, b) { return b[1] - a[1]; });

// Now output total point rankings for all events
var count = 1;
var prev = 0;
var rank = 0;
var spoilerDone = false;
overallResults += "[B]Total points all events[/B] (" + sortedTotals.length + ")\\n\\n";
sortedTotals.forEach(output_points);
overallResults += "[/spoiler]\\n";

// Output point rankings for each individual event
for (event in events) {
    if (eventScores[event] == undefined) {
	// Not a valid event this year, or no one competed
	// Should probably generate events above from php without invalid events instead of this.
	continue;
    }
    var count = 1;
    var prev = 0;
    var rank = 0;
    spoilerDone = false;

    // Convert totals to a sorted array of pairs: (userid, score)
    var sortedEventScores = new Array();
    eventScores[event].forEach(function(value, index, array) {
        sortedEventScores.push([index, value]);
    });
    sortedEventScores.sort(function(a, b) { return b[1] - a[1]; });

    overallResults += "[B]" + events[event] + "[/B] (" + sortedEventScores.length + ")\\n\\n";
    sortedEventScores.forEach(output_points);
    overallResults += "[/spoiler]\\n";
}

// Means of best 5 results
for (event in events) {
    if (eventScores[event] == undefined) {
	// Not a valid event this year, or no one competed
	// Should probably generate events above from php without invalid events instead of this.
	continue;
    }
    var eventMeanRank = new Array();
    eventResults[event].forEach(function(value, index, array) {
        if (value.length >= 5) {
            newValue = value;
            if (event == 13) {
                newValue.sort(function(a, b) { return b - a; });
            } else {
                newValue.sort(function(a, b) { return a - b; });
            }
            var mean = 0;
            var i = 0;
            var valid = true;
            for (result of newValue) {
                if (++i > 5) {
                    break;
                } else if (result == 8888 || result == 9999 || result < 0) {
                    valid = false;
                    break;
                }
                mean += result;
            }
            if (valid) {
                // valid mean
                eventMeanRank[index] = mean / 5;
            }
        }
    });
    var sortedEventMeanRank = new Array();
    eventMeanRank.forEach(function(value, index, array) {
        sortedEventMeanRank.push([index, value]);
    });
    if (event == 13) {
        sortedEventMeanRank.sort(function(a, b) { return b[1] - a[1]; });
    } else {
        sortedEventMeanRank.sort(function(a, b) { return a[1] - b[1]; });
    }
    count = 1;
    prev = 0;
    rank = 0;
    spoilerDone = false;
    overallResults += "[B]" + events[event] + "[/B] (" + sortedEventMeanRank.length + ")\\n\\n";
    sortedEventMeanRank.forEach(output_result);
    overallResults += "[/spoiler]\\n";
}

document.getElementById("results").innerHTML = overallResults;

</script>
EOD;
    } else if (isset($_POST['showTables'])) {
        echo "<textarea id='results' style='height:400px;width:100%'>";
        show_tables();
        echo "</textarea>";
    } else if (isset($_POST['showTable']) && ($tableName !== '')) {
        echo "<textarea id='results' style='height:400px;width:100%'>";
        if ($year == "") {
            show_specific_results($tableName, "");
        } else {
            show_specific_results($tableName, "where yearId = $year and weekId = $week");
        }
        echo "</textarea>";
    } else if (isset($_POST['testMerge']) || isset($_POST['mergeUsers'])) {
        $fromResult = $mysqli->query("SELECT * FROM userlist WHERE id = $from AND username = '$nameFrom'");
        $toResult = $mysqli->query("SELECT * FROM userlist WHERE id = $to AND username = '$nameTo'");
        if ($from <= $to) {
            echo "From account should be more recent than to account. Database unchanged.";
        } else if ($fromResult->num_rows != 1 || $toResult->num_rows != 1) {
            echo "Mismatched usernames and userids! Database unchanged.";
        } else {
            $duplicateResults = $mysqli->query("SELECT weekId, yearId, eventId, count(*) FROM weeklyResults WHERE (userId = $from OR userId = $to) GROUP BY weekId, yearId, eventId HAVING count(*) > 1");
            if ($duplicateResults->num_rows > 0) {
                echo "Duplicate results for users $nameFrom and $nameTo! Resolve by hand before continuing. Database unchanged.";
            } else {
                echo "Merging $nameFrom ($from) into $nameTo ($to).<br>";
                $updateConditions = "SET userId = $to WHERE userId = $from";
                echo "UPDATE weeklyResults $updateConditions\n";
                echo "UPDATE weeklyFmcSolves $updateConditions\n";
                echo "UPDATE discussion $updateConditions\n";
                echo "UPDATE issue $updateConditions\n";
                echo "UPDATE quarantine $updateConditions\n";
                echo "UPDATE records $updateConditions\n";
                echo "UPDATE userlist SET username = $nameFrom WHERE id = $to<br>";
                if (isset($_POST['mergeUsers'])) {
                    $mysqli->query("UPDATE weeklyResults $updateConditions");
                    $mysqli->query("UPDATE weeklyFmcSolves $updateConditions");
                    $mysqli->query("UPDATE discussion $updateConditions");
                    $mysqli->query("UPDATE issue $updateConditions");
                    $mysqli->query("UPDATE quarantine $updateConditions");
                    $mysqli->query("UPDATE records $updateConditions");
                    $mysqli->query("UPDATE userlist SET username = '$nameFrom' WHERE id = $to");
                    echo "Done!!";
                }
            }
        }
    } else if (isset($_POST['changeUsername'])) {
        $fromResult = $mysqli->query("SELECT * FROM userlist WHERE id = $from AND username = '$nameFrom'");
        if ($from != $to) {
            echo "From userId and to userId should match for this operation. Database unchanged.";
        } else if ($fromResult->num_rows != 1) {
            echo "Mismatched username and userId! Database unchanged.";
        } else if (!isset($nameTo) || $nameTo == "") {
            echo "No new name specified. Database unchanged.";
        } else {
            echo "UPDATE userlist SET username = '$nameTo' WHERE id = $from<br>";
            $mysqli->query("UPDATE userlist SET username = '$nameTo' WHERE id = $from");
        }
    } else if (isset($_POST['findUser'])) {
        if (isset($from) && $from != "") {
            $queryResults = $mysqli->query("SELECT id, username FROM userlist WHERE id = $from");
            while ($results = $queryResults->fetch_array()) {
                $id = $results['id'];
                $username = $results['username'];
                echo "$id: $username<br>";
            }
        }
        if (isset($nameFrom) && $nameFrom != "") {
            $queryResults = $mysqli->query("SELECT id, username FROM userlist WHERE username = '$nameFrom'");
            while ($results = $queryResults->fetch_array()) {
                $id = $results['id'];
                $username = $results['username'];
                echo "$id: $username<br>";
            }
        }
    }
    
    function show_tables()
    {
        global $mysqli;
        $result = $mysqli->query("SHOW TABLES");
        while ($row = $result->fetch_row()) {
            foreach ($row as $name => $value) {
                echo $value;
                $table = $value;
            }
            echo "\n";
            $result2 = $mysqli->query("DESCRIBE ".$table);
            while ($row2 = $result2->fetch_assoc()) {
                foreach ($row2 as $name => $value) {
                    echo "\t".$name."=".$value;
                }
                echo "\n";
            }
            echo "\n";
            $result2 = $mysqli->query("SHOW INDEX FROM ".$table);
            while ($row2 = $result2->fetch_row()) {
                foreach ($row2 as $name => $value) {
                    echo $name."\t".$value."\n";
                }
                echo "\n";
            }
            echo "\n";
        }
    }

    function create_inserts_for_data($row, $tablename)
    {
        global $mysqli;
        print "INSERT INTO $tablename (";
        $first = true;
        foreach ($row as $name => $value) {
            if ($first) {
                $first = false;
            } else {
                print ", ";
            }
            print $name;
        }
        print ") VALUES (";
        $first = true;
        foreach ($row as $name => $value) {
            if ($first) {
                $first = false;
            } else {
                print ", ";
            }
            if (   strncmp($name, 'comment', 7) === 0
                || strncmp($name, 'solution', 8) === 0
                || strncmp($name, 'modTime', 7) === 0
                || strncmp($name, 'scramble', 8) === 0
                || strncmp($name, 'status', 6) === 0
                || strncmp($name, 'description', 11) === 0
                || strncmp($name, 'entry', 5) === 0) {
                $value = "'".str_ireplace("\\r\\n", "", $mysqli->real_escape_string($value))."'";
            }
            print $value;
        }
        print ");\n";
    }

    function show_specific_results($tablename, $where)
    {
        global $mysqli;
        echo "select * from $tablename $where\n";
        $result = $mysqli->query("select * from $tablename $where");
        while ($row = $result->fetch_assoc()) {
            create_inserts_for_data($row, $tablename);
        }
    }

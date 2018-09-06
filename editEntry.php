<?php require_once 'newconnect.php'; ?>
<!DOCTYPE html>
<html>
<head>
<title>Weekly Competition Entry Editor (speedsolving.com)</title>
<link rel='stylesheet' href='style.css' type='text/css' />
<link rel="stylesheet" href="cubing-icons.css">
<meta name='viewport' content='width=device-width, initial-scale=1'>
<meta charset="UTF-8">
</head>

<?php
    require_once 'statFunctions.php';
    require_once 'readEvents.php';

    if (!is_admin()) {
        // Protect against someone inadvertently allowing this code to be called by a non-admin.  This shouldn't ever execute.
        print "ERROR: Do not allow editor to be used unless admin privileges have already been verified!";
        exit;
    }
    
    $operation = filter_input(INPUT_POST, 'operation', FILTER_VALIDATE_INT);
    if (!$operation) {
        $operation = 0;
        $week = filter_input(INPUT_GET, 'week', FILTER_VALIDATE_INT);
        $year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);
        $user = filter_input(INPUT_GET, 'user', FILTER_VALIDATE_INT);
        $eventId = filter_input(INPUT_GET, 'eventId', FILTER_VALIDATE_INT);
    } else {
        $week = filter_input(INPUT_POST, 'week', FILTER_VALIDATE_INT);
        $year = filter_input(INPUT_POST, 'year', FILTER_VALIDATE_INT);
        $user = filter_input(INPUT_POST, 'user', FILTER_VALIDATE_INT);
        $eventId = filter_input(INPUT_POST, 'eventId', FILTER_VALIDATE_INT);
    }

    $query = $mysqli->query("SELECT * FROM weeklyResults WHERE weekId = $week AND yearId = $year AND userId = $user AND eventId = $eventId");
    $row = $query->fetch_assoc();
    $personInfo = get_person_info($user);
    $userFullName = $personInfo['firstName']." ".$personInfo['lastName'];
    $titleText = $userFullName."(".$user."): ".$eventNames[$eventId].", Week ".$year."-".$week;
    $solve1PreviousRaw = $row['solve1'];
    $solve2PreviousRaw = $row['solve2'];
    $solve3PreviousRaw = $row['solve3'];
    $solve4PreviousRaw = $row['solve4'];
    $solve5PreviousRaw = $row['solve5'];
    $resultPreviousRaw = $row['result'];
    $solve1Previous = pretty_number($row['solve1']);
    $solve2Previous = pretty_number($row['solve2']);
    $solve3Previous = pretty_number($row['solve3']);
    $solve4Previous = pretty_number($row['solve4']);
    $solve5Previous = pretty_number($row['solve5']);
    $resultPrevious = pretty_number($row['result']);
    $commentPrevious = stripslashes($row['comment']);
    $fmcSolutionPrevious = stripslashes($row['fmcSolution']);
    $multiBLDPrevious = $row['multiBLD'];
    
    if ($operation == 0) {
        $solve1 = $solve1Previous;
        $solve2 = $solve2Previous;
        $solve3 = $solve3Previous;
        $solve4 = $solve4Previous;
        $solve5 = $solve5Previous;
        $result = $resultPrevious;
        $comment = $commentPrevious;
        $fmcSolution = $fmcSolutionPrevious;
        $multiBLD = $multiBLDPrevious;
    } else {
        // Change data in specific entry
        $options = array("options" => array("regexp" => "/[0-9]*\:*[0-9.]*|DNF/"));
        $solve1 = ugly_number(filter_input(INPUT_POST, 'solve1', FILTER_VALIDATE_REGEXP, $options));
        $solve2 = ugly_number(filter_input(INPUT_POST, 'solve2', FILTER_VALIDATE_REGEXP, $options));
        $solve3 = ugly_number(filter_input(INPUT_POST, 'solve3', FILTER_VALIDATE_REGEXP, $options));
        $solve4 = ugly_number(filter_input(INPUT_POST, 'solve4', FILTER_VALIDATE_REGEXP, $options));
        $solve5 = ugly_number(filter_input(INPUT_POST, 'solve5', FILTER_VALIDATE_REGEXP, $options));
        $multiBLD = filter_input(INPUT_POST, 'multiBLD', FILTER_VALIDATE_INT);
        $options = array("options" => array("regexp" => "/[a-zA-Z\ \n]*/"));
        $comment = filter_input(INPUT_POST, 'comment', FILTER_VALIDATE_REGEXP, $options);
        $fmcSolution = filter_input(INPUT_POST, 'fmcSolution', FILTER_VALIDATE_REGEXP, $options);
        if ($eventId == 13) {
            $result = number_to_MBLD($multiBLD)[0];
        } elseif ($eventId == 17) {
            $result = count_moves($fmcSolution);
        } elseif ($solveCounts[$eventId] == 3) {
            // best of 3 event
            $result = get_best_result($solveCounts[$eventId], array(1=>$solve1, $solve2, $solve3));
        } else {
            $result = get_average($solveCounts[$eventId], array(1=>$solve1, $solve2, $solve3, $solve4, $solve5));
        }
        // Reset values for solves that don't belong to this event and shouldn't change
        if ($solve1Old == 0 && $solve1 == 9999) {
            $solve1 = 0;
        }
        if ($solve2Old == 0 && $solve2 == 9999) {
            $solve2 = 0;
        }
        if ($solve3Old == 0 && $solve3 == 9999) {
            $solve3 = 0;
        }
        if ($solve4Old == 0 && $solve4 == 9999) {
            $solve4 = 0;
        }
        if ($solve5Old == 0 && $solve5 == 9999) {
            $solve5 = 0;
        }
        if ($solveCounts[$eventId] == 1) {
            if ($solve2 == 9999) {
                $solve2 = 0;
            }
            if ($solve3 == 9999) {
                $solve3 = 0;
            }
        }
        if ($solveCounts[$eventId] <= 3) {
            if ($solve4 == 9999) {
                $solve4 = 0;
            }
            if ($solve5 == 9999) {
                $solve5 = 0;
            }
        }
    }

    print <<<EOD
    <body>
        <form method='post'>
            <div id='dialogTitle'><h1>$titleText</h1></div>
            <div>Solve 1:<input name='solve1' id='solve1' value='$solve1'></div>
            <div>Solve 2:<input name='solve2' id='solve2' value='$solve2'></div>
            <div>Solve 3:<input name='solve3' id='solve3' value='$solve3'></div>
            <div>Solve 4:<input name='solve4' id='solve4' value='$solve4'></div>
            <div>Solve 5:<input name='solve5' id='solve5' value='$solve5'></div>
            <div>Result: $result</div>
            <div>multiBLD:<input name='multiBLD' id='multiBLD' value='$multiBLD'></div>
            Comment:<textarea class='editor-comment' type='text' name='comment' id='comment'>$comment</textarea>&nbsp;&nbsp;&nbsp;
            FMC Solution:<textarea class='editor-comment' type='text' name='fmcSolution' id='fmcSolution'>$fmcSolution</textarea><br>
            <input type='hidden' name='week' id='week' value='$week'>
            <input type='hidden' name='year' id='year' value='$year'>
            <input type='hidden' name='user' id='user' value='$user'>
            <input type='hidden' name='eventId' id='eventId' value=$eventId>
EOD;
    if ($operation == 1) {
        print "<input type='hidden' name='operation' id='operation' value='2'>";
        print "<input class='button' name='submit' value='Submit Statements' type='submit'>";
    } elseif ($operation == 2) {
        print "<b>Submitted!!!</b><br>";
    } else {
        print "<input type='hidden' name='operation' id='operation' value='1'>";
        print "<input class='button' name='submit' value='Create SQL Statements' type='submit'>";
    }
    print "</form>";
EOD;

    $comment = str_ireplace("\\r\\n","<br />", $mysqli->real_escape_string($comment));
    $commentPrevious = str_ireplace("\\r\\n","<br />", $mysqli->real_escape_string($commentPrevious));
    $fmcSolution = str_ireplace("\\r\\n","<br />", $mysqli->real_escape_string($fmcSolution));
    $fmcSolutionPrevious = str_ireplace("\\r\\n","<br />", $mysqli->real_escape_string($fmcSolutionPrevious));
    $query = "UPDATE weeklyResults SET solve1 = $solve1, solve2 = $solve2, solve3 = $solve3, solve4 = $solve4, solve5 = $solve5,
                                       result = $result, fmcSolution = '$fmcSolution', multiBLD = $multiBLD, comment = '$comment'
                                   WHERE userId = $user AND yearId = $year AND weekId = $week AND eventId = $eventId";
    $error = false;
    if ($week > 0 && $week < 52 && $year >= 2012 && $year < 2018 && $user > -1 && $user < 2000 && eventId > 0 && eventId < 31) {
        print "Value out of range<br>";
        $error = true;
    }
    $modUser = $_SESSION['logged_in'];
    $query2 = "INSERT INTO weeklyCorrections
               (weekId, yearId, userId, eventId,
                resultOld, commentOld, solve1Old, solve2Old, solve3Old, solve4Old, solve5Old, fmcSolutionOld, multiBLDOld,
                resultNew, commentNew, solve1New, solve2New, solve3New, solve4New, solve5New, fmcSolutionNew, multiBLDNew,
                modUser, modTime) VALUES
               ($week, $year, $user, $eventId,
                $resultPreviousRaw, '$commentPrevious', $solve1PreviousRaw, $solve2PreviousRaw, $solve3PreviousRaw, $solve4PreviousRaw, $solve5PreviousRaw,
                '$fmcSolutionPrevious', $multiBLDPrevious,
                $result, '$comment', $solve1, $solve2, $solve3, $solve4, $solve5,
                '$fmcSolution', $multiBLD,
                $modUser, NOW())";

    if ($operation == 1) {
        print $query."<br>";
        print $query2."<br>";
    } elseif ($operation == 2) {
        $mysqli->query($query);
        $mysqli->query($query2);
        $query = $mysqli->query("SELECT * FROM weeklyResults WHERE userId = $user AND yearId = $year AND weekId = $week AND eventId = $eventId");
        while ($row = $query->fetch_assoc()) {
            print "weeklyResults: ";
            $first = true;
            foreach ($row as $name => $value) {
                if ($first) {
                    $first = false;
                } else {
                    print ", ";
                }
                print $name."=".$value;
            }
            print "<br>";
        }
        $query2 = $mysqli->query("SELECT * FROM weeklyCorrections WHERE userId = $user AND yearId = $year AND weekId = $week AND eventId = $eventId");
        while ($row = $query2->fetch_assoc()) {
            print "weeklyCorrections: ";
            $first = true;
            foreach ($row as $name => $value) {
                if ($first) {
                    $first = false;
                } else {
                    print ", ";
                }
               print $name."=".$value;
            }
            print "<br>";
        }
    }

    print "</body>";
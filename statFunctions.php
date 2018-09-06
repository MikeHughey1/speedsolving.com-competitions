<?php
    require_once 'fmcSolve.php';

    function is_BLD($eventId)
    {
        if ($eventId >= 7 && $eventId <= 12) {
            return true;
        }
        return false;
    }
    
    function is_average_event($eventId)
    {
        if ($eventId <= 6 || ($eventId >= 14 && $eventId <= 16) || ($eventId >= 22 && $eventId <= 27) || ($eventId >= 29 && $eventId <= 31)) {
            return true;
        }
        return false;
    }
    
    function is_active_event($eventId, $week, $year)
    {
        switch ($eventId) {
            case 5:
            case 6:
                if ($year < 2008 || ($year === 2008 & $week < 27)) { return false; } break;  // 6x6x6 and 7x7x7 speed valid only starting week 27 of 2008
            case 11:
            case 12:
                if ($year < 2008 || ($year === 2008 & $week < 27) || $year === 2009 || $year === 2010) { return false; } break;  // 6x6x6 and 7x7x7 BLD valid starting week 27 to the end of 2008, and then from 2011 to present
            case 13:
            case 15:
            case 16:
                if ($year < 2008) { return false; } break;  // MultiBLD, With Feet, and Match the scramble - valid from 2008 to present
            case 17:
                if ($year === 2007 && $week < 24) { return false; } break;  // Fewest Moves valid only starting week 34 of 2007
            case 18:
                if ($year < 2008 || ($year === 2008 && $week < 13)) { return false; } break;  // 2-4 relay valid only starting week 13 of 2008
            case 19:
                if ($year === 2007 && $week < 34) { return false; } break;  // 2-5 relay valid only starting week 34 of 2007
            case 20:
            case 21:
                if ($year < 2017) { return false; } break;  // 2-6 and 2-7 relays valid from 2017 to present
            case 22:
                if ($year < 2008) { return false; } break;  // Clock - valid from 2008 to present
            case 23:
            case 24:
                if ($year === 2007 && $week < 27) { return false; } break;  // Megaminx - valid only starting week 27 of 2007
            case 25:
                if ($year === 2007 && $week < 20) { return false; } break;  // Square-1 - valid only starting week 20 of 2007
            case 26:
                if ($year < 2011) { return false; } break;  // Skewb valid from 2011 to present
            case 27:
            case 28:
                if ($year < 2017) { return false; } break;  // kilominx and mini Guildford valid from 2017 to present
            case 29:
            case 30:
                if ($year > 2016) { return false; } break;  // Magics - valid only through 2016
            case 31:
            case 32:
                if ($year !== 2009) { return false; } break;  // Snake and 4x4x4 Fewest Moves - valid only in the year 2009
        }

        return true;
    }
    
    function get_competition_name($week, $year)
    {
        return $year."-".str_pad($week, 2, '0', STR_PAD_LEFT);
    }
    
    function get_weekly_kinch_rankings($week, $year)
    {
        global $mysqli;
        
        $kinchTotals = array();
        $userNames = array();
        $results = $mysqli->query("select distinct userId from weeklyResults where weekId=".$week." and yearId=".$year);
        while ($row = $results->fetch_row()) {
            $userId = $row[0];
            $kinchTotals[$userId] = get_weekly_user_kinch_score($userId, $week, $year);
        }
        arsort($kinchTotals);
        return $kinchTotals;
    }
    
    function get_place_string($rank) {
        switch ($rank % 10) {
            case 1: $place = "st"; break;
            case 2: $place = "nd"; break;
            case 3: $place = "rd"; break;
            default: $place = "th";
        }
        switch ($rank % 100) {
            case 11: case 12: case 13: $place = "th";
        }
        return $rank.$place;
    }
    
    function get_weekly_user_kinch_score($user, $week, $year)
    {
        $userRecords = get_user_weekly_results($user, $week, $year);
        $overallRecords = get_overall_records();
        $score = 0;
        $count = 0;
        foreach ($userRecords as $eventId => $result) {
            if (is_active_event($eventId, $week, $year)) {
                $score += calculate_kinch_event_score($overallRecords[$eventId], $result, $eventId);
                ++$count;
            }
        }
        return $score / $count;
    }

    function get_user_weekly_results($user, $week, $year)
    {
        $records = array();

        global $mysqli;
        global $events;
        foreach ($events as $eventId) {
            if ($eventId == '13') {
                $result = $mysqli->query("select multiBLD from weeklyResults where userId=".$user." and eventId=13 and weekId=".$week." and yearId=".$year);
            } else {
                $result = $mysqli->query("select result from weeklyResults where userId=".$user." and eventId=".$eventId." and weekId=".$week." and yearId=".$year);
            }
            $row = $result->fetch_row();
            $records[$eventId] = $row[0];
        }

        return $records;
    }

    function get_overall_user_kinch_scores($user)
    {
        $userRecords = get_user_records($user);
        $overallRecords = get_overall_records();
        $score = 0;
        $count = 0;
        foreach ($userRecords as $eventId => $result) {
            $scores[$eventId] = calculate_kinch_event_score($overallRecords[$eventId], $result, $eventId);
            if (is_active_event($eventId, get_current_week(), get_current_year())) {
                $score += $scores[$eventId];
                ++$count;
            }
        }
        $scores[0] = $score / $count;
        return $scores;
    }

    function get_user_records($user)
    {
        static $records = array();
        global $mysqli;
        global $events;
        
        if (count($records) === 0) {
            foreach ($events as $eventId) {
                if ($eventId == '13') {
                    $result = $mysqli->query("select min(multiBLD) from weeklyResults where userId=".$user." and eventid=13");
                } else {
                    $result = $mysqli->query("select min(result) from weeklyResults where userId=".$user." and eventid=".$eventId);
                }
                $row = $result->fetch_row();
                $records[$eventId] = $row[0];
            }
        }
        
        return $records;
    }
    
    function calculate_kinch_event_score($record, $result, $eventId)
    {
        if ($result == '8888' || $result == 0) {
            // No score for DNF
            return 0;
        }
        
        if ($eventId == 13) {
            $mbld = number_to_MBLD($result);
            $mbldRecord = number_to_MBLD($record);
            if (is_numeric($mbld[0]) && is_numeric($mbld[1])) {
                return (($mbld[0] + (3600 - $mbld[1]) / 3600) / ($mbldRecord[0] + (3600 - $mbldRecord[1]) / 3600)) * 100;
            } else {
                return 0;
            }
        }
        
        return ($record / $result) * 100;
    }
 
    function get_overall_records()
    {
        static $records = array();
        global $mysqli;
        global $events;
        
        if (count($records) > 0) {
            return $records;
        }
        
        foreach ($events as $eventId) {
            if ($eventId == '13') {
                $result = $mysqli->query("select min(multiBLD) from weeklyResults where eventid=13");
            } else {
                $result = $mysqli->query("select min(result) from weeklyResults where eventid=".$eventId);
            }
            $row = $result->fetch_row();
            $records[$eventId] = $row[0];
        }
        
        return $records;
    }

    function get_all_person_info($userId)
    {
        global $mysqli;
        $query = $mysqli->query("SELECT id, firstName, lastName, username FROM userlist WHERE id='$userId'");
        return $query->fetch_assoc();
    }
    
    function get_person_info($userId)
    {
        static $firstNames = array();
        static $lastNames = array();
        static $userNames = array();
        static $displayNames = array();
        static $emails = array();
        static $hideNames = array();
        global $mysqli;

        if (!array_key_exists($userId, $userNames)) {
            $query = $mysqli->query("SELECT id, firstName, lastName, username, email, hideNames FROM userlist WHERE id='$userId'");
            while ($row = $query->fetch_assoc()) {
                $firstNames[$row['id']] = $row['firstName'];
                $lastNames[$row['id']] = $row['lastName'];
                $userNames[$row['id']] = $row['username'];
                $emails[$row['id']] = $row['email'];
                $hideNames[$row['id']] = $row['hideNames'];
                if ($hideNames[$row['id']] == 1) {
                    $displayNames[$row['id']] = $row['username'];
                } elseif ($row['firstName'] === 'Forum') {
                    $displayNames[$row['id']] = $row['username']." (Forum)";
                } else {
                    $displayNames[$row['id']] = $row['firstName']." ".$row['lastName'];
                }
            }
        }

        $personInfo = array('firstName' => $firstNames[$userId], 'lastName' => $lastNames[$userId],
                            'username' => $userNames[$userId], 'displayName' => $displayNames[$userId], 'email' => $emails[$userId], 'hideNames' => $hideNames[$userId]);
        return $personInfo;
    }

    function get_event_info($eventId)
    {
        static $eventNames = array();
        static $solveCounts = array();
        global $mysqli;

        if (!array_key_exists($eventId, $eventNames)) {
            // Get all the events into the static arrays above with a single SELECT, and then just look inside the array for any future requests
            $query = $mysqli->query("SELECT id, eventName, weekly FROM events");
            while ($row = $query->fetch_assoc()) {
                $currentId = $row['id'];
                $eventNames[$currentId] = $row['eventName'];
                $solveCounts[$currentId] = $row['weekly'];
                if ($row['weekly'] > 5) {
                    // multiBLD
                    $solveCounts[$currentId] = 1;
                }
            }
        }

        $eventInfo = array('eventName' => $eventNames[$eventId], 'solveCount' => $solveCounts[$eventId]);
        return $eventInfo;
    }
    
    function round_score($score)
    {
        return number_format($score, 2, '.', '');
    }

    // pretty numbers
    function pretty_number($uglyNumber)
    {
        if ($uglyNumber == 8888 || $uglyNumber == 'DNF') {return 'DNF';}
        if ($uglyNumber == 9999 || $uglyNumber == 'DNS' || $uglyNumber == '0') {return 'DNS';}
        $seconds = floatval($uglyNumber);
        if ($seconds >= 60){
            $minutes = 0;
            if ($seconds > 1000000) {return $uglyNumber;}
            while ($seconds >= 60) {
                $minutes++;
                $seconds = $seconds - 60;
            }
            if ($seconds < 10) {
                $prettyNumber = $minutes . ":0" . number_format($seconds, 2, '.', '');
            } else {
                $prettyNumber = $minutes . ":" . number_format($seconds, 2, '.', '');
            }
            return $prettyNumber;
        }
        return number_format($seconds, 2, '.', '');
    }
    
    // ugly number
    function ugly_number($prettyNumber)
    {
        if ($prettyNumber == "DNS" || $prettyNumber == 9999 || $prettyNumber == '0' || $prettyNumber == '') {
            return 9999;
        }
        if ($prettyNumber == "DNF" || $prettyNumber == 8888) {
            return 8888;
        }
        $prettyNumberModified = str_ireplace("-", "", $prettyNumber);
        $prettyNumbers = explode(":", $prettyNumberModified);
        if (count($prettyNumbers) == 2){
            $uglyNumber = (floatval($prettyNumbers['0']) * 60) + floatval(str_ireplace(",", ".", $prettyNumbers['1']));
        } else {
            $uglyNumber = floatval(str_ireplace(",", ".", $prettyNumbers['0']));
        }
        return $uglyNumber;
    }

    // decode Multi BLD
    function number_to_MBLD($result)
    {
        if ($result == 999999999 || $result == 98999900) { // dnf 
            return array("DNF", 0, 0, 0);
        } elseif ($result == 0) { // no entry yet
            return array("", "", "", "");
        }
        $points = 99 - (floor($result / 1000000));
        $time = (floor($result / 100))-((99 - $points) * 10000);
        $solved = $result - ((99 - $points) * 1000000) - ($time * 100);
        $attempted = 2 * $solved - $points;
        if ($points < 0 || ($points == 0 && $attempted == 2)) {
            $points = "DNF";
        }
        $return = array($points, $time, $solved, $attempted);
        return $return;
    }

    function MBLD_to_number($solved, $attempted, $time) {
        $timeModified = str_ireplace("-", "", $time);
        $times = explode(":", $timeModified);
        if (count($times) == 2) {
            $time = (floatval($times['0']) * 60) + round(floatval($times['1']));
        } else {  // Only seconds - someone was very fast!
            $time = round(floatval($times['0']));
        }

        if (is_numeric($time) && is_numeric($solved) && is_numeric($attempted)) {
            $result = (99 - 2 * $solved + $attempted) * 1000000 + ($time * 100) +($solved);
        } else {
            $result = 0;
        }
        return $result;
    }

    // Count moves for FMC
    function count_moves($solution){
        $validMoves = array("U","R","L","B","D","F","M","M","E","E","S","S");
        foreach ($validMoves as $move) {
            $moveCount += substr_count($solution, $move);
        }
        if ($solution=="DNF") {return 8888;}
        return $moveCount;
    }

    function get_competitions($userId)
    {
        global $mysqli;
        $result = $mysqli->query("SELECT distinct weekId, yearId FROM weeklyResults WHERE userid='$userId'");
        return $result->num_rows;
    }
    
    function get_completed_solves($userId)
    {
        global $mysqli;
        $result = $mysqli->query("SELECT SUM(completed) FROM weeklyResults WHERE userId = $userId");
        $row = $result->fetch_row();
        $solves += $row[0];
        return $solves;
    }
    	
    function add_icon($eventName, $size) {
        switch ($eventName) {
            case "2x2x2": echo '<span class="cubing-icon '.$size.' event-222"></span>'; break;
            case "3x3x3": echo '<span class="cubing-icon '.$size.' event-333"></span>'; break;
            case "4x4x4": echo '<span class="cubing-icon '.$size.' event-444"></span>'; break;
            case "5x5x5": echo '<span class="cubing-icon '.$size.' event-555"></span>'; break;
            case "6x6x6": echo '<span class="cubing-icon '.$size.' event-666"></span>'; break;
            case "7x7x7": echo '<span class="cubing-icon '.$size.' event-777"></span>'; break;
            case "2x2x2 blindfolded": echo '<span class="cubing-icon '.$size.' unofficial-222bf"></span>'; break;
            case "3x3x3 blindfolded": echo '<span class="cubing-icon '.$size.' event-333bf"></span>'; break;
            case "4x4x4 blindfolded": echo '<span class="cubing-icon '.$size.' event-444bf"></span>'; break;
            case "5x5x5 blindfolded": echo '<span class="cubing-icon '.$size.' event-555bf"></span>'; break;
            case "6x6x6 blindfolded": echo '<span class="cubing-icon '.$size.' unofficial-666bf"></span>'; break;
            case "7x7x7 blindfolded": echo '<span class="cubing-icon '.$size.' unofficial-777bf"></span>'; break;
            case "3x3x3 multiple blindfolded": echo '<span class="cubing-icon '.$size.' event-333mbf"></span>'; break;
            case "3x3x3 one-handed": echo '<span class="cubing-icon '.$size.' event-333oh"></span>'; break;
            case "3x3x3 With feet": echo '<span class="cubing-icon '.$size.' event-333ft"></span>'; break;
            case "3x3x3 Match the scramble": echo '<span class="cubing-icon '.$size.' unofficial-333mts"></span>'; break;
            case "3x3x3 Fewest moves": echo '<span class="cubing-icon '.$size.' event-333fm"></span>'; break;
            case "2-3-4 Relay": echo '<span class="cubing-icon '.$size.' unofficial-234relay"></span>'; break;
            case "2-3-4-5 Relay": echo '<span class="cubing-icon '.$size.' unofficial-2345relay"></span>'; break;
            case "2-3-4-5-6 Relay": echo '<span class="cubing-icon '.$size.' unofficial-23456relay"></span>'; break;
            case "2-3-4-5-6-7 Relay": echo '<span class="cubing-icon '.$size.' unofficial-234567relay"></span>'; break;
            case "Clock": echo '<span class="cubing-icon '.$size.' event-clock"></span>'; break;
            case "Megaminx": echo '<span class="cubing-icon '.$size.' event-minx"></span>'; break;
            case "Pyraminx": echo '<span class="cubing-icon '.$size.' event-pyram"></span>'; break;
            case "Square-1": echo '<span class="cubing-icon '.$size.' event-sq1"></span>'; break;
            case "Skewb": echo '<span class="cubing-icon '.$size.' event-skewb"></span>'; break;
            case "Kilominx": echo '<span class="cubing-icon '.$size.' unofficial-kilominx"></span>'; break;
            case "Mini Guildford": echo '<span class="cubing-icon '.$size.' unofficial-miniguild"></span>'; break;
            case "Magic": echo '<span class="cubing-icon '.$size.' event-magic"></span>'; break;
            case "Master Magic": echo '<span class="cubing-icon '.$size.' event-mmagic"></span>'; break;
            case "Snake": echo '<span class="cubing-icon '.$size.' event-minx"></span>'; break;
            case "4x4x4 Fewest Moves": echo '<span class="cubing-icon '.$size.' event-333fm"></span>'; break;
        }
    }
    
    function calculate_single_ranking($eventId, $solveCount, $rankResult, $userId)
    {
        global $mysqli;
        $place = 1;
        if ($eventId == 13) {
            $queryRanking = $mysqli->query("SELECT DISTINCT userId FROM weeklyResults WHERE eventId='$eventId' AND multiBLD<'$rankResult' AND userID>='0' AND userId!='$userId'");
        } elseif ($solveCount == 3) {
            $queryRanking = $mysqli->query("SELECT DISTINCT userId FROM weeklyResults WHERE eventId='$eventId' AND ((solve1<'$rankResult' AND solve1>'0') OR (solve2<'$rankResult' AND solve2>'0') OR (solve3<'$rankResult' AND solve3>'0')) AND userID>='0' AND userId!='$userId'");
        } elseif ($solveCount == 5) {
            $queryRanking = $mysqli->query("SELECT DISTINCT userId FROM weeklyResults WHERE eventId='$eventId' AND ((solve1<'$rankResult' AND solve1>'0') OR (solve2<'$rankResult' AND solve2>'0') OR (solve3<'$rankResult' AND solve3>'0') OR (solve4<'$rankResult' AND solve4>'0') OR (solve5<'$rankResult' AND solve5>'0')) AND userID>='0' AND userId!='$userId'");
        } else {
            $queryRanking = $mysqli->query("SELECT DISTINCT userId FROM weeklyResults WHERE eventId='$eventId' AND result<'$rankResult' AND result>'0' AND userID>='0' AND userId!='$userId'");
        }
        while($placeArr = $queryRanking->fetch_array()){
            $place++;
        }
        return $place;
    }
    
    function calculate_average_ranking($eventId, $solveCount, $rankResult, $userId)
    {
        global $mysqli;
        $place = 1;
        if ($solveCount == 5) {
            // Result contains the average; simply look for rank based on that
            $queryRanking = $mysqli->query("SELECT DISTINCT userId FROM weeklyResults WHERE eventId='$eventId' AND result<'$rankResult' AND userID>='0' AND userId!='$userId'");
            while($placeArr = $queryRanking->fetch_array()) {
                $place++;
            }
            return $place;
        } elseif ($solveCount == 3) {
            // Get the list of people who have full means of 3, then compare averages
            $queryRanking = $mysqli->query("SELECT * FROM weeklyResults WHERE eventId='$eventId' AND solve1 != '8888' AND solve2 != '8888' AND solve3 != '8888' AND userID>='0' AND userId!='$userId'");
            $userAverages = array();
            while($placeArr = $queryRanking->fetch_array()) {
                $avg = ($placeArr['solve1'] + $placeArr['solve2'] + $placeArr['solve3']) / 3;
                if ($avg < $rankResult) {
                    if (!$userAverages[$placeArr['userId']]) {
                        $userAverages[$placeArr['userId']] = $avg;
                        $place++;
                    } elseif ($userAverages[$placeArr['userId']] > $avg) {
                        $userAverages[$placeArr['userId']] = $avg;
                    }
                }
            }
            return $place;
        } else {
            // Averages not supported for these events
            return 0;
        }
    }
    
    function calculate_place_ranking($eventId, $rankResult, $userId, $week, $year)
    {
        global $mysqli;
        $place = 1;
        if ($eventId != 13) {
            $queryRanking = $mysqli->query("SELECT DISTINCT userId, result FROM weeklyResults WHERE weekId = $week AND yearId = $year AND eventId = $eventId AND result < $rankResult AND userID >= 0 AND userId != $userId");
        } else {
            $queryRanking = $mysqli->query("SELECT DISTINCT userId, multiBLD FROM weeklyResults WHERE weekId = $week AND yearId = $year AND eventId = $eventId AND multiBLD < $rankResult AND userID >= 0 AND userId != $userId");
        }
        while($placeArr = $queryRanking->fetch_array()){
            // For some reason, WHERE result < $rankResult returns cases where result == $rankResult, so I need the following check.  Bizarre.
            if ($rankResult !== $placeArr['result']) {
                $place++;
            }
        }
        return $place;
    }
    
    function calculate_place_ranking_new($eventId, $rankAverage, $rankBest, $userId, $week, $year)
    {
        global $mysqli;
        $place = 1;
        if (is_average_event($eventId)) {
            $queryRanking = $mysqli->query("SELECT average, best FROM weeklyResults WHERE weekId = $week AND yearId = $year AND eventId = $eventId AND average > 0 AND (average < $rankAverage OR (average = $rankAverage AND best < $rankBest)) AND userId != $userId");
            while($placeArr = $queryRanking->fetch_array()){
                // For some reason, WHERE result < $rankResult returns cases where result == $rankResult, so I need the following check.  Bizarre.
                if ($rankAverage !== $placeArr['average'] || ($rankAverage == $placeArr['average'] && $rankBest !== $placeArr['best'])) {
                    $place++;
                }
            }
        } else {
            $queryRanking = $mysqli->query("SELECT best FROM weeklyResults WHERE weekId = $week AND yearId = $year AND eventId = $eventId AND best > 0 AND best < $rankBest AND userId != $userId");
            while($placeArr = $queryRanking->fetch_array()){
                // For some reason, WHERE result < $rankResult returns cases where result == $rankResult, so I need the following check.  Bizarre.
                if ($rankBest !== $placeArr['best']) {
                    $place++;
                }
            }
        }
        return $place;
    }
    
    function get_single_output($eventId, $result)
    {
        if ($eventId == 13) {
            $multiBLDInfo = number_to_MBLD($result);
            $singleOutput = $multiBLDInfo[0];
        } elseif ($eventId == 17) {
            $singleOutput = get_FMC_output($result);
        } elseif ($eventId == 32) {
            $singleOutput = $result;
        } else {
            $singleOutput = pretty_number($result);
        }
        
        return $singleOutput;
    }
    
    function get_FMC_output($result)
    {
        if ($result == 8888 || $result == 0) {
            $output = "DNF";
        } else {
            $output = round($result);
        }
        return $output;
    }
    
    function get_solve_details($eventId, $solveCount, $solves, $result, $multiBLD, $fmcSolution, $short)
    {
        if ($eventId == 13) {
            $multiBLDInfo = number_to_MBLD($multiBLD);
            $time = substr(pretty_number($multiBLDInfo[1]), 0, -3);
            $solveDetails = $multiBLDInfo[2]."/".$multiBLDInfo[3]." ".$time;
        } elseif ($eventId == 17) {
            $solveDetails = stripslashes($fmcSolution);
        } else {
            $solveDetails = "";
            for ($i = 1; $i <= $solveCount; $i++) {
                $solveDetails .= pretty_number($solves[$i]);
                if ($short && $i < $solveCount) {
                    $solveDetails .= ", ";
                } elseif ($i < $solveCount) {
                    $solveDetails .= " &nbsp; ";
                }
            }
        }
        
        return $solveDetails;
    }

    function is_dnf($result)
    {
        if ($result == '8888' || $result == 'DNF') {
            return true;
        }
        return false;
    }

    function is_dns($result)
    {
        if ($result == '9999' || $result == 'DNS') {
            return true;
        }
        return false;
    }

    function get_scramble_text($eventId, $weekNo, $yearNo)
    {
        global $mysqli;
        $scramble = "";
        $query = $mysqli->query("SELECT scramble FROM scrambles WHERE eventId = '$eventId' AND weekId = '$weekNo' AND yearId = '$yearNo'");

        while ($row = $query->fetch_row()) {
            $scramble .= $row[0];
        }
        return $scramble;
    }
     
    function get_average($solveCount, $solves)
    {
        $dnfCount = 0;
        $avg = 0;
        $minSolve = PHP_INT_MAX;
        $maxSolve = 0;
        for ($i = 1; $i <= $solveCount; ++$i) {
            if ($solves[$i] != 0 && $solves[$i] != '8888' && $solves[$i] != '9999' && $solves[$i] != 'DNS' && $solves[$i] != 'DNF') {
                $avg += $solves[$i];
                if ($minSolve > $solves[$i]) {
                    $minSolve = $solves[$i];
                }
                if ($maxSolve < $solves[$i]) {
                    $maxSolve = $solves[$i];
                }
            } else {
                ++$dnfCount;
            }
        }
        if ($solveCount <= 3) {
            // Calculate mean; any DNFs mean a DNF average
            if ($dnfCount > 0) {
                return 8888;
            } else {
                return $avg / $solveCount;
            }
        } else {
            // Calculate average throwing out min and max
            // If $dnfCount is 1, that counts as the max, so just throw out the min
            // If $dnfCount is greater than 1, average is DNF
            if ($dnfCount > 1) {
                return 8888;
            } else if ($dnfCount == 1) {
                return ($avg - $minSolve) / ($solveCount - 2);
            } else {
                return ($avg - $minSolve - $maxSolve) / ($solveCount - 2);
            }
        }
    }
     
    function get_best_result($solveCount, $solves)
    {
        $minSolve = PHP_INT_MAX;
        for ($i = 1; $i <= $solveCount; ++$i) {
            if (is_valid_score($solves[$i])) {
                if ($minSolve > $solves[$i]) {
                    $minSolve = $solves[$i];
                }
            }
        }
        if ($minSolve == PHP_INT_MAX) {
            return 8888;
        }
        return $minSolve;
    }
    
    function get_start_year()
    {
        return 2007;
    }
    
    function get_current_week()
    {
        return (int)gmdate("W", strtotime('-1 day'));
    }
    
    function get_current_year()
    {
        return gmdate("o", strtotime('-1 day'));
    }

    function calculate_points($eventId, $rankFromBottom, $ties, $cubes, $bldComplete, $bldSuccess, $week, $year) {
        $participation = 0;
        switch ($eventId) {
            case 1: $participation = 2; break;
            case 2: $participation = 3; break;
            case 3: $participation = 4; break;
            case 4: $participation = 5; break;
            case 5: $participation = 6; break;
            case 6: $participation = 7; break;
            case 7: $participation = 3; break;
            case 8: $participation = 5; break;
            case 9: $participation = 7; break;
            case 10: $participation = 9; break;
            case 11: $participation = 11; break;
            case 12: $participation = 13; break;
            case 13: $participation = 3; break;
            case 14: $participation = 4; break;
            case 15: $participation = 4; break;
            case 16: $participation = 4; break;
            case 17: $participation = 10; break;
            case 18: $participation = 2; break;
            case 19: $participation = 3; break;
            case 20: $participation = 4; break;
            case 21: $participation = 5; break;
            case 22: $participation = 2; break;
            case 23: $participation = 4; break;
            case 24: $participation = 2; break;
            case 25: $participation = 4; break;
            case 26: $participation = 2; break;
            case 27: $participation = 2; break;
            case 28: $participation = 4; break;
            default: $participation = 2; break;
        }
        if (!$bldComplete) { $participation = 0; }
        if ($bldSuccess && $year > 2016) { $participation = ceil(1.5 * $participation); }
        if ($cubes > 10 && $week >= 1 && $year >= 2017) { $cubes = 10; }
        $rankPoints = $rankFromBottom;
        if ($ties > 1) {
            if ($year > 2016) {
                $rankPoints = ceil((($rankFromBottom / 2 + 0.5) * $ties) / $ties);
            }
        }
        return $participation + $rankPoints + $cubes * 2;
    }
    
    function are_results_equal($result1, $result2, $solveCount, $best1, $best2)
    {
        if ($solveCount == 3 || $solveCount == 5) {
            if (pretty_number($result1) === pretty_number($result2)) {
                if (pretty_number($best1) === pretty_number($best2)) {
                    return true;
                }
            }
        } else {
            if ($best1 === $best2) {
                return true;
            }
        }
        return false;
    }
    
    function get_personal_best_single($event, $userId, $showWeek)
    {
        global $mysqli;
        global $events;

        $query = get_query_for_best($event, "", "LIMIT 1", false, true, "AND userId = $userId");
        while ($row = $query->fetch_array()) {
            if ($event == 13 || $event == 17) {
                $output = get_single_output($event, $row['min']);
            } else {
                $output = get_single_output($event, $row['min'] / 100);
            }
            if ($showWeek) {
                $output .= " (".get_competition_name($row['weekId'], $row['yearId']).")";
            }
            return $output;
        }
        return "-";
    }
    
    function get_personal_best_average($event, $userId, $showWeek)
    {
        global $mysqli;
        global $events;

        $query = get_query_for_best($event, "", "LIMIT 1", false, false, "AND userId = $userId");
        while ($row = $query->fetch_array()) {
            $output = pretty_number($row['min'] / 100);
            if ($showWeek) {
                $output .= " (".get_competition_name($row['weekId'], $row['yearId']).")";
            }
            return $output;
        }
        return "-";
    }

    function get_short_event_name($eventId) {
        switch ($eventId) {
            case 1: return "2x2x2";
            case 2: return "3x3x3";
            case 3: return "4x4x4";
            case 4: return "5x5x5";
            case 5: return "6x6x6";
            case 6: return "7x7x7";
            case 7: return "2x2x2 BLD";
            case 8: return "3x3x3 BLD";
            case 9: return "4x4x4 BLD";
            case 10: return "5x5x5 BLD";
            case 11: return "6x6x6 BLD";
            case 12: return "7x7x7 BLD";
            case 13: return "3x3x3 Multi";
            case 14: return "3x3x3 OH";
            case 15: return "3x3x3 Feet";
            case 16: return "3x3x3 Match";
            case 17: return "3x3x3 FMC";
            case 18: return "2->4 Relay";
            case 19: return "2->5 Relay";
            case 20: return "2->6 Relay";
            case 21: return "2->7 Relay";
            case 22: return "Clock";
            case 23: return "Megaminx";
            case 24: return "Pyraminx";
            case 25: return "Square-1";
            case 26: return "Skewb";
            case 27: return "Kilominx";
            case 28: return "Mini Guildford";
            default: return "Error: unknown event!";
        }
    }

    class Events implements Iterator
    {
        private $eventNames = array();
        private $eventIds = array();
        private $numSolves = array();
        private $initial = -1;
        private $position = 0;
        
        public function __construct() {
            global $mysqli;
            $query = $mysqli->query("SELECT eventName, id, weekly FROM events WHERE weekly>'0' ORDER BY id");
            while ($row = $query->fetch_array()) {
                $id = $row['id'];
                $this->eventNames[$id] = $row['eventName'];
                $this->eventIds[$id] = intval($row['id']);
                $this->numSolves[$id] = $row['weekly'];
                if ($this->initial == -1) {
                    $this->initial = $id;
                }
            }
            // multiBLD - return numSolves as 1, not 60!
            $this->numSolves[13] = 1;
            $this->position = $this->initial;
        }
        
        public function rewind() {
            $this->position = $this->initial;
        }
        
        public function current() {
            return $this->eventIds[$this->position];
        }
        
        public function key() {
            return $this->eventIds[$this->position];
        }
        
        public function next() {
            ++$this->position;
        }
        
        public function valid() {
            return isset($this->eventIds[$this->position]);
        }
        
        public function name($id) {
            return $this->eventNames[$id];
        }
        
        public function num_solves($id) {
            return $this->numSolves[$id];
        }
        
        public function count()
        {
            return count($this->eventIds);
        }
    }
    
    $events = new Events;
    
    class WeeklyResults
    {
        private $totalScores = array();     // array of overall scores indexed by user id
        private $kinchScores = array();     // array of Kinch scores indexed by user id
        private $userIds = array();         // array of numeric user ids
        private $results = array();         // array of arrays, one per event, of result value indexed by user
        private $solveDetails = array();    // array of arrays, one per event, of solve details indexed by user
        private $userPlaces = array();      // array of arrays, one per event, of place in that event indexed by user
        private $scores = array();          // array of arrays, one per event, of score value of that event indexed by user
        private $comments = array();        // array of arrays, one per event, of comments for that event indexed by user
        private $partials = array();        // array of arrays, one per user id, of whether event is started, indexed by event id; zero event entry is total count of all events started
        private $completeds = array();      // array of arrays, one per user id, of whether event is completed, indexed by event id; zero event entry is total count of all events completed
        private $overallRecords = array();  // array of overall best results, indexed by event
        
        public function __construct($week, $year)
        {
            global $mysqli;
            global $events;
            $events = new Events; // try passing this by reference to the constructor?
            $overallRecords = get_overall_records();
            foreach ($events as $eventId) {
                if (!is_active_event($eventId, $week, $year)) {
                    continue;
                }
                $solveCount = $events->num_solves($eventId);
                $currentRank = 1;
                $userPlace = 0;
                $prevAverage = 0;
                $prevBest = 0;
                $prevUser = 0;
                $ties = 0;
                $tieCorrections = array();  // temporary array indicating amount for each user to correct for tie
                $query = $mysqli->query("SELECT userId, result, comment, solve1, solve2, solve3, solve4, solve5, multiBLD, fmcSolution, average, best FROM weeklyResults WHERE eventId='$eventId' AND weekId='$week' AND yearId='$year' ORDER BY ".get_order_by_string($eventId));
                $numResults = $query->num_rows;
                while ($resultRow = $query->fetch_array()) {
                    $cubesMBLD = 0;
                    $finishedSolves = 0;
                    $result = 0;
                    $userId = $resultRow['userId'];
                    $multiBLD = $resultRow['multiBLD'];
                    $fmcSolution = $resultRow['fmcSolution'];
                    $this->userIds[$userId] = $userId;
                    for ($i = 1; $i <= $solveCount; ++$i) {
                        $solves[$i] = $resultRow['solve'.$i];
                    }
                    $best = $resultRow['best'];
                    $average = $resultRow['average'];

                    if (are_results_equal($average, $prevAverage, $solveCount, $best, $prevBest)) {
                        // tie; store previous user so tie points can be added later
                        $tieCorrections[$ties] = $prevUser;
                        $ties++;
                    } else {
                        // fix all the scores with ties and clear the tieCorrections array
                        if ($ties > 0) {
                            $tieCorrections[$ties] = $prevUser;
                            for ($k = 0; $k <= $ties; $k++) {
                                $this->scores[$eventId][$tieCorrections[$k]] -= floor($ties / 2);
                                $this->totalScores[$tieCorrections[$k]] -= floor($ties / 2);
                            }
                        }
                        unset($tieCorrections);
                        $ties = 0;
                    }
                    $prevAverage = $average;
                    $prevBest = $best;

                    $userPlace = $currentRank - $ties;
                    $currentRank++;

                    if ($eventId !== 13 && $eventId !== 17) { // regular event
                       for($i = 1; $i <= $solveCount; $i++){
                           $finishedSolves += ($resultRow['solve'.$i] != 0 && $resultRow['solve'.$i] != 9999) ? 1 : 0;
                        }
                    } elseif ($eventId === 13) { // multiBLD
                        $rezult = number_to_MBLD($resultRow['multiBLD']);
                        if ($rezult[0] !== 'DNF') {
                            $cubesMBLD = $rezult[3];
                        }
                    }
                    $result = get_single_output($eventId, $eventId == 13 ? $resultRow['multiBLD'] : $resultRow['result']);
                    $this->solveDetails[$eventId][$userId] = get_solve_details($eventId, $solveCount, $solves, $result, $multiBLD, $fmcSolution, true);

                    // Set BLD scoring flags
                    $bldComplete = $result ? 1 : 0;
                    $bldSuccess = false;
                    if (is_BLD($eventId)) {
                        if ($result == "DNF" && $finishedSolves < $solveCount) {
                            $bldComplete = false;
                        } else if ($result != "DNF") {
                            $bldSuccess = true;
                        }
                    }

                    // Calculate score and update total
                    if ($result) {
                        $score = calculate_points($eventId, $numResults - $userPlace + 1, 0, $cubesMBLD, $bldComplete, $bldSuccess, $week, $year);
                        $this->totalScores[$userId] += $score;
                        if (is_valid_score($result)) {
                            $kinchResult = ($eventId == 13) ? $multiBLD : ugly_number($result);
                            $this->kinchScores[$userId] += calculate_kinch_event_score($overallRecords[$eventId], $kinchResult, $eventId);
                        }
                    }

                    $prevUser = $userId;

                    // Figure out how done we are on this event
                    $partials[$userId][$eventId] = false;
                    $completeds[$userId][$eventId] = false;
                    if ($result || ($this->solveDetails[$eventId][$userId] !== "" && $eventId === 13)) {
                        $this->partials[$userId][$eventId] = true;
                        ++$this->partials[$userId][0];
                        if ($eventId == 13 || $eventId == 17 || $finishedSolves == $solveCount) {
                            $this->completeds[$userId][$eventId] = true;
                            ++$this->completeds[$userId][0];
                        }
                    }

                    $this->results[$eventId][$userId] = $result;
                    $this->userPlaces[$eventId][$userId] = $userPlace;
                    $this->scores[$eventId][$userId] = $score;
                    $this->comments[$eventId][$userId] = $resultRow['comment'];
                }
                // Make the following a subroutine (also happens above)
                if ($ties > 0) {
                    $tieCorrections[$ties] = $prevUser;
                    for ($k = 0; $k <= $ties; $k++) {
                        $this->scores[$eventId][$tieCorrections[$k]] -= floor($ties / 2);
                        $this->totalScores[$tieCorrections[$k]] -= floor($ties / 2);
                    }
                }
                unset($tieCorrections);
                $ties = 0;

                if ($numResults > 0) {
                    arsort($this->scores[$eventId]);
                    asort($this->userPlaces[$eventId]);
                }
            }
            
            foreach ($this->userIds as $userId) {
                $this->kinchScores[$userId] /= 28;
            }
            unset($this->totalScores[0]);
            unset($this->totalScores['']);
            arsort($this->totalScores);
            arsort($this->kinchScores);
        }
        
        public function print_bbcode_results()
        {
            global $events;
            echo "<br>";
            foreach ($events as $eventId) {
                if (count($this->scores[$eventId]) == 0) {
                    continue;
                }
                $i = 1;
                echo "[B]".$events->name($eventId)."[/B](".count($this->scores[$eventId]).")<br>";
                echo "[LIST=1]<br>";
                if ($eventId == 13) {
                    foreach ($this->solveDetails[$eventId] as $key=>$value) {
                        $personInfo = get_person_info($key);
                        echo "[*][COLOR=Blue] ".$value."[/COLOR] ".$personInfo['username']."<br>";
                    }
                } else {
                    foreach ($this->results[$eventId] as $key=>$value) {
                        $personInfo = get_person_info($key);
                        echo "[*][COLOR=Blue] ".$value."[/COLOR] ".$personInfo['username']."<br>";
                    }
                }
                echo "[/LIST]<br>";
            }
            $i = 1;
            echo "<br>[B]Contest results[/B]<br>";
            echo "[LIST=1]<br>";
            foreach ($this->totalScores as $key=>$value) {
                $personInfo = get_person_info($key);
                echo "[*][COLOR=Blue] ".$value."[/COLOR] ".$personInfo['username']."<br>";
            }
            echo "[/LIST]<br>";
        }
        
        public function get_event_scores($eventId)
        {
            global $events;
            $i = 1;
            echo $events->name($eventId)."<br>";
            echo "---------------------<br>";
            foreach ($this->scores[$eventId] as $key=>$value) {
                $personInfo = get_person_info($key);
                echo $i++.$personInfo['displayName']."(".$key.")[".$personInfo['username']."]: ".$this->results[$eventId][$key]."(".$value.")<br>";
            }
            echo "<br>";
        }
        
        public function &get_score_list()
        {
            return $this->totalScores;
        }
        
        public function get_user_total_score($userId)
        {
            return $this->totalScores[$userId];
        }
        
        public function get_participant_count($eventId)
        {
            return count($this->results[$eventId]);
        }
        
        public function get_participant_total()
        {
            return count($this->userIds);
        }
        
        public function get_kinch_total($userId)
        {
            return $this->kinchScores[$userId];
        }
        
        public function get_user_place($eventId, $userId)
        {
            return $this->userPlaces[$eventId][$userId];
        }
        
        public function get_user_result($eventId, $userId)
        {
            return $this->results[$eventId][$userId];
        }
        
        public function get_user_solve_details($eventId, $userId)
        {
            return $this->solveDetails[$eventId][$userId];
        }
        
        public function get_user_comment($eventId, $userId)
        {
            return $this->comments[$eventId][$userId];
        }
        
        public function get_user_score($eventId, $userId)
        {
            return $this->scores[$eventId][$userId];
        }
        
        public function get_users()
        {
            return $this->userIds;
        }
        
        public function get_kinch_scores()
        {
            return $this->kinchScores;
        }
        
        public function get_results($eventId)
        {
            return $this->results[$eventId];
        }
        
        public function get_user_places($eventId)
        {
            return $this->userPlaces[$eventId];
        }
        
        public function get_attempted_events($userId)
        {
            return $this->partials[$userId][0];
        }
        
        public function is_completed($eventId, $userId)
        {
            return $this->completeds[$userId][$eventId];
        }
        
        public function is_partial($eventId, $userId)
        {
            return ($this->partials[$userId][$eventId] && (!$this->completeds[$userId][$eventId]));
        }
    }
    
    function get_order_by_string($eventId)
    {
        global $events;
        if ($events->num_solves($eventId) == 5) {
            return "average, best";
        }
        return "best";
    }
    
    function update_weeklyResults_rank_field_for_week($year, $week)
    {
        global $mysqli;
        global $events;
        foreach ($events as $eventId) {
            $queryString = "SELECT userId, average, best FROM weeklyResults where weekId = $week AND yearId = $year AND eventId = $eventId ORDER BY ".get_order_by_string($eventId);
            $query = $mysqli->query($queryString);
            $rank = 1;
            $place = 1;
            $first = true;
            while ($row = $query->fetch_assoc()) {
                if (!$first) {
                    if ($average != $row['average'] || $best != $row['best']) {
                        $rank = $place;
                    }
                }
                $first = false;
                $average = $row['average'];
                $best = $row['best'];
                $userId = $row['userId'];
                $mysqli->query("UPDATE weeklyResults SET rank = $rank WHERE userId = $userId AND eventId = $eventId AND weekId = $week AND yearId = $year");
                ++$place;
            }
        }
    }
    
    function update_weeklyResults_rank_field($year)
    {
        for ($week = 1; $week <= 53; ++$week) {
            update_weeklyResults_rank_field_for_week($year, $week);
        }
    }
    
    function update_weeklyResults_calculated_fields($year, $week = 0)
    {
        global $mysqli;
        global $events;
        
        print $year."--".$week."<br>";
        
        if ($week === 0) {
            $queryString = "SELECT eventId, userId, weekId, result, solve1, solve2, solve3, solve4, solve5, multiBLD, fmcSolution FROM weeklyResults WHERE yearId = $year";
        } else {
            $queryString = "SELECT eventId, userId, weekId, result, solve1, solve2, solve3, solve4, solve5, multiBLD, fmcSolution FROM weeklyResults WHERE yearId = $year AND weekId = $week";
        }
        $query = $mysqli->query($queryString);
        while ($row = $query->fetch_array()) {
            $eventId = $row['eventId'];
            $userId = $row['userId'];
            $week = $row['weekId'];
            $solves = array();
            $solveCount = $events->num_solves($eventId);
            $best = PHP_INT_MAX;
            $completed = 0;
            for ($i = 1; $i <= $solveCount; ++$i) {
                if ($eventId == 13) {
                    $best = $row['multiBLD'];  // Should change this to limit dangerous input
                    if ($best > 99000000 && ($best % 100 < 2)) {
                        // Check for 1/2; this needs to be treated as DNF
                        $best = PHP_INT_MAX;
                    } elseif ($best >= 99999999) {
                        // DNF; shouldn't be treated differently from other DNFs for calculations
                        $best = PHP_INT_MAX;
                    }
                    if ($best > 0 && $best != PHP_INT_MAX) {
                        ++$completed;
                    }
                } elseif ($eventId == 17 || $eventId == 32) {
                    $best = $row['result'];
                    if (is_valid_score($best)) {
                        ++$completed;
                    }
                } else {
                    $solves[$i] = $row['solve'.$i];
                    if (is_valid_score($solves[$i])) {
                        $value = $solves[$i] * 100;
                        if ($best > $value) {
                            $best = $value;
                        }
                        ++$completed;
                    }
                }
            }
            $average = get_average_from_solves($solves, $completed);
            $mysqli->query("UPDATE weeklyResults SET average = $average, best = $best, completed = $completed WHERE userId = $userId AND eventId = $eventId AND weekId = $week AND yearId = $year");
        }
        if ($week === 0) {
            update_weeklyResults_rank_field($year);
        } else {
            update_weeklyResults_rank_field_for_week($year, $week);
        }
    }
    
    function get_average_from_solves($solves, $completed)
    {
        $solveCount = count($solves);
        if ($solveCount == 3 && $completed == 3) {
            return round_score(array_sum($solves) / 3) * 100;
        } elseif ($solveCount == 5 && $completed >= 4) {
            $resMin = PHP_INT_MAX;
            for ($i = 1; $i <= $solveCount; ++$i) {
                if (is_valid_score($solves[$i]) && $solves[$i] < $resMin) {
                    $resMin = $solves[$i];
                }
            }
            $resMax = max($solves);
            return round_score((array_sum($solves) - $resMin - $resMax) / 3) * 100; // avg5
        } elseif ($solveCount == 3 || $solveCount == 5) {
            // max average for events that take averages
            return PHP_INT_MAX;
        }
        return 0;
    }

    function get_query_for_best($event, $yearsLimitString, $limitString, $showPersons, $showSingles, $userLimitString)
    {
        global $mysqli;
        global $events;
        $maxInt = 2147483647;

        if ($showPersons) {
            if ($showSingles) {
                $queryString = "SELECT userId, result, weekId, yearId, solve1, solve2, solve3, solve4, solve5, multiBLD, fmcSolution, average, best AS min FROM weeklyResults AS current
                                       WHERE eventId = $event $yearsLimitString $userLimitString
                                       AND best != $maxInt
                                       AND NOT EXISTS (SELECT userId, best FROM weeklyResults AS better
                                                              WHERE current.eventId = better.eventId $yearsLimitString $userLimitString
                                                                AND better.best < current.best
                                                                AND better.userId = current.userId)
                                       ORDER BY best";
            } elseif ($events->num_solves($event) >= 3) {
                $queryString = "SELECT userId, result, weekId, yearId, solve1, solve2, solve3, solve4, solve5, multiBLD, fmcSolution, average AS min, best FROM weeklyResults AS current
                                       WHERE eventId = $event $yearsLimitString $userLimitString
                                       AND average != $maxInt
                                       AND NOT EXISTS (SELECT userId, average, best FROM weeklyResults AS better
                                                              WHERE current.eventId = better.eventId $yearsLimitString $userLimitString
                                                                AND ((better.average < current.average) OR (better.average = current.average AND better.best < current.best))
                                                                AND better.userId = current.userId)
                                       ORDER BY average, best";
            }
        } else {
            if ($showSingles) {
                $queryString = "SELECT userId, result, weekId, yearId, solve1, solve2, solve3, solve4, solve5, multiBLD, fmcSolution, average, best AS min FROM weeklyResults AS current
                                       WHERE eventId = $event $yearsLimitString $userLimitString
                                       AND best != $maxInt
                                       ORDER BY best $limitString";
            } elseif ($events->num_solves($event) >= 3) {
                $queryString = "SELECT userId, result, weekId, yearId, solve1, solve2, solve3, solve4, solve5, multiBLD, fmcSolution, average AS min, best FROM weeklyResults AS current
                                       WHERE eventId = $event $yearsLimitString $userLimitString
                                       AND average != $maxInt
                                       ORDER BY average, best $limitString";
            }
        }
        
        return $mysqli->query($queryString);
    }
    
    function is_valid_score($score) {
        if ($score !== 0 && $score !== 8888 && $score !== 9999 && $score !== '0' && $score !== '8888' && $score !== '9999' && $score !== 'DNF' && $score !== 'DNS' && $score !== '' && $score !== '0.00') {
            return true;
        }
        return false;
    }

    function get_min($scores)
    {
        $min = PHP_INT_MAX;
        foreach ($scores as $score) {
            if (is_valid_score($score) && $score < $min) {
                $min = $score;
            }
        }
        return $min;
    }

    function get_max($scores)
    {
        $max = 0;
        foreach ($scores as $score) {
            if (!is_valid_score($score) || $score > $max) {
                $max = $score;
            }
        }
        return $max;
    }
    
    function get_decorated_diff($old, $new){
        $from_start = strspn($old ^ $new, "\0");        
        $from_end = strspn(strrev($old) ^ strrev($new), "\0");

        $old_end = strlen($old) - $from_end;
        $new_end = strlen($new) - $from_end;

        $start = substr($new, 0, $from_start);
        $end = substr($new, $new_end);
        $new_diff = "$".substr($new, $from_start, $new_end - $from_start)."$";  
        $old_diff = "$".substr($old, $from_start, $old_end - $from_start)."$";

        if ($from_start > $old_end) {
            // overlapping - treat specially
            $old_diff = "$".substr($old, $old_end, $from_start - $old_end)."$";
        }
        if ($from_start > $new_end) {
            // overlapping - treat specially
            $new_diff = "$".substr($new, $new_end, $from_start - $new_end)."$";
        }

        $new = "$start<ins class='insert-text'>$new_diff</ins>$end";
        $old = "$start<del class='delete-text'>$old_diff</del>$end";
        return array("old"=>$old, "new"=>$new);
    }

    function test_fewest_moves_results($week, $year)
    {
        global $mysqli;
        $queryScramble = $mysqli->query("SELECT scramble FROM scrambles WHERE eventId = 17 AND weekId = $week AND yearId = $year");
        $scramble = $queryScramble->fetch_array()['scramble'];
        $scramble = substr($scramble, 20);
        echo "<div>Week ".$year."-".$week."<br>";
        echo "Scramble:<br>".$scramble."<br>";
        $queryResults = $mysqli->query("SELECT userId, fmcSolution FROM weeklyResults WHERE eventId = 17 AND weekId = $week AND yearId = $year");
        while ($results = $queryResults->fetch_array()) {
            $solution = correct_solution($results['fmcSolution']);
            $fmcValue = FMCsolve($scramble, $solution);
            if ($fmcValue > 0) {
            echo $results['userId'].": (".$fmcValue.") ".$solution."<br>";
            } else {
                echo "<div  class='dnf-text'>".$results['userId'].": (".$fmcValue.") ".$solution."</div>";
            }
            if ($solution != $results['fmcSolution']) {
            $diff = get_decorated_diff($results['fmcSolution'], $solution);
            echo "<div>".$diff['old']."</div>
                  <div>".$diff['new']."</div>";
            }
        }
    }
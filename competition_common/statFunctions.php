<?php
    require_once(__DIR__.'/fmcSolve.php');

    function is_admin()
    {
        global $currentUserId;
        return (is_admin_id($currentUserId));
    }
    
    function is_mike()
    {
        global $currentUserId;
        return (is_mike_id($currentUserId));
    }
    
    function is_mats()
    {
        global $currentUserId;
        return (is_mats_id($currentUserId));
    }

    function is_BLD($eventId)
    {
        if ($eventId >= 7 && $eventId <= 12) {
            return true;
        }
        return false;
    }
    
    function is_movecount_scored($eventId)
    {
        if ($eventId == 17 || $eventId == 32)
        {
            return true;
        }
        return false;
    }
    
    function is_fewest_moves($eventId)
    {
        if ($eventId == 17 || $eventId == 32 || $eventId == 36)
        {
            return true;
        }
        return false;
    }
    
    function is_average_event($eventId, $year)
    {
        if ($eventId == 17 && $year > 2018) {
            return true;
        }
        if ($eventId <= 6 || ($eventId >= 14 && $eventId <= 16) || ($eventId >= 22 && $eventId <= 27) || ($eventId >= 29 && $eventId <= 31) || ($eventId >= 33 && $eventId <= 39)) {
            return true;
        }
        return false;
    }
    
    function is_wca_event($eventId)
    {
        if ($eventId <= 6 || ($eventId >= 8 && $eventId <= 10) || ($eventId >= 13 && $eventId <= 14) || ($eventId == 17) || ($eventId >= 22 && $eventId <= 26)) {
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
                if ($year !== 2009) { return false; } break;  // Snake - valid only in the year 2009
            case 32:
                if ($year < 2008 || $year > 2009) { return false; } break;  // 4x4x4 Fewest Moves - valid only 2008 - 2009
            case 33:
            case 34:
                if ($year < 2019) { return false; } break;  // Redi Cube and Master Pyraminx valid from 2019 to present
            case 35:
            case 36:
            case 37:
            case 38:
                if ($year < 2020) { return false; } break;  // 15 puzzle, speed FMC, mirror blocks, and curvy copter valid from 2020 to present
            case 39:
                if ($year < 2021) { return false; } break;  // FTO valid from 2021 to present
        }

        return true;
    }
    
    function active_event_count($week, $year) {
        $eventCount = 0;
        for ($event = 1; $event <= 39; ++$event) {
            if (is_active_event($event, $week, $year)) {
                ++$eventCount;
            }
        }
        return $eventCount;
    }
    
    function wca_event_count()
    {
        return 18;
    }
    
    function get_competition_name($week, $year)
    {
        return $year."-".str_pad($week, 2, '0', STR_PAD_LEFT);
    }
    
    function get_weekly_kinch_rankings($week, $year, $wcaOnly)
    {
        global $mysqli;
        
        $kinchTotals = array();
        $userNames = array();
        $results = $mysqli->query("select distinct userId from weeklyResults where weekId=".$week." and yearId=".$year);
        while ($row = $results->fetch_row()) {
            $userId = $row[0];
            $kinchTotals[$userId] = get_weekly_user_kinch_score($userId, $week, $year, $wcaOnly);
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
    
    function get_weekly_user_kinch_score($user, $week, $year, $wcaOnly)
    {
        $userRecords = get_user_weekly_results($user, $week, $year);
        $overallRecords = get_overall_records($year, $week);
        $score = 0;
        $count = 0;
        foreach ($userRecords as $eventId => $result) {
            if (($wcaOnly && is_wca_event($eventId)) || (!$wcaOnly && is_active_event($eventId, $week, $year))) {
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
        foreach ($events as $eventId => $eventName) {
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

    function get_overall_user_kinch_scores($user, $wcaOnly)
    {
        global $mysqli;

        $userRecords = get_user_records($user);
        $overallRecords = get_overall_records(get_current_year(), get_current_week());
        $score = 0;
        $count = 0;
        foreach ($userRecords as $eventId => $result) {
            if ($eventId === 17 && (empty($result) || $result == 8888)) {
                // The user does not have a fewest moves average; calculate Kinch based on fewest move single, if any
                $query = $mysqli->query("select min(result) from weeklyResults where userId = $user and eventId = $eventId and yearId < 2019");
                $result = $query->fetch_row()[0];
                // For now, hardcoded WR for single as 17; that will always be the WR for pre-2019, unless data cleanup discovers something different.
                // This is ugly, but looking up the answer seems uglier.
                $scores[$eventId] = calculate_kinch_event_score(17, $result, $eventId);
            } else {
                $scores[$eventId] = calculate_kinch_event_score($overallRecords[$eventId], $result, $eventId);
            }
            if (($wcaOnly && is_wca_event()) || (!$wcaOnly && is_active_event($eventId, get_current_week(), get_current_year()))) {
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
            foreach ($events as $eventId => $eventName) {
                if ($eventId === 13) {
                    $result = $mysqli->query("select min(multiBLD) from weeklyResults where userId = $user and eventId = 13");
                } elseif ($eventId == 17) {
                    // Return only record based on averages (post-2018); if they don't have one, Kinch score calculation needs to recalculate using singles from before 2019.
                    $result = $mysqli->query("select min(result) from weeklyResults where userId = $user and eventId = $eventId and yearId > 2018");
                } else {
                    $result = $mysqli->query("select min(result) from weeklyResults where userId = $user and eventId = $eventId");
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
 
    function get_overall_records($year, $week)
    {
        static $records = array();
        global $mysqli;
        global $events;
        
        if (count($records) > 0) {
            return $records;
        }
        
        $year = intval($year);
        $week = intval($week);
        foreach ($events as $eventId => $eventName) {
            $eventId = intval($eventId);
            if ($eventId === 13) {
                $result = $mysqli->query("select min(multiBLD) from weeklyResults where eventId = 13 and (yearId < $year or (yearId = $year and weekId <= $week))");
            } elseif ($eventId === 17 and $year > 2018) {
                // For competitions starting in 2019, only consider results with averages; singles are not considered.
                $result = $mysqli->query("select min(result) from weeklyResults where eventId = $eventId and yearId > 2018 and (yearId < $year or (yearId = $year and weekId <= $week))");
            } else {
                $result = $mysqli->query("select min(result) from weeklyResults where eventId = $eventId and (yearId < $year or (yearId = $year and weekId <= $week))");
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

        if (array_key_exists($userId, $userNames)) {
            $personInfo = array('firstName' => $firstNames[$userId], 'lastName' => $lastNames[$userId],
                            'username' => $userNames[$userId], 'displayName' => $displayNames[$userId], 'email' => $emails[$userId], 'hideNames' => $hideNames[$userId]);
        } else {
            $personInfo = array('firstName' => 'Unknown', 'lastName' => 'User', 'username' => 'Unknown User', 'displayName' => 'Unknown User', 'email' => 'Unknown', 'hideNames' => 0);
        }
        
        return $personInfo;
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
    
    function pretty_score($uglyNumber)
    {
        if ($uglyNumber == 8888 || $uglyNumber == 'DNF') {return 'DNF';}
        if ($uglyNumber == 9999 || $uglyNumber == 'DNS' || $uglyNumber == '0') {return 'DNS';}
        $score = floatval($uglyNumber);
        return number_format($score, 2, '.', '');
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
        $solves = 0;
        if (isset($row)) {
            $solves += $row[0];
        }
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
            case "Redi Cube": echo '<span class="cubing-icon '.$size.' unofficial-redi"></span>'; break;
            case "Master Pyraminx": echo '<span class="cubing-icon '.$size.' unofficial-mpyram"></span>'; break;
            case "15 Puzzle": echo '<span class="cubing-icon '.$size.' unofficial-15puzzle"></span>'; break;
            case "Speed Fewest Moves": echo '<span class="cubing-icon '.$size.' unofficial-speedfmc"></span>'; break;
            case "Mirror Blocks": echo '<span class="cubing-icon '.$size.' unofficial-mirror"></span>'; break;
            case "Curvy Copter": echo '<span class="cubing-icon '.$size.' unofficial-curvy"></span>'; break;
            case "Face-Turning Octahedron": echo '<span class="cubing-icon '.$size.' unofficial-fto"></span>'; break;
        }
    }
    
    function calculate_single_ranking($eventId, $rankResult)
    {
        global $mysqli;
        if ($eventId != 13 && !is_movecount_scored($eventId)) {
            $rankResult = round($rankResult * 100);
        }
        return 1 + $mysqli->query("SELECT DISTINCT userId FROM weeklyResults WHERE eventId = $eventId AND best != 0 AND best < $rankResult")->num_rows;
    }
    
    function calculate_average_ranking($eventId, $solveCount, $rankResult)
    {
        global $mysqli;
        if ($eventId != 13) {
            $rankResult = round($rankResult * 100);
        }
        if ($solveCount > 1) {
            return 1 + $mysqli->query("SELECT DISTINCT userId FROM weeklyResults WHERE eventId = $eventId AND average != 0 AND average < $rankResult")->num_rows;
        } else {
            // Averages not supported for these events
            return 0;
        }
    }
    
    function calculate_place_ranking($eventId, $rankAverage, $rankBest, $userId, $week, $year)
    {
        global $mysqli;
        $place = 1;
        if (is_average_event($eventId, $year)) {
            $queryRanking = $mysqli->query("SELECT average, best FROM weeklyResults WHERE weekId = $week AND yearId = $year AND eventId = $eventId AND (average < $rankAverage OR (average = $rankAverage AND best < $rankBest)) AND userId != $userId");
            while($placeArr = $queryRanking->fetch_array()){
                // For some reason, WHERE result < $rankResult returns cases where result == $rankResult, so I need the following check.  Bizarre.
                if ($rankAverage !== $placeArr['average'] || ($rankAverage == $placeArr['average'] && $rankBest !== $placeArr['best'])) {
                    $place++;
                }
            }
        } else {
            $queryRanking = $mysqli->query("SELECT best FROM weeklyResults WHERE weekId = $week AND yearId = $year AND eventId = $eventId AND best < $rankBest AND userId != $userId");
            while($placeArr = $queryRanking->fetch_array()){
                // For some reason, WHERE result < $rankResult returns cases where result == $rankResult, so I need the following check.  Bizarre.
                if ($rankBest !== $placeArr['best']) {
                    $place++;
                }
            }
        }
        return $place;
    }
    
    // The following two functions could be combined if we changed the database data so that fewest moves singles were represented in hundredths of a unit, like everything else
    function get_single_output_from_best($eventId, $result)
    {
        $MYSQL_MAX_INT = 2147483647; // PHP_MAX_INT input into the database and back out gives this
        if ($result == $MYSQL_MAX_INT) {
            return "DNF";
        } elseif (is_movecount_scored($eventId) || $eventId == 13) {
            return get_single_output($eventId, $result);
        } else {
            return get_single_output($eventId, $result / 100.0);
        }
    }
    
    function get_average_output($eventId, $year, $result)
    {
        $MYSQL_MAX_INT = 2147483647; // PHP_MAX_INT input into the database and back out gives this
        if ($result == $MYSQL_MAX_INT) {
            return "DNF";
        } elseif ($eventId == 13 || get_solve_count($eventId, $year) == 1) {
            return "";
        } elseif ($eventId == 36) {
            return pretty_score($result / 100.0);
        } else {
            return pretty_number($result / 100.0);
        }
    }
    
    function get_single_output($eventId, $result)
    {
        if ($eventId == 13) {
            $multiBLDInfo = number_to_MBLD($result);
            $singleOutput = $multiBLDInfo[0];
        } elseif (is_movecount_scored($eventId)) {
            $singleOutput = get_FMC_output($result);
        } elseif ($eventId == 36) {
            $singleOutput = pretty_score($result);
        } else {
            $singleOutput = pretty_number($result);
        }
        
        return $singleOutput;
    }
    
    function get_FMC_average_output($result)
    {
        if ($result == 8888) {
            $output = "DNF";
        } elseif ($result == 9999 || $result == 0) {
            $output = "DNS";
        } else {
            $output = round_score($result);
        }
        return $output;
    }
    
    function get_FMC_output($result)
    {
        if ($result == 8888) {
            $output = "DNF";
        } elseif ($result == 9999 || $result == 0) {
            $output = "DNS";
        } else {
            $output = round($result);
        }
        return $output;
    }
    
    function get_speed_FMC_result($moves, $time)
    {
        if ($moves == 9999 || $moves == 0) {
            return 9999;
        }
        return round_score($moves + $time / 60);
    }
    
    function get_solve_details($eventId, $solveCount, $solves, $result, $multiBLD, $short)
    {
        if ($eventId == 13) {
            $multiBLDInfo = number_to_MBLD($multiBLD);
            $time = substr(pretty_number($multiBLDInfo[1]), 0, -3);
            $solveDetails = $multiBLDInfo[2]."/".$multiBLDInfo[3]." ".$time;
        } else {
            $solveDetails = "";
            for ($i = 1; $i <= $solveCount; $i++) {
                $solveDetails .= get_single_output($eventId, $solves[$i]);
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
        return intval(gmdate("W", strtotime('-1 day')));
    }
    
    function get_current_year()
    {
        return intval(gmdate("o", strtotime('-1 day')));
    }
    
    function get_previous_week()
    {
        return intval(gmdate("W", strtotime('-8 days')));
    }
    
    function get_previous_week_year()
    {
        return intval(gmdate("o", strtotime('-8 days')));
    }
    
    function get_time_to_next_week()
    {
        return ((60 * 60 * 24 * 7) - strtotime('-5 day') % (60 * 60 * 24 * 7));
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
            case 29: $participation = 2; break;
            case 30: $participation = 2; break;
            case 31: $participation = 2; break;
            case 32: $participation = 10; break;
            case 33: $participation = 3; break;
            case 34: $participation = 4; break;
            case 35: $participation = 3; break;
            case 36: $participation = 7; break;
            case 37: $participation = 3; break;
            case 38: $participation = 5; break;
            case 39: $participation = 4; break;
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
    
    $eventRules = [
        "", // Unused - event 0
        "", // 2x2x2
        "", // 3x3x3
        "", // 4x4x4
        "", // 5x5x5
        "", // 6x6x6
        "", // 7x7x7
        "Please note that 2x2x2 blindfolded is intended to work like the other blindfolded events."
        . " Start the timer before uncovering the puzzle to begin inspection. The result should be"
        . " the sum of the time to inspect, the time to don the blindfold, and the time to solve the puzzle.\n\n"
        . "If all of your results are DNFs, a comment is expected; it is suggested you provide a description"
        . " of the failed results and/or the attempt times.", // 2x2x2 BLD
        "Start the timer before uncovering the puzzle to begin inspection. The result should be"
        . " the sum of the time to inspect, the time to don the blindfold, and the time to solve the puzzle.\n\n"
        . "If all of your results are DNFs, a comment is expected; it is suggested you provide a description"
        . " of the failed results and/or the attempt times.", // 3x3x3 BLD
        "Start the timer before uncovering the puzzle to begin inspection. The result should be"
        . " the sum of the time to inspect, the time to don the blindfold, and the time to solve the puzzle.\n\n"
        . "If all of your results are DNFs, a comment is expected; it is suggested you provide a description"
        . " of the failed results and/or the attempt times.", // 4x4x4 BLD
        "Start the timer before uncovering the puzzle to begin inspection. The result should be"
        . " the sum of the time to inspect, the time to don the blindfold, and the time to solve the puzzle.\n\n"
        . "If all of your results are DNFs, a comment is expected; it is suggested you provide a description"
        . " of the failed results and/or the attempt times.", // 5x5x5 BLD
        "Start the timer before uncovering the puzzle to begin inspection. The result should be"
        . " the sum of the time to inspect, the time to don the blindfold, and the time to solve the puzzle.\n\n"
        . "If all of your results are DNFs, a comment is expected; it is suggested you provide a description"
        . " of the failed results and/or the attempt times.", // 6x6x6 BLD
        "Start the timer before uncovering the puzzle to begin inspection. The result should be"
        . " the sum of the time to inspect, the time to don the blindfold, and the time to solve the puzzle.\n\n"
        . "If all of your results are DNFs, a comment is expected; it is suggested you provide a description"
        . " of the failed results and/or the attempt times.", // 7x7x7 BLD
        "", // 3x3x3 Multi
        "", // 3x3x3 OH
        "", // 3x3x3 Feet
        "Begin with 2 cubes. Apply the scramble to a cube (prior to starting inspection)."
        . " It is not allowed to memorize the scramble, just as it would not be allowed to memorize"
        . " the scramble before a normal 3x3x3 solve. The second cube should already be solved."
        . " 15 seconds are allowed to inspect the scrambled cube. Then, start the timer, and begin"
        . " applying moves to the second, solved cube, until it matches the first cube. Then, stop"
        . " the timer.\n\n"
        . "This is obviously a significantly harder task than simply solving a regular 3x3x3, so"
        . " naturally times for this event should be significantly slower than a normal 3x3x3 solve."
        . " If you are familiar with the Red Bull Rubik\'s Cube competitions, this is actually very"
        . " similar to the Red Bull \"rescramble\" event, except that here the normal 15 seconds of"
        . " inspection is allowed, whereas in the Red Bull event inspection time is not allowed.\n\n"
        . " We started holding this event years before Red Bull did, and our rules have always"
        . " allowed the inspection time.", // 3x3x3 Match
        "", // 3x3x3 FMC
        "After applying scrambles to all puzzles, no more than 15 seconds total inspection time"
        . " may be used prior to starting the solve.", // 2->4 Relay
        "After applying scrambles to all puzzles, no more than 15 seconds total inspection time"
        . " may be used prior to starting the solve.", // 2->5 Relay
        "After applying scrambles to all puzzles, no more than 15 seconds total inspection time"
        . " may be used prior to starting the solve.", // 2->6 Relay
        "After applying scrambles to all puzzles, no more than 15 seconds total inspection time"
        . " may be used prior to starting the solve.", // 2->7 Relay
        "", // Clock
        "", // Megaminx
        "", // Pyraminx
        "", // Square-1
        "", // Skewb
        "To scramble, hold the kilominx with the top face such that the top of the pentagon is a point."
        . " Then the faces indicated to turn should be obvious. R2 means turn the right face 144 degrees clockwise,"
        . " R2' means turn the right face 144 degrees counterclockwise. 'flip' means flip the puzzle 180 degrees"
        . " about what is commonly thought of as the x axis (same as x2 in the old competition kilominx notation).", // Kilominx
        "After applying scrambles to all puzzles, no more than 15 seconds total inspection time"
        . " may be used prior to starting the solve.", // Mini Guildford
        "", // Magic
        "", // Master Magic
        "", // Snake
        "", // 4x4x4 FMC
        "", // Redi Cube
        "", // Master Pyrmx
        "", // 15 Puzzle
        "The result time for this event should be the time from when the scramble was first viewed"
        . " until the time when the scramble has been completely written down or typed in. After"
        . " completing the solve, extra time may be used to provide an explanation for the solve"
        . " that was written down, but the solve itself must be recorded completely prior to stopping"
        . " the timer.\n\n"
        . "The score is the number of minutes (to hundredths of a minute resolution) taken,"
        . " added to the number of moves.", // Speed FMC
        "", // Mirror Blocks
        "", // Curvy Copter
        "To scramble, hold the puzzle with a corner facing you. The four faces visible to you together"
        . " make a square. Orient the puzzle so that each of the four faces are directly above, below,"
        . " to the left, and to the right of the corner facing you. These make up the U, F, L, and R faces,"
        . " respectively. For the four faces on the back, B is adjacent to U, BL is adjacent to L, BR is"
        . " adjacent to R, and D is adjacent to F."  // Face-Turning Octahedron
    ];
    
    function has_rules($eventId) {
        global $eventRules;
        
        if ($eventRules[$eventId] !== "") return true;
        return false;
    }
    
    function get_rules($eventId) {
        global $eventRules;
        
        return $eventRules[$eventId];
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
            case 29: return "Magic";
            case 30: return "Master Magic";
            case 31: return "Snake";
            case 32: return "4x4x4 FMC";
            case 33: return "Redi Cube";
            case 34: return "Master Pyrmx";
            case 35: return "15 Puzzle";
            case 36: return "Speed FMC";
            case 37: return "Mirror Blocks";
            case 38: return "Curvy Copter";
            case 39: return "FTO";
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
                $this->numSolves[$id] = intval($row['weekly']);
                if ($this->initial == -1) {
                    $this->initial = $id;
                }
            }
            // multiBLD - return numSolves as 1, not 70!
            $this->numSolves[13] = 1;
            $this->position = $this->initial;
        }
        
        public function rewind() {
            $this->position = $this->initial;
        }
        
        public function current() {
            return $this->eventNames[$this->position];
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
        private $kinchScoresWca = array();  // array of Kinch scores for WCA events only indexed by user id
        private $userIds = array();         // array of numeric user ids
        private $results = array();         // array of arrays, one per event, of result value indexed by user
        private $bests = array();           // array of arrays, one per event, of best single indexed by user
        private $averages = array();        // array of arrays, one per event, of best average indexed by user
        private $solveDetails = array();    // array of arrays, one per event, of solve details indexed by user
        private $userPlaces = array();      // array of arrays, one per event, of place in that event indexed by user
        private $scores = array();          // array of arrays, one per event, of score value of that event indexed by user
        private $comments = array();        // array of arrays, one per event, of comments for that event indexed by user
        private $partials = array();        // array of arrays, one per user id, of whether event is started, indexed by event id; zero event entry is total count of all events started
        private $completeds = array();      // array of arrays, one per user id, of whether event is completed, indexed by event id; zero event entry is total count of all events completed
        private $overallRecords = array();  // array of overall best results, indexed by event
        private $partialsWca = array();     // array of count of WCA events that have been started, indexed by user id
        
        public function __construct($week, $year)
        {
            global $mysqli;
            global $events;
            $events = new Events; // try passing this by reference to the constructor?
            $overallRecords = get_overall_records($year, $week);
            foreach ($events as $eventId => $eventName) {
                if (!is_active_event($eventId, $week, $year)) {
                    continue;
                }
                $solveCount = get_solve_count($eventId, $year);
                $currentRank = 1;
                $userPlace = 0;
                $prevAverage = 0;
                $prevBest = 0;
                $prevUser = 0;
                $ties = 0;
                $tieCorrections = array();  // temporary array indicating amount for each user to correct for tie
                $query = $mysqli->query("SELECT userId, result, comment, solve1, solve2, solve3, solve4, solve5, multiBLD, average, best FROM weeklyResults WHERE eventId='$eventId' AND weekId='$week' AND yearId='$year' ORDER BY ".get_order_by_string($eventId, $year));
                $numResults = $query->num_rows;
                while ($resultRow = $query->fetch_array()) {
                    $cubesMBLD = 0;
                    $finishedSolves = 0;
                    $result = 0;
                    $userId = $resultRow['userId'];
                    $multiBLD = $resultRow['multiBLD'];
                    if (!isset($this->userIds[$userId])) {
                        $this->userIds[$userId] = $userId;
                        $this->totalScores[$userId] = 0;
                        $this->kinchScores[$userId] = 0;
                        $this->kinchScoresWca[$userId] = 0;
                        $this->partials[$userId][0] = 0;
                        $this->partialsWca[$userId] = 0;
                        $this->completeds[$userId][0] = 0;
                    }
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

                    if ($eventId !== 13) { // regular event
                       for($i = 1; $i <= $solveCount; $i++){
                           $finishedSolves += ($resultRow['solve'.$i] != 0 && $resultRow['solve'.$i] != 9999) ? 1 : 0;
                        }
                    } elseif ($eventId === 13) { // multiBLD
                        $rezult = number_to_MBLD($resultRow['multiBLD']);
                        if ($rezult[0] !== 'DNF') {
                            $cubesMBLD = $rezult[3];
                        }
                    }
                    if (is_movecount_scored($eventId) && $solveCount > 1) {
                        $result = get_FMC_average_output($resultRow['result']);
                    } else {
                        $result = get_single_output($eventId, $eventId == 13 ? $resultRow['multiBLD'] : $resultRow['result']);
                    }                
                    $this->solveDetails[$eventId][$userId] = get_solve_details($eventId, $solveCount, $solves, $result, $multiBLD, true);

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
                            $this->kinchScores[$userId] += calculate_kinch_event_score($overallRecords[$eventId], $kinchResult, $eventId, false);
                            if (is_wca_event($eventId)) {
                                $this->kinchScoresWca[$userId] += calculate_kinch_event_score($overallRecords[$eventId], $kinchResult, $eventId, false);
                            }
                        }
                    }

                    $prevUser = $userId;

                    // Figure out how done we are on this event
                    $partials[$userId][$eventId] = false;
                    $completeds[$userId][$eventId] = false;
                    if ($result || ($this->solveDetails[$eventId][$userId] !== "" && $eventId === 13)) {
                        $this->partials[$userId][$eventId] = true;
                        ++$this->partials[$userId][0];
                        if (is_wca_event($eventId)) {
                            ++$this->partialsWca[$userId];
                        }
                        if ($eventId == 13 || $finishedSolves == $solveCount) {
                            $this->completeds[$userId][$eventId] = true;
                            ++$this->completeds[$userId][0];
                        }
                    }

                    $this->results[$eventId][$userId] = $result;
                    $this->bests[$eventId][$userId] = $best;
                    $this->averages[$eventId][$userId] = $average;
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
                $this->kinchScores[$userId] /= active_event_count($week, $year);
                $this->kinchScoresWca[$userId] /= wca_event_count();
            }
            unset($this->totalScores[0]);
            unset($this->totalScores['']);
            arsort($this->totalScores);
            arsort($this->kinchScores);
            arsort($this->kinchScoresWca);
        }

        public function print_bbcode_results()
        {
            global $events;
            echo "\n";
            foreach ($events as $eventId => $eventName) {
                if (!isset($this->scores[$eventId]) || count($this->scores[$eventId]) == 0) {
                    continue;
                }
                $spoilerDone = false;
                $count = 1;
                $prev = 0;
                $rank = 0;
                echo "[B]".$eventName."[/B](".count($this->scores[$eventId]).")\n";
                foreach ($this->results[$eventId] as $key=>$value) {
                    // MultiBLD - BUG: Assume all ties that are not DNF are nonties. This is wrong, but minimizes the work necessary to massage the results to be correct.
                    //                 The only erroneous values should be rare cases where the points and times are ties - they will be reported as nonties, when they are actually ties.
                    if ($prev !== $value || ($eventId == 13 && $value !== "DNF")) {
                        $rank = $count;
                        $prev = $value;
                        if ($rank > 3 && !$spoilerDone) {
                            echo "[spoiler]\n";
                            $spoilerDone = true;
                        }
                    }
                    if ($rank == 0) {
                        // When all results for an event are DNFs, we can have this happen. Make all tied DNFs be first place, instead of zeroth place.
                        $rank = 1;
                    }
                    $personInfo = get_person_info($key);
                    if ($eventId == 13) {
                        echo $rank.". [COLOR=Blue] ".$this->solveDetails[$eventId][$key]."[/COLOR] ".$personInfo['username']."\n";
                    } elseif ($eventId == 17) {
                        $result = $this->get_user_result($eventId, $key);
                        echo $rank.". [COLOR=Blue] ".$result." (".$this->solveDetails[$eventId][$key].")[/COLOR] ".$personInfo['username']."\n";
                    } else {
                        echo $rank.". [COLOR=Blue] ".$value."[/COLOR] ".$personInfo['username']."\n";
                    }
                    ++$count;
                }
                if ($rank > 3) {
                    echo "[/spoiler]";
                }
                echo "\n";
            }
            $i = 1;
            echo "\n[B]Contest results[/B](".count($this->totalScores).")\n";
            $spoilerDone = false;
            $count = 1;
            $prev = 0;
            $rank = 0;
            foreach ($this->totalScores as $key=>$value) {
                if ($prev != $value) {
                    $rank = $count;
                    $prev = $value;
                    if ($rank > 3 && !$spoilerDone) {
                        echo "[spoiler]\n";
                        $spoilerDone = true;
                    }
                }
                $personInfo = get_person_info($key);
                echo $rank.". [COLOR=Blue] ".$value."[/COLOR] ".$personInfo['username']."\n";
                ++$count;
            }
            if ($rank > 3) {
                echo "[/spoiler]\n";
            }
        }
        
        public function get_event_scores($eventId)
        {
            global $events;
            $i = 1;
            echo $events->name($eventId)."\n";
            echo "---------------------\n";
            foreach ($this->scores[$eventId] as $key=>$value) {
                $personInfo = get_person_info($key);
                echo $i++.$personInfo['displayName']."(".$key.")[".$personInfo['username']."]: ".$this->results[$eventId][$key]."(".$value.")\n";
            }
            echo "\n";
        }
        
        public function &get_score_list()
        {
            return $this->totalScores;
        }
        
        public function get_user_total_score($userId)
        {
            return isset($this->totalScores[$userId]) ? $this->totalScores[$userId] : 0;
        }
        
        public function get_participant_count($eventId)
        {
            return isset($this->results[$eventId]) ? count($this->results[$eventId]) : 0;
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
        
        public function get_user_best($eventId, $userId)
        {
            return $this->bests[$eventId][$userId];
        }
        
        public function get_user_average($eventId, $userId)
        {
            return $this->averages[$eventId][$userId];
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
        
        public function get_kinch_scores_wca()
        {
            return $this->kinchScoresWca;
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
        
        public function get_attempted_events_wca($userId)
        {
            return $this->partialsWca[$userId];
        }
        
        public function is_completed($eventId, $userId)
        {
            return (isset($this->completeds[$userId][$eventId]) && $this->completeds[$userId][$eventId]);
        }
        
        public function is_partial($eventId, $userId)
        {
            return ((isset($this->partials[$userId][$eventId]) && $this->partials[$userId][$eventId]) && (!isset($this->completeds[$userId][$eventId]) || !$this->completeds[$userId][$eventId]));
        }
        
        public function get_result_info($week)
        {
            echo "userIds[$week] = ".json_encode($this->userIds).";\n";
            echo "totalScores[$week] = ".json_encode($this->totalScores).";\n";
            echo "scores[$week] = ".json_encode($this->scores).";\n";
            echo "results[$week] = ".json_encode($this->results).";\n";
        }
    }
    
    function get_order_by_string($eventId, $year)
    {
        global $events;
        if (is_average_event($eventId, $year)) {
            return "average, best";
        }
        return "best";
    }
    
    function update_weeklyResults_rank_field_for_week($year, $week)
    {
        global $mysqli;
        global $events;
        foreach ($events as $eventId => $eventName) {
            $queryString = "SELECT userId, average, best FROM weeklyResults where weekId = $week AND yearId = $year AND eventId = $eventId ORDER BY ".get_order_by_string($eventId, $year);
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
        
        print $year."--".$week."\n";
        
        if ($week === 0) {
            $queryString = "SELECT eventId, userId, weekId, result, solve1, solve2, solve3, solve4, solve5, multiBLD FROM weeklyResults WHERE yearId = $year";
        } else {
            $queryString = "SELECT eventId, userId, weekId, result, solve1, solve2, solve3, solve4, solve5, multiBLD FROM weeklyResults WHERE yearId = $year AND weekId = $week";
        }
        $query = $mysqli->query($queryString);
        while ($row = $query->fetch_array()) {
            $eventId = $row['eventId'];
            $userId = $row['userId'];
            $week = $row['weekId'];
            $solves = array();
            $solveCount = get_solve_count($eventId, $year);
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
                } elseif ($eventId == 32) {
                    $best = $row['result'];
                    if (is_valid_score($best)) {
                        ++$completed;
                    }
                } else {
                    $solves[$i] = $row['solve'.$i];
                    if (is_valid_score($solves[$i])) {
                        if (!is_movecount_scored($eventId)) {
                            $value = $solves[$i] * 100;
                        } else {
                            $value = $solves[$i];
                        }
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
            return round((array_sum($solves) / 3) * 100);
        } elseif ($solveCount == 5 && $completed >= 4) {
            $resMin = PHP_INT_MAX;
            for ($i = 1; $i <= $solveCount; ++$i) {
                if (is_valid_score($solves[$i]) && $solves[$i] < $resMin) {
                    $resMin = $solves[$i];
                }
            }
            $resMax = max($solves);
            return round(((array_sum($solves) - $resMin - $resMax) / 3) * 100); // avg5
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
                $queryString = "SELECT userId, result, weekId, yearId, solve1, solve2, solve3, solve4, solve5, multiBLD, average, best AS min FROM weeklyResults AS current
                                       WHERE eventId = $event $yearsLimitString $userLimitString
                                       AND best != $maxInt
                                       AND NOT EXISTS (SELECT userId, best FROM weeklyResults AS better
                                                              WHERE current.eventId = better.eventId $yearsLimitString $userLimitString
                                                                AND better.best < current.best
                                                                AND better.userId = current.userId)
                                       ORDER BY best";
            } else {
                $queryString = "SELECT userId, result, weekId, yearId, solve1, solve2, solve3, solve4, solve5, multiBLD, average AS min, best FROM weeklyResults AS current
                                       WHERE eventId = $event $yearsLimitString $userLimitString
                                       AND average != $maxInt AND average != 0
                                       AND NOT EXISTS (SELECT userId, average, best FROM weeklyResults AS better
                                                              WHERE current.eventId = better.eventId $yearsLimitString $userLimitString
                                                                AND ((better.average < current.average) OR (better.average = current.average AND better.best < current.best))
                                                                AND better.userId = current.userId
                                                                AND better.average != 0)
                                       ORDER BY average, best";
            }
        } else {
            if ($showSingles) {
                $queryString = "SELECT userId, result, weekId, yearId, solve1, solve2, solve3, solve4, solve5, multiBLD, average, best AS min FROM weeklyResults AS current
                                       WHERE eventId = $event $yearsLimitString $userLimitString
                                       AND best != $maxInt
                                       ORDER BY best $limitString";
            } else {
                $queryString = "SELECT userId, result, weekId, yearId, solve1, solve2, solve3, solve4, solve5, multiBLD, average AS min, best FROM weeklyResults AS current
                                       WHERE eventId = $event $yearsLimitString $userLimitString
                                       AND average != $maxInt AND average != 0
                                       ORDER BY average, best $limitString";
            }
        }
        
        return $mysqli->query($queryString);
    }
    
    function is_valid_score($score) {
        if ($score !== 0 && $score !== 8888 && $score !== 9999 && $score !== '0' && $score !== '8888' && $score !== '9999' && $score !== 'DNF' && $score !== 'DNS' && $score !== '' && $score !== '0.00' && $score !== PHP_INT_MAX && $score !== '2147483647') {
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
    
    function get_solve_count($eventId, $year)
    {
        global $events;
        
        if ($eventId == 17 && ($year > 2018)) {
            // Beginning in 2019 we changed to 3 solves for fewest moves
            return 3;
        }
        
        return $events->num_solves($eventId);
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
        $queryResults = $mysqli->query("SELECT userId, solution FROM weeklyFmcSolves WHERE eventId = 17 AND weekId = $week AND yearId = $year");
        while ($results = $queryResults->fetch_array()) {
            $solution = correct_solution($results['solution']);
            $fmcValue = FMCsolve($scramble, $solution);
            if ($fmcValue > 0) {
            echo $results['userId'].": (".$fmcValue.") ".$solution."<br>";
            } else {
                echo "<div  class='dnf-text'>".$results['userId'].": (".$fmcValue.") ".$solution."</div>";
            }
            if ($solution != $results['solution']) {
            $diff = get_decorated_diff($results['solution'], $solution);
            echo "<div>".$diff['old']."</div>
                  <div>".$diff['new']."</div>";
            }
        }
    }
    
    function get_wca_event_id($eventId)
    {
        switch ($eventId) {
            case 1: return "222";
            case 2: return "333";
            case 3: return "444";
            case 4: return "555";
            case 5: return "666";
            case 6: return "777";
            case 8: return "333bf";
            case 9: return "444bf";
            case 10: return "555bf";
            case 13: return "333mbf";
            case 14: return "333oh";
            case 15: return "333ft";
            case 17: return "333fm";
            case 22: return "clock";
            case 23: return "minx";
            case 24: return "pyram";
            case 25: return "sq1";
            case 26: return "skewb";
        }
        return $eventId;
    }
    
    class Record {
        var $year;
        var $week;
        var $user;
        var $value;

        function __construct($year, $week, $event, $user, $value)
        {
            $this->year = $year;
            $this->week = $week;
            $this->event = $event;
            $this->user = $user;
            $this->value = $value;
        }

        function insert_record($type)
        {
            global $mysqli;
            if ($type === "average") {
                $mysqli->query("INSERT INTO records VALUES ($this->year, $this->week, $this->event, $this->user, 0, $this->value, '', 'SR')");
            } else {
                $mysqli->query("INSERT INTO records VALUES ($this->year, $this->week, $this->event, $this->user, $this->value, 0, 'SR', '')");
            }
        }
    }

    function update_records($year)
    {
        global $mysqli;
        global $events;
        
        $MYSQL_MAX_INT = 2147483647; // PHP_MAX_INT input into the database and back out gives this
        echo "Starting output<br>";
        
        $singleCurrentRecord = array();  // Array of the current best single for each event
        $averageCurrentRecord = array(); // Array of the current best average for each event
        $singleRecords = array();        // Array of single records to insert
        $averageRecords = array();       // Array of average records to insert
        // Initialize the current records
        foreach ($events as $eventId => $eventName) {
            $singleCurrentRecord[$eventId] = $MYSQL_MAX_INT;
            $averageCurrentRecord[$eventId] = $MYSQL_MAX_INT;
        }
        // This could be done with a single query looking for minimum best and average up to the given year, giving only the records that matter, but for now, this will do.
        $query = $mysqli->query("SELECT eventId, best, average FROM records WHERE yearId < $year");
        while ($results = $query->fetch_array()) {
            $best = $results['best'];
            $average = $results['average'];
            $eventId = $results['eventId'];
            if ($best < $singleCurrentRecord[$eventId] && $best != 0) {
                $singleCurrentRecord[$eventId] = $best;
            }
            if ($average < $averageCurrentRecord[$eventId] && $average != 0) {
                $averageCurrentRecord[$eventId] = $average;
            }
        }
        for ($week = 1; ($year == 2007 && $week <= 46) || (($year == 2008 || $year == 2015 || $year == 2020) && $week == 53) || ($year > 2007 && $year < get_current_year() && $week <= 52) || $year == get_current_year() && $week < get_current_week(); ++$week) {
            collect_records_for_week($year, $week, 'best', $singleRecords, $singleCurrentRecord);
        }
        for ($week = 1; ($year == 2007 && $week <= 46) || (($year == 2008 || $year == 2015 || $year == 2020) && $week == 53) || ($year > 2007 && $year < get_current_year() && $week <= 52) || $year == get_current_year() && $week < get_current_week(); ++$week) {
            collect_records_for_week($year, $week, 'average', $averageRecords, $averageCurrentRecord);
        }

        // Delete records for this year. Note that entire table can be done by running against each year in order, starting with 2007 until current year.
        // This was originally done all at once to guarantee database reliability, but because it caused problems with Cloudflare due to taking too long, splitting to by year was necessary.
        $mysqli->query("DELETE FROM records WHERE yearId = $year");
        echo "singles: ".count($singleRecords).", averages: ".count($averageRecords)."<br>";
        foreach ($singleRecords as $record) {
            $record->insert_record("single");
        }
        foreach ($averageRecords as $record) {
            $record->insert_record("average");
        }
    }
    
    function collect_records_for_week($year, $week, $type, &$recordArray, &$currentRecordArray)
    {
        global $mysqli;
        global $events;
        
        foreach ($events as $eventId => $eventName) {
            $query = $mysqli->query("SELECT userId, ".$type." FROM weeklyResults WHERE eventId = $eventId AND weekId = $week AND yearId = $year ORDER BY ".$type);
            while ($results = $query->fetch_array()) {
                $recordResult = $results[$type];
                $userId = $results['userId'];
                if ($recordResult > $currentRecordArray[$eventId] || !is_valid_score($recordResult)) {
                    // All items in the query after this are not records
                    break;
                }
                // If we got here, it's a record; mark it as such
                $recordArray[] = new Record($year, $week, $eventId, $userId, $recordResult);
                $currentRecordArray[$eventId] = $recordResult;
            }
        }
    }
    
    function event_list()
    {
        global $events;
        
        $eventIds = array();
        foreach ($events as $eventId => $eventName) {
            if (is_active_event($eventId, get_current_week(), get_current_year())) {
                $eventIds[] = $eventId;
            }
        }
        
        return $eventIds;
    }
    
    function sorted_event_list_by_previous_week($week, $year)
    {
        global $mysqli;
        global $events;
        
        $weekPrev = get_previous_week();
        $yearPrev = get_previous_week_year();
        $eventIds = array();
        // First add all WCA events in normal order
        foreach ($events as $eventId => $eventName) {
            if (is_wca_event($eventId)) {
                $eventIds[] = $eventId;
            }
        }
        
        // Now add non-WCA events in order according to their popularity the previous week
        $results = $mysqli->query("select eventId, count(eventId) from weeklyResults where yearId = ".$yearPrev." and weekId = ".$weekPrev." group by eventId order by count(eventId) desc");
        while ($row = $results->fetch_assoc()) {
            $eventId = $row['eventId'];
            if (!is_wca_event($eventId)) {
                $eventIds[] = $eventId;
            }
        }

        // Now add any other current events that might not have been competed in last week
        foreach ($events as $eventId => $eventName) {
            if (is_active_event($eventId, $week, $year) && !in_array($eventId, $eventIds)) {
                $eventIds[] = $eventId;
            }
        }

        return $eventIds;
    }
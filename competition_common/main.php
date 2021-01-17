<?php
    require_once '../competition_common/jwt.php';
    require_once '../competition_common/token.php';
    $options = array("options" => array("regexp" => "/^[^,;}{]*$/")); // Unfortunately, I guess I need to blacklist here because I need to allow non-standard English characters
    $side = filter_input(INPUT_GET, 'side', FILTER_VALIDATE_REGEXP, $options);
    $showRecords = filter_input(INPUT_GET, 'showRecords', FILTER_VALIDATE_INT);
    $defaultManualEntry = filter_input(INPUT_POST, 'defaultManualEntry', FILTER_VALIDATE_BOOLEAN);
    $fmcResultInfo = filter_input(INPUT_GET, 'fmcResultInfo', FILTER_VALIDATE_REGEXP, $options);
    
    if (isset($freshSessionType)) {
        $date = date('Y-m-d H:i:s');
        $remoteAddress = $_SERVER['REMOTE_ADDR'];
        $mysqli->query("insert into logins (userId, sessionType, IP, time) VALUES ($currentUserId, '$sessionType', '$remoteAddress', '$date')");
        unset($freshSessionType);
    }
    
    // Get the week
    $sessWeek = filter_input(INPUT_GET, 'sessWeek', FILTER_VALIDATE_INT);
    if ($sessWeek) {
        // Admin access
        if (is_admin()) 
            {$_SESSION['weekNo'] = $sessWeek;}
        else 
            {$_SESSION['weekNo'] = get_current_week();}
    } else { // Set to default
        $_SESSION['weekNo'] = get_current_week();
    }
	
    // Get the year
    $sessYear = filter_input(INPUT_GET, 'sessYear', FILTER_VALIDATE_INT);
    if($sessYear) {
        // Admin access
        if (is_admin())
            {$_SESSION['yearNo']=$sessYear;}
        else 
            {$_SESSION['yearNo'] = get_current_year();}
    } else { // Set to default
        $_SESSION['yearNo']= get_current_year();
    }
	
    /*** SESSION WEEK STORAGE!!! ***/
    if($_SESSION['weekNo']){
        $weekNo=$_SESSION['weekNo'];
        $yearNo=$_SESSION['yearNo'];
    }

    $site = $side;
    if (!$side) {
        if (isset($currentUserId)) {
            $site = "weeklyView";
        } else {
            $site = "forside";
        }
    }

    //decide colour scheme
    $color['background']="b2ec96"; // CCC
    $color['content']="f6ff62"; // CE2
    $color['waffo']="256b04"; // 3E3
    $color['odder']="111222"; //000


    /********** START OF FUNCTIONS **********/

    // pretty numbers
    function prettyNumber($uglyNumber)
    {
            if($uglyNumber == 8888){return "DNF";}
            if($uglyNumber == 9999){return "DNS";}
            $seconds=floatval($uglyNumber);
            if($seconds>=60){
                    $minutes=0;
                    while($seconds>=60){
                            $minutes++;
                            $seconds=$seconds-60;
                    }
                    if ($seconds<10){
                            $prettyNumber=$minutes . ":0" . number_format($seconds, 2, '.', '');
                    } else {
                            $prettyNumber=$minutes . ":" . number_format($seconds, 2, '.', '');
                    }
                    return $prettyNumber;
            } 
            return number_format($seconds, 2, '.', '');
    }

    // ugly number
    function uglyNumber($prettyNumber){
            if($prettyNumber == "DNS") { return 9999; }
            if($prettyNumber == "DNF") { return 8888; }
            if($prettyNumber === "" || $prettyNumber === "0") { return 9999; }
            $prettyNumber = str_ireplace("-","",$prettyNumber);
            $prettyNumber=explode(":",$prettyNumber);
            if(count($prettyNumber)==2){
                    $uglyNumber=(floatval($prettyNumber['0'])*60)+floatval(str_ireplace(",",".",$prettyNumber['1']));
            } else {
                    $uglyNumber=floatval(str_ireplace(",",".",$prettyNumber['0']));
            }
            return $uglyNumber;
    }

    // Count moves for FMC
    function countMoves($solution){
        $validMoves = array("U","R","L","B","D","F","M","M","E","E","S","S");
        foreach ($validMoves as $move){
                $moveCount += substr_count($solution, $move);
        }
        if ($solution=="DNF") {
            return 8888;
        }
        return $moveCount;
    }
    
    function get_fmc_result_from_string($fmcResultString)
    {
        global $events;
        
        $resultString = "";
        if ($fmcResultString != "") {
            $eventResults = explode("|", substr($fmcResultString, 1));
            $resultString .= "<span class='data-panel fmc-result'>";
            $eventNumber = 0;
            foreach ($eventResults as $eventResult) {
                $results = explode(":", $eventResult);
                $solveNumber = 0;
                foreach ($results as $result) {
                    if ($solveNumber == 0) {
                        $result = intval($result);
                        if ($result > 0 && $result <= $events->count()) {
                            $resultString .= $events->name($result)." Results:<br>";
                        } else {
                            // Illegal entry in URL
                            break;
                        }
                    } else {
                        if ($result == "D") {
                            $resultString .= "Solve ".$solveNumber." was submitted as a DNF.<br>";
                        } else if ($result == "X") {
                            $resultString .= "Solve ".$solveNumber." was not successful; submitted as DNF.<br>";
                        } else if ($result == "S" || $result == "") {
                            // Don't print anything for this one - it hasn't been attempted yet.
                        } else {
                            $resultString .= "Solve ".$solveNumber." successfully solved in ".$result." moves!<br>";
                        }
                    }

                    ++$solveNumber;
                }
                ++$eventNumber;
                if ($eventNumber == count($eventResults)) {
                    $resultString .= "</span>";
                } else {
                    $resultString .= "</span><span class='data-panel fmc-result'>";
                }
            }
        }
        
        return $resultString;
    }

    /********** END OF FUNCTIONS **********/
	
    // check for updates
    $update = isset($_POST['update']) ? $_POST['update'] : '';

    // Creating a new user
    if ($update == "addUser.php"){
        if (is_admin()) {
            $username = filter_input(INPUT_POST, 'username', FILTER_VALIDATE_REGEXP, $options);
            $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
            $firstName = filter_input(INPUT_POST, 'firstName', FILTER_VALIDATE_REGEXP, $options);
            $lastName = filter_input(INPUT_POST, 'lastName', FILTER_VALIDATE_REGEXP, $options);
            $newPassword = hashPassword($_POST['password']);
            $password2 = hashPassword($_POST['password2']);
            $site = "opretBruger";
            $statement = $mysqli->prepare("SELECT username FROM userlist WHERE username = ?");
            $statement->bind_param("s", $username);
            $statement->execute();
            $statement->bind_result($usernameMatch);
            $statement->fetch();
            if (!$username) {echo "error: Username was left empty";}
            elseif ($usernameMatch == $username) {echo "error: Username is already in use";}
            elseif (!$firstName) {echo "error: First name was invalid";}
            elseif (!$lastName) {echo "error: Last name was invalid";}
            elseif (!$email) {echo "error: Email was invalid";}
            elseif (!$newPassword) {echo "error: Password was left empty";}
            elseif ($newPassword != $password2) {echo "error: New passwords did not match";}
            else {
                $statement->close();
                $statement = $mysqli->prepare("INSERT INTO userlist (firstName, lastName, username, email, password) VALUES (?, ?, ?, ?, ?)");
                $statement->bind_param("sssss", $firstName, $lastName, $username, $email, $newPassword);
                if ($statement->execute()) {
                    echo "success! $firstName $lastName, managed to succesfully create the user $username";
                    $statement->close();
                    $statement = $mysqli->prepare("SELECT id, firstName, username FROM userlist WHERE username = ?");
                    $statement->bind_param("s", $username);
                    $statement->execute();
                    $statement->bind_result($loggedInId, $loggedInFirstName, $loggedInUsername);
                    $statement->fetch();
                    $statement->close();
                    $_SESSION['logged_in'] = $loggedInId;
                    $_SESSION['firstName'] = $loggedInFirstName;
                    $_SESSION['usName'] = $loggedInUsername;
                    $currentUserId = $loggedInId;
                    $site = "weeklyView";
                } else {
                    echo "failed to create the user $username";
                    $statement->close();
                    $site = "forside";
                }
            }
        } else {
            echo "YOU DON'T HAVE ACCESS!";
            $site="forside";
        }
    }

    // Change profile info
    if ($update == "changeProfile.php") {
        if ($sessionType == "old") {
            // Old version - allow changing password and email
            $oldPassword = hashPassword($_POST['oldPassword']);
            $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
            $firstName = filter_input(INPUT_POST, 'firstName', FILTER_VALIDATE_REGEXP, $options);
            $lastName = filter_input(INPUT_POST, 'lastName', FILTER_VALIDATE_REGEXP, $options);
            $hideNames = filter_input(INPUT_POST, 'hideNames', FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

            $retrievedPassword = $mysqli->query("SELECT password FROM userlist WHERE id='$currentUserId'")->fetch_array();
            if (!$firstName) {
                echo "<b>Error: First name was invalid - data not changed!!!</b><br>";
            } elseif (!$lastName) {
                echo "<b>Error: Last name was invalid - data not changed!!!</b><br>";
            } elseif (!$email) {
                echo "<b>Error: Email was invalid - data not changed!!!</b><br>";
            } elseif ($retrievedPassword['password'] != $oldPassword) {
                echo "<b>Old password was invalid - data not changed!!!</b><br>";
            } elseif ($_POST['newPassword']) {
                $newPassword = hashPassword($_POST['newPassword']);
                $password2 = hashPassword($_POST['password2']);
                if ($newPassword != $password2) {
                    echo "New passwords did not match - changes discarded";
                } else {
                    $statement = $mysqli->prepare("UPDATE userlist SET password = ?, email = ?, firstName = ?, lastName = ?, hideNames = ? WHERE id = ? AND password = ?");
                    $statement->bind_param("ssssiis", $newPassword, $email, $firstName, $lastName, $hideNames, $currentUserId, $oldPassword);
                    $statement->execute();
                    $statement->close();
                    $result = $mysqli->query("SELECT * FROM userlist WHERE id='$currentUserId'")->fetch_array();
                    if ($result['password'] == $newPassword) {
                        echo "Password changed...";
                    } else {
                        echo "Failed to change password!";
                    }
                }
            } else {
                $statement = $mysqli->prepare("UPDATE userlist SET email = ?, firstName = ?, lastName = ?, hideNames = ? WHERE id = ? AND password = ?");
                $statement->bind_param("sssiis", $email, $firstName, $lastName, $hideNames, $currentUserId, $oldPassword);
                $statement->execute();
                $statement->close();
            }
        } else {
            // New version - inside XenForo - just allow changing name and name visibility
            $firstName = filter_input(INPUT_POST, 'firstName', FILTER_VALIDATE_REGEXP, $options);
            $lastName = filter_input(INPUT_POST, 'lastName', FILTER_VALIDATE_REGEXP, $options);
            $hideNames = filter_input(INPUT_POST, 'hideNames', FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

            if (!$firstName) {
                echo "<b>Error: First name was invalid - data not changed!!!</b><br>";
            } elseif (!$lastName) {
                echo "<b>Error: Last name was invalid - data not changed!!!</b><br>";
            } else {
                $statement = $mysqli->prepare("UPDATE userlist SET firstName = ?, lastName = ?, hideNames = ? WHERE id = ?");
                $statement->bind_param("ssii", $firstName, $lastName, $hideNames, $currentUserId);
                $statement->execute();
                $statement->close();
            }
        }
        
        // Redirect to index.php so it will refresh without POST - this fixes the menus for the Forum.
        header("Location: index.php?side=forside");
        exit;
    }

    // Resetting passwords
    if($update=="resetPassword.php") {
        if (is_admin()) {
            $resetUserId=$_POST['userID'];
            $password=$_POST['password'];
            $password = hashPassword($password);
            $mysqli->query("UPDATE userlist SET password='$password' WHERE id='$resetUserId'");
            echo "password changed...";
        } else {
            echo "YOU DON'T HAVE ACCESS!";
        }
        $site="forside";
    }

    // submitting results
    if($update=="weeklySubmit") {
        if (!$_POST['encoding']) {
            echo "<script> location.href='index.php'; </script>";
            exit;
        }
        $oldWeekNo = $weekNo;
        $oldYearNo = $yearNo;
        try {
            $tokenizer = new JWT();
            $result = $tokenizer->decode($_POST['encoding'], $token_key);
            $userId = $result->userId;
            $weekNo = $result->weekNo;
            $yearNo = $result->yearNo;
        } catch (Exception $ex) {
            // invalid token; redirect
            echo "<script> location.href='index.php'; </script>";
            exit;
        }
        // running through each event!!!
        $fmcResultString = "";
        foreach ($events as $eventId => $eventName) {
            $weeklyStuff = "weekly" . $eventId . "Result";
            $solveCount = get_solve_count($eventId, $yearNo);
            $comment = isset($_POST['weeklyComment'.$eventId]) ? str_ireplace("\n","<br />", $_POST['weeklyComment'.$eventId]) : "";
            $average = 0;
            unset($best);
            $solves = array();
            $solutions = array();
            $times = array();
            $moves = array(); // move count for FMC
            $hasEventData = false;
            $completedSolves = 0;
            $iterCount = $solveCount;
            if ($eventId == 13) {
                $iterCount = 3; // 3 values to process for multiBLD - cubes succeeded, cubes attempted, time
            }
            for ($i = 1; $i <= $iterCount; ++$i) {
                if (ISSET($_POST[$weeklyStuff.$i]) || (is_fewest_moves($eventId) && $eventId != 32 && ISSET($_POST["weekly".$eventId."Solution".$i]))) {
                    $hasEventData = true;
                    if ($eventId == 13) {
                        $solves[$i] = $_POST[$weeklyStuff.$i];
                    } elseif (is_fewest_moves($eventId) && $eventId != 32) {
                        $solutions[$i] = $_POST["weekly".$eventId."Solution".$i];
                        $explanations[$i] = str_ireplace("\n","<br />", $_POST["weekly".$eventId."Explanation".$i]);
                        $times[$i] = 0;
                        if (isset($_POST["weekly".$eventId."Time".$i])) {
                            $times[$i] = ugly_number($_POST["weekly".$eventId."Time".$i]);
                        }
                    } else {
                        $solves[$i] = ugly_number($_POST[$weeklyStuff.$i]);  // Need to change so results are sent in proper format
                    }
                }
            }
            if (!$hasEventData) {
                continue;
            }

            if (is_fewest_moves($eventId) && $eventId != 32) {
                // Preprocessor for fewest moves to calculate results and verify solves
                $explodeScrambles = explode("<br />",get_scramble_text($eventId, $weekNo, $yearNo));
                $solveNumber = 0;
                $resultCount = 0;
                $eventResultString = "|$eventId:";
                foreach ($solutions as $solution) {
                    $scramble = substr($explodeScrambles[$solveNumber], 20);
                    ++$solveNumber;
                    if ($solution != "DNF" && $solution != "DNS" && $solution != "" && $scramble != "") {
                        $solution = correct_solution($solution);
                        $solves[$solveNumber] = FMCsolve($scramble, $solution);
                        $moves[$solveNumber] = $solves[$solveNumber];
                        if ($solves[$solveNumber]) {
                            // Solution was successful; give message to user indicating the success and number of moves
                            $eventResultString .= $solves[$solveNumber].":";
                            ++$resultCount;
                            if ($eventId == 36) {
                                $solves[$solveNumber] = get_speed_FMC_result($moves[$solveNumber], $times[$solveNumber]);
                            }
                        } else {
                            $solves[$solveNumber] = 8888;
                            $moves[$solveNumber] = 8888;
                            $solution = "DNF";
                            $eventResultString .= "X:";
                            ++$resultCount;
                        }
                    } elseif ($solution == "DNF") {
                        $solves[$solveNumber] = 8888;
                        $moves[$solveNumber] = 8888;
                        $eventResultString .= "D:";
                            ++$resultCount;
                    } else {
                        $solves[$solveNumber] = 9999;
                        $moves[$solveNumber] = 9999;
                        $eventResultString .= "S:";
                    }
                }
                if ($resultCount > 0) {
                    $fmcResultString .= $eventResultString;
                }
            }

            if ($eventId == 13) {
                $suc = $solves[1];
                $try = $solves[2];
                $time = $solves[3];
                if ($time == 'DNF') {
                   $result = 8888;
                   $solves = array (1 => 999999999);
                } elseif (is_numeric($suc) && is_numeric($try)) {
                    $solves = array( 1 => MBLD_to_number($suc, $try, $time));
                    $result = 2 * $suc - $try;
                    if ($result < 0 || ($result == 0 && $try == 2)) {
                        $result = 8888; 
                    } elseif ($try < 2) {
                        $result = 9999;
                    } else {
                        $completedSolves = 1;
                    }
                } else {
                    $result = 0;
                }
                if($solves[1] == 99000000) {
                   $solves[1] = 0;
                }
                if ($solves[1] == 8888) {
                    $solves[1] = 999999999;
                }
                $best = $solves[1];
            } elseif ($solveCount == 5) {
                $completedSolves = 0;
                // count if we should calculate avg
                foreach ($solves as $solve) {
                    if (is_valid_score($solve)) {
                        ++$completedSolves;
                    }
                }
                $best = get_best_result($solveCount, $solves);
                if ($completedSolves >= $solveCount - 1) {
                    // check if first time is DNS, then delete row
                    $resMax = max($solves);
                    $resMin = $best;
                    $result = (array_sum($solves) - $resMin - $resMax) / 3; // avg5
                    $average = round_score($result);
                } else { // too bad, you get DNF avg ;) 
                    $result = 8888;
                    $average = PHP_INT_MAX;
                }
            } elseif ($solveCount == 3) {
                $completedSolves = 0;
                // count if we should calculate avg
                foreach ($solves as $solve) {
                    if (is_valid_score($solve)) {
                        ++$completedSolves;
                    }
                }
                for ($m = 1; $m <= $solveCount; $m++) {
                    // if 0 && result on next solve this is dnf
                    if ($solves[$m] === 0 && ($solves[$m+1] || $solves[$m+2])) {
                        $solves[$m] = 8888;
                    }
                   // if 0 && result on prev. solve this is dns
                    if ($solves[$m] == 0 && ($solves[$m-1] || $solves[$m-2])) {
                        $solves[$m] = 9999;
                    }
                }
                $best = get_best_result($solveCount, $solves);
                if ($completedSolves == $solveCount) {
                    $average = round_score(array_sum($solves) / 3);
                } else {
                    $average = PHP_INT_MAX;
                }
                if ($eventId == 17) {
                    // Fewest moves winner is determined by mean of 3
                    if ($average != PHP_INT_MAX) {
                        $result = $average;
                    } else {
                        $result = 8888;
                    }
                } else {
                    $result = $best;
                }
            } elseif ($solveCount == 1) {
                $best = get_best_result($solveCount, $solves);
                $result = $best;
                if (is_valid_score($result)) {
                   ++$completedSolves;
                }
            }
            if ($best == 8888) {
                $best = PHP_INT_MAX;
            }
            // average and best are expressed in centiseconds so they can be accurate as integers in the database
            if ($average != PHP_INT_MAX) {
                $average = round($average * 100);
            }
            if ($best != PHP_INT_MAX && $eventId != 13 && $eventId != 17) {
                $best = round($best * 100);
            }

            // Inserting times
            $k = 0;
            $existence = $mysqli->query("SELECT * FROM weeklyResults WHERE weekId='$weekNo' AND yearId='$yearNo' AND userId='$userId' AND eventId='$eventId'")->num_rows;
            foreach ($solves as $solve){
                if ($eventId != 13 && (!is_fewest_moves($eventId) || $eventId == 32)) {
                    $solve = uglyNumber($solve);
                }
                $k++;
                $solveId= "solve" . $k;
                if ($existence == 0) { // If row doesn't already exist
                    if ($solve) {
                        if ($eventId != 13 && (!is_fewest_moves($eventId) || $eventId == 32)) {
                            $statement = $mysqli->prepare("INSERT INTO weeklyResults ($solveId, weekId, yearId, eventId, userId) VALUES (?, ?, ?, ?, ?)");
                            $statement->bind_param("diiii", $solve, $weekNo, $yearNo, $eventId, $userId);
                            $statement->execute();
                            $statement->close();
                            $existence = 1;
                        } elseif ($eventId == 13) {
                            $statement = $mysqli->prepare("INSERT INTO weeklyResults (multiBLD, weekId, yearId, eventId, userId) VALUES (?, ?, ?, ?, ?)");
                            $statement->bind_param("iiiii", $solve, $weekNo, $yearNo, $eventId, $userId);
                            $statement->execute();
                            $statement->close();
                        } else {
                            $statement = $mysqli->prepare("INSERT INTO weeklyResults ($solveId, weekId, yearId, eventId, userId) VALUES (?, ?, ?, ?, ?)");
                            $statement->bind_param("diiii", $solve, $weekNo, $yearNo, $eventId, $userId);
                            $statement->execute();
                            $statement->close();
                            $statement = $mysqli->prepare("INSERT INTO weeklyFmcSolves (yearId, weekId, userId, eventId, solveId, moves, time, solution, comment) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            $statement->bind_param("iiiiiidss", $yearNo, $weekNo, $userId, $eventId, $k, $moves[$k], $times[$k], $solutions[$k], $explanations[$k]);
                            $statement->execute();
                            $statement->close();
                            $existence = 1;
                        }
                   }
                } else { // If row exists
                    if ($eventId != 13 && (!is_fewest_moves($eventId) || $eventId == 32)) {
                        $statement = $mysqli->prepare("UPDATE weeklyResults SET $solveId = ? WHERE weekId = ? AND yearId = ? AND eventId = ? AND userId = ?");
                        $statement->bind_param("diiii", $solve, $weekNo, $yearNo, $eventId, $userId);
                        $statement->execute();
                        $statement->close();
                    } elseif ($eventId == 13) {
                        $statement = $mysqli->prepare("UPDATE weeklyResults SET multiBLD = ? WHERE weekId = ? AND yearId = ? AND eventId = ? AND userId = ?");
                        $statement->bind_param("iiiii", $solve, $weekNo, $yearNo, $eventId, $userId);
                        $statement->execute();
                        $statement->close();
                    } else {
                        $statement = $mysqli->prepare("UPDATE weeklyResults SET $solveId = ? WHERE weekId = ? AND yearId = ? AND eventId = ? AND userId = ?");
                        $statement->bind_param("diiii", $solve, $weekNo, $yearNo, $eventId, $userId);
                        $statement->execute();
                        $statement->close();
                        $fmcExistence = $mysqli->query("SELECT * FROM weeklyFmcSolves WHERE yearId='$yearNo' AND weekId='$weekNo' AND userId='$userId' AND eventId='$eventId' AND solveId='$k'")->num_rows;
                        if ($fmcExistence === 0) {
                            $statement = $mysqli->prepare("INSERT INTO weeklyFmcSolves (yearId, weekId, userId, eventId, solveId, moves, time, solution, comment) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            $statement->bind_param("iiiiiidss", $yearNo, $weekNo, $userId, $eventId, $k, $moves[$k], $times[$k], $solutions[$k], $explanations[$k]);
                            $statement->execute();
                            $statement->close();
                        } else {
                            $statement = $mysqli->prepare("UPDATE weeklyFmcSolves SET solution = ?, comment = ?, moves = ?, time = ? WHERE yearId = ? AND weekId = ? AND userId = ? AND eventId = ? AND solveId = ?");
                            $statement->bind_param("ssidiiiii", $solutions[$k], $explanations[$k], $moves[$k], $times[$k], $yearNo, $weekNo, $userId, $eventId, $k);
                            $statement->execute();
                            $statement->close();
                        }
                    }
                }
            }
            
            // HAZ DELETE SKILLS!
            if ($solves[1] == "DNS" || ((($solves[1] < 0.4) && $solves[1] != "DNF") && (!is_fewest_moves($eventId))) || $solves[1] == 9999) {
                $mysqli->query("DELETE FROM weeklyResults WHERE weekId='$weekNo' AND yearId='$yearNo' AND userId='$userId' AND eventId='$eventId'");
                $mysqli->query("DELETE FROM weeklyFmcSolves WHERE yearId='$yearNo' AND weekId='$weekNo' AND userId='$userId' AND eventId='$eventId'");
            }

            // Add result and comments
            if (!($eventId == 17 && $result < 10 )) {
                $statement = $mysqli->prepare("UPDATE weeklyResults SET result = ?, comment = ?, average = ?, best = ?, completed = ? WHERE weekId = ? AND yearId = ? AND userId = ? AND eventId = ?");
                $statement->bind_param("dsiiiiiii", $result, $comment, $average, $best, $completedSolves, $weekNo, $yearNo, $userId, $eventId);
                $statement->execute();
                $statement->close();
            }
        }
        
        // Update precalculated data if this is an old week being edited
        if (is_admin() && ($weekNo != get_current_week() || ($yearNo != get_current_year()))) {
            update_weeklyResults_calculated_fields($yearNo, $weekNo);
        }
        $weekNo = $oldWeekNo;
        $yearNo = $oldYearNo;
        
        // Redirect to index.php so it will refresh without POST - this fixes the menus for the Forum.
        if ($defaultManualEntry) {
            header("Location: index.php?side=weeklySubmit&fmcResultInfo=$fmcResultString");
        } else {
            header("Location: index.php?fmcResultInfo=$fmcResultString");
        }
        exit;
    }

    // Logging in
    $text = "";
    if($update=="logIn.php"){
        $username = $_POST['brugernavn'];
        $password = hashPassword($_POST['password']);
        $statement = $mysqli->prepare("SELECT id, firstName, username, password FROM userlist WHERE username = ?");
        $statement->bind_param("s", $username);
        $statement->execute();
        $statement->bind_result($loggedInId, $loggedInFirstName, $loggedInUsername, $loggedInPassword);
        $statement->fetch();
        $statement->close();
        if($password == $loggedInPassword){
            // First check to see if an issue exists against this user
            $result = $mysqli->query("SELECT * FROM issue WHERE userId = $loggedInId AND (status = 'resolve' OR status = 'disable')");
            if ($result->num_rows > 0) {
                // Transfer to new page to collect issue entry from user if still awaiting one. Otherwise output this message to let the user know
                // they must wait for a response before they are allowed to compete again.
                // For now, as a temporary measure, just output the discussion so far along with a message that it needs resolving by contacting me.
                $row = $result->fetch_array();
                $issue = $row['issueId'];
                $entry = $mysqli->query("SELECT * FROM discussion WHERE issueId = $issue");
                $text = "The following issue is still not resolved. A moderator must consider your response and be sufficiently satisfied to resolve the issue in order for you to compete again:<br><br>";
                while ($row2 = $entry->fetch_array()) {
                    $text .= $row2['entry']."<br><br>";
                }
                $text .= "Please contact Mike Hughey via Speedsolving.com to resolve the issue.";
                echo "<h1>$text</h1>";
            } else {
                $_SESSION['logged_in'] = $loggedInId;
                $_SESSION['firstName'] = $loggedInFirstName;
                $_SESSION['usName'] = $loggedInUsername;
                $currentUserId = $loggedInId;
                
                // Redirect to index.php so it will refresh cleanly.
                if ($sessionType == "new") {
                    if ($defaultManualEntry) {
                        header("Location: index.php?side=weeklySubmit");
                    } else {
                        header("Location: index.php");
                    }
                    exit;
                }

                // For outside forum - just change the $site appropriately.
                $site="weeklyView";
                if ($defaultManualEntry) {
                    $site="weeklySubmit";
                }
            }
        } else {
            echo "Failed to log in!";
        }
    }

    if ($site=="logud") {
        session_unset();
        unset($currentUserId);
    }

    // emptying update :)
    $update="";

    //check what f to include
    if ($site=="rules") {
        $side="rules.php";
    } elseif (is_admin() && $site=="opretBruger") {
        $side="opretBruger.php";
    } elseif ($site=="weeklySubmit") {
        if (isset($currentUserId)) { $side="weeklySubmit.php"; } else { $side="forside.php"; }
    } elseif ($site=="weeklyView") {
        if (isset($currentUserId)) { $side="weeklyView.php"; } else { $side="forside.php"; }
    } elseif ($site=="weeklyShow") {
        $side="showWeekly.php";
    } elseif ($site=="showWeekly") {
        $side="showWeekly.php";
    } elseif ($site=="visArtikel") {
        $side="newWeek.php";
    } elseif ($site=="settings") {
        $side="settings.php";
    } elseif ($site=="timer") {
        if (!isset($currentUserId)) {
            $side="forside.php";
        } else {
            $side="timer.php";
        }
    } elseif ($site=="showUsers") {
        $side="userList.php";
    } elseif ($showRecords > 0) {
        $side = "showPersonalRecords.php";
    } elseif($site=='resetPassword') {
        if (is_admin()) {$side="resetPassword.php";} else {echo "YOU DON'T HAVE ACCESS!"; $side="forside.php";}
    } elseif ($site=='changeName') {
        if (is_admin()) {$side="changeName.php";} else {echo "YOU DON'T HAVE ACCESS!"; $side="forside.php";}
    } elseif (isset($currentUserId) && $site != "forside") {
        $side = "weeklyView.php";
    } else {
        $side="forside.php";
    }
    
    if (isset($fmcResultInfo) && $sessionType == "new") {
        echo "<div class='centerText'>";
        echo get_fmc_result_from_string($fmcResultInfo);
        echo "</div>";
    }
    
    // Handle welcome messages for new logins to the Forum-integrated version
    if (isset($personInfo['newInitialUser'])) {
        print <<<EOD
        <div class="centerText">
        <br>
        Welcome to the new integrated Forum Competition!<br>
        <br>
        We were unable to detect a previous account with this username, so you have been assigned a new account. 
        You may change your name information on the Competition Profile page, which you can reach from the menu above.
        <br>
        If you already had a Forum Competition account and have received this message, we would like to link your old
        and new accounts. Please contact Mike Hughey by PM on the Forum, and he will merge your old and new
        account, so that your history will be kept consolidated. Thank you for helping us keep the Forum Competition
        history consistent!<br>
        <br>
        </div>
EOD;
        unset($personInfo['newInitialUser']);
    } else if (isset($personInfo['newReturnUser'])) {
        print <<<EOD
        <div class="centerText">
        Welcome to the new integrated Forum Competition! We see this is your first time using the new system!<br>
        <br>
        Your current username matches a username that is already in the Forum Competition, so you will be competing with
        the same account as before, and your history should be maintained. Now, logging into the Forum will also grant access
        to the Competition. Thank you for continuing to participate in our Weekly Competition!
        <br>
        Please feel free to contact Mike Hughey by PM if you have any questions.<br>
        <br>
        </div>
EOD;
        unset($personInfo['newReturnUser']);
    } else if (isset($personInfo['oldReturnUser'])) {
    //} else if ($sessionType == "old") {
        print <<<EOD
        <div class="centerText">
        The new integrated version of the Weekly Competition is now available.
        Consider joining us within the Forum at <a href='https://www.speedsolving.com/competition'>www.speedsolving.com/competition</a>, or by choosing the "Competition" menu in the Forum!
        <br>
        </div>
EOD;
        unset($personInfo['oldReturnUser']);
    }
    
    $result = $mysqli->query("SELECT * FROM issue WHERE userId = $currentUserId AND (status = 'resolve' OR status = 'disable')");
    if ($result && $result->num_rows > 0) {
        // Transfer to new page to collect issue entry from user if still awaiting one. Otherwise output this message to let the user know
        // they must wait for a response before they are allowed to compete again.
        // For now, as a temporary measure, just output the discussion so far along with a message that it needs resolving by contacting me.
        $row = $result->fetch_array();
        $issue = $row['issueId'];
        $entry = $mysqli->query("SELECT * FROM discussion WHERE issueId = $issue");
        $text = "The following issue is still not resolved. A moderator must consider your response and be sufficiently satisfied to resolve the issue in order for you to compete again:<br><br>";
        while ($row2 = $entry->fetch_array()) {
            $text .= $row2['entry']."<br><br>";
        }
        $text .= "Please contact Mike Hughey via Speedsolving.com to resolve the issue.";
        echo "<h1>$text</h1>";
        sync\XF::render(array('content' => ob_get_clean()));
        exit;
    }
?>

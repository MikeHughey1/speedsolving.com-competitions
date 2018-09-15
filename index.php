<?php
    require_once 'newconnect.php';
    require_once 'statFunctions.php';

    require_once 'jwt.php';
    require_once 'token.php';
    $options = array("options" => array("regexp" => "/^[^,;}{]*$/")); // Unfortunately, I guess I need to blacklist here because I need to allow non-standard English characters
    $side = filter_input(INPUT_GET, 'side', FILTER_VALIDATE_REGEXP, $options);
    $showPerson = filter_input(INPUT_GET, 'showPerson', FILTER_VALIDATE_INT);
    $showRecords = filter_input(INPUT_GET, 'showRecords', FILTER_VALIDATE_INT);
    $defaultManualEntry = filter_input(INPUT_POST, 'defaultManualEntry', FILTER_VALIDATE_BOOLEAN);
    
    // Get the week
    $sessWeek = filter_input(INPUT_GET, 'sessWeek', FILTER_VALIDATE_INT);
    if ($sessWeek) {
        // Admin access
        if (is_admin()) 
            {$_SESSION['weekNo']=$sessWeek;}
        else 
            {$_SESSION['weekNo']=gmdate("W",strtotime('-1 day'));}
    } else { // Set to default
        $_SESSION['weekNo']=gmdate("W",strtotime('-1 day'));
    }
	
    // Get the year
    $sessYear = filter_input(INPUT_GET, 'sessYear', FILTER_VALIDATE_INT);
    if($sessYear) {
        // Admin access
        if (is_admin())
            {$_SESSION['yearNo']=$sessYear;}
        else 
            {$_SESSION['yearNo']=gmdate("o",strtotime('-1 day'));}
    } else { // Set to default
        $_SESSION['yearNo']=gmdate("o",strtotime('-1 day'));
    }
	
    /*** SESSION WEEK STORAGE!!! ***/
    if($_SESSION['weekNo']){
        $weekNo=$_SESSION['weekNo'];
        $yearNo=$_SESSION['yearNo'];
    }

    $userId=$_SESSION['logged_in'];

    if(!$side && !$showPerson) { $site="forside"; } else {$site=$side;}

    //decide colour scheme
    $color['background']="b2ec96"; // CCC
    $color['content']="f6ff62"; // CE2
    $color['waffo']="256b04"; // 3E3
    $color['odder']="111222"; //000


    /********** START OF FUNCTIONS **********/

    // pretty numbers
    function prettyNumber($uglyNumber)
    {
            if($uglyNumber == 8888){return DNF;}
            if($uglyNumber == 9999){return DNS;}
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
    /********** END OF FUNCTIONS **********/
	
    // check for updates
    $update = $_POST['update'];

    // Creating a new user
    if ($update == "addUser.php"){
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
                $userId = $loggedInId;
                $site = "weeklyView";
            } else {
                echo "failed to create the user $username";
                $statement->close();
                $site = "forside";
            }
        }
    }

    // Change profile info
    if ($update == "changeProfile.php") {
        $userID = $_SESSION['logged_in'];
        $oldPassword = hashPassword($_POST['oldPassword']);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $firstName = filter_input(INPUT_POST, 'firstName', FILTER_VALIDATE_REGEXP, $options);
        $lastName = filter_input(INPUT_POST, 'lastName', FILTER_VALIDATE_REGEXP, $options);
        $hideNames = filter_input(INPUT_POST, 'hideNames', FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        
        $retrievedPassword = $mysqli->query("SELECT password FROM userlist WHERE id='$userID'")->fetch_array();
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
                $statement->bind_param("ssssiis", $newPassword, $email, $firstName, $lastName, $hideNames, $userID, $oldPassword);
                $statement->execute();
                $statement->close();
                $result = $mysqli->query("SELECT * FROM userlist WHERE id='$userID'")->fetch_array();
                if ($result['password'] == $newPassword) {
                    echo "Password changed...";
                } else {
                    echo "Failed to change password!";
                }
            }
        } else {
            $statement = $mysqli->prepare("UPDATE userlist SET email = ?, firstName = ?, lastName = ?, hideNames = ? WHERE id = ? AND password = ?");
            $statement->bind_param("sssiis", $email, $firstName, $lastName, $hideNames, $userID, $oldPassword);
            $statement->execute();
            $statement->close();
        }
        $site="forside";
    }

    // Resetting passwords
    if($update=="resetPassword.php") {
        if (is_admin()) {
            $userID=$_POST[userID];
            $password=$_POST[password];
            $password = hashPassword($password);
            $mysqli->query("UPDATE userlist SET password='$password' WHERE id='$userID'");
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
        foreach ($events as $eventId => $eventName) {
            $weeklyStuff = "weekly" . $eventId . "Time";
            $solveCount = $events->num_solves($eventId);
            $comment = str_ireplace("\n","<br />", $_POST['weeklyComment' . $eventId]);
            unset($average);
            unset($best);
            unset($completedSolves);
            unset($solves);
            $hasEventData = false;
            $completedSolves = 0;
            $iterCount = $solveCount;
            if ($eventId == 13) {
                $iterCount = 3; // 3 values to process for multiBLD - cubes succeeded, cubes attempted, time
            }
            for ($i = 1; $i <= $iterCount; ++$i) {
                if ($eventId == 13 || $eventId == 17) {
                    $solves[$i] = $_POST[$weeklyStuff.$i];  // Should change this to limit dangerous input
                } else {
                    $solves[$i] = ugly_number($_POST[$weeklyStuff.$i]);  // Need to change so results are sent in proper format
                }
                if (ISSET($_POST[$weeklyStuff.$i])) {
                    $hasEventData = true;
                }
            }
            if (!$hasEventData) {
                continue;
            }

            if ($eventId == 13) {
                $suc = $solves[1];
                $try = $solves[2];
                $time = $solves[3];
                if ($time == 'DNF') {
                   $result = 8888;
                   $solves = array (1 => 999999999);
                } else if (is_numeric($suc) && is_numeric($try)) {
                    $solves = array( 1 => MBLD_to_number($suc, $try, $time));
                    $result = 2 * $suc - $try;
                    if ($result < 0 || ($result == 0 && $try == 2)) {
                        $result = 8888; 
                    } else if ($try < 2) {
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
                $best = $solves[1];
            } elseif ($eventId == 17) {
                // FMC is lovely
                $scramble = substr(get_scramble_text($eventId, $weekNo, $yearNo), 20);
                if ($solves[1] != "DNF" && $solves[1] != "DNS" && $solves[1] != "" && $scramble != "") { // new code to check solves
                    $solves[1] = correct_solution($solves[1]);
                    $result = FMCsolve($scramble, $solves[1]);
                    if ($result) {
                        // Solution was successful; give message to user indicating the success and number of moves
                        echo <<<END
                        <script> 
                            alert("Fewest Moves: Successfully solved in "+$result+" moves!");
                        </script>
END;
                    } else {
                        $comment = str_ireplace("\n", "<br />", "[mod: changed to DNF because submitted solution did not solve the puzzle. Original submitted solution:\n".$solves[1]."]\n").$comment;
                        $result = 8888;
                        $solves[1] = "DNF";
                        echo <<<END
                        <script> 
                            alert("Fewest Moves: Solution was not successful; submitted as DNF.  Original solution included in comments for solve.");
                        </script>
END;
                    }
                } else {
                    $result = countMoves($solves[1]);
                }
                $solves = array(1 => $solves[1]);
                $best = $result;
                if ($result > 0) {
                    $completedSolves = 1;
                }
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
                $result = get_best_result($solveCount, $solves);
                $best = $result;
                if ($completedSolves == $solveCount) {
                    $average = round_score(array_sum($solves) / 3);
                } else {
                    $average = PHP_INT_MAX;
                }
            } elseif ($solveCount == 1) {
                $result = get_best_result($solveCount, $solves);
                $best = $result;
                if (is_valid_score($result)) {
                   ++$completedSolves;
                }
                if ($eventId == 17) {
                    number_format($result, 0, '.', '');
                }
            }
            if ($best == 8888) {
                $best = PHP_INT_MAX;
            }
            // average and best are expressed in centiseconds so they can be accurate as integers in the database
            if ($average != PHP_INT_MAX) {
                $average *= 100;
            }
            if ($best != PHP_INT_MAX && $eventId != 13 && $eventId != 17) {
                $best *= 100;
            }

            // Inserting times
            $k = 0;
            $existence = $mysqli->query("SELECT * FROM weeklyResults WHERE weekId='$weekNo' AND yearId='$yearNo' AND userId='$userId' AND eventId='$eventId'")->num_rows;
            foreach ($solves as $solve){
                if ($eventId != 13 && $eventId != 17) {
                    $solve = uglyNumber($solve);
                }
                $k++;
                if ($existence == 0) { // If row doesn't already exist
                    $solveId= "solve" . $k;
                    if ($solve) {
                        if ($eventId != 13 && $eventId != 17) {
                            $statement = $mysqli->prepare("INSERT INTO weeklyResults ($solveId, weekId, yearId, eventId, userId) VALUES (?, ?, ?, ?, ?)");
                            $statement->bind_param("diiii", $solve, $weekNo, $yearNo, $eventId, $userId);
                            $existence = 1;
                        } elseif ($eventId == 13) {
                            if ($solve == 8888){
                                $solve = 999999999;
                            }
                            $statement = $mysqli->prepare("INSERT INTO weeklyResults (multiBLD, weekId, yearId, eventId, userId) VALUES (?, ?, ?, ?, ?)");
                            $statement->bind_param("iiiii", $solve, $weekNo, $yearNo, $eventId, $userId);
                        } elseif ($eventId == 17) {
                            $statement = $mysqli->prepare("INSERT INTO weeklyResults (fmcSolution, weekId, yearId, eventId, userId) VALUES (?, ?, ?, ?, ?)");
                            $statement->bind_param("siiii", $solve, $weekNo, $yearNo, $eventId, $userId);
                        }
                        $statement->execute();
                        $statement->close();
                   }
                } else { // If row exists
                    if($eventId != 13 && $eventId != 17) {
                        $solveId= "solve" . $k;
                        $statement = $mysqli->prepare("UPDATE weeklyResults SET $solveId = ? WHERE weekId = ? AND yearId = ? AND eventId = ? AND userId = ?");
                        $statement->bind_param("diiii", $solve, $weekNo, $yearNo, $eventId, $userId);
                    } elseif ($eventId == 13) {
                        if ($solve == 8888) { $solve = 999999999; }
                        $statement = $mysqli->prepare("UPDATE weeklyResults SET multiBLD = ? WHERE weekId = ? AND yearId = ? AND eventId = ? AND userId = ?");
                        $statement->bind_param("iiiii", $solve, $weekNo, $yearNo, $eventId, $userId);
                    } elseif ($eventId == 17) {
                        $statement = $mysqli->prepare("UPDATE weeklyResults SET fmcSolution = ? WHERE weekId = ? AND yearId = ? AND eventId = ? AND userId = ?");
                        $statement->bind_param("siiii", $solve, $weekNo, $yearNo, $eventId, $userId);
                    }
                    $statement->execute();
                    $statement->close();
                }
            }

            // HAZ DELETE SKILLS!
            if ($solves[1] == "DNS" || ((($solves[1] < 0.4) && $solves[1]!="DNF") && ($eventId != 17)) || $solves[1] == 9999) {
                $mysqli->query("DELETE FROM weeklyResults WHERE weekId='$weekNo' AND yearId='$yearNo' AND userId='$userId' AND eventId='$eventId'");
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
        $userId = is_admin() ? $userId : $_SESSION['logged_in'];
        $weekNo = $oldWeekNo;
        $yearNo = $oldYearNo;
        $site="weeklyView";
        if ($defaultManualEntry) {
            $site="weeklySubmit";
        }
    }

    // Logging in
    if($update=="logIn.php"){
        $username = $_POST['brugernavn'];
        $password = hashPassword($_POST['password']);
        $statement = $mysqli->prepare("SELECT id, firstName, username, password FROM userlist WHERE username = ?");
        $statement->bind_param("s", $username);
        $statement->execute();
        $statement->bind_result($loggedInId, $loggedInFirstName, $loggedInUsername, $loggedInPassword);
        $statement->fetch();
        if($password == $loggedInPassword){
            $_SESSION['logged_in'] = $loggedInId;
            $_SESSION['firstName'] = $loggedInFirstName;
            $_SESSION['usName'] = $loggedInUsername;
            $site = "weeklyView";
            if ($defaultManualEntry) {
                $site="weeklySubmit";
            }
        } else {
            echo "Failed to log in!";
        }
        $statement->close();
    }

    if ($site=="logud") {
        session_unset();
    }

    // emptying update :)
    $update="";

    //check what f to include
    if ($site=="rules") {
        $side="rules.php";
    } elseif ($site=="opretBruger") {
        $side="opretBruger.php";
    } elseif ($site=="weeklySubmit") {
        if (is_admin()) { $side = "weeklySubmitNew.php"; }
        elseif ($_SESSION['logged_in']) { $side="weeklySubmit.php"; } else { $side="forside.php"; }
    } elseif ($site=="weeklyView") {
        if (is_admin()) { $side = "weeklyViewNew.php"; }
        elseif ($_SESSION['logged_in']) { $side="weeklyView.php"; } else { $side="forside.php"; }
    } elseif ($site=="weeklyShow") {
        $side="showWeekly.php";
    } elseif ($site=="showWeekly") {
        $side="showWeekly.php";
    } elseif ($site=="visArtikel") {
        $side="newWeek.php";
    } elseif ($site=="settings") {
        $side="settings.php";
    } elseif ($site=="timer") {
        if (!$_SESSION['logged_in']) {
            $side="forside.php";
        } else if (is_mike()) {
            $side="timerNew.php";
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
    } else {
        $side="forside.php";
    }
    
    require_once 'newHeader.php';
?>

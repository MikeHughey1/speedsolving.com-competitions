<?php
    require_once 'newconnect.php';
    require_once '../competition_common/statFunctions.php';

    if (!is_admin()) {
    	exit("invalid user");
    }

    $successCount = 0;
    $failureCount = 0;

    echo "Starting unit tests<br>";
    
    function validate($actualResult, $expectedResult, $testname)
    {
    	global $successCount, $failureCount;
        if ($actualResult == $expectedResult) {
            ++$successCount;
        } else {
            ++$failureCount;
            echo "Test $testname failed.  Expected result: [$expectedResult], actual result: [$actualResult]<br>";
        }
    }

    $weeklyResults = new WeeklyResults(20, 2012);
    validate($weeklyResults->get_user_total_score(85), 297, "get_user_total_score user 85 week 2012-20");
    $weeklyResults = new WeeklyResults(52, 2016);
    validate($weeklyResults->get_user_total_score(111), 141, "get_user_total_score user 111 week 2016-52");
    
    // Test of Fewest Moves checking.
    // For each case, check to make sure scramble adjusts correctly to meet proper notation, then check to see if it gives the correct result (count or failure).
    $testName[0]     = "test for rotations";
    $scrambleTest[0] = "R' U' F D F2 U L2 D' U2 B2 L2 D R2 D2 U' R' U B2 U2 L2 R F2 U L F R' U' F";
    $solveTest[0]    = "[u] R' B D F2 L' D' [l] L2 [r2] U2 R2 U R U' R U L2 R2 [l2] U2 L' [l] U R [d'] R U R' U R U2 R' [u'] R' L [l'] U2 R' L x U R' L x U2 L2 R2 x2 U F2 R' L x F2 L2 R2";
    $adjustTest[0]   = "[u] R' B D F2 L' D' [l] L2 [r2] U2 R2 U R U' R U L2 R2 [l2] U2 L' [l] U R [d'] R U R' U R U2 R' [u'] R' L [l'] U2 R' L x U R' L x U2 L2 R2 x2 U F2 R' L x F2 L2 R2";
    $moveCount[0]    = 45;

    $testName[1]     = "substitution test 1";
    $scrambleTest[1] = "R' U' F R2 B2 D2 B2 R B2 D2 U2 L' B2 R D L' B' F' R2 B2 D B L U2 R' U' F";
    $solveTest[1]    = "x2 U' L D U2 F' U R B F' L F L' B' U B U' L U L' R' U2 R U2 R' U2 RU R U' R' U F' U F f R U R' U' R U R' U' f y x'  R U' R D2 R' U R D2 R2";
    $adjustTest[1]   = "x2 U' L D U2 F' U R B F' L F L' B' U B U' L U L' R' U2 R U2 R' U2 R U R U' R' U F' U F Fw R U R' U' R U R' U' Fw y x' R U' R D2 R' U R D2 R2";
    $moveCount[1]    = false;
    
    $testName[2]     = "substitution test 2";
    $scrambleTest[2] = "R' U' F B2 R' F2 R2 B2 R F2 R' D2 R F' R U2 R' D' U' F U R' D R' U' F";
    $solveTest[2]    = "L2 R X U2 R U R' F U' F' L' U2 L B2 R U R' U' R U R' Y2 R U' R' F R' F' R Y' U' F U R U' R' F' R U' R' U R U R' U' M' U R U' r' Y r U R' U' r' F R F' d2 R' U R' d' R' F' R2 U' R' U R' F R F";
    $adjustTest[2]   = "L2 R x U2 R U R' F U' F' L' U2 L B2 R U R' U' R U R' y2 R U' R' F R' F' R y' U' F U R U' R' F' R U' R' U R U R' U' Rw R' U R U' Rw' y Rw U R' U' Rw' F R F' Dw2 R' U R' Dw' R' F' R2 U' R' U R' F R F";
    $moveCount[2]    = 71;

    foreach ($testName as $index => $name) {
        validate(correct_solution($solveTest[$index]), $adjustTest[$index], "correct_solution ".$name);
        validate(FMCsolve($scrambleTest[$index], $adjustTest[$index]), $moveCount[$index], "FMCsolve ".$name);
    }

    echo "Tests run: ".($successCount + $failureCount)."<br>";
    echo "Successes: $successCount<br>";
    echo "Failures: $failureCount<br>";


<?php
    require_once 'newconnect.php';
    require_once 'statFunctions.php';

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

    validate(pretty_number(get_weekly_kinch_rankings(20, 2012)[85]), 24.03, "get_weekly_kinch_rankings rank 1 week 2012-20");
    validate(pretty_number(get_weekly_kinch_rankings(52, 2016)[111]), 8.33, "get_weekly_kinch_rankings rank 10 week 2016-52");

    echo "Tests run: ".($successCount + $failureCount)."<br>";
    echo "Successes: $successCount<br>";
    echo "Failures: $failureCount<br>";


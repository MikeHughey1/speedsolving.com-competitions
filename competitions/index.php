<?php
    require_once 'newconnect.php';
    require_once '../competition_common/statFunctions.php';
    
    
    $sessionType = "old";
    if (isset($currentUserId) && !isset($_SESSION['old_login'])) {
        $_SESSION['old_login'] = 1;
        unset($_SESSION['new_login']);
        $freshSessionType = "old";
    }

    $personInfo = get_person_info($currentUserId);    
    $hasLogin = $mysqli->query("SELECT userId FROM logins WHERE userId = $currentUserId AND sessionType = 'new'")->num_rows;
    if ($hasLogin == 0) {
        $personInfo['oldReturnUser'] = 1;
    }

    require_once '../competition_common/main.php';
    require_once 'newHeader.php';
?>

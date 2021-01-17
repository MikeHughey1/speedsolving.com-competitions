<?php
    require_once '../newconnect.php';
    require_once '../sync/XF.php';
    require_once '../../competition_common/statFunctions.php';

    if (!isset($app))
        $app = sync\XF::app();

    $personInfo = sync\XF::user();
    $currentUserId = $personInfo['id'];
    
    if (!is_admin()) {
        echo "Invalid permissions!!";
        exit;
    }

<?php
    require_once 'newconnect.php';
    require_once 'head.php';
    
    if (!$currentUserId) {
            echo <<<END
            <h2 class="centerText">Weekly Competition Persons<br><br>
                If you would like to see the list of competitors in our Weekly Competition, please Log in first.<br>
                If you are not yet a member, please Register by clicking the Register option in the upper right corner.<br>
                <br><br><br><br>
            </h2>
END;
    } else {
        require_once '../competition_common/users.php';
    }

    if (!isset($title)) {
	$title = 'Speedsolving.com';
    }

    sync\XF::render(array('content' => ob_get_clean()));

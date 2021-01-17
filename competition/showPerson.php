<?php
    require_once 'newconnect.php';
    require_once 'head.php';
    require_once '../competition_common/person.php';

    if (!isset($title)) {
	$title = 'Speedsolving.com';
    }

    sync\XF::render(array('content' => ob_get_clean()));

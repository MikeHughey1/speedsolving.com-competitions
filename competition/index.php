<?php
    require_once 'newconnect.php';
    require_once 'head.php';

    require_once '../competition_common/main.php';
    include_once '../competition_common/'.$side;

    if (!isset($title)) {
        $title = get_competition_name(get_current_week(), get_current_year())." Weekly Competition";
    }

    sync\XF::render(array('content' => ob_get_clean()));

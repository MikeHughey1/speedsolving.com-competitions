<?php
    // Get info on all the events
    static $eventNames = array();
    static $solveCounts = array();
    $query = $mysqli->query("SELECT id, eventName, weekly FROM events ORDER BY id");
    while ($row = $query->fetch_assoc()) {
        $eventNames[$row['id']] = $row['eventName'];
        $solveCounts[$row['id']] = $row['weekly'];
        if ($row['weekly'] > 5) {
            // multiBLD
            $solveCounts[$row['id']] = 1;
        }
    }

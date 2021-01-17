<?php
    $title = "Weekly Competition Individual Results (Speedsolving.com)";
    
    $userId = filter_input(INPUT_GET, 'showPerson', FILTER_VALIDATE_INT);
    $weekId = filter_input(INPUT_GET, 'week', FILTER_VALIDATE_INT);
    if (!$weekId) {
        $weekId = get_current_week();
    }
    $yearId = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);
    if (!$yearId) {
        $yearId = get_current_year();
    }

    if (is_admin() && isset($_POST['quarantine'])) {
        // Handle posted data for quarantining
        $update = $_POST['quarantine'];
        if ($update == 'yes') {
            $userId = filter_input(INPUT_POST, 'user', FILTER_VALIDATE_INT);
            $yearId = filter_input(INPUT_POST, 'year', FILTER_VALIDATE_INT);
            $weekId = filter_input(INPUT_POST, 'week', FILTER_VALIDATE_INT);
            $issue = filter_input(INPUT_POST, 'issue', FILTER_VALIDATE_INT);
            $description = $_POST['description'];
            $entry = $_POST['entry'];
            $allWeeks = filter_input(INPUT_POST, 'allWeeks', FILTER_VALIDATE_BOOLEAN);
            $preventCompeting = filter_input(INPUT_POST, 'preventCompeting', FILTER_VALIDATE_BOOLEAN);
            // Possible values for status:
            // disable: Don't allow user to compete again until problem is resolved
            // resolve: Require user to respond, but then allow user to compete immediately without resolving problem
            // done: Problem has been resolved; no more discussion needs to take place - will be ignored at future logins
            if ($preventCompeting) {
                $status = "disable";
            } else {
                $status = "resolve";
            }
            echo "user=$userId, issue=$issue, description=$description, entry=$entry, allWeeks=$allWeeks, preventCompeting=$preventCompeting<br>";
            $statement = $mysqli->prepare("INSERT INTO issue (userId, description, status) VALUES (?, ?, ?)");
            $statement->bind_param("iss", $userId, $description, $status);
            $statement->execute();
            $issueId = $mysqli->insert_id;
            $statement->close();
            $statement = $mysqli->prepare("INSERT INTO discussion (issueId, userId, entry) VALUES (?, ?, ?)");
            $statement->bind_param("iis", $issueId, $currentUserId, $entry);
            $statement->execute();
            $statement->close();
            $statement = $mysqli->prepare("INSERT INTO quarantine (weekId, yearId, userId, eventId, result, comment, solve1, solve2, solve3, solve4, solve5, multiBLD, average, best, completed, rank, issueId) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $statement->bind_param("iiiidsdddddiiiiii", $weekId, $yearId, $userId, $eventId, $result, $comment, $solve1, $solve2, $solve3, $solve4, $solve5, $multiBLD, $average, $best, $completed, $rank, $issueId);
            if ($allWeeks) {
                $query = "FROM weeklyResults WHERE userId = $userId";
            } else {
                $query = "FROM weeklyResults WHERE userId = $userId AND yearId = $yearId AND weekId = $weekId";
            }
            $queryResult = $mysqli->query("SELECT * ".$query);
            while ($row = $queryResult->fetch_assoc()) {
                $weekId = $row['weekId'];
                $yearId = $row['yearId'];
                $eventId = $row['eventId'];
                $result = $row['result'];
                $comment = $row['comment'];
                $solve1 = $row['solve1'];
                $solve2 = $row['solve2'];
                $solve3 = $row['solve3'];
                $solve4 = $row['solve4'];
                $solve5 = $row['solve5'];
                $multiBLD = $row['multiBLD'];
                $average = $row['average'];
                $best = $row['best'];
                $completed = $row['completed'];
                $rank = $row['rank'];
                if (!$statement->execute()) {
                    echo "insertion failed - aborting!<br>";
                    exit;
                }
                echo "$weekId, $yearId, $userId, $eventId, $result, $rank<br>";
            }
            $statement->close();
            echo "Finished!<br>";
            $mysqli->query("DELETE ".$query);
        }
    }

    $personData = get_person_info($userId);
    $fullname = $personData['displayName'];
    $username = $personData['username'];

    print <<<END
    <div id='canvas'>
    <div class='xLargeText'><br><br><a href='showPersonalRecords.php?showRecords=$userId'>$fullname</a><br></div><br>
END;
    if (is_admin()) {
        print <<<END
        <button type='button' class='btn' id='enableQuarantine' onclick='enable_quarantine()'>Quarantine</button>
        <form action="showPerson.php" id='quarantineForm' method="post" onsubmit="return validate(event);" style='display:none'>
            <br>
            Quarantine results for $username<br><br>
            <input type='text' style='display:none' name='quarantine' value='yes'>
            <input type='text' style='display:none' name='user' value='$userId'>
            <input type='text' style='display:none' name='year' value='$yearId'>
            <input type='text' style='display:none' name='week' value='$weekId'>
            Add to issue number (leave blank for new issue):&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <input type='text' class='mbldInput' id='issue' name='issue'/><br>
            One-line description of issue:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <input type='text' class='full-width' id='description' name='description' /><br>
            Initial message to user:<br>
            <textarea class='submit-weekly-comment' id='entry' name='entry'></textarea>
            <input onchange='checkbox_change(this.id)' type='checkbox' id='allWeeks' name='allWeeks' />
            <label for='allWeeks'>Quarantine results for all weeks for this user (if unchecked, only quarantine this week's results)</label>
            <br>
            <input onchange='checkbox_change(this.id)' type='checkbox' id='preventCompeting' name='preventCompeting' />
            <label for='preventCompeting'>Prevent competing until issue is resolved</label>
            <br>
            <input type='submit' value='Execute Quarantine' />
        </form>
END;
    }
    print <<<END
        <table class='table-striped table-dynamic'>
            <thead>
                <tr>
                    <th class='l'>Event</th>
                    <th class='r'>#</th>
                    <th class='r'>Best</th>
                    <th class='r'>Average</th>
                    <th class='c'>Solves</th>
                    <th class='l'>Comment</th>
                </tr>
            </thead>
            <tbody>
END;
    $weeklyResults = new WeeklyResults($weekId, $yearId);
    
    foreach ($events as $eventId => $eventName) {
        if (is_active_event($eventId, $weekId, $yearId) && ($weeklyResults->is_completed($eventId, $userId) || $weeklyResults->is_partial($eventId, $userId))) {
            print "<tr>";
            print "<td class='l'>";
            add_icon($eventName, "");
            print " $eventName</td>";
            $rank = $weeklyResults->get_user_place($eventId, $userId);
            $best = get_single_output_from_best($eventId, $weeklyResults->get_user_best($eventId, $userId));
            $average = get_average_output($eventId, $yearId, $weeklyResults->get_user_average($eventId, $userId));
            $solveDetails = $weeklyResults->get_user_solve_details($eventId, $userId);
            $comment = $weeklyResults->get_user_comment($eventId, $userId);
            if (is_average_event($eventId, $yearId)) {
                $average = "<b>".$average."</b>";
            } else {
                $best = "<b>".$best."</b>";
            }
            print <<<END
            <td class='r'>$rank</td>
            <td class='r'>$best</td>
            <td class='r'>$average</td>
            <td>$solveDetails</td>
            <td class='l'>$comment</td>
            </tr>
END;
        }
    }

    print "</tbody>";
    print "</table>";
    print "</div>";

    if (is_admin()) {
        print <<<END
        <script>
        function enable_quarantine()
        {
            document.getElementById("enableQuarantine").style.display = 'none';
            document.getElementById("quarantineForm").style.display = 'block';
        }
        </script>

END;
    }

print <<<END
</body>
END;

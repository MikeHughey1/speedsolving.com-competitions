<?php
    $title = "Weekly Competition List of Users (speedsolving.com)";
    
    if (is_admin()) {
        require_once 'modalDialogs.php';
    }
    
    print "<br><br><br><div id='canvas'>";

    if (is_admin()) {
        create_modal();
    }
    
    print <<<END
    <div id='user'><br>Persons<br></div><br>
    <h3 id='sortingIndicator'>Loading...</h3>
    <table id='nameSortTable' class='table-striped table-dynamic'>
        <thead>
            <tr>
                <th class='l' onclick='sortTable(0)'>Name &#9660;</th>
                <th class='c' onclick='sortTable(1)'>Competitions</th>
                <th class='c' onclick='sortTable(2)'>Completed Solves</th>
            </tr>
        </thead>
END;

    print "<tbody>";
    $query = $mysqli->query("SELECT * FROM userlist ORDER BY firstName, lastName ASC");

    while ($resultRow = $query->fetch_array()) {
        $username = $resultRow['username'];
        $userId = $resultRow['id'];
        if (is_admin()) {
            $fullname = $resultRow['firstName']." ".$resultRow['lastName']."($username)[$userId] ".$resultRow['email'];
        } elseif ($resultRow['hideNames'] == 1) {
            $fullname = $resultRow['username'];
        } elseif ($resultRow['firstName'] === 'Forum') {
            $fullname = $resultRow['username']." (Forum)";
        } else {
            $fullname = $resultRow['firstName']." ".$resultRow['lastName'];
        }
        $competitionCount = get_competitions($userId);
        $completedSolveCount = get_completed_solves($userId);
        
        if ($competitionCount > 0 || is_admin()) {
            print <<<END
            <tr>
                <td class='userLink'><a href='showPersonalRecords.php?showRecords=$userId'>$fullname</a></td>
END;
                if (is_admin()) {
                    print "<td class='c with-pointer' onclick='open_modal(\"editName.php\", $userId)'>$competitionCount</td>";
                } else {
                    print "<td class='c'>$competitionCount</td>";
                }
            print <<<END
                <td class='c'>$completedSolveCount</td>
            </tr>
END;
        }
    }
    print <<<END
            </tbody>
        </table>
    </div>
END;
?>

    <script>
        var userIds = [];
        var userTags = [];
        var userNames = [];
        var competitions = [];
        var completedSolves = [];
        var readyToSort = false;
        
        function initTable() {
            document.getElementById('sortingIndicator').style.visibility = 'visible';
            table = document.getElementById("nameSortTable");
            rows = table.getElementsByTagName("tr");
            for (i = 1; i < rows.length; i++) {
                userIds[i] = i;
                userTags[i] = rows[i].getElementsByTagName("td")[0].innerHTML;
                userNames[i] = rows[i].getElementsByTagName("td")[0].innerText.toUpperCase();
                competitions[i] = rows[i].getElementsByTagName("td")[1].innerHTML;
                completedSolves[i] = rows[i].getElementsByTagName("td")[2].innerHTML;
            }
            readyToSort = true;
            document.getElementById('sortingIndicator').style.visibility = 'hidden';
        }
        
        function sortTable(n) {
            // Can't sort until all the data has been loaded.  This makes sure we don't.
            if (!readyToSort) return;

            document.getElementById('sortingIndicator').innerHTML = 'Sorting...';
            document.getElementById('sortingIndicator').style.visibility = 'visible';
            setTimeout(sortTableBegin, 10, n);
        }

        function sortTableBegin(n) {
            table = document.getElementById("nameSortTable");
            switch (n) {
                case 0:
                    userIds.sort(function(a, b) {return a - b;});
                    break;
                case 1:
                    userIds.sort(function(a, b) {return competitions[b] - competitions[a];});
                    break;
                case 2:
                    userIds.sort(function(a, b) {return completedSolves[b] - completedSolves[a];});
                    break;
            }
            rows = table.getElementsByTagName("tr");
            rows[0].getElementsByTagName("th")[0].innerHTML = (n == 0) ? "Name &#9660;" : "Name";
            rows[0].getElementsByTagName("th")[1].innerHTML = (n == 1) ? "Competitions &#9660;" : "Competitions";
            rows[0].getElementsByTagName("th")[2].innerHTML = (n == 2) ? "Completed Solves &#9660;" : "Completed Solves";

            for (i = 1; i < rows.length; i++) {
                rows[i].getElementsByTagName("td")[0].innerHTML = userTags[userIds[i-1]];
                rows[i].getElementsByTagName("td")[1].innerHTML = competitions[userIds[i-1]];
                rows[i].getElementsByTagName("td")[2].innerHTML = completedSolves[userIds[i-1]];
            }
            document.getElementById('sortingIndicator').style.visibility = 'hidden';
        }

        initTable();
    
    </script>
</body>
</html>

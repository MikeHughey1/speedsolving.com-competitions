<h2>List of all users</h2>
<?php
    $query2 = $mysqli->query("SELECT * FROM userlist ORDER BY firstName, lastName ASC");
    while($resultRow = $query2->fetch_array()){
        if ($_SESSION['logged_in']==66 || $_SESSION['logged_in']==85 || $_SESSION['logged_in']==111 || $_SESSION['logged_in']==1581) {
            print "<a href='?showPerson=".$resultRow['id']."'>".$resultRow['firstName']." ".$resultRow['lastName']." ".$resultRow['username']." ".$resultRow['id']." ".$resultRow['email']."</a><br />";
        } else {
            print "<a href='?showPerson=".$resultRow['id']."'>".$resultRow['firstName']." ".$resultRow['lastName']."</a><br />";
        }
    }
?>
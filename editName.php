<?php require_once 'newconnect.php'; ?>
<!DOCTYPE html>
<html>
<head>
<title>Weekly Competition Name Editor (speedsolving.com)</title>
<link rel='stylesheet' href='style.css' type='text/css' />
<link rel="stylesheet" href="cubing-icons.css">
<meta name='viewport' content='width=device-width, initial-scale=1'>
<meta charset="UTF-8">
</head>

<?php
    require_once 'statFunctions.php';

    if (!is_admin()) {
        // Protect against someone inadvertently allowing this code to be called by a non-admin.  This shouldn't ever execute.
        print "ERROR: Do not allow editor to be used unless admin privileges have already been verified!";
        exit;
    }
    
    $user = filter_input(INPUT_GET, 'user', FILTER_VALIDATE_INT);

    $personInfo = get_person_info($user);
    $username = $personInfo['username'];
    $first = $personInfo['firstName'];
    $last = $personInfo['lastName'];
    $userFullName = $personInfo['firstName']." ".$personInfo['lastName'];
    $titleText = $username."(".$user."):";
    print <<<EOD
    <body>
        <form method='post'>
            <div>User is ($user)</div>
            <div id='dialogTitle'><h1>$titleText</h1></div>
            <div>First Name:<input name='first' id='first' value='$first'></div>
            <div>Last Name:<input name='last' id='last' value='$last'></div>
            <input class='button' name='submit' value='Submit Changes' type='submit'>
        </form>
    </body>
EOD;

    // Just ban a few characters we don't want to see in names
    $options = array("options" => array("regexp" => "/^[^,;}{]*$/"));
    $firstName = filter_input(INPUT_POST, 'first', FILTER_VALIDATE_REGEXP, $options);
    $lastName = filter_input(INPUT_POST, 'last', FILTER_VALIDATE_REGEXP, $options);
    print "$firstName $lastName<br>";

    $query = "UPDATE userlist SET firstName = ?, lastName = ? WHERE id = ?";
    print "$query<br>";
    if ($firstName) {
        $statement = $mysqli->prepare($query);
        $statement->bind_param("ssi", $firstName, $lastName, $user);
        $statement->execute();
        $statement->close();
    }

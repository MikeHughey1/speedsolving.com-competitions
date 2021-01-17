<!DOCTYPE html>
<?php
    if (!isset($title)) {
        $title = "Speedsolving.com Statistics";
    }
?>
<html>
<head>
    <title><?php print $title ?></title>
    <link rel='stylesheet' href='../competition_common/style.css?version=1.2' type='text/css' />
    <link rel="stylesheet" href="../competition_common/cubing-icons.css?version=2">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css">
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <meta charset="UTF-8">

    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-1539656-3"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'UA-1539656-3');
    </script>
</head>
<body class='header-body'>

    <!–– Main header and menu ––>
    <div class='header-div'>
        <img class='left fit' src='https://www.speedsolving.com/img/logo.png' alt='SpeedSolving.com' />
        <nav id='stats-nav'>
            <ul>
                <li><a href='https://www.speedsolving.com'>&nbsp;<i class="fas fa-home"></i>&nbsp;Home</a></li>
                <li><a href='index.php?side=weeklyView'>&nbsp;<i class="fas fa-dice-d6"></i>&nbsp;Compete!</a></li>
                <li><a href='showWeeks.php'>&nbsp;<i class="fas fa-cube"></i>&nbsp;Weeks</a></li>
                <li><div class='dropdown'>&nbsp;<i class="fas fa-list-ol"></i>&nbsp;Results &#9660;
                    <div class='dropdown-content'>
                        <a href='showEvents.php'>&nbsp;<i class="fas fa-signal fa-rotate-90"></i>&nbsp;Rankings</a>
                        <a href='showRecords.php'>&nbsp;<i class="fas fa-trophy"></i>&nbsp;Records</a>
                        <a href='showUsers.php'>&nbsp;<i class="fas fa-users"></i>&nbsp;Persons</a>
                        <a href='showPersonalRecords.php?showRecords=<?php echo $currentUserId ?>'>&nbsp;<i class="fas fa-user"></i>&nbsp;My Results</a>
                        <div style='height:1px; background-color: #e5e5e5'></div>
                        <a href='results/export.html'>&nbsp;<i class="fas fa-download"></i>&nbsp;Database Export&nbsp;</a>
                    </div>
                </div></li>
                <li class='final'></li>
            </ul>
        </nav>
    </div>


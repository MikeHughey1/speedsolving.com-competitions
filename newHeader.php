<!DOCTYPE html>
<html lang='en-us' class='full-height'>
<head>
<title>Weekly Competition <?php print $yearNo; ?>-<?php print $weekNo; ?> (speedsolving.com)</title>
<meta name='viewport' content='width=device-width, initial-scale=1'>
<meta charset="UTF-8">
<link rel='stylesheet' href='style.css' type='text/css' />
<link rel="stylesheet" href="cubing-icons.css">
<script>
    function initialize() {
        configureIndexForMobile();
    }
    
    function configureIndexForMobile() {
        document.getElementById("cross").hidden = true;
        document.getElementById("menuMobile").hidden = true;
        document.getElementById("hamburger").addEventListener("click", hamburgerClicked);
        document.getElementById("cross").addEventListener("click", crossClicked);
    }
    
    function hamburgerClicked() {
        document.getElementById("hamburger").hidden = true;
        document.getElementById("cross").hidden = false;
        document.getElementById("menuMobile").hidden = false;
    }
    
    function crossClicked() {
        document.getElementById("hamburger").hidden = false;
        document.getElementById("cross").hidden = true;
        document.getElementById("menuMobile").hidden = true;
    }
</script>

</head>
<body class='header-body full-height'>
    <div class='header-div'>
        <div id='large-top'>
            <table class='header-table'>
                <tr>
                <td>
                    <img src='https://www.speedsolving.com/img/logo.png' alt='SpeedSolving.com' />
                </td>
                <td class='topbar'>
                    <div class='topbar-text'>
                        <span><b>Weekly Competition <?php print $yearNo; ?>-<?php print $weekNo; ?></b></span>
                    </div>
                </td>
                </tr>
            </table>
        </div>
        <div id='small-top'>
            <div>
                <img class='left fit' src='https://www.speedsolving.com/img/logo.png' alt='SpeedSolving.com' />
            </div>
            <div class='topbar'>
                <div class='topbar-text'>
                    <span><b>Weekly Competition <?php print $yearNo; ?>-<?php print $weekNo; ?></b></span>
                    <button id="hamburger">&#9776;</button>
                    <button id="cross" hidden>X</button>
                </div>
            </div>
        </div>
        <div class="menu-mobile" id="menuMobile" hidden>
            <ul>
                <?php if (is_admin()) { echo "<li><a href='?side=forside'>Profile</a></li>"; } else { echo "<li><a href='?side=forside'>Hi</a></li>"; } ?>
                <?php if ($_SESSION['logged_in']) { echo "<li><a href='?side=weeklyView'>Weekly View</a></li>"; } ?>
                <?php if ($_SESSION['logged_in']) { echo "<li><a href='?side=weeklySubmit'>Manual Entry</a></li>";} else {echo "<li><a href='?side=opretBruger'>Join the fun!</a></li>";}?>
                <li><a href='showWeeks.php'>Statistics</a></li>
                <li><a href='?side=rules'>Rules</a></li>
                <li><a href='?side=settings'>Settings</a></li>
                <li><a href='?side=logud'>Log Out</a></li>
            </ul>
        </div>
        <nav class='protectedMenu' id='main-nav'>
            <ul>
                <?php
                if ($_SESSION['logged_in']) {
                    echo "<li><a href='?side=forside'>Profile</a></li>";
                    echo "<li><a href='?side=weeklyView'>Weekly View</a></li>";
                    echo "<li><a href='?side=weeklySubmit'>Manual Entry</a></li>";
                } else {
                    echo "<li><a href='?side=forside'>Log In</a></li>";
                    echo "<li><a href='?side=opretBruger'>Join the fun!</a></li>";
                }
                ?>
                <li><a href='showWeeks.php'>Statistics</a></li>
                <li><a href='?side=rules'>Rules</a></li>
                <li><a href='?side=settings'>Settings</a></li>
                <?php if ($_SESSION['logged_in']) { echo "<li><a href='?side=logud'>Log Out</a></li>"; } ?>
                <li class='final'></li>
            </ul>
        </nav>
        <div id='content'>
            <?php include("$side"); ?>
        </div>
    </div>	
    <script>initialize();</script>
</body>
<?php
    $userId = $_SESSION['logged_in'];
    if (!$userId || $weekNo != gmdate("W",strtotime('-1 day'))) {
        echo "<script> location.href='index.php'; </script>";
        exit;
    }
    
    $events = new Events;
    $eventId = filter_input(INPUT_GET, 'event', FILTER_VALIDATE_INT);
    $eventName = $events->name($eventId);
    $noSolves = $events->num_solves($eventId);
    $result = $mysqli->query("SELECT comment, solve1, solve2, solve3, solve4, solve5, multiBLD, fmcSolution FROM weeklyResults WHERE eventId='$eventId' AND userId='$userId' AND weekId='$weekNo' AND yearId='$yearNo'")->fetch_array();
    $scramble = $mysqli->query("SELECT scramble FROM scrambles WHERE eventId='$eventId' AND weekId='$weekNo' AND yearId='$yearNo'")->fetch_array();
    $explodeScrambles = explode("<br />",$scramble[0]);
    $scrambles = json_encode($explodeScrambles);
    add_icon($eventName, "cubing-icon-3x");
    echo "<span class='submit-weekly-header' id='eventName'> $eventName </span>";
    $personalBestString = "PB Single: ".get_personal_best_single($eventId, $userId, true);
    $personalBestStringShort = "PB Sgl: ".get_personal_best_single($eventId, $userId, false);
    if ($events->num_solves($eventId) > 1 && $events->num_solves($eventId) < 30) {
        $personalBestString .= ", Average: ".get_personal_best_average($eventId, $userId, true);
        $personalBestStringShort .= ", Avg: ".get_personal_best_average($eventId, $userId, false);
    }
    echo "<span class='centerText' id='pb-text'>&nbsp;&nbsp;$personalBestString</span>";
    echo "<div class='centerText' id='pb-text-short'>$personalBestStringShort</div>";
    $fmcSolution = stripslashes($result['fmcSolution']);
    $comment = str_ireplace("<br />","\n",stripslashes($result['comment']));
    $results = array($result['solve1'],$result['solve2'],$result['solve3'],$result['solve4'],$result['solve5']);
    $multiBLD = $result['multiBLD'];

    $rezult = number_to_MBLD($multiBLD);
    $timeMBLD = prettyNumber(round($rezult[1]));
    $attemptedNo = $rezult[3];
    $solvedNo = $rezult[2];

    // calculate token
    $data = array('userId' => $userId, 'weekNo' => $weekNo, 'yearNo' => $yearNo);
    $tokenizer = new JWT();
    $token = $tokenizer->encode($data, $token_key);
    
    // output timer window based on eventId
    echo "<div>";
    echo "<div class='timerPanel' id='timerWindow'>";
    echo "<div class='scrambleText' id='currentScramble'></div>";
    echo "<input class='hiddenText' type='text' id='timeInput' hidden/>";
    echo "<div class='timeDisplay' id='timeDisplay'>0.00</div>";
    echo "<div class='centerText' id='touchInstructions' hidden>Touch here to start timer</div>";
    echo "<img class='touchImage' id='touchImage' src='Hand.png' alt='Touch here' hidden>";
    // buttons to be shown when manually editing times
    echo "<div class='btn-group centerText' id='editButtons' hidden>";
    echo "<br />";
    echo "<button type='button' class='buttonLarge' id='dnf' onclick='dnfEdited()' value=0>DNF</button><span>   </span>";
    echo "<button type='button' class='buttonLarge' id='save' onclick='saveEdits()'>Save</button><span>   </span>";
    echo "<button type='button' class='buttonLarge' id='cancel' onclick='cancelEdits()'>Cancel</button>";
    echo "</div>";
    // end of edit buttons
    echo "</div>";
    if ($eventId == 17) {
        echo "<div class='fmcTimerPanel' id='resultsWindow'>";
    } else {
        echo "<div class='timerPanel' id='resultsWindow'>";
    }
    echo "<button type='button' class='centerText' onclick='manualEntryStart()' id='manualSelect'>Click to enter times manually</button><br /><br />";
    echo "<form action='index.php' method='post' onsubmit='return validate(event);'>";
    if ($eventId == 17) {
        echo "<div class='centerText' id='resultHeader'><strong>Solution</strong></div>";
        echo "<input type='hidden' name='weekly".$eventId."Time1' id='resultSolve1' value='".$results[0]."' />";
        echo "<textarea class='submit-weekly-comment-timer' name='weekly".$eventId."Time1' id='solution'>".$fmcSolution."</textarea><br />";
    } else if ($eventId == 13) {
        echo "<input type='hidden' name='weekly".$eventId."Time1' id='resultSolve1' value='".$timeMBLD."' />";
        echo "<div class='xLargeText' id='MBLDInputs' hidden>";
        echo "<div>Number attempted: <input class='mbldInput' type='text' name='weekly".$eventId."Time2' id='attemptedNo' value='".$attemptedNo."' /></div><br />";
        echo "<div>Number solved: <input class='mbldInput' type='text' name='weekly".$eventId."Time1' id='solvedNo' value='".$solvedNo."' /></div><br />";
        echo "<div>Time: <input class='mbldInputTime' type='text' name='weekly".$eventId."Time3' id='multiTime' value='".$timeMBLD."' /></div><br />";
        echo "</div>";
        echo "<div class='xLargeText' id='MBLDTexts'>";
        echo "<div id='attemptedNoText'>Number attempted: ".$attemptedNo."</div><br />";
        echo "<div id='solvedNoText'>Number solved: ".$solvedNo."</div><br />";
        echo "<div id='multiTimeText'>Time: ".$timeMBLD."</div><br />";
        echo "</div>";
    } else {
        echo "<div>";
        echo "<div class='centerText' id='resultHeader'><strong>Results</strong></div>";
        for ($i = 1; $i <= $noSolves; $i++) {
            echo "<div>";
            echo "<input type='hidden' name='weekly".$eventId."Time".$i."' id='resultSolve".$i."' value='".$results[$i - 1]."' />";
            echo "<div class='btn-group centerText' id='solveGroup".$i."'>";
            echo "$i. <span class='timerTooltip'><span id='solve".$i."'></span><span class='timerTooltiptext'>".$explodeScrambles[$i - 1]."</span></span>";
            echo "<button type='button' class='btn-group-xs' id='plusTwo".$i."' value=0 onclick='plusTwoThis(".$i.")'>+2 (+0)</button><span>  </span>";
            echo "<button type='button' class='btn-group-xs' id='dnf".$i."' onclick='dnfThis(".$i.")' value=8888>DNF</button><span>  </span>";
            echo "<button type='button' class='btn-group-xs' id='edit".$i."' onclick='editThis(".$i.")'>Edit</button><span>  </span>";
            echo "<button type='button' class='btn-group-xs' id='delete".$i."' onclick='deleteThis(".$i.")'>Delete</button>";
            echo "</div>";
            echo "</div><br />";
        }
        echo "</div>";
    }
    echo "<div class='centerText' id='avgResult'></div><br />";
    echo "<div class='centerText'>Comment:</div>";
    echo "<textarea class='submit-weekly-comment-timer' name='weeklyComment".$eventId."' id='editComment'>".$comment."</textarea>";
    echo "<br /><div  class='centerText'><input type='submit' value='Submit Times' id='submitButton' /></div>";
    echo "<input type='hidden' value='weeklySubmit' name='update' />";
    echo "<input type='hidden' value='false' name='defaultManualEntry' />";
    echo "<input type='hidden' value=".$token." name='encoding' />";
    echo "</form></div>";
    // Android Chrome bug prevents us from preloading the audio here; we must do a play on each audio control based on a touch release before it will be active; then we can load the audio
    echo "<audio id='eightSeconds' muted preload='auto'></audio>";
    echo "<audio id='twelveSeconds' muted preload='auto'></audio>";
    echo "<audio id='plusTwo' muted preload='auto'></audio>";
    echo "<audio id='dnfAnnounce' muted preload='auto'></audio>";
    echo "<audio id='fiveMinutes' muted preload='auto'></audio>";
    echo "<audio id='go' muted preload='auto'></audio>";
    echo "<audio id='penalty' muted preload='auto'></audio>";
    echo "<audio id='stop' muted preload='auto'></audio>";
    echo "</div>";
?>
<script>
    // Status : 0=not running, 1=activated, 2=running, 3=deactivated, 4=delayed(to prevent accidental restart), 5=edit mode, 6=done
    var status_notRunning = 0;
    var status_inspectionActivated = 1;
    var status_inspecting = 2;
    var status_preparingActivation = 3;
    var status_activated = 4;
    var status_running = 5;
    var status_deactivated = 6;
    var status_delayed = 7;
    var status_edit = 8;
    var status_done = 9;
    var status_enterAttempted = 10;
    var status_enterSolved = 11;
    var status_indicatingSplit = 12;
    var playStatus = 0;
    var nextPlay = 0;
    var wcaInspection = localStorage.getItem('wcaInspection') === 'true' ? true : false;
    var disableStartDelay = localStorage.getItem('disableStartDelay') === 'true' ? true : false;
    var blindfoldedMode = localStorage.getItem('blindfoldedMode') === 'true' ? true : false;
    
    function init(){
        noSolves = <?php print $noSolves ?>;
        scrambles = <?php print $scrambles ?>;
        eventId = <?php print $eventId ?>;
        fmcSolution = "<?php print $fmcSolution ?>";
        splitCount = (blindfoldedMode && is_blindfolded_event()) ? 1 : 0;
        splitsRemaining = splitCount;
        splitValues = new Array(splitCount);
        splitValues[0] = 0;
        changeStatus(status_notRunning);
        if (eventId !== 13) {
            removeDNS();
        }
        editedItem = 0;
        countdown = 0;
        for (i = 1; i <= noSolves; i++) {
            // Replace nonbreaking spaces with regular spaces, especially for Clock scrambles.
            scrambles[i - 1] = scrambles[i - 1].replace(/\&nbsp\;/g,' ');

            // Populate DNF info
            if (!is_countdown_event()) {
                if (document.getElementById("resultSolve" + i).value == "8888") {
                    var dnfButton = document.getElementById('dnf' + i);
                    dnfButton.value = 0;
                    dnfButton.style.backgroundColor = '#AF5050';
                }
            }
        }
        updateSolveText(0, true);
        if (eventId === 17) {
            applyFMCCorrections();
        } else if (eventId === 13) {
            applyMultiCorrections();
        }
        document.body.addEventListener("keydown", pressKey, false);
        document.body.addEventListener("keyup", releaseKey, false);
        if ("ontouchstart" in window) {
            var touchImage = document.getElementById("touchImage");
            touchImage.addEventListener("touchstart",  pressKey, false);
            touchImage.addEventListener("touchend",  releaseKey, false);
            var touchInstructions = document.getElementById("touchInstructions");
            touchInstructions.addEventListener("touchstart",  pressKey, false);
            touchInstructions.addEventListener("touchend",  releaseKey, false);
        }
        document.getElementById("timerWindow").focus();

        var confirmIt = function (event) {
            if (!confirm("Any changes will be lost - are you sure you want to leave without submitting?")) event.preventDefault();
        };
        var elems = document.getElementsByClassName('protectedMenu');
        for (var i = 0, l = elems.length; i < l; i++) {
            elems[i].addEventListener('click', confirmIt, false);
        }
        elems = document.getElementsByClassName('menuMobile');
        for (var i = 0, l = elems.length; i < l; i++) {
            elems[i].addEventListener('click', confirmIt, false);
        }
        if (window.history && history.pushState) {
            addEventListener('load', function() {
                history.pushState(null, null, null); // creates new history entry with same URL
                addEventListener('popstate', function() {
                    var goBack = confirm("Any changes will be lost - are you sure you want to leave without submitting?");
                    if (goBack) {
                        history.back(); 
                    } else {
                        history.pushState(null, null, null);
                    }
                });    
            });
        }
        if (eventId === 7 && !localStorage.getItem('2BldDialogShown')) {
            alertString = "Please note that 2x2x2 blindfolded is intended to work like the other blindfolded events.\n\n";
            alertString += "Start the timer before uncovering the puzzle to begin inspection.\n\n";
            alertString += "The result should be the sum of the time to inspect, the time to don the blindfold, and the time to solve the puzzle.";
            alert(alertString);
            localStorage.setItem('2BldDialogShown', true);
        }
        if (localStorage.getItem('defaultTimerToManualEntry') === 'true') {
            manualEntryStart();
            if (editedItem > noSolves) {
                hideEditFunctions();
            }
        }
    }

    function is_DNF(result)
    {
        if (result == 8888 || result == "DNF") {
            return true;
        }
        return false;
    }

    function is_DNS(result)
    {
        if (result == 0 || result == 9999 || result == "DNS") {
            return true;
        }
        return false;
    }
    
    function validate(event)
    {
        // Prevent submission when we're clearly waiting on important data before submitting
        if (currentStatus === status_enterSolved) {
            alert("Please enter number of cubes solved before submitting results");
            return false;
        }
        
        // Make sure Manual Entry is displayed when returning if settings have been changed to do so
        if (localStorage.getItem('defaultManualEntry') === 'true') {
            document.getElementsByName('defaultManualEntry')[0].value = 'true';
        }
        
        hasDNF = false;
        hasValidSolve = false;

        // For FMC, preprocess solution to make sure it is valid.
        solution = "";
        if (eventId == 17) {
            solution = document.getElementById("solution").value;
            if (solution == "DNF") {
                hasDNF = true;
            } else if (solution != "" && solution != "DNS") {
                hasValidSolve = true;
            }
            if (hasValidSolve) {
                solution = solution.replace(/W/g, "w");
                solution = solution.replace(/X/g, "x");
                solution = solution.replace(/Y/g, "y");
                solution = solution.replace(/Z/g, "z");
                solution = solution.replace(/’/g, "'");
                solution = solution.replace(/‘/g, "'");
                validRegex = /^(([FBUDLRxyz][w]?[2']?\s*)|([\[][fbudlr][2']?[\]]\s*))*$/;
                validRegex = /^(.)*$/;
                if (!validRegex.test(solution)) {
                    alertString = "Your submitted solution does not meet WCA notation rules.  Please adjust your solution to meet WCA regulations.";
                    alert(alertString);
                    return false;
                } else {
                    document.getElementById("solution").value = solution;
                }
            }
        } else {
            for (i = 1; i <= noSolves; i++) {
                result = document.getElementById("resultSolve" + i).value;
                if (is_DNF(result)) {
                    hasDNF = true;
                } else if (!is_DNS(result)) {
                    hasValidSolve = true;
                }
            }
        }
        // Prevent cases of all DNFs with no comments to prevent bogus entries to pad scores
        if (document.getElementById("editComment").value != "") {
            // A comment has been provided; no need to check for DNFs
            return true;
        }
        if (!hasValidSolve && hasDNF) {
            event.preventDefault();
            alertString = "Please enter a comment.\n\nWe now require a comment for any event in which only DNFs are submitted. ";
            alertString += "This is to discourage entering DNFs for events in which you did not try to solve the puzzle. ";
            alertString += "In order to submit a DNF, you are expected to have made a genuine attempt to solve the puzzle.\n\n";
            if (is_blindfolded_event()) {
                 alertString += "For blindfolded events, consider entering memorization/execution splits, a description of the failure, or both.\n\n";
            } else {
                alertString += "For non-blindfolded events, consider adding a comment describing what caused the solve to be a DNF.\n\n";
            }
            alertString += "The moderators reserve the right to delete any entries for which the comments are meaningless, frivolous, or otherwise inappropriate.";
            alert(alertString);
            return false;
        }
        if (eventId == 17) {
            if (hasValidSolve) {
                event.preventDefault();
                alertString = "Please enter a comment.\n\nAll fewest moves solutions require a comment that explains how you obtained the solution.\n\n";
                alertString += "Please give a sufficient explanation such that a moderator can figure out how you found the solution.  If the comment is not sufficent to explain ";
                alertString += "how the solution was obtained, a moderator may contact you for a better explanation, and if none is provided, we reserve the right to change ";
                alertString += "your solution to a DNF.";
                alert(alertString);
                return false;
            }
        }
        return true;
    }

    function is_blindfolded_event()
    {
        if (eventId >= 7 && eventId <= 13) {
            return true;
        }
        return false;
    }
    
    function is_countdown_event() {
        if (eventId === 17 || eventId === 13) {
            return true;
        }
        return false;
    }
    
    function is_no_inspection_event() {
        if ((eventId >= 7 && eventId <= 13) || eventId === 17) {
            return true;
        }
        return false;
    }
    
    function enableTouch(instructionText) {
        if ("ontouchstart" in window) {
            document.getElementById("touchInstructions").hidden = false;
            document.getElementById("touchInstructions").innerHTML = instructionText;
            document.getElementById("touchImage").hidden = false;
            document.getElementById("touchImage").src = "Hand.png";
        }
    }
    
    function disableTouch() {
        document.getElementById("touchInstructions").hidden = true;
        document.getElementById("touchImage").hidden = true;
    }
    
    function applyFMCCorrections() {
        document.getElementById('editComment').style.height = "300px";
        document.getElementById('resultHeader').innerHTML = "<strong>Solution</strong>";
        document.getElementById('submitButton').value = "Submit Solution";
        document.getElementById('manualSelect').hidden = true;
        setCountdown();
        document.getElementById("timeDisplay").innerHTML = getDisplayTime(countdown);
    }
    
   function applyMultiCorrections() {
        document.getElementById('submitButton').value = "Submit Result";
        document.getElementById("manualSelect").innerHTML = "Click to enter results manually";
        multiResult = "<?php print $result['multiBLD'] ?>";
        setCountdown();
        if (multiResult) {
            changeStatus(status_done);
        } else {
            changeStatus(status_enterAttempted);
            document.getElementById("timeInput").hidden = false;
        }
    }
    
    function removeDNS() {
        for (i = 1; i <= noSolves; i++){
            if (document.getElementById("resultSolve" + i).value == "9999") {
                document.getElementById("resultSolve" + i).value = 0;
            }
        }
    }
    
    function manualEntryStart() {
        if (currentStatus !== status_edit) {
            if (eventId === 13) {
                document.getElementById("timeDisplay").hidden = true;
                document.getElementById("currentScramble").hidden = true;
                document.getElementById("manualSelect").innerHTML = "Restart with timer";
                changeStatus(status_edit);
            } else {
                document.getElementById("manualSelect").innerHTML = "Use timer";
                editThis(getNextSolveId());
            }
        } else {
            // Currently editing; stop editing now
            if (editedItem != 0) {
                document.getElementById('solveGroup' + editedItem).style.backgroundColor = '#FFFFFF';
            }
            editedItem = 0;

            if (eventId === 13) {
                if (confirm("This will delete your current multiBLD results and restart the event.  Are you sure you want to do this?")) {
                    document.getElementById("timeDisplay").hidden = false;
                    document.getElementById("currentScramble").hidden = false;
                    document.getElementById("manualSelect").innerHTML = "Click to enter results manually";
                    document.getElementById("resultSolve1").value = 0;
                    document.getElementById("editComment").value = "";
                    changeStatus(status_enterAttempted);
                    splitsRemaining = splitCount;
                }
            } else {
                document.getElementById("manualSelect").innerHTML = "Click to enter times manually";
                changeStatus(status_notRunning);
            }
            hideEditFunctions();
            updateSolveText(0, true);
        }
        document.getElementById('manualSelect').blur();
    }
	
    function getNextSolveId() {
        for (i = 1; i <= noSolves; i++) {
            if (document.getElementById("resultSolve" + i).value == 0) {
                return i;
            }
        }
        return i;
    }
    
    function mouseOverText(element) {
        element.style.cursor = "pointer";
        element.style.color = "blue";
    }
    
    function mouseOutText(element) {
        element.style.color = "black";
    }

    function prime_plays()
    {
        if (playStatus === 0) {
            // Handle Android Chrome bug here; activate audio elements with silence, then add sounds to the elements.
            playStatus = 1;
            document.getElementById("eightSeconds").play();
            document.getElementById("twelveSeconds").play();
            document.getElementById("plusTwo").play();
            document.getElementById("dnfAnnounce").play();
            document.getElementById("fiveMinutes").play();
            document.getElementById("stop").play();
            document.getElementById("go").play();
            document.getElementById("penalty").play();
            document.getElementById("eightSeconds").src = "Sounds/EightSeconds.mp3";
            document.getElementById("twelveSeconds").src = "Sounds/TwelveSeconds.mp3";
            document.getElementById("plusTwo").src = "Sounds/PlusTwo.mp3";
            document.getElementById("dnfAnnounce").src = "Sounds/DNF.mp3";
            document.getElementById("fiveMinutes").src = "Sounds/FiveMinutes.mp3";
            document.getElementById("stop").src = "Sounds/Stop.mp3";
            document.getElementById("go").src = "Sounds/Go.mp3";
            document.getElementById("penalty").src = "Sounds/Penalty.mp3";
        }
    }

    function play_sound(soundName)
    {
        document.getElementById(soundName).muted = false;
        document.getElementById(soundName).play();
    }
    
    function showNextScramble() {
        if (eventId === 13) {
            document.getElementById("currentScramble").innerHTML = "";
            noSolves = document.getElementById("attemptedNo").value;
            if (noSolves < 1) {
                noSolves = 0;
                document.getElementById("currentScramble").innerHTML = "Please type in number of cubes to attempt and hit Enter:";
            } else if (noSolves == 1) {
                noSolves = 0;
                document.getElementById("currentScramble").innerHTML = "Must attempt at least 2 cubes for multiBLD!";
            } else if (noSolves > scrambles.length - 1) {
                noSolves = 0;
                document.getElementById("currentScramble").innerHTML = "Not enough scrambles!  Please select a number of cubes less than or equal to " + (scrambles.length - 1) + ".";
            }
            document.getElementById("timeInput").focus();
            document.getElementById("timeInput").click();
        }
        if (currentStatus === status_edit) {
            document.getElementById("currentScramble").innerHTML = scrambles[editedItem - 1];
            return;
        }
        for (i = 1; i <= noSolves; i++) {
            if (eventId === 13) {
                document.getElementById("currentScramble").innerHTML += scrambles[i - 1]+"<br />";
            } else if (eventId === 17) {
                if (currentStatus !== status_notRunning && currentStatus !== status_activated) {
                    document.getElementById("currentScramble").innerHTML = scrambles[i - 1];
                }
                break;
            } else if (document.getElementById("resultSolve"+i).value == 0) {
                document.getElementById("currentScramble").innerHTML = scrambles[i - 1];
                break;
           }
        }
    }
    
    function calculateAverage() {
        if (noSolves === 1) return;
        var dnfCount = 0;
        var avgText = "Average of 5";
        solve1 = parseFloat(document.getElementById("resultSolve1").value);
        solve2 = parseFloat(document.getElementById("resultSolve2").value);
        solve3 = parseFloat(document.getElementById("resultSolve3").value);
        if (solve1 == 8888 || solve1 == 9999) dnfCount++;
        if (solve2 == 8888 || solve2 == 9999) dnfCount++;
        if (solve3 == 8888 || solve3 == 9999) dnfCount++;
        if (noSolves === 3) {
            avg = Math.round(100*(solve1+solve2+solve3)/3)/100;
            avgText = "Mean of 3";
        } else {
            solve4 = parseFloat(document.getElementById("resultSolve4").value);
            solve5 = parseFloat(document.getElementById("resultSolve5").value);
            if (solve4 == 8888 || solve4 == 9999) dnfCount++;
            if (solve5 == 8888 || solve5 == 9999) dnfCount++;
            low = Math.min(solve1, solve2, solve3, solve4, solve5);
            high = Math.max(solve1, solve2, solve3, solve4, solve5);
            avg = Math.round(100*(solve1+solve2+solve3+solve4+solve5-low-high)/3)/100;
        }
        if (dnfCount > 1 || (noSolves === 3 && dnfCount > 0)) {
            document.getElementById('avgResult').innerHTML = "DNF " + avgText;
        } else if (!isNaN(avg)) {
            document.getElementById('avgResult').innerHTML = getDisplayTime(avg) + " " + avgText;
        } else {
            document.getElementById('avgResult').innerHTML = "";
        }
    }

    function hideEditFunctions() {
        if (eventId === 13) {
            document.getElementById('MBLDInputs').hidden = true;
            document.getElementById('MBLDTexts').hidden = false;
        } else {
            document.getElementById("editButtons").hidden = true;
            document.getElementById("timeInput").hidden = true;
            document.getElementById("timeInput").blur();
        }
    }
    
    function changeStatus(newStatus) {
        currentStatus = newStatus;
        switch (currentStatus) {
            case status_edit:
                if (eventId === 13) {
                    document.getElementById('MBLDInputs').hidden = false;
                    document.getElementById('MBLDTexts').hidden = true;
                    document.getElementById("attemptedNo").focus();
                } else {
                    document.getElementById("timeInput").value = 0;
                    document.getElementById("timeInput").hidden = false;
                    document.getElementById("timeInput").focus();
                    document.getElementById("editButtons").hidden = false;
                }
                disableTouch();
                break;
                
            case status_done:
                document.getElementById("resultsWindow").style.pointerEvents = "initial";
                if (eventId === 13) {
                    document.getElementById("solvedNo").focus();
                    document.getElementById("multiTime").value = document.getElementById("resultSolve1").value;
                    document.getElementById('manualSelect').innerHTML = "Click to edit result or restart";
                } else {
                    document.getElementById("manualSelect").innerHTML = "Click to enter times manually";
                    document.getElementById('manualSelect').hidden = true;
                }
                editedItem = 0;
                hideEditFunctions();
                disableTouch();
                document.getElementById("timeDisplay").innerHTML = "Done!";
                break;
                
            case status_notRunning:
                document.getElementById("resultsWindow").style.pointerEvents = "initial";
                if (eventId === 17) {
                    document.getElementById("currentScramble").innerHTML = "Start timer to see scramble";
                }
                document.getElementById("timeDisplay").style.color = "black";
                editedItem = 0;
                hideEditFunctions();
                document.getElementById('manualSelect').innerHTML = "Click to enter times manually";
                document.getElementById('manualSelect').hidden = false;
                enableTouch("Touch here to start timer");
                splitsRemaining = splitCount; // reset split counter for next solve
                break;
                
            case status_running:
                scroll(0,0);
                enableTouch("Touch here to stop timer");
                document.getElementById("timeDisplay").style.color = "black";
                if (eventId === 17) {
                    showNextScramble();
                } else {
                    document.getElementById("resultsWindow").style.pointerEvents = "none";
                    document.getElementById("currentScramble").innerHTML = "Solving scramble " + getNextSolveId();
                    if (splitCount > 0 && splitsRemaining < splitCount) {
                        document.getElementById("currentScramble").innerHTML += ", memorization time: " + getDisplayTime(splitValues[0]);
                    }
                }
                break;
                
            case status_preparingActivation:
                scroll(0,0);
                document.getElementById("timeDisplay").style.color = "red";
                document.getElementById("touchImage").src = "HandStop.png";
                break;
                
            case status_activated:
                scroll(0,0);
                if (eventId === 13) {
                    document.getElementById("currentScramble").innerHTML = "";
                }
                document.getElementById("timeDisplay").innerHTML = "0.00";
                document.getElementById("timeDisplay").style.color = "green";
                document.getElementById("touchImage").src = "HandGo.png";
                break;
                
            case status_delayed:
                //document.getElementById("timeDisplay").style.color = "red";
                //document.getElementById("touchImage").src = "HandStop.png";
                break;

            case status_enterAttempted:
                document.getElementById("attemptedNo").value = "";
                document.getElementById("attemptedNoText").innerHTML = "Number Attempted: ";
                document.getElementById("solvedNo").value = "";
                document.getElementById("solvedNoText").innerHTML = "Number Solved: ";
                document.getElementById("multiTime").value = "";
                document.getElementById("multiTimeText").innerHTML = "Time: ";
            case status_enterSolved:
                document.getElementById("timeInput").hidden = false;
                document.getElementById("timeInput").focus();
                document.getElementById("timeInput").value = "";
                document.getElementById("timeDisplay").innerHTML = "?";
                break;
                
            case status_inspectionActivated:
                scroll(0,0);
                document.getElementById("currentScramble").innerHTML = "Release to start inspection";
                document.getElementById("timeDisplay").innerHTML = "&nbsp;";
                break;
                
            case status_inspecting:
                document.getElementById("currentScramble").innerHTML = "Inspection time remaining:";
                document.getElementById("timeDisplay").style.color = "black";
                enableTouch("Touch here to start timer");
                scroll(0,0);
                break;
                
            case status_indicatingSplit:
                document.getElementById("timeDisplay").style.color = "blue";
                if (splitCount > 0 && splitsRemaining < splitCount) {
                    document.getElementById("currentScramble").innerHTML += ", memorization time: " + splitValues[0];
                }
                break;
        }
    }
    
    function changeAttempted() {
        document.getElementById("timerWindow").focus();
        document.getElementById("timerWindow").click();
        enableTouch("Touch here to start timer");
        setCountdown();
        showNextScramble();
    }
    
    function updateSolveText(resultTime, checkForDone) {
        if (resultTime != 0) {
            changeStatus(status_deactivated);
        }
        
        if (is_countdown_event()) {
            if (resultTime != 0) {
                document.getElementById("resultSolve1").value = resultTime;
                changeStatus(status_done);
             } else {
                showNextScramble();
            }
            refresh();
            return;
        }

        var solveUpdated = 0;
        allSolved = true;
        for (i = 1; i <= noSolves; i++) {
            if (document.getElementById("resultSolve" + i).value == 0) {
                if (resultTime == 0) {
                    document.getElementById("solve" + i).innerHTML = "";
                    hideCorrectionButtons(i);
                    allSolved = false;
                } else {
                    document.getElementById("resultSolve"+i).value = resultTime;
                    document.getElementById("solve" + i).innerHTML = getDisplayTime(resultTime) + "  ";
                    showCorrectionButtons(i);
                    solveUpdated = i;
                    resultTime = 0;
                }
            } else {
                document.getElementById("solve" + i).innerHTML = getDisplayTime(document.getElementById("resultSolve" + i).value) + "  ";
                showCorrectionButtons(i);
            }
        }
        if (allSolved && checkForDone) {
            disableTouch();
            document.getElementById("currentScramble").innerHTML = "Click Submit Times to submit, or click on buttons at right to edit";
            calculateAverage();
            changeStatus(status_done);
        } else if (!allSolved && currentStatus === status_done) {
            changeStatus(status_notRunning);
        }
        showNextScramble();
        refresh();
        //changeStatus(currentStatus);
        return solveUpdated;
    }
    
    function activateTimer() {
        changeStatus(status_activated);
    }

    function resumeTimer() {
        if (!is_countdown_event() && !allSolved) {
            changeStatus(status_notRunning);
        }
    }
    
    function setCountdown() {
        countdown = 3600;
        if (eventId === 13) {
            noSolves = document.getElementById("attemptedNo").value;
            if (noSolves < 6) {
                // Time allowed for multiBLD
                countdown = noSolves * 600;
            }
        }
    }

    function getDisplayTime(time)
    {
        if (time === "DNF") {return "DNF";}
        if (time === "8888") {return "DNF";}
        if (time === "9999") {return "DNS";}
        if (time === "0") {return time;}
        m = Math.floor(time / 60);
        s = Math.floor(time - (m * 60));
        c = Math.round((time * 100) - (m * 6000) - (s * 100));
        result = "";
        if (m > 0) {
            if (s === 0) {
                result = m + ":00";
            } else if (s < 10) {
                result = m + ":0" + s;
            } else {
                result = m + ":" + s;
            }
        } else {
            result = s;
        }
        if (c < 10) {
            result = result + ".0" + c;
        } else {
            result = result + "." + c;
        }
        return result;
    }
    
    function get_result_time()
    {
        var resultTime = new Date().getTime() - startTime;
        resultTime = Math.round(resultTime/10)/100;
        return resultTime;
    }

    function update_split_comment(resultTime, updatedSolve)
    {
        if (splitCount > 0) {
            // Copy splits for solve into comment field
            newComment = document.getElementById("editComment").value;
            if (newComment !== "") {
                document.getElementById("editComment").value += "\n";
            }
            document.getElementById("editComment").value += updatedSolve + ". " + getDisplayTime(resultTime) + "[" + getDisplayTime(splitValues[0]) + "]";
        }
    }
    
    function prepare_enter_solved(resultTime)
    {
        document.getElementById("multiTime").value = getDisplayTime(resultTime);
        document.getElementById("multiTimeText").innerHTML = "Time: " + getDisplayTime(resultTime);
        update_split_comment(resultTime, 1);
        changeStatus(status_enterSolved);
    }

    function refresh() {
        if (currentStatus === status_edit) {
            document.getElementById("timeDisplay").innerHTML = getDisplayTime(document.getElementById("resultSolve" + editedItem).value);
        }
        if (   currentStatus !== status_running
            && currentStatus !== status_inspecting
            && currentStatus !== status_preparingActivation
            && currentStatus !== status_activated
            && currentStatus !== status_indicatingSplit) {
            return;
        }
        curTime = get_result_time();
        if (currentStatus === status_running && countdown > 0 && countdown - curTime < 0) {
            play_sound("stop");
            if (eventId === 17) {
                document.getElementById("currentScramble").innerHTML = "Time is up; make necessary edits at right and press 'submitSolution' button.";
            } else if (eventId === 13) {
                document.getElementById("currentScramble").innerHTML = "Time is up; enter number of cubes solved and hit Enter:";
            }
            document.getElementById("timeDisplay").innerHTML = getDisplayTime(countdown);
            updateSolveText(getDisplayTime(countdown), true);
            if (eventId === 13) {
                prepare_enter_solved(curTime);
            } else {
                changeStatus(status_done);
            }
            return;
        } else if (currentStatus === status_running && eventId === 17 && nextPlay === 0 && countdown - curTime < 300) {
            nextPlay = 1;
            play_sound("fiveMinutes");
        }
        
        countdownCurrent = countdown - curTime;
        if (eventId === 17) {
            document.getElementById("timeDisplay").innerHTML = getDisplayTime(countdownCurrent);
        } else if (eventId !== 13 && (currentStatus === status_inspecting || currentStatus === status_preparingActivation || currentStatus === status_activated)) {
            if (countdownCurrent < 7 && nextPlay === 1) {
                play_sound("eightSeconds");
                nextPlay = 2;
            }
            if (countdownCurrent < 3 && nextPlay === 2) {
                play_sound("twelveSeconds");
                nextPlay = 3;
            }
            if (countdownCurrent < 0 && nextPlay === 3) {
                play_sound("plusTwo");
                nextPlay = 4;
            }
            if (countdownCurrent < -2 && nextPlay === 4) {
                play_sound("dnfAnnounce");
                nextPlay = 5;
                var updatedSolve = updateSolveText(-1, true);
                dnfThis(updatedSolve);
                document.getElementById("timeDisplay").style.color = "red";
                document.getElementById("timeDisplay").innerHTML = "DNF";
                changeStatus(status_delayed);
                pauseTimer = setTimeout(resumeTimer, 1000);
                return;
            }
            if (currentStatus === status_inspecting) {
                if (countdownCurrent > 0) {
                    document.getElementById("timeDisplay").innerHTML = Math.round(countdownCurrent);
                } else if (countdownCurrent > -2) {
                    document.getElementById("timeDisplay").innerHTML = "+2";
                } else {
                    document.getElementById("timeDisplay").innerHTML = "DNF";
                }
            }
        } else {
            document.getElementById("timeDisplay").innerHTML = getDisplayTime(curTime);
        }
        timer=setTimeout(refresh, "90");
    }

    function pressKey(event) {
        if (document.activeElement.id === "editComment") return;
        if (document.activeElement.id === "solution") return;
        if (document.activeElement.id === "attemptedNo") return;
        if (document.activeElement.id === "solvedNo") return;
        if (document.activeElement.id === "multiTime") return;
        if (document.activeElement.id === "weekly17Time3") return;
        if (currentStatus === status_running) {
            if (is_blindfolded_event() && splitsRemaining > 0) {
                splitValues[splitCount - splitsRemaining] = get_result_time();
                --splitsRemaining;
                changeStatus(status_indicatingSplit);
            } else if (is_countdown_event()) {
                finished = true;
                if (eventId === 17) {
                    if (confirm('Time is not up yet; please select "Cancel" if you really want to stop now.')) {
                        finished = false;
                    }
                }
                if (finished) {
                    clearTimeout(timer);
                    resultTime = get_result_time();
                    document.getElementById("timeDisplay").innerHTML = getDisplayTime(resultTime);
                    updateSolveText(resultTime, true);
                    if (eventId === 17) {
                        document.getElementById("currentScramble").innerHTML = "Finished; enter solution and explanation at right and then click 'Submit Solution'.";
                    } else if (eventId === 13) {
                        document.getElementById("currentScramble").innerHTML = "Finished; enter number of cubes solved and hit Enter:";
                        prepare_enter_solved(resultTime);
                    }
                }
            } else {
                clearTimeout(timer);
                resultTime = get_result_time();
                document.getElementById("timeDisplay").innerHTML = getDisplayTime(resultTime);
                var updatedSolve = updateSolveText(resultTime, true);
                update_split_comment(resultTime, updatedSolve);
               if (nextPlay === 4) {
                    // +2 occurred during inspection
                    plusTwoThis(updatedSolve);
                    nextPlay = 0;
                    play_sound("penalty");
                }
            }
        } else if ((currentStatus === status_notRunning && (!wcaInspection || is_no_inspection_event())) || currentStatus === status_inspecting) {
            if (disableStartDelay) {
                changeStatus(status_activated);
            } else {
                changeStatus(status_preparingActivation);
                pauseTimer = setTimeout(activateTimer, 300);
            }
        } else if (currentStatus === status_notRunning && wcaInspection  && !is_no_inspection_event()) {
            changeStatus(status_inspectionActivated);
        }

        if (currentStatus !== status_edit && currentStatus !== status_enterAttempted && currentStatus !== status_enterSolved) {
            event.preventDefault();
        } else {
            document.getElementById("timeInput").focus();
            document.getElementById("timeInput").click();
            if (event.keyCode === 13) { // "Enter" key
                if (eventId === 13) {
                    var newValue = document.getElementById("timeInput").value;
                    newValue = parseInt(newValue.replace(/\D/g, ''));
                    if (currentStatus === status_enterAttempted) {
                        if (newValue >= 2 && newValue <= 60) {
                            // valid number of attempted cubes
                            document.getElementById("attemptedNo").value = newValue;
                            document.getElementById("attemptedNoText").innerHTML = "Number attempted: " + newValue;
                            changeAttempted();
                            changeStatus(status_notRunning);
                            document.getElementById("timeDisplay").innerHTML = "0.00";
                        } else if (newValue < 2) {
                            document.getElementById("currentScramble").innerHTML = "Must attempt at least 2 cubes for multiBLD!";
                        } else if (newValue > 60) {
                            document.getElementById("currentScramble").innerHTML = "Not enough scrambles!  Please select a number of cubes less than or equal to " + (scrambles.length - 1) + ".";
                            document.getElementById("timeInput").value = "";
                            document.getElementById("timeDisplay").innerHTML = "";
                        }
                    } else if (currentStatus === status_enterSolved) {
                        if (newValue < 0 || newValue > parseInt(document.getElementById("attemptedNo").value)) {
                            document.getElementById("currentScramble").innerHTML = "Invalid number of solved cubes; enter number of cubes solved and hit Enter:";
                        } else {
                            document.getElementById("solvedNo").value = newValue;
                            document.getElementById("solvedNoText").innerHTML = "Number solved: " + newValue;
                            document.getElementById("currentScramble").innerHTML = "";
                            document.getElementById("currentScramble").innerHTML = "Finished; click 'Submit Result' to save result.";
                            changeStatus(status_done);
                        }
                    }
                } else {
                    finishEdit();
                }
            } else if (event.keyCode === 27) { // "Esc" key
                cancelEdits();
            } else if (startingEdit) {
                startingEdit = false;
                document.getElementById("timeInput").value = "";
            }
        }
    }

    function releaseKey(){
        prime_plays();
        if (document.activeElement.id === "editComment" || document.activeElement.id === "solution") return;
        if (currentStatus === status_indicatingSplit) {
            changeStatus(status_running);
        }
        if (currentStatus === status_activated) {
            startTime = new Date().getTime();
            changeStatus(status_running);
            if (!is_countdown_event()) {
                countdown = 0;
            } else {
                play_sound("go");
            }
            refresh();
        } else if (currentStatus === status_deactivated) {
            changeStatus(status_delayed);
            pauseTimer = setTimeout(resumeTimer, 1000);
        } else if (currentStatus === status_inspectionActivated) {
            changeStatus(status_inspecting);
            startTime = new Date().getTime();
            countdown = 15;
            nextPlay = 1;
            refresh();
        } else if (currentStatus === status_preparingActivation) {
            if (wcaInspection && !is_no_inspection_event()) {
                changeStatus(status_inspecting);
            } else {
                changeStatus(status_notRunning);
            }
            clearTimeout(pauseTimer);
            refresh();
        } else if (currentStatus === status_edit) {
            var newValue = document.getElementById("timeInput").value;
            if (newValue.toUpperCase() === "0D" || newValue.toUpperCase() === "D") {
                newValue = "DNF";
                document.getElementById("timeDisplay").innerHTML = newValue;
                document.getElementById("timeInput").value = newValue;
                dnfEdited();
                document.getElementById("timeDisplay").innerHTML = "DNF";
                return;
            } else if (newValue.toUpperCase() === "DN" || newValue.toUpperCase() === "DNFD") {
                // backspaced from DNF or typed D on top of a DNF; go back to 0
                newValue = "0";
                dnfEdited();
            } else {
                newValue = newValue.replace(/\D/g, '');
            }
            document.getElementById("timeInput").value = newValue;
            document.getElementById("timeDisplay").innerHTML = getDisplayTime(getEditTime(newValue));
        } else if (currentStatus === status_enterAttempted || currentStatus === status_enterSolved) {
            var newValue = document.getElementById("timeInput").value;
            newValue = newValue.replace(/\D/g, '');
            if (newValue == "") {
                document.getElementById("timeDisplay").innerHTML = "0";
            } else {
                document.getElementById("timeInput").value = newValue;
                document.getElementById("timeDisplay").innerHTML = newValue;
            }
        }
    }

    function getEditTime(value) {
        return (Math.floor(value / 10000) * 6000 + value % 10000) / 100;
    }

    function createEditTime(time) {
        if (time === "DNF") {return "DNF";}
        if (time === "8888") {return "DNF";}
        if (time === "9999") {return "0";}
        if (time === "0") {return time;}
        if (time === "") {return "0";}
        m = Math.floor(time / 60);
        s = Math.floor(time - (m * 60));
        c = Math.round((time * 100) - (m * 6000) - (s * 100));
        result = "";
        if (m > 0) {
            if (s === 0) {
                result = m + "00";
            } else if (s < 10) {
                result = m + "0" + s;
            } else {
                result = m + "" + s;
            }
        } else {
            result = s;
        }
        if (c < 10) {
            result = result + "0" + c;
        } else {
            result = result + "" + c;
        }
        return result;
    }

    function showCorrectionButtons(id) {
        document.getElementById('plusTwo' + id).hidden = false;
        document.getElementById('dnf' + id).hidden = false;
        document.getElementById('edit' + id).hidden = false;
        document.getElementById('delete' + id).hidden = false;
    }
    
    function hideCorrectionButtons(id) {
        document.getElementById('plusTwo' + id).hidden = true;
        document.getElementById('dnf' + id).hidden = true;
        document.getElementById('edit' + id).hidden = true;
        document.getElementById('delete' + id).hidden = true;
    }
    
    function plusTwoThis(id) {
        var plusTwoButton = document.getElementById('plusTwo' + id);
        var plusTwoValue = Number(plusTwoButton.value);
        if (plusTwoValue < 16) {
            addPlusTwoStatus(id);
        } else {
            removePlusTwoStatus(id);
        }
        updateSolveText(0, true);
        plusTwoButton.blur();
    }
    
    function addPlusTwoStatus(id) {
        var plusTwoButton = document.getElementById('plusTwo' + id);
        var plusTwoValue = Number(plusTwoButton.value);

        // Apply this +2
        var resultValue = Number(document.getElementById('resultSolve' + id).value);
        if (resultValue !== 8888 && resultValue !== 9999) {
            plusTwoValue += 2;
            plusTwoButton.style.backgroundColor = '#AF5050';
            document.getElementById('resultSolve' + id).value = resultValue + 2;
        }
        plusTwoButton.value = plusTwoValue;
        plusTwoButton.innerHTML = "+2 (+" + plusTwoValue + ")";
    }
    
    function removePlusTwoStatus(id) {
        var plusTwoButton = document.getElementById('plusTwo' + id);
        var plusTwoValue = Number(plusTwoButton.value);

        // Clear the +2s
        var resultValue = Number(document.getElementById('resultSolve' + id).value);
        document.getElementById('resultSolve' + id).value = resultValue - plusTwoValue;
        plusTwoValue = 0;
        plusTwoButton.style.backgroundColor = '#4CAF50';
        plusTwoButton.value = plusTwoValue;
        plusTwoButton.innerHTML = "+2 (+" + plusTwoValue + ")";
    }
    
    function dnfThis(id) {
        var previousValue = document.getElementById('resultSolve' + id).value;
        if (previousValue !== "8888") {
            addDnfStatus(id);
        } else {
            removeDnfStatus(id);
        }
        updateSolveText(0, true);
        var dnfButton = document.getElementById('dnf' + id);
        dnfButton.blur();
    }
    
    function addDnfStatus(id) {
        var previousValue = document.getElementById('resultSolve' + id).value;
        var dnfButton = document.getElementById('dnf' + id);
        dnfButton.value = previousValue;
        dnfButton.style.backgroundColor = '#AF5050';
        document.getElementById('resultSolve' + id).value = "8888";
        if (editedItem != 0) {
            document.getElementById('dnf').value = getEditTime(document.getElementById("timeInput").value);
            document.getElementById('dnf').style.backgroundColor = '#AF5050';
        }
    }
    
    function removeDnfStatus(id) {
        var dnfButton = document.getElementById('dnf' + id);
        document.getElementById('resultSolve' + id).value = (dnfButton.value == 8888) ? 0 : dnfButton.value;
        dnfButton.value = 8888;
        dnfButton.style.backgroundColor = '#4CAF50';
        if (editedItem != 0) {
            document.getElementById('dnf').value = 8888;
            document.getElementById('dnf').style.backgroundColor = '#4CAF50';
        }
    }

    function deleteThis(id){
        changeStatus(status_deactivated); // temporarily disable while processing the delete
        if (confirm("Are you sure you want to delete the time for solve " + id + "?")) {
            removePlusTwoStatus(id);
            removeDnfStatus(id);
            document.getElementById('resultSolve' + id).value = "";
            document.getElementById('solve' + id).innerHTML = id + ".  ";
        }
        document.getElementById("timeDisplay").innerHTML = "0.00";
        changeStatus(status_notRunning);
        updateSolveText(0, true);
        if (!is_countdown_event()) {
            if (localStorage.getItem('defaultTimerToManualEntry') === 'true') {
                manualEntryStart();
            } else {
                document.getElementById('manualSelect').innerHTML = "Click to enter times manually";
            }
            document.getElementById('manualSelect').hidden = false;
        }
        document.getElementById('dnf' + id).value = 8888;
        document.getElementById('dnf' + id).style.backgroundColor = '#4CAF50';
        document.getElementById('delete' + id).blur();
    }
    
    function editThis(id) {
        if (editedItem !== 0) {
            document.getElementById('solveGroup' + editedItem).style.backgroundColor = '#FFFFFF'; // Remove any previous editing indication
        }            
        editedItem = id;
        changeStatus(status_edit);
        newValue = document.getElementById("resultSolve" + editedItem).value;
        document.getElementById("timeInput").value = createEditTime(newValue);
        document.getElementById("timeDisplay").innerHTML = getDisplayTime(newValue);
        document.getElementById('dnf').value = document.getElementById('dnf' + editedItem).value;
        if (document.getElementById('dnf').value != 8888) {
            document.getElementById('dnf').style.backgroundColor = '#AF5050';
        }
        document.getElementById('solveGroup' + editedItem).style.backgroundColor = '#EEEEEE';
        updateSolveText(0, false);
        startingEdit = true;
    }
    
    function finishEdit() {
        var dnfButton = document.getElementById('dnf' + editedItem);
        var newValue = document.getElementById("timeInput").value;
        if (newValue === "DNF") {
            addDnfStatus(editedItem);
        } else if (document.getElementById('dnf').value != 8888) {
            document.getElementById("resultSolve" + editedItem).value = getEditTime(newValue);
            newValue = "DNF";
            addDnfStatus(editedItem);
        } else if (newValue === "0") {
            cancelEdits();
            return;
        } else {
            newValue = newValue.replace(/\D/g,'');
            document.getElementById("resultSolve" + editedItem).value = getEditTime(newValue);
            dnfButton.value = getEditTime(newValue);
            removeDnfStatus(editedItem);
        }
        document.getElementById("timeInput").value = "";
        document.getElementById("timeDisplay").innerHTML = getDisplayTime(getEditTime(newValue));
        document.getElementById('dnf').value = 0;
        document.getElementById('dnf').style.backgroundColor = '#4CAF50';
        document.getElementById('solveGroup' + editedItem).style.backgroundColor = '#FFFFFF';
        startingEdit = false;
        if (newValue !== "") {
            editedItem++;
            finishEditOperation();
        }
    }
    
    function finishEditOperation() {
        needsUpdateSolveText = true;
        if (eventId === 13) {
            hideEditFunctions();
        } else if (document.getElementById("manualSelect").innerHTML != "Use timer") {
            changeStatus(status_notRunning);
        } else if (editedItem > noSolves) {
            changeStatus(status_done);
        } else {
            editThis(editedItem);
            needsUpdateSolveText = false;
        }
        if (needsUpdateSolveText) {
            updateSolveText(0, true);
        }
    }
    
    function dnfEdited() {
        dnfEditButton = document.getElementById('dnf');
        if (dnfEditButton.value == 8888) {
            document.getElementById('dnf').value = getEditTime(document.getElementById("timeInput").value);
            document.getElementById('dnf').style.backgroundColor = '#AF5050';
        } else {
            newValue = document.getElementById('dnf').value;
            document.getElementById("timeInput").value = createEditTime(newValue);
            document.getElementById("timeDisplay").innerHTML = getDisplayTime(newValue);
            document.getElementById('dnf').value = 8888;
            document.getElementById('dnf').style.backgroundColor = '#4CAF50';
        }
    }
   
    function saveEdits() {
        finishEdit();
    }
    
    function cancelEdits() {
        document.getElementById("timeInput").value = 0;
        document.getElementById("timeDisplay").innerHTML = getDisplayTime(document.getElementById("resultSolve" + editedItem).value);
        document.getElementById('dnf').value = 0;
        document.getElementById('dnf').style.backgroundColor = '#4CAF50';

        // Because we're canceling, it's weird if we stay in manual edit mode.  So switch back to timer mode.
        document.getElementById("manualSelect").innerHTML = "Click to enter times manually";
        document.getElementById('solveGroup' + editedItem).style.backgroundColor = '#FFFFFF';
        editedItem = 0;
        finishEditOperation();
    }

    init();
</script>
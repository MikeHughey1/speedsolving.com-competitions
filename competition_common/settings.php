<script>
    function init() {
        document.getElementById('defaultTimerToManualEntry').checked = (localStorage.getItem('defaultTimerToManualEntry') == 'true');
        document.getElementById('disableStartDelay').checked = (localStorage.getItem('disableStartDelay') == 'true');
        document.getElementById('wcaInspection').checked = (localStorage.getItem('wcaInspection') == 'true');
        document.getElementById('blindfoldedMode').checked = (localStorage.getItem('blindfoldedMode') == 'true');
        document.getElementById('hideScrambles').checked = (localStorage.getItem('hideScrambles') == 'true');
        document.getElementById('defaultManualEntry').checked = (localStorage.getItem('defaultManualEntry') == 'true');
    }

    function checkboxChange(id) {
        localStorage.setItem(document.getElementById(id).name, document.getElementById(id).checked);
    }
</script>

<?php

    echo <<<EOD
    <div class='settings-panel'>
        <div>
            <div><b><u>Timer settings</u></b></div>

            <input onchange='checkboxChange(this.id)' type='checkbox' id='disableStartDelay' name='disableStartDelay' />
            <label for='disableStartDelay'>Disable Timer Start Delay</label>
            <br>
            <input onchange='checkboxChange(this.id)' type='checkbox' id='wcaInspection' name='wcaInspection' />
            <label for='wcaInspection'>WCA Inspection (only activated for non-blindfolded events)</label>
            <br>
            <input onchange='checkboxChange(this.id)' type='checkbox' id='blindfoldedMode' name='blindfoldedMode' />
            <label for='blindfoldedMode'>Blindfolded Mode (only activated for blindfolded events, touch once between memorization and solving; gives splits)</label>
            <br>
            <input onchange='checkboxChange(this.id)' type='checkbox' id='defaultTimerToManualEntry' name='defaultTimerToManualEntry' />
            <label for='defaultTimerToManualEntry'>Set Timer to Default to Manual Entry of Results</label>
            <br>
        </div>
        <div>
            <br>
            <div><b><u>Manual Entry settings</u></b></div>

            <input onchange='checkboxChange(this.id)' type='checkbox' id='hideScrambles' name='hideScrambles' />
            <label for='hideScrambles'>Hide Scrambles</label>
            <br>
            <input onchange='checkboxChange(this.id)' type='checkbox' id='defaultManualEntry' name='defaultManualEntry' />
            <label for='defaultManualEntry'>Make my default page Manual Entry instead of Weekly View</label>
        </div>
    </div>
EOD;

    echo "<script>init();</script>";
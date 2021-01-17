<div class="centerText">
<?php
    if(!$currentUserId) {
        if ($sessionType == "old") {
            echo <<<END
            <h2 class="centerText">Log on:</h2><br>
            <form action='index.php' method='post' onsubmit='return checkSettings(event);'><br>
                <input id='brugernavn' class='input-cp' type='text' name='brugernavn' autofocus onClick = '{this.value = "";}' /><br>
                <input id='password' class='input-cp' type='password' name='password' onClick = '{this.value = "";}' /><br>
                <input type='hidden' value='logIn.php' name='update' />
                <input type='hidden' value='false' name='defaultManualEntry' />
                <div class="centerText"><input type='submit' value='Login!' id='submitButton'/></div>
            </form>
END;
        } else {
            echo <<<END
            <h2 class="centerText">Weekly Competition<br><br>
                If you would like to join our Weekly Competition, please Log in first.<br>
                If you are not yet a member, please Register by clicking the Register option in the upper right corner.<br>
                <br><br><br><br>
            </h2>
END;
        }
    } else {
        $personInfo = get_person_info($currentUserId);
        $username = $personInfo['username'];
        $firstName = $personInfo['firstName'];
        $lastName = $personInfo['lastName'];
        $displayName = $personInfo['displayName'];
        $email = $personInfo['email'];
        $hideNamesChecked = ($personInfo['hideNames'] == 1) ? 'checked' : '';
        
        if ($sessionType == "old") {
        echo <<<END
        <h1><u> Profile </u></h1><br>
        <form action='index.php' method='post' onsubmit='return validate(event);'>
            <div class='centerText'>
                <table align='center'>
                    <tbody>
                        <tr>
                            <th class='r'>Username:</th>
                            <th class='l' id='username'>$username</th>
                            <th></th>
                        </tr>
                        <tr>
                            <th>&nbsp;</th>
                            <th>&nbsp;</th>
                            <th>&nbsp;</th>
                        </tr>
                        <tr>
                            <th>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Old Password:</th>
                            <th><input type="password" name="oldPassword" onChange='enableChanges(event);' onkeydown='handlePasswordKeydown(event);' autofocus /></th>
                        </tr>
                    </tbody>
                </table>
                <div>For security reasons, you must verify your existing password before changing your profile data.</div>
                <table align='center'>
                    <tbody>
                        <tr>
                            <th>&nbsp;</th>
                            <th>&nbsp;</th>
                            <th>&nbsp;</th>
                        </tr>
                        <tr>
                            <th class='r'>email address:</th>
                            <th class='l'><input class='enableWithPassword' disabled id='email' name='email' value='$email' /></th>
                        </tr>
                        <tr>
                            <th>&nbsp;</th>
                            <th>&nbsp;</th>
                            <th>&nbsp;</th>
                        </tr>
                        <tr>
                            <th class='r'>New Password:</th>
                            <th class='l'><input class='enableWithPassword' disabled type="password" name="newPassword" id='password' /></th>
                        </tr>
                        <tr>
                            <th class='r'>Retype New Password:</th>
                            <th class='l'><input class='enableWithPassword' disabled type="password" name="password2" id="password2" /></th>
                        </tr>
                        <tr>
                            <th>&nbsp;</th>
                            <th>&nbsp;</th>
                            <th>&nbsp;</th>
                        </tr>
                        <tr>
                            <th class='r'>First Name:</th>
                            <th class='l'><input class='enableWithPassword' disabled id='firstName' name='firstName' value='$firstName' onChange='name_changed()' /></th>
                        </tr>
                        <tr>
                            <th class='r'>Last Name:</th>
                            <th class='l'><input class='enableWithPassword' disabled id='lastName' name='lastName' value='$lastName'  onChange='name_changed()' /></th>
                        </tr>
                        <tr>
                            <th class='r'>Name displayed to others:</th>
                            <th class='l' id='displayName'>$displayName</th>
                        </tr>
                        <tr>
                            <th>&nbsp;</th>
                            <th>&nbsp;</th>
                            <th>&nbsp;</th>
                        </tr>
                    </tbody>
                </table>
                <input class='enableWithPassword' disabled type='checkbox' id='hideNames' name='hideNames' $hideNamesChecked onChange='name_changed()' />
                <label for='hideNames'>Display username instead of first and last names</label>
            </div><br><br>
            <input type="hidden" value="changeProfile.php" name="update" />
            <input class='enableWithPassword' disabled type='submit' value='Save Changes' />
        </form>
END;
        } else {
        echo <<<END
        <h1><u> Profile </u></h1><br>
        <form action='index.php' method='post' onsubmit='return validate(event);'>
            <div class='centerText'>
                <table align='center'>
                    <tbody>
                        <tr>
                            <th class='r'>Username:</th>
                            <th class='l' id='username'>$username</th>
                            <th></th>
                        </tr>
                    </tbody>
                </table>
                <table align='center'>
                    <tbody>
                        <tr>
                            <th>&nbsp;</th>
                            <th>&nbsp;</th>
                            <th>&nbsp;</th>
                        </tr>
                        <tr>
                            <th class='r'>First Name:</th>
                            <th class='l'><input id='firstName' name='firstName' value='$firstName' onChange='name_changed()' /></th>
                        </tr>
                        <tr>
                            <th class='r'>Last Name:</th>
                            <th class='l'><input id='lastName' name='lastName' value='$lastName'  onChange='name_changed()' /></th>
                        </tr>
                        <tr>
                            <th class='r'>Name displayed to others:</th>
                            <th class='l' id='displayName'>$displayName</th>
                        </tr>
                        <tr>
                            <th>&nbsp;</th>
                            <th>&nbsp;</th>
                            <th>&nbsp;</th>
                        </tr>
                    </tbody>
                </table>
                <input type='checkbox' id='hideNames' name='hideNames' $hideNamesChecked onChange='name_changed()' />
                <label for='hideNames'>Display username instead of first and last names</label>
            </div><br><br>
            <input type="hidden" value="changeProfile.php" name="update" />
            <input type='submit' value='Save Changes' />
        </form>
END;
        }
    }

    echo <<<END
    <h2> Welcome! </h2><br>
    This is an automatic submission page for the weekly competition that has been going on forever on speedsolving.com!<br>
    This system was aired on 4th January 2012 and so far it takes care of more than 5400 users.<br>
    So far these users have posted <b>over 370,000 results</b> and they have altogether solved around <b>1,500,000 puzzles</b>!<br><br>
    Have a fun and puzzling time here! <br> <br>
    </div>
END;
?>
<script>
    var changesEnabled = false;

    function name_changed()
    {
        if (document.getElementById('hideNames').checked) {
            document.getElementById('displayName').innerHTML = document.getElementById('username').innerHTML;
        } else {
            document.getElementById('displayName').innerHTML = document.getElementById('firstName').value + " " + document.getElementById('lastName').value;
        }
    }

    function checkSettings(event)
    {
        if (localStorage.getItem('defaultManualEntry') === 'true') {
            document.getElementsByName('defaultManualEntry')[0].value = 'true';
        }
    }

    function validate(event)
    {
        if (localStorage.getItem('defaultManualEntry') === 'true') {
            document.getElementsByName('defaultManualEntry')[0].value = 'true';
        }
        if (document.getElementById('password').value !== document.getElementById('password2').value) {
            event.preventDefault();
            alert("New passwords must match");
            return false;
        }
        return true;
    }

    function enableChanges(event)
    {
        if (!changesEnabled) {
            changesEnabled = true;
            var inputs = document.getElementsByClassName('enableWithPassword');
            for (i = 0; i < inputs.length; ++i) {
                inputs[i].disabled = false;
            }
        }
    }
    
    function handlePasswordKeydown(event)
    {
        if (event.keyCode === 13 || (event.keyCode === 9 && !changesEnabled)) {
            enableChanges();
            event.preventDefault();
        }
    }
</script>
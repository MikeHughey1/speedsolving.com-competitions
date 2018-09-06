<div class="centerText">
<h1>Create an account</h1>
<form action="index.php" method="post" onsubmit="return validate(event);">
Username: <input class='input-cp' type="text" name="username" /> (same as speedsolving.com)<br><br>
Password: <input class='input-cp' type="password" name="password" id="password" />
Retype Password: <input class='input-cp' type="password" name="password2" id="password2" />
Firstname: <input class='input-cp' type="text" name="firstName" />
Lastname: <input class='input-cp' type="text" name="lastName" />
Email: <input class='input-cp' type="text" name="email" />
<input type="hidden" value="addUser.php" name="update" /><br>
<input type="submit" value="Submit Account Request" id="submitButton" />
</form>
</div>
<script>
    function validate(event)
    {
        if (document.getElementById('password').value !== document.getElementById('password2').value) {
            event.preventDefault();
            alert("Passwords must match");
            return false;
        }
        return true;
    }
</script>
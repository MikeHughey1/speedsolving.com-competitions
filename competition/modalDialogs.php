<script>
// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event)
{
    modal = document.getElementById('editResults');
    if (event.target === modal) {
        modal.style.display = "none";
    }
};
function close_modal()
{
    modal = document.getElementById('editResults');
    document.getElementById('iframe_editEntry').src = '';
    modal.style.display = "none";
}
function open_modal(address, userId, week, year, eventId)
{
    locationString = address + "?user=" + userId + "&week=" + week + "&year=" + year + "&eventId=" + eventId;
    document.getElementById('iframe_editEntry').src = locationString;
    document.getElementById('editResults').style.display = "block";
};
</script>
<style>
    .iframe-input {
        height: 450px;
        width: 98%;
    }
    
    .modal-content {
        display: inline-block;
        text-align: center;
        width: 72%;
    }
</style>
<?php

    function create_modal() {

        if (!is_admin()) {
            // Protect against someone inadvertently allowing this code to be called by a non-admin.  This shouldn't ever execute.
            print "Must be logged in as admin to execute this function!";
            exit;
        }
        
        print <<<EOD
        <div id="editResults" class="modal">
            <div class="modal-content">
                <span id='close' class='close' onclick='close_modal()'>&times;</span>
                <iframe class='iframe-input' id="iframe_editEntry" name="iframe_editEntry"></iframe>
            </div>
        </div>
EOD;
    }

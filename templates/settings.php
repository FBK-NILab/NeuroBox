
<script type="text/javascript">
function mount_homes() {
    $("#mount_output").text("Executing command...");
    
    $.get(OC.filePath('neurocloud','ajax','mount_homes.php'), '', function(data, result) {
        $("#mount_output").text(data);
    });
    
}

function get_public_key() {
    var user = $("#select_user").val();
    if (user === '') {
        $("#key_output").text("Please select a user");
    } else {
        $.get(OC.filePath('neurocloud','ajax','get_public_key.php'), {"user" : user }, function(data, result) {
            $("#key_output").text(data);
        });
    }
}
</script>

<div class="personalblock">
    <strong>Neurocloud users public keys</strong>
<?php
/*
$datadir = OC_Config::getValue("datadirectory");
$files = array();
$dh = opendir($datadir);
while ($file = readdir($dh)) {
    $files[] = $file;
}
closedir($dh);
*/
$files = OC_User::getUsers();
?>
    <select id="select_user" name="user">
        <option value="" label="-- select --">-- select --</option>
    
<?php
foreach ($files as $file) {
    ?><option value="<?php echo $file ?>" label="<?php echo $file ?>"><?php echo $file ?></option>
<?php
    }
?>
        </select>
    <div>
    <input type='button' onclick='javascript:get_public_key()' value="Get public key"/>
    </div>
           <pre id="key_output" class="wrap"></pre>
    <?php

if (array_count_values($files) > 0) {
    ?>
    <div><input type="button" onclick="javascript:mount_homes()" value="Mount homes"/></div>
    <div><pre id="mount_output"></pre></div>
    <?php
}

?>
</div>

<div class="personalblock">
    <strong>User homes space usage</strong>
    <table>
        <thead>
        <th>User</th>
        <th>Used space</th>
        <th>Quota</th>
        </thead>
        <tbody id='homes_disk_usage_tbody'>
            <tr><td colspan='2'><img src="/apps/neurocloud/img/loading.gif"/> Loading...</td></tr>
         </tbody></table>
    <script type="text/javascript">calculate_disk_usage('homes_disk_usage_tbody', 'homes')</script>
</div>

<div class="personalblock">
    <strong>Active jobs</strong>
    <table>
        <thead>
        <th>User</th>
        <th>Job id</th>
        <th>Job status</th>
        <th>Sandbox space</th>
        <th>Commands</th>
        </thead>
        <tbody id='jobs_disk_usage_tbody'>
            <tr><td colspan='4'><img src="/apps/neurocloud/img/loading.gif"/> Loading...</td></tr>
            </tbody></table>
    <script type="text/javascript">calculate_disk_usage('jobs_disk_usage_tbody', 'jobs')</script>
</div>

<!--
<div class="personalblock">
    <strong>Job execution log</strong>
</div>
-->

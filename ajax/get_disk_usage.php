<?php

/*
 * get_disk_usage
 * Created on: May 28, 2013 2:27:56 PM
 * 
 * Copyright 2013 EnginSoft S.p.A.
 * All rights reserved
 */
?>
<?php

if (!OC_User::isLoggedIn()) {
    exit();
}

include_once 'neurocloud/lib/common.php';

$table = "homes";
if (isset($_GET["table"])) {
    $table = $_GET["table"];
}

// get the active jobs for all users
foreach(OC_User::getUsers() as $user) {
    
    if ($table === "homes") {
        $usage = get_disk_usage($user);

        $userquota = OC_Preferences::getValue($user, 'files', 'quota', OC_Appconfig::getValue('files', 'default_quota', '???'));
        if ($usage) {
            ?><tr>
                <td><?php echo $user ?></td>
                <td><?php echo $usage ?></td>
                <td><?php echo $userquota ?></td>
            </tr><?php
        } else {
            ?><tr>
                <td><?php echo $user ?></td>
                <td>???</td>
                <td><?php echo $userquota ?></td>
            </tr><?php
        }
        
    } elseif ($table === "jobs") {
        $jobids = get_user_jobs($user);
        
        if (count($jobids) > 0) {

            foreach($jobids as $jobid) {
                $jobinfo = get_temp_job_info($user, $jobid);
                
                
                $status = JobStatus::$RUNNING;
                if ($jobinfo === false) { // if $jobinfo is false, the nc_jobinfo.json file does not exist, because the results dir was deleted from the files section
                    $status = JobStatus::$UNKNOWN; 
                } elseif (is_array($jobinfo) && isset($jobinfo['status'])) {
                    $status = $jobinfo['status'];
                } else {
                    $status = get_job_status($jobinfo);
                }
                $usage = get_disk_usage($user, $jobid);
                
            ?>
            <tr>
                <td><?php echo $user ?></td> 
                <td><?php echo $jobid ?></td>
                <td><?php echo $status ?></td>
                <td><?php echo $usage ?></td>
                <td>
                    <?php
                    if ($status === JobStatus::$UNKNOWN || $status == JobStatus::$FINISHED || $status == JobStatus::$KILLED) {
                        ?><a href='<?php echo OC_Helper::linkTo("neurocloud", "ajax/delete_temp_dir.php", array("user" => $user, "jobid" => $jobid, "redirect" => "/index.php/settings/admin")) ?>'>
                            Delete job sandbox
                        </a><?php
                    } else {
                        /* status is RUNNING */
                        ?><a href='<?php echo OC_Helper::linkTo("neurocloud", "ajax/kill_job.php", array("user" => $user, "jobid" => $jobid, "redirect" => 1, "url" => "/index.php/settings/admin")) ?>'>
                            Kill
                        </a><?php
                    }
                ?></td>
            </tr><?php
            
            }
        }
    }
}
exit();
?>
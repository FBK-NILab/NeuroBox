<?php

/*
 * job_info_row
 * Created on: Apr 17, 2013 6:07:00 PM
 * 
 * Copyright 2013 EnginSoft S.p.A.
 * All rights reserved
 */

include_once 'neurocloud/lib/common.php';

if (!OC_User::isLoggedIn()) {
    exit();
}

$job = $_GET['jobid'];
$file = $_GET['study'];
$outputid = $_GET['outputid'];
$rowid = $_GET['rowid'];


# check if we have to print the <tr> elements (we do not print them if this file is called from a AJAX call
if (isset($_GET["print_tr"])) {
    $print_tr = $_GET["print_tr"];
} else {
    $print_tr = true; // default true
}

$filelink = OC_Helper::linkTo('files', '', array("dir" => "$file/results/$job"));

$output_file = get_job_output_file($file, $job);
$jobinfo = get_job_info($file, $job);

// obtain the precedent status
$precstatus = isset($jobinfo['status']) ? $jobinfo['status'] : JobStatus::$RUNNING;
$qsub_jobname = $jobinfo["qsub_jobname"];

// obtain the current status (potentially slow operation, calls ssh/qstat)
$status = get_job_status($jobinfo);
$sgequeued = 0;
$sgerunning = 0;

if (($status == JobStatus::$FINISHED || $status === JobStatus::$KILLED)) {
    
    if ($precstatus !== $status) {
        $jobinfo['status'] = $status;
    
        // get_job_status returned FINISHED or KILLED, we save that info in the job JSON 
        save_job_info($file, $job, $jobinfo);
    }
    
    if (!isset($jobinfo["usedspace"])) {
        // calculate space used by temp dir + results
        $usedspace = get_job_disk_usage(OC_User::getUser(), $file, $job);
        if (!$usedspace) {
            $usedspace = "--";
        }
        $jobinfo['usedspace'] = $usedspace;
        
        // get_job_status returned FINISHED or KILLED, we save that info in the job JSON 
        save_job_info($file, $job, $jobinfo);
        
        insert_job_log($file, $job, "$status. Used disk space: $usedspace");
    }
    
} elseif ($status === JobStatus::$UNKNOWN) {
    $jobinfo['status'] = $status;
    save_job_info($file, $job, $jobinfo);
    
} else { /* Job is RUNNING */
    
    $queueinfo = get_queue_info($qsub_jobname);
    
    $sgequeued = $queueinfo[0];
    $sgerunning = $queueinfo[1];
}

// check the transition of a job from RUNNING to FINISHED  in order to create and destroy directory
// to make owncloud sync its file index.
if ($precstatus === JobStatus::$RUNNING && $status === JobStatus::$FINISHED)
{
    $newdir = OC_User::getHome(OC_User::getUser()).DIRECTORY_SEPARATOR."files".DIRECTORY_SEPARATOR.$job."_dummydir";
    mkdir($newdir);
    sleep(3);
    rmdir($newdir);
}

$showoutputjs = "javascript:show_output('" . $outputid . "', '" . $output_file . "')";
$killjs = OC_Helper::linkTo("neurocloud", "ajax/kill_job.php", array("study" => $file, "jobid" => $job, "redirect" => 1));
$refreshstatusjs = "javascript:refresh_job_status('" . $rowid . "','" . $file . "','" . $job . "','" . $outputid . "')";
$showjobinfojs = "javascript:show_output('" . $outputid . "','". get_job_info_file($file, $job) . "', true)";
#$queueinfojs = "javascript:show_queue_info('" . $outputid . "','". $jobinfo['qsub_jobname'] . "', true)";
$deletejs = "javascript:delete_results('" . $rowid . "','" . $file . "','" . $job . "')";

$script = isset($jobinfo["script"]) ? basename($jobinfo["script"]) : false;

$params = array("study" => $file, "script" => $script, "jobid" => $job, "mode" => "remote");
$rerun_link = OC_Helper::linkTo("neurocloud", "execute.php", $params);

?>
<?php
if ($print_tr) {
?>    
<tr id="<?php echo $rowid ?>">
<?php
}
?>
<td> <!-- script name -->
    <a href="<?php echo $filelink ?>" title="Click to open the results folder"><?php echo str_replace("pipeline/", "", $jobinfo['script']); ?></a>
</td>
<td> <!-- start date -->
    <?php echo $jobinfo["start_date"] ?>
</td>
<td> <!-- status -->
    <span class="jobstatus"><?php echo $status ?>
    <?php 
    if ($status === JobStatus::$RUNNING) {
    ?>
    <img class="jobstatusimg" onclick="<?php echo $refreshstatusjs ?>" alt="refresh" src="<?php echo OC_Helper::linkTo("neurocloud", "img/refresh.png") ?>" title="Click to refresh job status"/>
    <?php 
    if (isset($jobinfo["exec_type"]) && $jobinfo["exec_type"] === "remote") {
        ?>
    <span class="sgequeueinfo">Queued: <?php echo $sgequeued ?>, running: <?php echo $sgerunning ?></span>
    <?php
    }
    
    }
    ?>
    </span>
</td>
<td>
    <span>
    <?php
    if (isset($jobinfo['usedspace'])) {
        echo $jobinfo['usedspace'];
    } else {
        echo "--";
    }
    ?>
    </span>
</td> <!-- occupied space tmp -->
<td> <!-- commands -->
    <?php
    if ($status !== JobStatus::$RUNNING) {
        if (is_dir(get_job_exec_dir($job))) {
    ?>    
        <a href="<?php echo $rerun_link ?>">Rerun</a>
        &nbsp;
    <?php
    }
    }
    ?>
    <a href="<?php echo $showjobinfojs ?>">Info</a>
    &nbsp;
    <a href="<?php echo $showoutputjs ?>" title="Click to show a text area with the log of the standard output">Log</a> 
    &nbsp;
    <a href="<?php echo $filelink ?>" title="Click to open the results folder">Results</a>
    <?php
    // print the links to kill job (if running) and delete results (if finished)
    if ($status !== JobStatus::$RUNNING) {
    ?>
        &nbsp;
        <a href="<?php echo $deletejs ?>">Delete</a> <?php
    } 
    if ($status === JobStatus::$RUNNING) {
    ?>
        &nbsp;
        <a href="<?php echo $killjs ?>" title="Kills the running job">Kill</a> <?php
    }
?>
</td>
<?php
if ($print_tr) {
?> 
</tr>
<tr id="<?php echo $rowid . "_output" ?>" class="output_tr">
    <td colspan="5">
        <pre id="<?php echo $outputid ?>" class="output wrap"></pre>
    </td>
</tr>
<?php
}
?>

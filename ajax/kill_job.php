<?php

/*
 * kill_job
 * Created on: Jan 30, 2013 3:31:11 PM
 * 
 * Copyright 2013 EnginSoft S.p.A.
 * All rights reserved
 */
require_once 'neurocloud/lib/common.php';
require_once 'neurocloud/config.inc.php';

function do_exit($jobid, $pid, $message) {
    $redirect = isset($_GET["redirect"]);
    $url = isset($_GET["url"]) ? $_GET["url"] : OC_Helper::linkTo("neurocloud", "index.php", array("jobid" => $jobid, "pid" => $pid, "message" => $message, "action" => "kill"));
    if ($redirect) {
        header("Location: " . $url);
    } else {
        echo $message;
        exit();
    }
}

$user = isset($_GET["user"]) ? $_GET["user"] : false;
$study = isset($_GET["study"]) ? $_GET["study"] : false;
$jobid = $_GET["jobid"];

if ($study !== false) {
    $jobinfo = get_job_info($study, $jobid);
} elseif ($user !== false) {
    $jobinfo = get_temp_job_info($user, $jobid);
}

if ($jobinfo === false) {
    echo "ERROR: cannot find job info";
    exit();
}

$killtree_path = get_kill_script(OC_User::getUser());

if ($killtree_path === false) {
    do_exit($jobid, null, "ERROR");
}

if (!isset($jobinfo["exec_type"])) {
    $jobinfo["exec_type"] = "local"; // default execution type is local execution
}
$pid = $jobinfo["pid"];
if ($pid > 0 && $jobinfo["exec_type"] === "local") {
    $ret = -1;
    $output = "";
    // uses the script killtree.sh
    exec($killtree_path . " " . $pid . " KILL", $output, $ret);
    
    if ($ret == 0) {
        $jobinfo["status"] = JobStatus::$KILLED;
        save_job_info($study, $jobid, $jobinfo);
        
        do_exit($jobid, $pid, JobStatus::$KILLED);
    } else {
        do_exit($jobid, $pid, JobStatus::$FINISHED);
    }
} else if ($pid > 0 && $jobinfo["exec_type"] === "remote") {
    $ret = _kill_job_remote($jobinfo);
    
    if ($ret == 0) {
        $jobinfo["status"] = JobStatus::$KILLED;
        save_job_info($study, $jobid, $jobinfo);
        
        do_exit($jobid, $pid, JobStatus::$KILLED);
    } else {
        do_exit($jobid, $pid, JobStatus::$FINISHED);
    }
} else {
    do_exit($jobid, $pid, "ERROR: Wrong pid " . $pid);
}
exit();
?>

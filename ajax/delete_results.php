<?php

/*
 * delete_results
 * Created on: Jan 30, 2013 12:30:40 PM
 * 
 * Copyright 2013 EnginSoft S.p.A.
 * All rights reserved
 */
include_once 'neurocloud/lib/common.php';

$study = $_POST["study"];
$jobid = $_POST["jobid"];


$path = "$study/results/$jobid";

$jobinfo = get_job_info($study, $jobid);
$usedspace = isset($jobinfo["usedspace"]) ? $jobinfo["usedspace"] : "undefined";

insert_job_log($study, $jobid, "deleted results. Used disk space: $usedspace");

if (\OC\Files\Filesystem::is_dir($path)) {
    //rmdirr($path);
    \OC\Files\Filesystem::unlink($path); // from Owncloud 5.0.0, this will recurse on subdirs (delTree)
}

$execdir = get_job_exec_dir($jobid);
if (is_dir($execdir)) {
    rmdirr($execdir);
}

exit();
?>

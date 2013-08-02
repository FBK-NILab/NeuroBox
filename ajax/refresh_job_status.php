<?php

/*
 * refresh_job_status
 * Created on: Feb 4, 2013 4:11:53 PM
 * 
 * Copyright 2013 EnginSoft S.p.A.
 * All rights reserved
 */

require_once 'neurocloud/lib/common.php';

if (isset($_POST["study"]) && isset($_POST["jobid"])) {
    $study = $_POST["study"];
    $jobid = $_POST["jobid"];

    echo get_job_status(get_job_info($study, $jobid));
} else {
    echo "ERROR";
}
exit();
?>

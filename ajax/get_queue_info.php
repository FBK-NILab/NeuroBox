<?php

/*
 * get_queue_info
 * Created on: Apr 8, 2013 11:06:30 AM
 * 
 * Copyright 2013 EnginSoft S.p.A.
 * All rights reserved
 */

include_once 'neurocloud/lib/common.php';

if (isset($_GET['jobname'])) {
    $jobname = $_GET['jobname'];
    echo get_queue_info($jobname);
} else {
    echo "ERROR";
}
?>

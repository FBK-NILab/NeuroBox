<?php

/*
 * load_jobs
 * Created on: Mar 5, 2013 10:07:08 AM
 * 
 * Copyright 2013 EnginSoft S.p.A.
 * All rights reserved
 */
?>
<?php
include_once 'neurocloud/lib/common.php';

$file = $_GET['study'];
$study_idx = $_GET['study_idx'];

$jobs = get_jobs_for_study($file);
$i = 0;
?>
    <table id="jobs_<?php echo $study_idx ?>" class="jobstable" style="width: 100%">
        <caption>Jobs</caption>
        <thead>
            <tr>
                <th>Job script</th>
                <th>Start date</th>
                <th>Status</th>
                <th>Temp. space</th>
                <th>Commands</th>
            </tr>
        </thead>
        <tbody>
<?php
foreach ($jobs as $job) {
    $rowid = "job_" . $study_idx . "_" . $i;
    $outputid = "output_" . $study_idx . "_" . $i;
    
    /* HACK: I modify the contents of $_GET to use the file job_info_row.php (which is used also to refresh the row when clicking on "Refresh job status" icon */
    $_GET['jobid'] = $job;
    $_GET['study'] = $file;
    $_GET['outputid'] = $outputid;
    $_GET['rowid'] = $rowid;
    
    include 'job_info_row.php';
    
    $i++;
}
?>
</tbody>
</table>
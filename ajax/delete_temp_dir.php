<?php

/*
 * delete_temp_dir
 * Created on: Jun 5, 2013 9:43:45 AM
 * 
 * Copyright 2013 EnginSoft S.p.A.
 * All rights reserved
 */

include_once 'neurocloud/lib/common.php';

$user = $_GET["user"];
$jobid = $_GET["jobid"];
$referer = isset($_GET["redirect"]) ? $_GET["redirect"] : false;

$path = get_local_exec_dir($user) . "/$jobid";

if (is_dir($path)) {
    rmdirr($path);
}

if ($referer) {
    header("Location: " . $referer);
}

exit();
?>
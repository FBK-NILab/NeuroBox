<?php

/*
 * checkrunnable
 * Created on: Jan 29, 2013 10:45:29 AM
 * 
 * Copyright 2013 EnginSoft S.p.A.
 * All rights reserved
 */

require_once 'neurocloud/lib/hooks.php';

$study=$_POST["study"];


#error_log(print_r($_SESSION["nc_sync_paths"],true));

if (OC_Neurocloud::is_study_runnable($study)) {
    echo "1";
} else {
    echo "0";
}
exit();
?>

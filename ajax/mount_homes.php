<?php

/*
 * mount_homes
 * Created on: May 6, 2013 12:17:31 PM
 * 
 * Copyright 2013 EnginSoft S.p.A.
 * All rights reserved
 */

include "neurocloud/config.inc.php";

$cmd = $NC_CONFIG["mount_cmd"];

$output = array();
exec($cmd, $output);

header("Content-Type: text/plain");
echo join("\n", $output);

?>

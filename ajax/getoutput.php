<?php

/*
 * getoutput
 * Created on: Jan 22, 2013 6:15:05 PM
 * 
 * Copyright 2013 EnginSoft S.p.A.
 * All rights reserved
 */

$filename = $_POST['filename'];
$json = false;
if (isset($_POST['json'])) {
    $json = (boolean)$_POST["json"];
}

/*
 * max size of output file, to avoid reading a very big output and clogging the network
 */
$MAX_SIZE = 1024 * 512; //# "512k of memory should be enough for everyone"
$content = "";
if (OC_Filesystem::is_file($filename) && OC_Filesystem::is_readable($filename)) {
    if (OC_Filesystem::filesize($filename) > $MAX_SIZE) {
        $handle = OC_Filesystem::fopen($filename, "r");
        if ($handle) {
            $content = fread($handle, $MAX_SIZE);
            fclose($handle);
        }
    } else {
        
        $content = OC_Filesystem::file_get_contents($filename);
    }
} else {
    echo "ERROR: Cannot read " . $filename;
}

if ($json) {
    $json_obj = json_decode($content);
    
    foreach ($json_obj as $key => $value) {
        echo "$key : $value\n";
    }
} else {
    echo htmlspecialchars($content);
}

?>

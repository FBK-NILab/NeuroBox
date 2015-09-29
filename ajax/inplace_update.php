<?php

/*
 * inplace_update
 * Created on: May 20, 2013 9:34:11 AM
 * 
 * Copyright 2013 EnginSoft S.p.A.
 * All rights reserved
 */

header("Content-Type: text/plain");

// updates the owncloud installation
// this operation is safe only when there is a point release (5.0.4 -> 5.0.5)
if (isset($_GET['url'])) {
    $url = $_GET['url'];
} else {
    http_response_code(400);
    exit();
}

$datadir = OC_Config::getValue("datadirectory");
$root = OC::$SERVERROOT;

$prefix = $root;
if (isset($_GET["debug"])) {
    $prefix = "/tmp/owncloud";
}

//# backup current installation
echo "Backup of current version in $datadir\n";
exec("tar czf " . $datadir . "/owncloud_bkp$(date +%Y%m%d).tar.gz $root");

//# download latest version
echo "Downloading last version\n";
$curl = curl_init($url);
$fp = fopen("/tmp/owncloud-latest.tar.bz2", "w");
curl_setopt($curl, CURLOPT_FILE, $fp);

curl_exec($curl);

curl_close($curl);
fclose($fp);

//# TODO: check version MD5
$md5_try = file_get_contents($url . ".md5");
if (strlen($md5_try) > 32) {
    $md5_try = substr($md5_try, 0, 32);
}

$md5 = md5_file("/tmp/owncloud-latest.tar.bz2");

if ($md5_try !== $md5) {
    echo "MD5 mismatch!\n";
    exit();
}


//# unpack latest version
echo "Unpacking last version\n";
exec("mkdir $datadir/owncloud_latest; tar -C $datadir/owncloud_latest -xjf /tmp/owncloud-latest.tar.bz2");


echo "Copying files in $prefix\n";
exec("cp -a $datadir/owncloud_latest/owncloud/* $prefix");

echo "Removing temporary files\n";

exec("rm -rf /tmp/owncloud-latest.tar.bz2 $datadir/owncloud_latest/");
exit();
?>


<?php

/*
 * get_public_key
 * Created on: May 7, 2013 12:20:57 PM
 * 
 * Copyright 2013 EnginSoft S.p.A.
 * All rights reserved
 */
include_once 'neurocloud/lib/common.php';

$user = $_GET['user'];

$keyfile = get_private_key_file($user);

if (is_file($keyfile)) {
    $output = exec("ssh-keygen -y -f $keyfile");

    header("Content-Type: text/plain");
    echo $output . " $user";
} else {
    header("Content-Type: text/plain");
    echo "No private key found: $keyfile";
}
exit();
?>

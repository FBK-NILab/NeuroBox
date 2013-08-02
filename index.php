<?php

require_once('base.php');

// Check if we are a user
if( !OC_User::isLoggedIn()){
	header( "Location: ".OC_Helper::linkTo( '', 'index.php' ));
	exit();
}

OC_Util::addStyle('files', "files");
OC_Util::addStyle('neurocloud', "nc");

OC_App::setActiveNavigationEntry( 'neurocloud_index');
$tmpl = new OC_Template( 'neurocloud', 'main', 'user');

$message = "";
$action = "launch";
if (isset($_GET["message"])) {
    $message = $_GET["message"];
}
if (isset($_GET["action"])) {
    $action = $_GET["action"];
}

if (isset($_GET["jobid"])) {
    if (isset($_GET["action"]) && $action === "kill") {
        $tmpl->assign('infomessage', "Successfully killed job " . $_GET["jobid"]);
    } elseif (isset($_GET["pid"]) && $_GET["pid"] !== "" && $action = "launch") {
        $tmpl->assign('infomessage', "Launched job " . $_GET["jobid"] . " (pid = " . $_GET["pid"]. ")");
    } else {
        $tmpl->assign('errormessage', "Error launching job " . $_GET["jobid"]);
    }
}
if (array_key_exists("error", $_GET)) {
    $tmpl->assign("errormessage", $_GET["error"]);
}
$tmpl->printPage();


?>

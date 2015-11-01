<?php
// add to $CONFIG array settings regarding the security bug fix
require_once('config.php');
if ( ! in_array("custom_csp_policy", OC_Config::getKeys()) ) {
	OC_Config::setValue('custom_csp_policy', 'default-src \'self\'; script-src \'self\' \'unsafe-eval\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\'; frame-src *; img-src *; font-src \'self\' data:; media-src *');
}

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

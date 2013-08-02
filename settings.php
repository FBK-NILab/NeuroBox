<?php

OC_Util::checkAdminUser();

OC_Util::addStyle('neurocloud', "nc");
OC_Util::addScript("neurocloud", "admin");

$tmpl = new OC_Template( 'neurocloud', 'settings');

return $tmpl->fetchPage();

?>

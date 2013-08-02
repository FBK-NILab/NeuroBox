<?php
/**
 * Copyright (c) 2011, Frank Karlitschek <karlitschek@kde.org>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

require_once('base.php');
OC_Util::checkAdminUser();

if (array_key_exists("nc_exec_env_root", $_POST)) {

    OC_Config::setValue('nc_exec_env_root', $_POST['nc_exec_env_root']);

    echo 'true';
} else {
    echo "false";
}
?>

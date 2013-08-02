<?php

/**
* register the page of admin settings form
*/
OC_App::registerAdmin('neurocloud','settings');

/**
 * register the navigation entry
 */
OC_App::addNavigationEntry(array(
    'id' => 'neurocloud_index',
    'order' => 74, 
    'href' => OC_Helper::linkTo('neurocloud', 'index.php'), 
    'icon' => OC_Helper::imagePath('neurocloud', 'neurocloud.png'), 
    'name' => 'Neurocloud'));

/**
 * register the classpath
 */
OC::$CLASSPATH["OC_Neurocloud"] = "apps/neurocloud/lib/hooks.php";

/**
 * register the hooks for file writing
 * 
 * NOTE: to make sure that the hooks are called everytime that a file is written, the application
 * must be registered with type "filesystem". Otherwise, those hooks will be called only when uploading from the web interface
 * 
 * to register the application as a filesystem type, put in info.xml * 
 * <types><filesystem/></types>
 *
 */
#OC_Hook::connect(OC\Files\Filesystem::CLASSNAME, OC\Files\Filesystem::signal_write, "OC_Neurocloud", "beforeFileWrite");
#OC_Hook::connect(OC\Files\Filesystem::CLASSNAME, OC\Files\Filesystem::signal_post_write, "OC_Neurocloud", "afterFileWrite");
OC_Hook::connect(OC\Files\Filesystem::CLASSNAME, 'post_delete', "OC_Neurocloud", "fileDeleted");
OC_Hook::connect(OC\Files\Filesystem::CLASSNAME, OC\Files\Filesystem::signal_post_rename, "OC_Neurocloud", "fileRenamed");

// hooks for delete/rename, do not allow deleting of directory if there is a running job
OC_Hook::connect(OC\Files\Filesystem::CLASSNAME, OC\Files\Filesystem::signal_delete, "OC_Neurocloud", "beforeFileRenameDelete");
OC_Hook::connect(OC\Files\Filesystem::CLASSNAME, OC\Files\Filesystem::signal_rename, "OC_Neurocloud", "beforeFileRenameDelete");

/**
 * User hooks:
 * - before login, check if the user has a home folder correctly mounted on the kore storage. Abort login if not.
 * - before creating the user, generate a RSA private key
 * - after deleting the user, delete the private key
 */
OC_Hook::connect("OC_User", "pre_createUser", "OC_Neurocloud", "beforeCreateUser");
OC_Hook::connect("OC_User", "post_deleteUser", "OC_Neurocloud", "afterDeleteUser");
OC_Hook::connect("OC_User", "pre_login", "OC_Neurocloud", "beforeLogin");

/**
 * add Javascript code
 */
OC_Util::addScript("neurocloud", "neurocloud");


// register the fileproxy implementation. This subtitutes the old pre/post write hook because of implementation changes of owncloud 5.0.0
include_once 'neurocloud/lib/proxy.php';

OC_FileProxy::register(new NC_FileProxy());

?>
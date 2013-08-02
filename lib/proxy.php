<?php

/*
 * proxy
 * Created on: May 15, 2013 5:55:58 PM
 * 
 * Copyright 2013 EnginSoft S.p.A.
 * All rights reserved
 */

include_once "persistentmap.php";
include_once "common.php";

class NC_FileProxy extends OC_FileProxy {
    
    static $DO_LOG = false;
    
    public function prefile_put_contents($path, $data) {
        if (self::$DO_LOG) {
            error_log("prefile_put_contents $path");
        }
        PersistentMap::putval($path, 1);
        return true;
    }
    
    public function postfile_put_contents($path, $data) {
        if (self::$DO_LOG) {
            error_log("postfile_put_contents $path");
        }
        PersistentMap::remove($path);
        return true;
    }
    
}

?>

<?php

/*
 * persistentmap
 * Created on: May 16, 2013 10:37:27 AM
 * 
 * Copyright 2013 EnginSoft S.p.A.
 * All rights reserved
 */
class PersistentMap {
    static $VAR_NAME = "nc_sync_paths";
    
    /**
     * @var array the key-value array in memory . It is loaded only the first time, and saved to disk at every modification (put/delete)
     */
    static $array = false;
    
    public static function save($array) {
        $file = OC_Config::getValue("datadirectory") . "/" . OC_User::getUser() . "/" . self::$VAR_NAME;
        $serializeddata = serialize($array);
        file_put_contents($file, $serializeddata);
    } 
    
    public static function get_paths() {
        $file = OC_Config::getValue("datadirectory") . "/" . OC_User::getUser() . "/" . self::$VAR_NAME;
        if (!is_file($file)) {
            self::save(array());
            return array();
        }
        $serializeddata = file_get_contents($file);
        if (!$serializeddata) {
            return array();
        }
        return unserialize($serializeddata);
    }
    
    public static function getval($key) {
        if (self::$array === false) {
            self::$array = self::get_paths();
        }
        return self::$array[$key];
    }
    
    public static function putval($key, $value) {
        if (self::$array === false) {
            self::$array = self::get_paths();
        }
        self::$array[$key] = $value;
        self::save(self::$array);
    }
    
    public static function remove($key) {
        if (self::$array === false) {
            self::$array = self::get_paths();
        }
        if (array_key_exists($key, self::$array)) {
            unset(self::$array[$key]);
            self::save(self::$array);
        }
    }
}

?>

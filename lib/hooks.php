<?php
/*
 * hooks
 * Created on: Jan 24, 2013 12:50:50 PM
 * 
 * Copyright 2013 EnginSoft S.p.A.
 * All rights reserved
 */

include_once 'common.php';
include_once 'persistentmap.php';

function is_job_running($study, $jobid) {
    $jobinfo = get_job_info($study, $jobid);
    
    if (!is_array($jobinfo)) {
        return false;
    }
    
    if (!isset($jobinfo['status'])) {
        // status is not set: the job could be still running
        return true;
    }
    if ($jobinfo['status'] === JobStatus::$FINISHED || $jobinfo['status'] === JobStatus::$KILLED) {
        return false;
    }
    
    return false;
}

function any_jobs_running($study) {
    $jobs = get_jobs_for_study($study);
    
    foreach ($jobs as $jobid) {
        if (is_job_running($study, $jobid)) {
            return true;
        }
    }
    
    return false;
}

class OC_Neurocloud {
    static $DO_LOG = true;
    
    static public function beforeFileWrite($info) {
        $path = $info['path'];
        if (self::$DO_LOG) {
            error_log("beforeFileWrite " . $path);
        }
        PersistentMap::putval($path, 1);
    }
    
    static public function afterFileWrite($info) {
        $path = $info['path'];
        if (self::$DO_LOG) {
        error_log("afterFileWrite " . $path);
        }
        PersistentMap::remove($path);
    }
    
    static public function fileDeleted($info) {
        $path = $info['path'];
        if (self::$DO_LOG) {
        error_log("fileDeleted " . $path);
        }
        PersistentMap::remove($path);
    }
    
    public static function fileRenamed($info) {
        $oldpath = $info["oldpath"];
        $newpath = $info["newpath"];
        
        $array = PersistentMap::get_paths();
        if (self::$DO_LOG) {
        error_log("fileRenamed " . $oldpath . " to " . $newpath);
        }
        if (array_key_exists($oldpath, $array)) {
            unset($array[$oldpath]);
        }
        if (array_key_exists($newpath, $array)) {
            unset($array[$newpath]);
        }
        PersistentMap::save($array);
    }
    
    /**
     * 
     * @param array $info the associative array of info for the file being processed
     */
    public static function beforeFileRenameDelete($info) {
        $file = $info['path'];
        if (!$file) {
            $file = $info['oldpath'];
        }
        if (self::$DO_LOG) {
            error_log("path === $file");
        }
        
        $pathsplit = explode("/", $file);
        $len = count($pathsplit);
        $lastelem = $pathsplit[$len - 1];
        
        // the splitted path has always the first element empty, so $pathsplit[0] === ""
        $firstelem = $pathsplit[1];
        
        if (is_valid_casestudy("/" . $firstelem) === null) {
            if (self::$DO_LOG) {
                error_log("$firstelem is a valid case study");
            }
            
            if ($len > 3 && $pathsplit[2] === 'results' && \OC\Files\Filesystem::is_dir($file)) {
                // case 1: trying to delete/rename one the results directories of a running job
                
                if (is_job_running($firstelem, $lastelem)) {
                    // since the job is still running, do not allow to rename/delete the results directory
                    if (self::$DO_LOG) {
                        error_log("beforeFileRenameDelete : trying to rename/delete $file while the job is still running");
                    }
                    $info['run'] = false;
                } else {
                    if (self::$DO_LOG) {
                        error_log("beforeFileRenameDelete : no job running for $file");
                    }
                    // the user is deleting a results directory: delete also the temp directory if existing
                    
                    $tempdir = get_job_exec_dir($file);
                    if (is_dir($tempdir)) {
                        rmdirr($tempdir);
                    }
                }
            }
            if ($lastelem === 'data' || $lastelem === 'pipeline' || $lastelem === 'results') {
                // case 2: we are trying to rename/delete one of the directories data/pipeline/results of a case study
                if (any_jobs_running($firstelem)) {
                    if (self::$DO_LOG) {
                        error_log("beforeFileRenameDelete : trying to rename/delete $file while jobs are running");
                    }
                    $info['run'] = false;
                } else {
                    if (self::$DO_LOG) {
                        error_log("beforeFileRenameDelete : no jobs running for $file");
                    }
                }
            }
            if ($len > 3 && $pathsplit[2] === 'data') {
                // case 3: renaming/deleting a file inside the data directory, while a job is running. Do not always allow it
                if (any_jobs_running($firstelem)) {
                    if (self::$DO_LOG) {
                        error_log("beforeFileRenameDelete : trying to rename/delete $file while jobs are running");
                    }
                    $info['run'] = false;
                } else {
                    if (self::$DO_LOG) {
                        error_log("beforeFileRenameDelete : no jobs running for $file");
                    }
                }
            }
        } 
        
    }
    
    /**
     * Hook that executes before a user is created. This will generate a new SSH key pair (public/private)
     * and places them in /home/neurocloud/keys
     * 
     * 
     * @param array $info keys: uid, password
     */
    public static function beforeCreateUser($info) {
        $user = $info['uid'];
        $keyfile = get_private_key_file($user);
        // by placing < /dev/null, if the key file already exists, do not overwrite it
        exec("ssh-keygen -q -C '$user' -t rsa -P '' -N '' -f $keyfile < /dev/null");
        // move out the generated public key
        if (is_file($keyfile . ".pub")) {
            unlink($keyfile . ".pub");
        }
        
        mkdir(OC_Config::getValue("datadirectory") . "/" . $user, 0770, true);
    }
    
    /**
     * procedure that launches before a user logs in.
     * Checks if a file named /home/neurocloud/keys/$username exists
     * If the user is present in the keys folder (which means that the user must be present in the cluster and authenticated by public/private key):
     * 
     * Checks if the home folder is ok, which means that the home folder must be mounted on the korein storage
     * 
     * @param type $info
     */
    public static function beforeLogin($info) {
        $user = $info['uid'];
        
        $keyfile = get_private_key_file($user);
        
        if (is_file($keyfile)) {
            exec("mount -l -t fuse.sshfs | grep $user", $output, $ret_val);
            if ($ret_val !== 0) {
                // the user home folder is NOT present in the mount list. So, the login cannot be completed
                $info["run"] = false; // tell the system to abort the login
                
                // log to owncloud that the user is 
                OC_Log::write("neurocloud", "User " . $user . " cannot login: /home/neurocloud/data/$user is unmounted or mounted uncorrectly", OC_Log::WARN);
            }
            // the home folder is mounted, all OK
        }
        // the user is local to owncloud, the files are saved on the local disk. all OK
    }
    
    public static function afterDeleteUser($info) {
        $user = $info['uid'];
        $keyfile = get_private_key_file($user);
        $publickeyfile = get_public_key_file($user);
        
        exec("rm -f $keyfile $publickeyfile");
        exec("sudo umount " . OC_Config::getValue("datadirectory") . "/$user");
    }
    
    /**
     * @return array an array with key=study name, value = SYNC OK or SYNC PROGRESS
     */
    public static function get_sync_status() {
        $result = array();
        $array = PersistentMap::get_paths();
        foreach(array_keys($array) as $key) {
            if ($array[$key] === 1) {
                $result[$key] = "SYNC PROGRESS";
            }
        }
        
        return $result;
    }

    /**
     * returns true if a case study can be used for launching scripts.
     * If all the statuses of 
     * 
     * @param type $study
     */
    public static function is_study_runnable($study) {
        $array = PersistentMap::get_paths();
        foreach(array_keys($array) as $key) {
            if (strpos($key, $study) > 0) {
                #$syncing = self::$SYNC_STATUS[$key];
                $syncing = $array[$key];
                if ($syncing) {
                    return false;
                }
            }
        }
        return true;
    }
    
}

?>

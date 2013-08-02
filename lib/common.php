<?php

/*
 * common
 * Created on: Jan 18, 2013 12:27:19 PM
 * 
 * Copyright 2013 EnginSoft S.p.A.
 * All rights reserved
 */

class JobStatus {
    public static $FINISHED = "FINISHED";
    public static $RUNNING = "RUNNING";
    public static $KILLED = "KILLED";
    public static $UNKNOWN = "UNKNONWN";
}


/**
 * Function copied from OC_Helper, with better checks if the directory is a symlink
 * 
 * @param type $dir
 * @return boolean
 */
function rmdirr($dir) {
    if(!is_link($dir) && is_dir($dir)) { // do not do rmdir if symlink
        $files=scandir($dir);
        foreach($files as $file) {
            if ($file != "." && $file != "..") {
                rmdirr("$dir/$file"); // recurse
            }
        }
        rmdir($dir);
    }elseif(is_link($dir)) {
        unlink($dir);
    }elseif(file_exists($dir)) {
        unlink($dir);
    }
    if(file_exists($dir)) {
        return false;
    }else{
        return true;
    }
}

function get_local_exec_dir($user) {
    include 'neurocloud/config.inc.php';
    return sprintf($NC_CONFIG["local_exec_dir"], $user);
}

function get_remote_exec_dir($user) {
    include 'neurocloud/config.inc.php';
    return sprintf($NC_CONFIG["remote_exec_dir"], $user);
}

function get_private_key_file($user) {
    include 'neurocloud/config.inc.php';
    return sprintf($NC_CONFIG["local_key_path"], $user);
}

function get_job_exec_dir($jobid) {
    return get_local_exec_dir(OC_User::getUser()) . DIRECTORY_SEPARATOR . $jobid;
}

function get_job_info_file($study, $jobid) {
    return "$study/results/$jobid/nc_jobinfo.json";
}

/**
 * returns a string that is a valid SSH command to connect to the cluster
 * 
 * @return string the SSH command to connect to the cluster for the connected user
 */
function create_ssh_command() {
    include 'neurocloud/config.inc.php';
    $user = OC_User::getUser();
    $host = $NC_CONFIG["remote_host"];
    $key = get_private_key_file($user);
    
    # The option BatchMode=yes is used to not asking for password
    # StrictHostKeyChecking=no is used to not ask to include the host in .ssh/known_hosts file
    return "ssh -n -i $key -o BatchMode=yes -o StrictHostKeyChecking=no $user@$host ";
}

/**
 * Returns the path (relative) to the job standard output
 * 
 * @param type $study
 * @param type $jobid
 * @return type
 */
function get_job_output_file($study, $jobid) {
    return "$study/results/$jobid/nc_stdout.log";
}

/**
 * Returns an associative array with the info associated with a running job
 * the array contains the keys:
 * status (either RUNNING, KILLED, FINISHED)
 * pid (numeric)
 * start_date
 * 
 * if the $study parameter does not represent a valid case study, or if $jobid does not exist
 * or if the 
 * 
 * @param type $study
 * @param type $jobid
 * @return array an array with the job info, containing the key "pid", "job_id"
 */
function get_job_info($study, $jobid) {
    $path = get_job_info_file($study, $jobid);
    if (OC_Filesystem::is_file($path)) {
        $json = OC_Filesystem::file_get_contents($path);
        return json_decode($json, true); // true for returning an associative array
    } else {
        return false;
    }
}

/**
 * 
 * @param type $user
 * @param type $jobid
 * @return array the associative array job info, or false if the file nc_jobinfo.json cannot be found
 */
function get_temp_job_info($user, $jobid) {
    include "neurocloud/config.inc.php";
    $basedir = sprintf($NC_CONFIG["local_exec_dir"],$user);
    
    if (is_dir($basedir)) {
        $dh = opendir($basedir);
        if ($dh) {
            while($entry = readdir($dh)) {
                $jobinfo_file = $basedir . "/$entry/results/nc_jobinfo.json";
                if ($entry === $jobid && is_file($jobinfo_file)) {
                    return json_decode(file_get_contents($jobinfo_file), true);
                }
            }
            closedir($dh);
        }
        return false;
    } else {
        return false;
    }
}

function save_job_info($study, $jobid, $jobinfo) {
    $path = get_job_info_file($study, $jobid);
    if (is_array($jobinfo)) {
        $json = json_encode($jobinfo);
        OC_Filesystem::file_put_contents($path, $json);
    }
}

function get_used_space_remote() {
    include "neurocloud/config.inc.php";
    $cmd = create_ssh_command() . " df -h " . $NC_CONFIG['remote_mount'];
    $lastrow = exec($cmd);
    # example of lastrow : 
    # korempba:/nilab0      2.0T   28G  1.9T   2% /nilab0
    
    if (is_string($lastrow)) {
        $spl = preg_split("/\s+/", $lastrow);
        if (count($spl) > 4) {
            $ret = array();
            $ret["total"] = $spl[1];
            $ret["used"] = $spl[2];
            $ret["free"] = $spl[3];
            $ret["percent"] = substr($spl[4],0,-1); # omit the % character
            
            return $ret;
        }
    }
    return false;
}

/**
 * 
 * @param string $user the user
 * @return array an array containing the list of the job ids associated with the user
 */
function get_user_jobs($user) {
    include "neurocloud/config.inc.php";
    $basedir = sprintf($NC_CONFIG["local_exec_dir"],$user);
    $jobs = array();
    if (is_dir($basedir)) {
        $dh = opendir($basedir);
        if ($dh) {

            while($entry = readdir($dh)) {
                if ($entry !== "." && $entry !== ".." && is_dir($basedir . "/" . $entry)) {
                    $jobs[] = $entry;
                }
            }
            closedir($dh);
        }
        array_multisort($jobs, SORT_DESC);
        
        return $jobs;
    } else {
        return $jobs;
    }
}

/**
 * calculates the disk usage of a user home OR the disk usage of the temp folder of a job
 * 
 * @param string $user the user
 * @param string $jobid a valid job id, or false
 * @return false if the calculation 
 */
function get_disk_usage($user, $jobid = false) {
    $return_val = -1;
    $output = array();
    $home = OC_User::getHome($user);
    
    // if $jobid is not false, calculate space used by the temp dir of the job
    if ($jobid && is_dir($home . "/jobs")) {
        $lastrow = exec("du -hs $home/jobs/$jobid", $output, $return_val);
    } elseif (is_dir($home . "/files")) {
        // if $jobid is false, calculate disk space occupied by the entire user home
        $toscan = "$home/files";
        
        $lastrow = exec("du -hsc $toscan", $output, $return_val);
    }
    
    if ($return_val !== 0) {
        return false;
    } else {
        $spl = preg_split('/\s+/', $lastrow);
        return $spl[0];
    }
}

function get_job_disk_usage($user, $study, $jobid) {
    $return_val = -1;
    $output = array();
    $home = OC_User::getHome($user);
    
    $lastrow = exec("du -hsc $home/jobs/$jobid $home/files/$study/results/$jobid", $output, $return_val);
    
    if ($return_val !== 0) {
        return false;
    } else {
        $spl = preg_split('/\s+/', $lastrow);
        return $spl[0];
    }
}

function insert_job_log($study, $jobid, $details) {
    include "neurocloud/config.inc.php";
    OC_Log::write("neurocloud", "Job $jobid (study $study): $details", OC_Log::INFO);
}

function _get_job_status_local($jobinfo) {
    $pid = $jobinfo["pid"];
    $output = "";
    $return_var = 0;
    exec("ps -p $pid", $output, $return_var);

    if ($return_var == 0) {
        return JobStatus::$RUNNING;
    } else {
        return JobStatus::$FINISHED;
    }
}

/**
 * Returns the status of a running neurocloud job in the remote SGE server. Issues a command in the remote server via SSH to check if there is still a running unix process
 * AND a command to check if there are still SGE jobs pending.
 * 
 * @param array $jobinfo the array returned from get_job_info
 * @return int if the job is running returns JobStatus::$RUNNING, if the job is finished, returns JobStatus::$FINISHED
 */
function _get_job_status_remote($jobinfo) {
    include "neurocloud/config.inc.php";
        
    $pid = $jobinfo["pid"];
    $qsub_jobname = $jobinfo["qsub_jobname"];
    $output = "";
    $return_var = 0;

    $user = OC_User::getUser();
    $host = $NC_CONFIG["remote_host"];
    $key = get_private_key_file($user);

    // 1. connect via SSH to host
    // 2. call ps to check the user's processes
    // 3. use grep to filter the job id
    $cmdline = create_ssh_command() . "ps -u $user | grep $pid ";
    
    exec($cmdline, $output, $return_var);
    if ($return_var == 0) {
        // the PID is still running, we can be pretty sure that there is at least a SGE job active
        return JobStatus::$RUNNING;
    } 
    
    // the process is not running, but there could be still pending SGE jobs in the cluster
    // so, we will check with qstat if there are still jobs
    $cmdline_qstat = create_ssh_command() . "qstat -j '*$qsub_jobname*' 1>/dev/null 2>&1";
    
    exec($cmdline_qstat, $output, $return_var);
    if ($return_var == 0) {
        return JobStatus::$RUNNING;
    } else {
        return JobStatus::$FINISHED;
    }
    
}

/**
 * Kills a job from the remote SGE server. Issues a command to kill the unix process AND a command to delete any SGE jobs pending or running.
 * 
 * @param array $jobinfo the array returned from get_job_info
 * @return int 0 if the job is correctly killed, 1 otherwise
 */
function _kill_job_remote($jobinfo) {
    include "neurocloud/config.inc.php";
    $user = OC_User::getUser();
    
    $pid = $jobinfo["pid"];
    $qsub_jobname = $jobinfo['qsub_jobname'];
    
    $killtree_remote = sprintf($NC_CONFIG["remote_kill_script"], $user);
    // 1. connect via SSH to host
    // 2. launch the script from the host
    $cmdline = create_ssh_command() . "$killtree_remote $pid KILL";
    //OC_Log::write("neurocloud", $cmdline, OC_Log::INFO);
    
    $cmdline_qdel = create_ssh_command() . "qdel '*$qsub_jobname'";
    
    $ret = -1;
    $ret_qdel = -1;
    
    $output = "";
    exec($cmdline, $output, $ret);
    exec($cmdline_qdel, $output, $ret_qdel);
    
    if ($ret == 0 || $ret_qdel == 0) {
        // if the first command is successful, then the job process is killed. Any queued jobs in SGE should have been killed too.
        return 0;
    } else {
        // neither command have been successful. This means that the job is already finished
        return 1;
    }
}

/**
 * 
 * @param array $jobinfo an associative array returned from the function get_job_info
 */
function get_job_status($jobinfo) {
    if (!$jobinfo) {
        return JobStatus::$UNKNOWN;
    }
    if (!array_key_exists("pid", $jobinfo)) {
        return JobStatus::$UNKNOWN;
    }
    if ($jobinfo["pid"] === "") {
        return JobStatus::$UNKNOWN;
    }
    
    // if there is a value set in the array for the key "status", then return it
    if (isset($jobinfo["status"])) {
        return $jobinfo["status"];
    }
    
    if (!isset($jobinfo["exec_type"])) {
        $jobinfo["exec_type"] = "local";
    }
    if ($jobinfo["exec_type"] === "local") {
        return _get_job_status_local($jobinfo);
    } elseif ($jobinfo["exec_type"] === "remote") {
        return _get_job_status_remote($jobinfo);
    } else {
        return JobStatus::$UNKNOWN;
    }
}

/**
 * 
 * @param type $qsub_jobname
 * @return array an array containing 2 values: the first is the number of queued jobs, the second is the number of running jobs
 */
function get_queue_info($qsub_jobname) {
    include "neurocloud/config.inc.php";
        
    $output = "";
    $return_var = 0;

    $user = OC_User::getUser();
    $host = $NC_CONFIG["remote_host"];
    $key = get_private_key_file($user);
    $return_string = array(0,0);
    // the process is not running, but there could be still pending SGE jobs in the cluster
    // so, we will check with qstat if there are still jobs
    $cmdline_qstat = create_ssh_command() . "qstat -u $user -xml 2>/dev/null ";
    
    exec($cmdline_qstat, $output, $return_var);
    if ($return_var == 0) {
        $xml_output = join("",$output);
        
        // parse the XML given in output
        $xml = new SimpleXMLElement($xml_output);
        
        /* the returned XML has this structure:
         <job_info><queue_info>[<job_list>*]<job_info>[<job_list>*]
         * 
         * each job_list contains the data for a single SGE job (name, queue, state, etc)
         */
        
        if ($xml->queue_info->job_list->count() == 0 && $xml->job_info->job_list->count() == 0) {
            #$return_string = "No running SGE jobs in queue for job_id " . $qsub_jobname;
            return $return_string;
        } else {
            #$return_string = "Running SGE jobs:\n";

            foreach ($xml->queue_info->job_list as $job) {
                // check if the SGE job name contains the generated job id for this neurocloud job
                if (strpos($job->JB_name, $qsub_jobname)) {
                    #$return_string = $return_string . $job->JB_name . "\n";
                    $return_string[1] = $return_string[1] + 1;
                }
            }

            #$return_string = $return_string . "\nQueued SGE jobs:\n";

            foreach ($xml->job_info->job_list as $job) {
                // check if the SGE job name contains the generated job id for this neurocloud job
                if (strpos($job->JB_name, $qsub_jobname)) {
                    #$return_string = $return_string . $job->JB_name . "\n";
                    $return_string[0] = $return_string[0] + 1;
                }
            }
        }
        
        return $return_string;
    } else {
        //return "No SGE jobs in queue for job_id " . $qsub_jobname;
        return array(0, 0);
    }
}

function get_absolute_path($path) {
    return OC_Config::getValue("datadirectory") . OC_Filesystem::getRoot() . "/" . $path;
}

function is_valid_casestudy($dir) {
    $musthavedirs = array("/data", "/pipeline", "/results");
    foreach ($musthavedirs as $subdir) {
        if (!OC_Filesystem::is_dir($dir . $subdir)) {
            return "Missing subdir $subdir";
        }
    }
    return null;
}

function get_scripts($pipedir) {
    $files = array();

    $content = OC_Files::getDirectoryContent($pipedir);
    foreach($content as $c) {
        
        $files[] = $c["name"];
    }
    return $files;
}


/**
 * Retrieves a list of jobs launched for a specln ific case study
 * @param type $study
 */
function get_jobs_for_study($study) {
    $jobids = array();
    
    $directory = $study . "/results";
    $content = OC_Files::getDirectoryContent($directory);
    foreach($content as $c) {
        if (\OC\Files\Filesystem::is_dir($directory. "/" . $c["name"])) {
            $jobids[] = $c["name"];
        }
    }
    array_multisort($jobids, SORT_DESC);
    
    return $jobids;
}

/**
 * @return the path for the remote kill script, or false if the file cannot be created
 */
function get_kill_script($user) {
    include "neurocloud/config.inc.php";
    $killtree_path = sprintf($NC_CONFIG["local_kill_script"],$user);

    copy(OC::$SERVERROOT . "/apps/neurocloud/lib/killtree.sh", $killtree_path);
    
    if (is_file($killtree_path)) {
        chmod($killtree_path, 0755);
        
        if (is_executable($killtree_path)) {
            return $killtree_path;
        }
    }
    return false;
    
}

function copy_dir($src_dir, $dest_dir) {
    
    if (!is_dir($dest_dir)) {
        if (!mkdir($dest_dir, 0777, true)) {
            return false;
        }
    }
    
    if(($dh = opendir($src_dir))) {
        while($entry = readdir($dh)) {
            if ("." == $entry || ".." == $entry) {
                continue;
            }
            $srcfile = $src_dir . "/" . $entry;
            $destfile = $dest_dir . "/" . $entry;
            if (is_dir($srcfile)) {
                $res = copy_dir($srcfile, $destfile);
                if (!$res) {
                    return false;
                }
            } else if (is_file($srcfile)) {
                copy($srcfile, $destfile);
            }
        }
        closedir($dh);
        return true;
    }    
}


?>

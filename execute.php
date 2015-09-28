<?php

/*
 * execute
 * Created on: Jan 18, 2013 12:11:35 PM
 * 
 * Copyright 2013 EnginSoft S.p.A.
 * All rights reserved
 */

require_once 'lib/common.php';


//# TODO: SUBSTITUTE WITH REAL CONFIG VALUE !!!
//#OC_Config::setValue("nc_exec_env_root" ,"/tmp/neurocloud");


function create_job_id($study_name, $script_name) {
    // remove spaces and strange characters from the script name
    $script_name = str_replace(array(" ",",",";",":"), "_", $script_name);
    
    return date("ymd") . "_" . date("His") . "_" . $script_name;
}

function get_job_exec_dir_remote($jobid) {
    return get_remote_exec_dir(OC_User::getUser()) . "/" . $jobid; 
}

function is_python_script($cmd) {
    return strrpos($cmd, ".py", -3);
}

/**
 * 
 * @param type $study_name the name of the study directory
 * @return the job id
 */
function create_execution_env($study_name, $script_name) {
    include 'config.inc.php';
    
    $jobid = create_job_id($study_name, $script_name);
    $job_dir = get_job_exec_dir($jobid);
    
    while (is_dir($job_dir)) {
        // the sandbox directory is already existing, sleep 1 second and generate another ID
        sleep(1);
        $jobid = create_job_id($study_name, $script_name);
        $job_dir = get_job_exec_dir($jobid);
    }
    
    mkdir($job_dir, 0777, true);
    
    // [job_root_dir]/[job_id]/data --> ../../data/[fs_root]/[study_name]/data
    $datadir = $NC_CONFIG["symlink_prefix"] . "/" . $study_name . "/data";
    $pipelinedir = get_absolute_path($study_name . "/pipeline");
    $resultsdir = $NC_CONFIG["symlink_prefix"] . "/" . $study_name . "/results/" . $jobid;
    
    OC_Filesystem::mkdir("$study_name/results/$jobid");

    //# le dir /data e /results sono link simbolici alle vere directory del caso di studio
    mkdir($job_dir . "/pipeline");
    symlink($datadir, $job_dir . "/data");
    symlink($resultsdir, $job_dir . "/results");

    //# creo il file in cui verrà rediretto lo standard output
    $date = date("Y-m-d H:i:s");
    OC_Filesystem::file_put_contents(get_job_output_file($study_name, $jobid), "Standard output for job $jobid, run at $date\n");
    
    $jobinfo = array("jobid" => $jobid, "study" => $study_name);
    
    save_job_info($study_name, $jobid, $jobinfo);
    
    //# copia gli script del caso di studio nella pipeline
    copy_dir($pipelinedir, $job_dir . "/pipeline");
    
    return $jobid;
}

/**
 * Executes locally a file
 * 
 * @param type $workdir the working directory
 * @param type $script_path the script file, with path relative to the study_dir
 */
function execute_script_local($workdir, $cmd, $study, $jobid) {
    include 'config.inc.php';
    
    if (chdir($workdir)) {
        $outfile = get_absolute_path(get_job_output_file($study, $jobid));
        //# things that are needed to proc_open, see http://it2.php.net/manual/en/function.proc-open.php
        $pipes = 0;
        $descriptors = array(
            0 => array('pipe', 'r'),
            1 => array('file', $outfile, 'a'),
            2 => array('file', $outfile, 'a')
        );
        $env = array();
        
        $env["HOME"] = get_local_exec_dir(OC_User::getUser());
        $env["PATH"] = $_SERVER["PATH"];
        //# use a UNIX command to get the current user name
        $env["USER"] = exec("whoami"); 

        chmod($cmd, 0755);
        
        $cmd = str_replace(" ", "\\ ", $cmd);
        $ph = proc_open($cmd . " &", $descriptors, $pipes, $workdir, $env);
        if ($ph) {
            /**
             * @var $status array
             */
            $status = proc_get_status($ph);
            $pid = $status["pid"];
            
            proc_close($ph);
            //
            // UGLY HACK , I increase the PID returned by proc_get_status by 1 
            // why ? because proc_open() spawns a process /bin/sh, that in turn launches our script.
            // the full command is /bin/sh -c $command $args
            // Our process is the direct child of such shell process.
            // The $pid returned above is the PID of the shell process, not the script launched by that shell.
            // 
            // Since in Unix the child process is always parent_pid + 1, it's mostly safe to increment that value by 1
            // to obtain the real PID
            // 
            if ($pid) {
                $pid = (int) $pid + 1;

                $jobinfo = array("jobid" => $jobid, "pid" => $pid, "start_date" => date("Y-m-d H:i:s"), "script" => $cmd, "exec_dir" => $workdir, "exec_type" => "local");
                save_job_info($study, $jobid, $jobinfo);

                // TODO: here we should check if the job is effectively running with that PID
            }

            return $pid;
        }
        return false;
    } else {
        return false;
    }
}

/**
 * Executes a script in a remote server via SSH
 * 
 * @param type $study the case study
 * @param type $jobid the job id
 * @param type $workdir the working directory
 * @param type $rerun if the execution is a re-run of an existing job
 * @param type $script_path the script file, with path relative to the study_dir
 */
function execute_script_remote($study, $jobid, $workdir, $rerun, $cmd, $args = "") {
    include 'config.inc.php';
    
    $jobid_qsub = 'j' . str_replace(".","_", $jobid);
    $orig_cmd = $cmd;
    
    //# check if the command is a python script
    if (is_python_script($cmd) !== false) {
        // prepend the python script with our command
        
        $exec_dir_local = get_job_exec_dir($jobid);
        if (!$rerun) {
            // if the job is NOT a re-run, prepend the python script with the python-prepend.py file
            $script_content = file_get_contents($exec_dir_local . "/" . $cmd);

            $script_prepend = file_get_contents("lib/pipeline_prepend.py", true);

            $script_content = $script_prepend . $script_content;

            //error_log("Prepending python script at " . $exec_dir_local . "/" . $cmd);
            file_put_contents($exec_dir_local . "/" . $cmd, $script_content);
            // copying qsub_template.sh to workdir
            $template = file_get_contents("lib/qsub_template.sh", true);
            file_put_contents($exec_dir_local . "/qsub_template.sh", $template);
        }
        
        $cmd = $NC_CONFIG["python-bin"] . " " . $cmd;
    } elseif (strpos($cmd, ".sh") !== false) {
        //#$cmd = "qsub -cwd -l mf=1.4G -N $jobid_qsub -sync y -o results/nc_stdout.log -e results/nc_stdout.log " . $cmd;
        $cmd = "/bin/bash " . $cmd;
        $args = $jobid_qsub;
    }

    // http://unix.stackexchange.com/a/29495
    // The jobs submitted by nipype using qsub are set with the name
    // node_name.workflow_name.user_name
    // the user_name is passed to nipype through the environment variable $LOGNAME
    // we cannot customize easily this behavior, so we customize the variable $LOGNAME to
    // job_id.user_name
    // in this way, the final job name will be
    // node_name.workflow_name.user_name.job_id
    // and it will be easier for the Neurocloud webapp to know if there are still running/queued SGE jobs for this job id
    $cmdline = create_ssh_command() . " 'cd $workdir && chmod +x $orig_cmd && { LOGNAME=$jobid_qsub.\$LOGNAME nohup $cmd $args >>results/nc_stdout.log 2>&1 & } && echo $!' ";
    
    //error_log($cmdline);
    $pid = exec($cmdline);
    
    $jobinfo = array("study" => $study, "jobid" => $jobid, "qsub_jobname" => $jobid_qsub, "pid" => $pid, "start_date" => date("Y-m-d H:i:s"), "script" => $orig_cmd, "cmdline" => $cmdline, "exec_dir" => $workdir, "exec_type" => "remote");
    save_job_info($study, $jobid, $jobinfo);
    
    return $pid;
}


/** REAL CODE STARTS HERE **/

if (isset($_GET["script"]) && isset($_GET["study"])) {
    include 'config.inc.php';
    
    $study = $_GET['study'];
    $script = $_GET['script'];
    $rerun = false;
    if (isset($_GET["jobid"])) {
        $rerun = true;
        $job_id = $_GET["jobid"];
    }
    
    $space = get_used_space_remote();
    if (is_array($space) && (int)$space['percent'] > (int)$NC_CONFIG['minimum_exec_space']) {
        //# Redirect to neurocloud app index , showing an error message
        $link = OC_Helper::linkTo("neurocloud", "index.php", array("error" => "Cannot execute script, low free space in remote server.<br>Contact administration or delete old jobs"));
        header("Location: " . $link);
        exit();
    }
    
    
    if (OC_Neurocloud::is_study_runnable($study)) {
        if (isset($_GET["mode"]) && $_GET["mode"] == "remote") { // execute the script in the remote server via a SSH command
            // obtain the key file for the current user
            $key = get_private_key_file(OC_User::getUser());
            // if the file does not exist, the user cannot launch a job
            if (!is_file($key)) {
                OC_Log::write("neurocloud", "Private key file $key not found", OC_Log::ERROR);
                $pid = false;
            } else {
                if (!$rerun) {
                    $job_id = create_execution_env($study, $script);
                }
                $exec_dir = get_job_exec_dir_remote($job_id);

                $pid = execute_script_remote($study, $job_id, $exec_dir, $rerun, "pipeline" . DIRECTORY_SEPARATOR . $script);
            }
        } else { /* execute script locally in the owncloud machine */
            if (!$rerun) {
                $job_id = create_execution_env($study, $script);
            }
            $exec_dir = get_job_exec_dir($job_id);
            $pid = execute_script_local($exec_dir, "pipeline" . DIRECTORY_SEPARATOR . $script, $study, $job_id);
        }
        //# Redirect to neurocloud app index , showing an info (or error) message
        if ($pid) {
            $link = OC_Helper::linkTo("neurocloud", "index.php", array("jobid" => $job_id, "pid" => $pid, "action" => "launch"));
        } else {
            $link = OC_Helper::linkTo("neurocloud", "index.php", array("error" => "Cannot launch job, see log for details"));
        }
        header("Location: " . $link);
    } else {
        //# Redirect to neurocloud app index , showing an error message
        $link = OC_Helper::linkTo("neurocloud", "index.php", array("error" => "Sync is in progress, cannot execute script"));
        header("Location: " . $link);
    }
} else {
    $link = OC_Helper::linkTo("neurocloud", "index.php", array("error" => "Missing script and/or study parameters"));
    header("Location: " . $link);
}

?>

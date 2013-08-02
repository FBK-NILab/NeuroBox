<?php

/*
 * config
 * Created on: Feb 6, 2013 4:18:30 PM
 * 
 * Copyright 2013 EnginSoft S.p.A.
 * All rights reserved
 */

$NC_CONFIG = array(
    // the command that executes the mounting of the homes. Called by mount_homes.php
    "mount_cmd" => "sudo /home/neurocloud/mount_nc_homes.sh",
    
    // the host to connect via SSH to execute the script
    "remote_host" => "192.168.104.252",
    // the local job execution directory root 
    // needs a sprintf parameter, the username
    "local_exec_dir" => "/home/neurocloud/data/%s/jobs",
    // the path for the private key for a user. Needs a parameter, which is the current username
    "local_key_path" => "/home/neurocloud/keys/%s",
    // the remote job execution directory root
    "remote_exec_dir" => "/nilab0/%s/nc_data/jobs",
    // the remote mount path containing the user data (used to check free space with df command)
    "remote_mount" => '/nilab0',
    # the minimum free space (expressed in percent) required in the remote mount partition to be able to run jobs
    "minimum_exec_space" => "90",
    // the script used to kill a job, remote path.
    // Needs a parameter, the user id
    "remote_kill_script" => "/nilab0/%s/nc_data/jobs/killtree.sh",
    // the script used to kill a job, local path.
    // Needs a parameter, the user id
    "local_kill_script" => "/home/neurocloud/data/%s/jobs/killtree.sh",
    // this is the prefix to prepend to the symbolic links from /data and /results of the launched jobs
    // the prefix is relative to [remote_exec_dir]/[job_id]
    // this prefix will be appended with the path of the case study
    "symlink_prefix" => "../../files",
    // the REMOTE absolute path for the python executable to launch remote python scripts
    "python-bin" => "/nilab0/local/epd/bin/python",
    // the execution type. "remote" launches scripts in remote via SSH and qsub, "local" launches them in the local host.
    "default_exec_type" => "remote"
);

?>

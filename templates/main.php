<?php
require_once 'neurocloud/lib/common.php';
require_once 'neurocloud/config.inc.php';
?>
<script type="text/javascript" src="<?php echo OC_Helper::linkTo("neurocloud", "js/main.js")?>"></script>

<?php
if (array_key_exists("infomessage", $_)) {
    ?><div class="info message"><?php echo $_["infomessage"] ?></div><?php
}
if (array_key_exists("errormessage", $_)) {
    ?><div class="error message"><?php echo $_["errormessage"] ?></div><?php
}

$casestudies = array();
$dh = OC_Filesystem::opendir(".");
if ($dh) {
    while (($file = readdir($dh)) !== false) {
        if (OC_Filesystem::is_dir($file) && $file !== "." && $file !== "..") {
            $error = is_valid_casestudy($file);
            if ($error) {
                ?><!--<div class="error message"><?php echo "Invalid case study $file: $error" ?></div>--><?php
            } else {
                $casestudies[] = $file;
            }
        }
    }
    closedir($dh);
}
?>
<ul id="testcases_block">
    <?php
    $study_idx = 0;
    foreach ($casestudies as $file) {
        $filelink = OC_Helper::linkTo("files",'',array("dir" => "$file"));
        $scripts = get_scripts($file . "/pipeline");
        ?><li class="casestudy">
            <div class="caseheader">
                <span class="expander"><img src="/apps/neurocloud/img/collapse.png" alt="[ - ]"></span>&nbsp;<span class="name"><?php echo $file ?></span>
                <div style="float: right">Sync status: <span class="study_img" id="<?php echo "study_img_" . $study_idx ?>" studyname="<?php echo $file ?>"><img alt="sync status" src="/apps/neurocloud/img/loading.gif"></span></div>
            </div>
            <div class="casecontents">
                <table class="scriptstable" style="width: 100%">
                    <caption>Scripts and pipelines</caption>
                    <thead>
                        <tr>
                            <th>Script name</th>
                            <th>Commands</th>
                        </tr>
                    </thead>
                    <tbody>
        <?php 
        $script_index = 0;
        foreach ($scripts as $s) {
            
            $script_index++;
            
            $scriptid = "script_". $study_idx . "_" . $script_index;
            $fullpath = "$file/pipeline/$s";
            $params = array("study" => $file, "script" => $s);
            $mimetype = OC_Filesystem::getMimeType($fullpath);
            
            if (strpos($mimetype,"text/x-shellscript") === false && strpos($mimetype,"text/x-script.phyton") === false) {
                continue;
            }
            
            $params["mode"] = $NC_CONFIG["default_exec_type"];
            
            $exec_link = OC_Helper::linkTo("neurocloud", "execute.php", $params);
            $jsline = "javascript:show_output('$scriptid', '$fullpath')";
            ?>
            <tr>
                
            <?php
            //# TODO: gli script con gli spazi nel nome non sono permessi, perché lanciandoli via SSH vengono parsati come due comandi
            if (strpos($s, " ") !== false) {
            ?>
                        
            <td colspan="2">
            <div class="script" style="background-color: lightcoral">
                <span><strong><?php echo $s ?></strong>: invalid script name; no spaces allowed</span>
            </div>  
            </td>
            <?php
            
            } else {
            ?>
            <td>
            <div class="script"><?php echo $s ?></div>
            </td>
            <td>
                <?php 
                if (is_file(get_private_key_file(OC_User::getUser())) || $NC_CONFIG['default_exec_type'] === 'local') { //# checking if the user can run scripts
                ?>
                <span ><a href="<?php echo $exec_link ?>" title="Click to execute this script">Run</a></span>
                &nbsp;
                <?php
                }
                ?>
                <span ><a href="<?php echo OC_Helper::linkTo('', "index.php/apps/files/download/$fullpath") ?>">Edit</a></span>
                &nbsp;
                <span ><a href="<?php echo $jsline ?>">Show</a></span>
            </td>
            </tr>
            <tr class="output_tr">
                <td colspan="2">
                <pre id="<?php echo $scriptid ?>" class="output"></pre>
                </td>
            </tr>
            <?php
            }
            ?>
            </tr>
        <?php
        }
        ?>
            </tbody>
        </table>
        <div class="jobs" id="jobs_<?php echo $study_idx ?>">
            <span>Loading jobs...<img alt="loading" src="/apps/neurocloud/img/loading.gif"></span>
            <script type="text/javascript">load_jobs('<?php echo $file?>', '<?php echo $study_idx ?>', 'jobs_<?php echo $study_idx ?>');</script>
        </div>
        </div>
        </li>
        <?php

        $study_idx++;
    }
    ?>
</ul>



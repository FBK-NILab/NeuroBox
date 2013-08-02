/* 
 * main
 * Created on: Feb 6, 2013 11:49:59 AM
 * 
 * Copyright 2013 EnginSoft S.p.A.
 * All rights reserved
 */
function get_job_output_file(study, jobid) {
    return study + "/results/" + jobid + "/nc_stdout.log";
}

function get_close_span(elem_id) {
    return '<span class="closebutton" onclick="close_output(\''+elem_id+'\')"><img src="/apps/neurocloud/img/close.png"></span>';
}

function load_job_info_row(row_id, study, jobid, outputid) {
    $.get(OC.filePath('neurocloud','ajax','job_info_row.php') , {"study" : study, "jobid" : jobid, "rowid" : row_id, "outputid" : outputid, "print_tr" : 0}, 
        function(data,status) {
            if (status == "success") {
                $("#" + row_id).html(data);
            }
        }
    );
}

function refresh_job_status(row_id, study, jobid, outputid) {
    $("#" + row_id + " .jobstatusimg").attr("src", '/apps/neurocloud/img/loading.gif');
    
    $.post( OC.filePath('neurocloud','ajax','refresh_job_status.php') , {"study" : study, "jobid" : jobid }, 
        function(data, status){
            load_job_info_row(row_id, study, jobid, outputid);
        });
}

/**
 * Shows the contents of the standard output of a job
 * @param {int} elem_id the element id
 * @param {string} filename the path of the file to show
 * @param {boolean} format_json true iif the file is in JSON format
 */
function show_output(elem_id, filename, format_json) {
    var element = $('#' + elem_id);
    if (element.size() > 0) {

        element.removeClass('shown');
        element.addClass('shown');
        element.stop().hide();
        element.text('Loading...');
        element.stop().show();

        $.post(OC.filePath('neurocloud','ajax','getoutput.php') , {'filename' : filename, 'json' : format_json}, 
            function(data) {
                //element.hide();
                element.html(get_close_span(elem_id) + data);
                element.slideDown(250);
                element.prop({"scrollTop" : element.prop("scrollHeight")});
            }
            );

    }
}

function close_output(elem_id) {
    var element = $('#' + elem_id);
    if (element.size() > 0 && element.hasClass('shown')) {            
        element.stop().slideUp(250,function() {
            element.text('');
            element.removeClass('shown');
        });
    }
}

function show_queue_info(elem_id, jobname) {
    var element = $('#' + elem_id);
    if (element.size() > 0) {
        element.removeClass('shown');
        element.addClass('shown');
        element.stop().hide();
        element.text('Loading...');
        element.stop().show();

        $.get(OC.filePath('neurocloud','ajax','get_queue_info.php') , {'jobname' : jobname}, 
            function(data) {
                element.hide();
                element.html(get_close_span(elem_id) + data);
                element.slideDown(250);
                element.prop({"scrollTop" : element.prop("scrollHeight")});
            }
        );
    }
}

function load_jobs(study, study_idx, jobs_elem_id) {
    
    $.get(
        OC.filePath('neurocloud','ajax','load_jobs.php'), 
        {"study" : study, "study_idx" : study_idx},
        function(data) {
            $('#' + jobs_elem_id).hide();
            $('#' + jobs_elem_id).html(data);
            $('#' + jobs_elem_id).slideDown(500);
        }
    );
}

/**
 * This function will ask the user to confirm the deletion of the job results
 * if the user replies "yes", the job result directory (and the execution environment) will be deleted
 */
function delete_results(row_id, study_id, job_id) {
    function onajaxresult(result, status) {
        
    }
    
    function onclick(result) {
        if (!result) {
            return;
        }
        var data = {study: study_id, jobid: job_id};
        $.post(OC.filePath('neurocloud', 'ajax', 'delete_results.php'), data, onajaxresult);
        $('#' + row_id).stop().animate({
                width: 0,
                opacity: 0
            }, 500, "swing", function() { $('#' + row_id).hide(); });
        // hide also the output row
        $('#' + row_id + "_output").stop().animate({
                width: 0,
                opacity: 0
            }, 500, "swing", function() { $('#' + row_id + "_output").hide(); });
    }
    
    OCdialogs.confirm("Are you sure you want to delete the results of job " + job_id + " ?<br><strong>WARNING: this will also delete the corresponding results folder</strong>", "Confirm delete", onclick, true);
}

function kill_job(elem_id, study, job_id) {
    function onajaxresult(result, status) {
        if (status == "success") {
            $('#' + elem_id + " .jobstatus").text(result);
        }
    }
    
    function onclick(result) {
        if (!result) {
            return;
        }
        var data = {study: study, jobid: job_id};
        $.get(OC.filePath('neurocloud', 'ajax', 'kill_job.php'), data, onajaxresult);
    }
    
    OCdialogs.confirm("Are you sure you want to kill the job " + job_id + " ?", "Confirm kill", onclick, true);
}

/**
 * checks if a study is runnable and change the content of an img tag to either a check image or a "not allowed" image
 */
function checkrunnable(elem_id, study, callback) {
    $.post(OC.filePath('neurocloud','ajax','checkrunnable.php') , 'study=' + study, callback);
}


$(document).ready(function() {
    // callback function after sync scan is finished
    var click_cb = function(elem_id, data){
        if (data == "1") {
            // ok, the study can be run
            $("#" + elem_id).html("SYNC OK");
        } else if (data == "0") {
            // nope, data is still syncing
            $("#" + elem_id).html("SYNC PROGRESS");
        } else {
            $("#" + elem_id).html("ERROR");
        }
    };
        
    var timeout_func = function() {
        $(".study_img").each(function(idx) {
            var elem_id = this.id;
            var study = $(this).attr("studyname");
                
            checkrunnable(elem_id, study, function(data) {
                click_cb(elem_id, data);
                    
            });
        }); 
        // re-call self after a timeout milliseconds
        window.setTimeout(timeout_func, 1000);
    }
        
        
    /**
         * check for synchronization progress
         */
    window.setTimeout(timeout_func, 0);
        
        
    $(".study_img").click(function(elem) {
        var elem_id = this.id;
        var study = $(this).attr("studyname");
                
        checkrunnable(elem_id, study, function(data) {
            click_cb(elem_id, data);
        });
    });
        
    /**
         * Make all messages disappear after 3 seconds
         */
    window.setTimeout(function() {
        $("div.message").fadeOut({
            "duration":1000
        });
    }, 3000);
        

    // expand/collapse the case study contents on click
    $(".casestudy .caseheader").click(function() {
        var contents = $(this).parents(".casestudy").children(".casecontents");
        
        if (contents.is(":visible")) {
            contents.hide(500);
            $(this).find(".expander img").attr({"src":'/apps/neurocloud/img/expand.png'});
        } else {
            contents.show(500);
            $(this).find(".expander img").attr({"src":'/apps/neurocloud/img/collapse.png'});
        }
    });
});
    

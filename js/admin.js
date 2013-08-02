

function calculate_disk_usage(elem_id, table) {
    var elem = $("#" + elem_id);
    
    $.get(OC.filePath('neurocloud','ajax','get_disk_usage.php') , {"table": table}, 
    function(data,status) {
        if (status == "success") {
            elem.html(data);
        } 
    }
    );

}
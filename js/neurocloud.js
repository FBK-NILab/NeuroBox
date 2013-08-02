/* 
 * neurocloud
 * Created on: Jan 22, 2013 5:30:48 PM
 * 
 * Copyright 2013 EnginSoft S.p.A.
 * All rights reserved
 */

function updatecache() {
    $("#updateCacheIcon").attr("src", oc_webroot + "/apps/neurocloud/img/loading.gif");
    
    $.post( OC.filePath('neurocloud','ajax','updateCache.php') , '', 
        function(data, status){
            if (status == "success") {
                $("#updateCacheIcon").attr("src", oc_webroot + "/apps/neurocloud/img/refresh.png");
            } else {
                
            }
        });
    
}

$(document).ready(function(){

/*
    $.on("click", "td.remove", "", "", function(event) {
        OC.dialogs.alert("ATTENZIONE ! Dopo la cancellazione dell'utente da owncloud,<br>mandare un'email ai sistemisti KORE per richiedere eliminazione utente");
    });
*/

});


/*
$(document).ready(function() {
    $('#apps li:first-child a:first-child').append('<img src="'+ oc_webroot +'/apps/neurocloud/img/refresh.png" style="position:absolute; right:15px;" id="updateCacheIcon" title="Refresh caches"/>');
    $('#updateCacheIcon').click( function(event) {
        event.preventDefault();
        var url   = window.location.search.split('&');
        var param = [];
        var dir   = null;

        return updatecache();
    });

});
*/
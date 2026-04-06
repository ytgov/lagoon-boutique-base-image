(function ($, Drupal) {
    var base_url = '{{base_url}}' ? '{{base_url}}' : '' ;
    var varsToPatch;
	var sid, webformId, flagToModify, uid;

    Drupal.behaviors.updateInfo = {
        attach: function (context, settings) {
            //Asign click event to buttons for patching webforms
            $('#btn-certify, #btn-status', context).once('certifyConfirm').each(function () {
    	    	$(".accept-action").on("click", function(e){
                    varsToPatch = $(this).children("div").attr("data");
                    [sid, webformId, flagToModify, uid] = varsToPatch.split('&');
                    $("#confirmModal").modal('show');
    		    });

                $("#modal-btn-yes").on("click", function(){  
              		switch(flagToModify){
                		case "certify":
                            patchNode( { "certify": 1, "frontend_status": 'Active' ,  "status": 'Active' , "user_uid": uid });
                        break;
                        case "frontend_status":
                            patchNode({ "frontend_status": 'Non-compliant' ,  "status": 'Non-compliant', "user_uid": uid });
                        break;
                        default:
                            patchNode({ "deleted": true });
                        break;
              		}
        		});
            });
        } 
    };
    function patchNode(data) {
        $.ajax({
            url:    '/patch/' + webformId    + '/' + sid + '/' + JSON.stringify(data),
            method: 'PATCH',
            headers: {'Content-Type': 'application/json',},
            success: function (data) {
                location.reload(true);
            }
        });
    }
})(jQuery, Drupal);
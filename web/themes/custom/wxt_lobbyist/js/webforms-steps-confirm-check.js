(function ($, Drupal) {
    var base_url = '{{base_url}}' ? '{{base_url}}' : '' ;
    var varsToPatch;
	var sid, webformId, flagToModify, uid;

    Drupal.behaviors.webformReviw = {
        attach: function (context, settings) {
            
            //Display step number in the top
            $('.webform-progress', context).once('webformSteps').each(function () {
                $(".webform-progress").css("display", "none");
                if( $('.webform-submission-data--view-mode-preview').length > 0){
                     var steps = $(".webform-progress").html();
                     $("#wb-cont").append(steps);
                }
            });

            //Display confirm checkbox in the preview page and check if is checked or not for enabling the submit button
            $('.webform-submission-data--view-mode-preview', context).once('webformReviw').each(function () {
                $(".previewing-webform #wb-cont").css("display", "none");
                $('#edit-actions-submit').attr('disabled','true');
                $("body.page-node-type-webform").addClass("previewing-webform");
    
                $('#confirm-checkbox:checkbox').click(function() {
                    var $checkbox = $(this), checked = $checkbox.is(':checked');
                    $checkbox.closest('#confirm-checkbox').find(':checkbox').prop('disabled', checked);
                    if(checked){
                        $('#edit-actions-submit').removeAttr('disabled');
                    }else{
                        $('#edit-actions-submit').attr('disabled','true');
                    }    
                    $checkbox.prop('disabled', false);
                }); 
            });

            //Move edit button to the top and assign event for go to prev page
            $('#preview-header-container', context).once('webformEditDetails').each(function () {
                var edit_button = 	 $('#edit-actions-preview-prev').html();	
                $('<div id="preview-header-edit"><button class="webform-button--previous button js-form-submit form-submit btn-default btn icon-before" id="custom-edit-actions-preview" name="op">'+edit_button+'</button><div>').insertBefore(document.getElementsByClassName('custom-webform-preview'));
                
                $('#custom-edit-actions-preview').click(function(){
                    $('#edit-actions-preview-prev').click();
                });
                $('#edit-actions-preview-prev').css("display", "none");
            }); 

            $('#edit-submit-search-activity', context).once('webformSubmitSearchbtn').each(function () {     		
                $("#edit-submit-search-activity").removeClass('btn');	
            });

        } 
    };
    
    Drupal.behaviors.userForm = {
        attach: function (context, settings) {
            //Force to change the characters to uppercase in the postal code input
            $('.postal-code.form-text', context).once('uppercasePostalCode').each(function () {
        		$('input.postal-code').keypress(forceUppercase);
            });
        }
    };

    Drupal.behaviors.webformFeedback = {
        attach: function (context, settings) {
            //This function changes the icon color when it was click
            $('#edit-was-this-page-helpful-yes,#edit-was-this-page-helpful-yes', context).once('webformHands').each(function () {     
                $("#edit-was-this-page-helpful-yes").click(function(){
                    $("label[for='edit-was-this-page-helpful-yes']").css('color','#DD971A');
                    $("label[for='edit-was-this-page-helpful-no']").css('color','#b2b3b2');
                });
                $("#edit-was-this-page-helpful-no").click(function(){
                    $("label[for='edit-was-this-page-helpful-yes']").css('color','#b2b3b2');
                    $("label[for='edit-was-this-page-helpful-no']").css('color','#DD971A');
                });
            });
        } 
    };

    Drupal.behaviors.webformLobbyist = {
        attach: function (context, settings) {
            
            //Show/hide input if the "Other" option is selected/unselected this only is happens when input is created
            $('.form-item-other', context).once('selectOther').each(function () {
                $('.form-item-other').css("display", "none");
                var values = $('#edit-which-topic-do-you-want-to-lobby-government-about-').find(':selected');
                $.each( values, function( key, value ) {
                    if(value.text == 'Other' || value.text == 'Autre'){
                        $('.form-item-other').css("display", "block");
                    }
                });
            });

            //Show/hide input if the "Other" option is selected/unselected
            $('#edit-which-topic-do-you-want-to-lobby-government-about-', context).once('selectOther').each(function () {
                $('#edit-which-topic-do-you-want-to-lobby-government-about-').on("select2:select", function (e) {    
                     var data = e.params.data;
                     if(data.text == 'Other' || data.text == 'Autre'){
                        $('#edit-other').val('');
                        $('.form-item-other').css("display", "block");
                     }
                });
                $('#edit-which-topic-do-you-want-to-lobby-government-about-').on("select2:unselect", function (e) {    
                    var data = e.params.data;
                    if(data.text == 'Other' || data.text == 'Autre'){
                        $('.form-item-other').css("display", "none");
                    }
                });
            });
            
            //Replace the "br" tags with correct line break
            $('#edit-add-detail', context).once('addDetails').each(function () {             
                var str =$('#edit-add-detail').val().split("<br />").join("\n");
                $('#edit-add-detail').val(str);
            });
        }
    };
    
    //Change the character lowercase to uppercase
    function forceUppercase(e){
        var charInput = e.keyCode;
        if((charInput >= 97) && (charInput <= 122)) { // lowercase
            if(!e.ctrlKey && !e.metaKey && !e.altKey) { // no modifier key
                var newChar = charInput - 32;
                var start = e.target.selectionStart;
                var end = e.target.selectionEnd;
                e.target.value = e.target.value.substring(0, start) + String.fromCharCode(newChar) + e.target.value.substring(end);
                e.target.setSelectionRange(start+1, start+1);
                e.preventDefault();
            }
        }
    }

})(jQuery, Drupal);

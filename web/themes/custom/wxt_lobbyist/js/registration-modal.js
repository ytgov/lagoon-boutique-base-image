var language;
var waitForEl = function(selector, callback) {
	if (jQuery(selector).length) {
	  callback();
	} else {
	  setTimeout(function() {
		waitForEl(selector, callback);
	  }, 100);
	}
};
var waitForInhouseEl = function(tabId, callback) {
  if (jQuery("a.use-ajax").attr("href", language + "/quicktabs/nojs/account_home/" + tabId)) {
      callback();
  } else {
      setTimeout(function() {
         waitForEl(selector, callback);
      }, 100);
  }
};

var waitForConsultantEl = function(tabId, callback) {
  if (jQuery("a.use-ajax").attr("href", language + "/quicktabs/nojs/consultant_account_home/" + tabId)) {
      callback();
  } else {
      setTimeout(function() {
         waitForEl(selector, callback);
      }, 100);
  }
};

var tabAccount = '0';
var hrefLanguage = '';
(function ($, Drupal) { 
    Drupal.behaviors.modalDesktop = {
        attach: function (context, settings) {
            language =  "/" +  settings.path.pathPrefix;
            var selector = 'div:contains("You do not appear to be a lobbyist"):last';
            var trigger_selector = '.ui-front.ui-dialog-content.ui-widget-content';
            var nextSelector = '.webform-button--next';
            var backSelector = '.webform-button--previous';
            var previousSelector = '.webform-button--previous';
            var urlParams = new URLSearchParams(window.location.search);
            
            $('a.language-link', context).once('language-link').each(function () {
                var href = $('a.language-link').attr('href');
                var n = href.indexOf('?');
                hrefLanguage = href.substring(0, n != -1 ? n : href.length);
            });
            
            $('a.use-ajax', context).once('tab-ajax').each(function () {
                $("a.use-ajax").click(function(event){
                    var tabHref = event.target.href;
                    var tabArray = tabHref.split("/");
                    var arrayLength = tabArray.length;
                    if(window.location.pathname.indexOf('consultant-account-home' )) {
                      $('a.language-link').attr("href",  hrefLanguage + '?qt-consultant_account_home=' + tabArray[arrayLength-1] );
                    }
                    else if(window.location.pathname.indexOf('account-home')){
                      $('a.language-link').attr("href",  hrefLanguage + '?qt-account_home=' + tabArray[arrayLength-1] );
                    }                });
            });
            
            if(!window.location.search) {
                $('#quicktabs-tabpage-account_home-1', context).once('hide-tab').each(function () {
                    $('#quicktabs-tabpage-account_home-1').addClass('quicktabs-hide');
                }); 
                $('#quicktabs-tabpage-consultant_account_home-1', context).once('hide-constultan-tab').each(function () {
                    $('#quicktabs-tabpage-consultant_account_home-1').addClass('quicktabs-hide');
                });    
            }
            
            $('body', context).once('select2-group').each(function () {
                //Collapse the group when is clicked on element
    		    $("body").on('click', '.select2-results__group', function() {
                    $(this).siblings().slideToggle();
                });
                
                //Collapse the groups the first time select2 is opened
                waitForEl("body .select2-results__group", function() {
                    $("body .select2-results__group").siblings().toggle();
                });

                //Check if need change the tab positions in lobbyist dashboard, this depends on query params
                
                if(urlParams.has('qt-account_home')) {
                    var tabId = urlParams.get('qt-account_home');
                    waitForInhouseEl(tabId, function() {
                            setTimeout(function() {
                                $('a[href^="' + language + 'quicktabs/nojs/account_home/' + tabId + '"]').click();
                            }, 50);
                    });
                }else if (urlParams.has('qt-consultant_account_home')) {
                    var tabId = urlParams.get('qt-consultant_account_home');
                    waitForConsultantEl(tabId, function() {
                            setTimeout(function() {
                                $('a[href^="' + language + 'quicktabs/nojs/consultant_account_home/' + tabId + '"]').click();
                            }, 50);
                    });
                }
            });

            //Add the href attribute if the element exists
            $('.modal-link-desktop', context).once('modalDesktop').each(function () {
    		    $('.modal-link-desktop'). attr("href", language + "form/lobbyist-finder-modal");
            });
            $('.modal-link-mobile', context).once('modalMobile').each(function () {
    		    $('.modal-link-mobile'). attr("href", language + "form/lobbyist-finder-modal");
            });

            //Show/hide buttons in modal if exist an option to selected  
            $('.modal-form-radio input', context).once('modalOptions').click(function(event){
                var webform_page_key = $('[id^="webform-submission-lobbyist-finder-modal-add-form"] [id^="edit-page-"]').attr('data-webform-key');
		        if(
    			    event.currentTarget.value == 'No' &&
    			    webform_page_key != 'page_5_1' &&
    			    webform_page_key != 'page_6' &&
    			    webform_page_key != 'page_6_1' &&
    			    webform_page_key != 'page_6_2' &&
    			    webform_page_key != 'page_6_3' &&
    			    webform_page_key != 'page_6_4' &&
    			    webform_page_key != 'page_6_5' &&
    			    webform_page_key != 'page_7' &&
    			    webform_page_key != 'page_9' &&
    			    webform_page_key != 'page_10' &&
    			    webform_page_key != 'page_11'
                ) {
    			    $('.modal-form-radio').hide();
    			    $('.webform-button--previous').hide();
    			    $(backSelector).hide();
    			    $(nextSelector).hide();
    		    } else {
    			    $(nextSelector).click();
    		    }
            });

            //Close modal when clicked on the close icon
            $('.ui-button.ui-corner-all.ui-widget.ui-button-icon-only.ui-dialog-titlebar-close', context).once('button-titlebar').each(function () {
    		    $('.ui-button.ui-corner-all.ui-widget.ui-button-icon-only.ui-dialog-titlebar-close').on('click', function(event) {
                    location.reload();
	            });
            });

            //Canada will be select as a default country in the address 
            $("#edit-field-street-address-0-address-country-code--2", context).once('address').each(function () {
                if($('#edit-field-street-address-0-address-address-line1').val().length == 0 ){
                    setTimeout(function() {
                        $('#edit-field-street-address-0-address-country-code--2').val('CA').trigger('change');
	                }, 150);
                }
            });
        } 
    };
})(jQuery, Drupal);
var language;
var tabAccount = '0';
var hrefLanguage = '';

var waitForEl = function(selector, callback) {
  if (jQuery(selector).length) {
    callback();
  } else {
    setTimeout(function() {
      waitForEl(selector, callback);
    }, 100);
  }
};

(function ($, Drupal) {

  Drupal.behaviors.modalDesktop = {
    attach: function (context, settings) {

      language = "/" + settings.path.pathPrefix;
      var nextSelector = '.webform-button--next';
      var backSelector = '.webform-button--previous';
      var urlParams = new URLSearchParams(window.location.search);

      // Language link
      $(context).find('a.language-link').each(function () {
        var href = $(this).attr('href');
        if (href) {
          var n = href.indexOf('?');
          hrefLanguage = href.substring(0, n != -1 ? n : href.length);
        }
      });

      // Tabs ajax
      $(context).find('a.use-ajax').off('click.modalDesktop').on('click.modalDesktop', function(event){

        var tabHref = event.currentTarget.href;
        var tabArray = tabHref.split("/");
        var arrayLength = tabArray.length;

        if (window.location.pathname.indexOf('consultant-account-home') !== -1) {
          $('a.language-link').attr("href", hrefLanguage + '?qt-consultant_account_home=' + tabArray[arrayLength-1]);
        }
        else if (window.location.pathname.indexOf('account-home') !== -1){
          $('a.language-link').attr("href", hrefLanguage + '?qt-account_home=' + tabArray[arrayLength-1]);
        }

      });

      // Hide tabs if no query
      if(!window.location.search) {
        $('#quicktabs-tabpage-account_home-1', context).addClass('quicktabs-hide');
        $('#quicktabs-tabpage-consultant_account_home-1', context).addClass('quicktabs-hide');
      }

      // Select2 collapse
      $('body').off('click.select2Group')
               .on('click.select2Group', '.select2-results__group', function() {
                  $(this).siblings().slideToggle();
               });

      waitForEl("body .select2-results__group", function() {
        $("body .select2-results__group").siblings().toggle();
      });

      // Modal links
      $(context).find('.modal-link-desktop')
        .attr("href", language + "form/lobbyist-finder-modal");

      $(context).find('.modal-link-mobile')
        .attr("href", language + "form/lobbyist-finder-modal");

      // ✅ RADIO LOGIC (Yes = Next, No = Hide)
      $(document).off('click.modalRadio')
                 .on('click.modalRadio', '.modal-form-radio input', function(event){

        var webform_page_key = $('[id^="webform-submission-lobbyist-finder-modal-add-form"] [id^="edit-page-"]')
                                .attr('data-webform-key');

        if (
          event.currentTarget.value === 'No' &&
          webform_page_key !== 'page_5_1' &&
          webform_page_key !== 'page_6' &&
          webform_page_key !== 'page_6_1' &&
          webform_page_key !== 'page_6_2' &&
          webform_page_key !== 'page_6_3' &&
          webform_page_key !== 'page_6_4' &&
          webform_page_key !== 'page_6_5' &&
          webform_page_key !== 'page_7' &&
          webform_page_key !== 'page_9' &&
          webform_page_key !== 'page_10' &&
          webform_page_key !== 'page_11'
        ) {

          $('.modal-form-radio').hide();
          $(backSelector).hide();
          $(nextSelector).hide();

        } else {

          setTimeout(function() {

            var $nextBtn = $(nextSelector);

            if ($nextBtn.length) {
              $nextBtn[0].click();   // Important for Drupal AJAX
            //   alert('nextSelector');
            }

          }, 300);

        }

      });

      // Close modal reload
      $(document).off('click.modalClose')
                 .on('click.modalClose',
                    '.ui-button.ui-dialog-titlebar-close',
                    function(){
                      location.reload();
                 });

      // Default country CA
      if ($('#edit-field-street-address-0-address-address-line1').val().length === 0) {
        setTimeout(function() {
          $('#edit-field-street-address-0-address-country-code--2')
            .val('CA')
            .trigger('change');
        }, 150);
      }

    }
  };

})(jQuery, Drupal);

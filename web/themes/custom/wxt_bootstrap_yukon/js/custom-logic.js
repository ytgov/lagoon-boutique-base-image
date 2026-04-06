jQuery(document).ready(function($){
  $('.yukon-department-sections__expand').on('click', function(e) {
    $('.panel-collapse:not(".in")').collapse('show');
  });

  $('.yukon-department-sections__collapse').on('click', function(e) {
    $('.panel-collapse.in').collapse('hide');
  });

// Main menu tools
  var ESCAPE_CODE = 27;
  var navButton = $('#menu-button'),
      closeBtn  = $('button.closebtn'),
      navMenu   = $('#global-nav'),
      overlay   = $('.global-nav_overlay'),
      addAnotherRequest = $("[data-drupal-selector='edit-information-add-add-submit']");

      //classList = $('.menu li');
  var navLinks = navMenu.find('button, a').filter(':visible');

  navMenu.on('keydown', handleKeydown);
  navButton.on('click', handleClick);
  closeBtn.on('click', handleClick);
  addAnotherRequest.on('click', handleSlick);

  disableNavLinks();

  function handleSlick(event) {
    $( "#edit-information-add" )
      .find( "table" )
      .addClass('fixed-table');

    $( "#edit-information-add" )
      .find( "table > tbody" )
      .addClass('carousel');

    $('.carousel').slick({
      infinite: true,
      speed: 300,
      slidesToShow: 1,
      adaptiveHeight: true
    });
  }

  function handleKeydown(event) {
    if (event.keyCode === ESCAPE_CODE) {
      document.body.classList.toggle('active');
      disableNavLinks();
      navButton.focus();
    }
  }

  function handleClick(event) {
    if (document.body.classList.contains('active')) {
      document.body.classList.remove('active');
      disableNavLinks();
    } else {
      document.body.classList.add('active');
      enableNavLinks();
      $('.menu li a').eq(0).focus();
    }
  }

  function enableNavLinks() {
    navButton.attr('aria-label', 'Menu expanded');
    navMenu.removeAttr('aria-hidden');
    navLinks.removeAttr('tabIndex');
    trapFocus(navMenu); // trap tab focus within menu loop when menu open
    overlay.removeAttr('aria-hidden');
  }
  function disableNavLinks() {
    navButton.attr('aria-label', 'Menu collapsed');
    navMenu.attr('aria-hidden', 'true');
    navLinks.attr('tabIndex', '-1');
    overlay.attr('aria-hidden', 'true');
  }
  $('.global-nav_overlay').click(function (e) {
      document.body.classList.toggle('active');
      disableNavLinks();
  });

});

(function ($, Drupal) {
  //Function for remove the panel heading from preview webform
  Drupal.behaviors.webformSettings = {
    attach: function (context, settings) {
      $('#edit-preview .panel-heading', context).once('webformPreviw').each(function () {
        $("#edit-preview .panel-heading").remove();
      });

    }
  };
  //Function for display load bar when upload a document in any webform
  Drupal.behaviors.fileUploadEnhancements = {
    attach: (context) => {
      let bar2;
      const fileUploads = $('.form-item-attach-complaint-documents-and-or-supporting-material .form-type-checkbox', context);
      $("#file_load_bar", context).once('webformPreviw').each(function () {
        let bar1 = new ldBar("#file_load_bar");
        $("#file_load_bar").css('display', 'none');
      });

      if (fileUploads.length >= 10) {
        const uploadInput = $('input[id^="edit-attach-complaint-documents-and-or-supporting-material-upload"]');
        if (uploadInput.length > 0) {
          uploadInput.prop('disabled', true);
        }
      }
      const buttons = [
        '.webform-button--next',
        '.webform-button--previous',
        '.webform-button--restart',
      ];
      $(context).ajaxStart(() => {
        $("#file_load_bar").css('display', 'inline-table');
        let count = 20;
        if(document.getElementById('file_load_bar')){
          /* ldBar stored in the element */
          bar2 = document.getElementById('file_load_bar').ldBar;


          bar2.set(count);
          for (const index in buttons) {
            if ($(buttons[index]).length > 0) {
              $(buttons[index]).prop('disabled', true);
              count += 10;
              bar2.set(count);
            }
          }
          // repeat with the interval of 2 seconds
          let timerId = setInterval(function () {
            count += 10;
            bar2.set(count);
          }, 5000);
          setTimeout(() => { clearInterval(timerId); count += 10;bar2.set(count); }, 25000);
        }
      });
      $(context).ajaxComplete(() => {
        bar2 = document.getElementById('file_load_bar').ldBar;
        bar2.set(100);
        $("#file_load_bar").css('display', 'none');
        for (const index in buttons) {
          if ($(buttons[index]).length > 0) {
            $(buttons[index]).prop('disabled', false);
          }
        }
      });
    },
    detach: (context) => {

    },
  };
  //Function for change selected icon color
  Drupal.behaviors.webformFeedback = {
    attach: function (context, settings) {
      $('#edit-was-this-page-helpful-yes,#edit-was-this-page-helpful-yes', context).once('webformHands').each(function () {
        $("#edit-was-this-page-helpful-yes").click(function () {
          $("label[for='edit-was-this-page-helpful-yes']").css('color','#00818f');
          $("label[for='edit-was-this-page-helpful-no']").css('color','#b2b3b2');
        });
        $("#edit-was-this-page-helpful-no").click(function () {
          $("label[for='edit-was-this-page-helpful-yes']").css('color','#b2b3b2');
          $("label[for='edit-was-this-page-helpful-no']").css('color','#00818f');
        });
      });

    }
  };
})(jQuery, Drupal);

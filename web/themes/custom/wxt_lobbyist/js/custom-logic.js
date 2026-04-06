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
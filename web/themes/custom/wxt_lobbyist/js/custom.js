
jQuery(document).ready(function ($) {
  jQuery("#block-wxt-lobbyist-useraccountmenu ul.menu.nav.account a.dropdown-toggle").click( function(event){
    event.preventDefault();
    jQuery(this).next().toggle();
  });
  var $select = $('select[name="which_topic_do_you_want_to_lobby_government_about_[]"]');
  var $otherField = $('.form-item-other'); 

  function toggleOther() {
    var selectedValues = $select.val(); 

    if (selectedValues && selectedValues.includes('45')) {
      $otherField.show();
    } else {
      $otherField.hide();
      $otherField.find('input').val('');
    }
  }

 
  toggleOther();

 
  $select.on('change', function () {
    toggleOther();
  });

});


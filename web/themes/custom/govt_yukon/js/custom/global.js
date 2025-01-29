/**
 * @file
 * Global Theme Functions.
 */

(function ($, Drupal, once) {
  'use strict';

  Drupal.govt_yukon_main = {};

  // Collapsible Navigation.
  function collapsibleNav (menu) {
    menu.addClass('menu--collapsible');
    menu.find('li.menu__item').each(function () {
      var $this = $(this);
      var $menuItemLink = $this.children('a');
      var $expandMarkup = '<button class="menu__item-expand" role="button" aria-label="' + Drupal.t('Toggle Menu') + '"></button>';

      if ($this.hasClass('menu__item--expanded')) {
        $menuItemLink.after($expandMarkup);
      }
      if ($this.hasClass('menu__item--active-trail')) {
        $this.children('.menu__item-expand').addClass('is-expanded');
      }
    });
  }

  var $mobileNavMenu = $('.mobile-nav nav > .menu');
  collapsibleNav($mobileNavMenu);

  // Collapsible Navigation - Expand Link.
  $('.menu__item-expand').click(function (e) {
    var $this = $(this);
    var $nextMenu = $this.parent().find('ul.menu').first();

    $nextMenu.slideToggle('fast', function () {
      if ($($nextMenu).is(':visible')) {
        $this.addClass('is-expanded');
      }
      else {
        $this.removeClass('is-expanded');
      }
    });

    e.preventDefault();
  });

  /**
   * Remove single submit event listener.
   *
   * @see Drupal.behaviors.formSingleSubmit
   */
  Drupal.govt_yukon_main.removeFormSingleSubmit = function (trigger_element) {
    var $form = $(trigger_element.form);
    $form.removeAttr("data-drupal-form-submit-last");
  }

  // General Theme Behaviors.
  Drupal.behaviors.govtYukonTheme = {
    attach: function (context, settings) {
      // Search Form - Apply placeholder.
      var $searchFormInput = $('.search-form input.form-search');

      if (!$searchFormInput.is("[placeholder]")) {
        $searchFormInput.attr('placeholder', Drupal.t('Keywords'));
      }

      // Pseudo Form Submit Trigger.
      if ($('.form-submit--trigger').length) {
        $(once('govtYukonTheme', '.form-submit--trigger', context)).on('click', function (e) {
          // Fix for re-activating trigger buttons after browser back button.
          Drupal.govt_yukon_main.removeFormSingleSubmit($(this).prev('input')[0]);
          // Trigger click on the actual input.
          $(this).prev('input').click();
          e.preventDefault();
        });
      }
    }
  };

})(jQuery, Drupal, once);

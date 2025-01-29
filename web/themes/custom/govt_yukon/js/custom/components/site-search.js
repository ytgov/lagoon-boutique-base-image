/**
 * @file
 * Site Search.
 */

(function ($, Drupal, once) {
  'use strict';

  Drupal.behaviors.govtYukonThemeSiteSearch = {
    attach: function (context, settings) {
      // Site Search Toggle.
      $(once('govtYukonThemeSiteSearch', '.comp-site-search-form', context)).each(function () {
        var $this = $(this);
        var $searchInput = $this.find('input[type="text"]');

        if ($this.find('input[type="search"]').length) {
          $searchInput = $this.find('input[type="search"]');
        }

        // Apply placeholder if not present.
        if (!$searchInput.is("[placeholder]")) {
          $searchInput.attr('placeholder', Drupal.t('Keywords'));
        }
      });

      $(once('govtYukonThemeSiteSearch', '.comp-site-search__menu__menu-link', context)).on('click', function (e) {
        $('.comp-site-search-form').each(function () {
          var $this = $(this);
          var $searchInput = $this.find('input[type="text"]');

          if ($this.find('input[type="search"]').length) {
            $searchInput = $this.find('input[type="search"]');
          }

          if ($this.is(':visible')) {
            $this.removeClass('is-active');
            $searchInput.blur();
          }
          else {
            $this.addClass('is-active');
            $searchInput.focus();
          }
        });

        e.preventDefault();
      });

      $(once('govtYukonThemeSiteSearch', '.comp-site-search-form__close')).on('click', function (e) {
        var $this = $(this);
        var $searchContainer = $this.parents('.comp-site-search-form');
        var $searchInput = $searchContainer.find('input[type="text"]');

        if ($searchContainer.find('input[type="search"]').length) {
          $searchInput = $searchContainer.find('input[type="search"]');
        }

        $searchContainer.removeClass('is-active');
        $searchInput.blur();

        e.preventDefault();
      });

      // Deactivate elements when clicked away from them.
      $(document).on('click', function (e) {
        if (!$(e.target).closest('.comp-site-search__menu__menu-link').length && !$(e.target).closest('.comp-site-search-form').length) {
          $('.comp-site-search-form').removeClass('is-active');

          var $searchInput = $('.comp-site-search-form input[type="text"]');

          if ($('.comp-site-search-form input[type="search"]').length) {
            $searchInput = $('.comp-site-search-form input[type="search"]');
          }

          $searchInput.blur();
        }
      });

      // Deactivate elements when Esc key is pressed.
      $(document).keyup(function (e) {
        if (e.key === "Escape") {
          if ($('.comp-site-search-form').hasClass('is-active')) {
            $('.comp-site-search-form').removeClass('is-active');

            var $searchInput = $('.comp-site-search-form input[type="text"]');

            if ($('.comp-site-search-form input[type="search"]').length) {
              $searchInput = $('.comp-site-search-form input[type="search"]');
            }

            $searchInput.blur();
          }
        }
      });
    }
  };

})(jQuery, Drupal, once);

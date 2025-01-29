/**
 * @file
 * Page Banner.
 */

(function ($, Drupal, once) {
  'use strict';

  Drupal.behaviors.govtYukonThemePageBanner = {
    attach: function (context, settings) {
      $(once('govtYukonThemePageBanner', '.comp-page-banner__carousel', context)).each(function () {
        $(this).slick({
          slidesToShow: 1,
          slidesToScroll: 1,
          autoplay: false,
          fade: false,
          dots: true,
          arrows: true,
          infinite: true,
          adaptiveHeight: true,
          responsive: [
            {
              breakpoint: 768,
              settings: {
                arrows: false,
                dots: true
              }
            }
          ]
        });
      });
    }
  };

})(jQuery, Drupal, once);

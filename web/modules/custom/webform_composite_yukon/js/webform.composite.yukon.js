/**
 * @file
 * JavaScript behaviors for multiple element.
 */

/**
 * @file
 * JavaScript behaviors for multiple element.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Move show weight to after the table.
   *
   * @type {Drupal~behavior}
   */
/*
  Drupal.behaviors.webformMultipleTableDrag = {
    attach: function (context, settings) {
      for (var base in settings.tableDrag) {
        if (settings.tableDrag.hasOwnProperty(base)) {
          $(context).find('.js-form-type-webform-multiple #' + base).once('webform-multiple-table-drag').each(function () {
            var $tableDrag = $(this);
            var $toggleWeight = $tableDrag.prev().prev('.tabledrag-toggle-weight-wrapper');
            if ($toggleWeight.length) {
              $toggleWeight.addClass('webform-multiple-tabledrag-toggle-weight');
              $tableDrag.after($toggleWeight);
            }
          });
        }
      }
    }
  };
*/

  /**
   * Submit multiple add number input value when enter is pressed.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webform_composite_yukon = {
    attach: function (context, settings) {
      $(context).find('.js-webform-multiple-add').once('webform-multiple-add').each(function () {
        var $submit = $(this).find('input[type="submit"], button');
        var $number = $(this).find('input[type="number"]');
        $number.keyup(function (event) {
          if (event.which === 13) {
            // Note: Mousedown is the default trigger for Ajax events.
            // @see Drupal.Ajax.
            $submit.mousedown();
          }
        });
      });
      $('.slick-carousel').find('tbody').not('.slick-initialized').slick({
          autoplay: false,
          dots: true,
          infinite: false,
          speed: 300,
          slidesToShow: 1,
          adaptiveHeight: true,
          arrows: false,
          draggable: false
      });	
      // $('.slick-carousel').find('tbody').slick("refresh");
      $('.slick-dots li button').on('click', function(e) { e.stopPropagation(); });
      $('.slick-initialized').slick('slickGoTo', $('.slick-track').children().length - 1);
    }
  };

})(jQuery, Drupal);
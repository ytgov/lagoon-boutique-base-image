/**
 * @file
 * Global Theme Functions.
 */

(function ($, Drupal, once) {
  'use strict';

  // General Theme Behaviors.
  Drupal.behaviors.govtYukonRmsTheme = {
    attach: function (context, settings) {
      // Blog facets list show and hide.
      once('facetListShowHide', '.views-exposed-form', context).forEach(
        function (element) {
          const checkboxesWrapper = element.querySelectorAll('.fieldset-wrapper')[0];
          const numOfCheckboxes = element.querySelectorAll('.form-type-checkbox').length;
          const moreButtonLabel = Drupal.t('Show more');
          const lessButtonLabel = Drupal.t('Show less');

          if (numOfCheckboxes > 12) {
            // Add create more button.
            const moreButtonElement = document.createElement('a');
            moreButtonElement.classList.add('blog-list__filters__more');
            moreButtonElement.innerHTML = moreButtonLabel;
            moreButtonElement.setAttribute('role', 'button');
            moreButtonElement.setAttribute('tabindex', '0');
            checkboxesWrapper.append(moreButtonElement);

            // Show/hide class toggle.
            moreButtonElement.addEventListener("click", function() {
              if (checkboxesWrapper.classList.contains('fieldset-wrapper--show-all')) {
                checkboxesWrapper.classList.remove('fieldset-wrapper--show-all');
                moreButtonElement.innerHTML = moreButtonLabel;
              }
              else {
                checkboxesWrapper.classList.add('fieldset-wrapper--show-all');
                moreButtonElement.innerHTML = lessButtonLabel;
              }
            });

            moreButtonElement.addEventListener("keypress", function(event) {
              if (event.key === "Enter") {
                if (checkboxesWrapper.classList.contains('fieldset-wrapper--show-all')) {
                  checkboxesWrapper.classList.remove('fieldset-wrapper--show-all');
                  moreButtonElement.innerHTML = moreButtonLabel;
                }
                else {
                  checkboxesWrapper.classList.add('fieldset-wrapper--show-all');
                  moreButtonElement.innerHTML = lessButtonLabel;
                }
              }
            });
          }
        }
      );

      // Blog facets mobile toggle.
      once('facetMobileToggle', '.blog-list__container', context).forEach(
        function (element) {
          const toggleButton = element.querySelectorAll('.blog-list__mobile-filters-toggle__button')[0];
          const filtersWrapper = element.querySelectorAll('.blog-list__filters')[0];

          toggleButton.addEventListener("click", function() {
            if (filtersWrapper.classList.contains('blog-list__filters--show-all')) {
              filtersWrapper.classList.remove('blog-list__filters--show-all');
            }
            else {
              filtersWrapper.classList.add('blog-list__filters--show-all');
            }
          });
        }
      );
    }
  };

})(jQuery, Drupal, once);

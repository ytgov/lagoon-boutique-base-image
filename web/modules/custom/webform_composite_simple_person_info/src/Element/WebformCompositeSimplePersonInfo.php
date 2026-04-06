<?php

namespace Drupal\webform_composite_simple_person_info\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a 'webform_composite_simple_person_info'.
 *
 * Webform composites contain a group of sub-elements.
 *
 * @FormElement("webform_composite_simple_person_info")
 *
 * @see \Drupal\webform\Element\WebformCompositeBase
 * @see \Drupal\webform_composite_simple_person_info\Element\WebformCompositeSimplePersonInfo
 */
class WebformCompositeSimplePersonInfo extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + ['#theme' => 'webform_composite_simple_person_info'];
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element) {
    $elements = [];
    $elements['first_name'] = [
      '#type' => 'textfield',
      '#title' => t('First name'),
    ];
    $elements['last_name'] = [
      '#type' => 'textfield',
      '#title' => t('Last name'),
    ];
    $elements['date_of_birth'] = [
      '#type' => 'datelist',
      '#title' => t('Date of birth'),
      '#date_part_order' => ['month','day','year'],
      '#date_year_range' => '1920:2020',
      '#date_year_range_reverse' => TRUE,
      '#date_abbreviate' => false,
      // Use #after_build to add #states.
      '#after_build' => [[get_called_class(), 'afterBuild']],
    ];

    return $elements;
  }

  /**
   * Performs the after_build callback.
   */
  public static function afterBuild(array $element, FormStateInterface $form_state) {
    // Add #states targeting the specific element and table row.
    preg_match('/^(.+)\[[^]]+]$/', $element['#name'], $match);
    $composite_name = $match[1];
    $element['#states']['disabled'] = [
      [':input[name="' . $composite_name . '[first_name]"]' => ['empty' => TRUE]],
      [':input[name="' . $composite_name . '[last_name]"]' => ['empty' => TRUE]],
    ];
    // Add .js-form-wrapper to wrapper (ie td) to prevent #states API from
    // disabling the entire table row when this element is disabled.
    $element['#wrapper_attributes']['class'][] = 'js-form-wrapper';
    return $element;
  }

}

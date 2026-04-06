<?php

namespace Drupal\webform_composite_simple_person_info\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'webform_composite_simple_person_info' element.
 *
 * @WebformElement(
 *   id = "webform_composite_simple_person_info",
 *   label = @Translation("composite simple person info"),
 *   description = @Translation("Provides composite for name and date of birth."),
 *   category = @Translation("Yukon"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 *
 * @see \Drupal\WebformCompositeSimplePersonInfo\Element\WebformCompositeSimplePersonInfo
 * @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase
 * @see \Drupal\webform\Plugin\WebformElementBase
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Annotation\WebformElement
 */
class WebformCompositeSimplePersonInfo extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    return $this->formatTextItemValue($element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);
    $formatted_date = '';
    if ($value['date_of_birth'] && strtotime($value['date_of_birth'])) {
      // This is likely not the right way to do this, but it's what works now.
      // One way to do this is with Custom Settings.
      // e.g. date_of_birth__format: html_date
      $formatted_date = \Drupal::service('date.formatter')->format(strtotime($value['date_of_birth']), 'standard_yukon');
    }

    $lines = [];
    $lines[] = ($value['first_name'] ? $value['first_name'] : '') .
      ($value['last_name'] ? ' ' . $value['last_name'] : '') .
      ($value['date_of_birth'] ? ' (' . $formatted_date . ')' : '');
    return $lines;
  }

}

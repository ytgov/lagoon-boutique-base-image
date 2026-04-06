<?php

namespace Drupal\webform_composite_yukon\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Render\Element as RenderElement;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\webform\Entity\WebformOptions;
use Drupal\webform\Plugin\WebformElementEntityReferenceInterface;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformOptionsHelper;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;


/**
 * Provides a 'webform_composite_yukon' element.
 *
 * @WebformElement(
 *   id = "webform_composite_yukon",
 *   label = @Translation("Webform example composite"),
 *   description = @Translation("Provides a webform element example."),
 *   category = @Translation("Example elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 *
 * @see \Drupal\webform_composite_yukon\Element\WebformCompositeYukon
 * @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase
 * @see \Drupal\webform\Plugin\WebformElementBase
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Annotation\WebformElement
 */
class WebformCompositeYukon extends WebformCompositeBase {
/**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = $this->getDefaultMultipleProperties() + parent::getDefaultProperties();
    $properties['title_display'] = '';
    $properties['element'] = [];
    unset($properties['flexbox']);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultMultipleProperties() {
    $properties = [
      'multiple' => TRUE,
      'multiple__header' => TRUE,
    ] + parent::getDefaultMultipleProperties();
    
    $properties['multiple__sorting'] = FALSE;
    $properties['multiple__header'] = FALSE;
    $properties['multiple__operations'] = FALSE;
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function hasMultipleValues(array $element) {
    // WebformComposite extends the WebformMultiple and will always store
    // multiple values.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);
    // Set cardinality.
    if (isset($element['#multiple'])) {
      $element['#cardinality'] = ($element['#multiple'] === FALSE) ? 1 : $element['#multiple'];
    }

    // Apply multiple properties.
    $multiple_properties = $this->getDefaultMultipleProperties();
    foreach ($multiple_properties as $multiple_property => $multiple_value) {
      if (strpos($multiple_property, 'multiple__') === 0) {
        $property_name = str_replace('multiple__', '', $multiple_property);
        $element["#$property_name"] = (isset($element["#$multiple_property"])) ? $element["#$multiple_property"] : $multiple_value;
      }
    }
    // Default to displaying table header.
    $element += ['#header' => TRUE];

    // If header label is defined use it for the #header.
    if (!empty($element['#multiple__header_label'])) {
      $element['#header'] = $element['#multiple__header_label'];
    }

    // Transfer '#{composite_key}_{property}' from main element to composite
    // element.
    foreach ($element['#element'] as $composite_key => $composite_element) {
      foreach ($element as $property_key => $property_value) {
        if (strpos($property_key, '#' . $composite_key . '__') === 0) {
          $composite_property_key = str_replace('#' . $composite_key . '__', '#', $property_key);
          $element['#element'][$composite_key][$composite_property_key] = $property_value;
        }
      }
    }
    //$element['#add_more'] = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareMultipleWrapper(array &$element) {
    // Don't set multiple wrapper since 'webform_composite' extends
    // 'webform_multiple'.
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Always to should multiple element settings since WebformComposite
    // extends WebformMultiple.
    unset($form['multiple']['#states']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildCompositeElementsTable(array $form, FormStateInterface $form_state) {
    return [
      '#type' => 'webform_element_composite',
      '#title' => $this->t('Elements'),
      '#title_display' => 'invisible',
    ];
  }

  /****************************************************************************/
  // Preview method.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return [
      '#type' => $this->getTypeName(),
      '#title' => $this->getPluginLabel(),
      '#element' => [
        'name' => [
          '#type' => 'textfield',
          '#title' => 'Name',
          '#title_display' => 'invisible',
        ],
        'gender' => [
          '#type' => 'select',
          '#title' => 'Gender',
          '#title_display' => 'invisible',
          '#options' => [
            'Male' => $this->t('Male'),
            'Female' => $this->t('Female'),
          ],
        ],
      ],
    ];
  }

  /****************************************************************************/
  // Composite element methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function initializeCompositeElements(array &$element) {
    $element['#webform_composite_elements'] = [];
    foreach ($element['#element'] as $composite_key => $composite_element) {
      $this->elementManager->initializeElement($composite_element);
      $element['#webform_composite_elements'][$composite_key] = $composite_element;
    }
    $this->initializeCompositeElementsRecursive($element, $element['#webform_composite_elements']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCompositeElements() {
    // Return empty array since composite (sub) elements are custom.
    return [];
  }

}

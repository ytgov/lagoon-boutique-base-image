<?php

use Drupal\webform_composite_yukon\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Container;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Element\WebformActions

/**
 * Provides a wrapper element to group one or more Webform buttons in a form.
 *
 * @RenderElement("webform_actions")
 *
 * @see \Drupal\Core\Render\Element\Actions
 */
class WebformActionsYukon extends WebformActions {

  public static $buttons = [
    'submit',
    'reset',
    'draft',
    'wizard_prev',
    'wizard_next',
    'preview_prev',
    'preview_next',
  ];

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    return $info;
  }

  /**
   * Processes a form actions container element.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   form actions container.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processWebformActions(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = parent::processWebformActions(&$element, FormStateInterface $form_state, &$complete_form);
    return $element;
  }

}

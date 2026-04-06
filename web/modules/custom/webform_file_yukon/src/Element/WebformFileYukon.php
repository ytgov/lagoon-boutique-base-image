<?php

namespace Drupal\webform_file_yukon\Element;

use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Element\ManagedFile;
/**
 * Provides a 'webform_file_yukon'.
 *
 * Webform elements are just wrappers around form elements, therefore every
 * webform element must have correspond FormElement.
 *
 * Below is the definition for a custom 'webform_file_yukon' which just
 * renders a simple text field.
 *
 * @FormElement("webform_file_yukon")
 *
 * @see \Drupal\Core\Render\Element\FormElement
 * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21Element%21FormElement.php/class/FormElement
 * @see \Drupal\Core\Render\Element\RenderElement
 * @see https://api.drupal.org/api/drupal/namespace/Drupal%21Core%21Render%21Element
 * @see \Drupal\webform_example_element\Element\WebformExampleElement
 */
class WebformFileYukon extends ManagedFile {
 /**
     * The types of files that the server accepts.
     *
     * @var string
     *
     * @see http://www.w3schools.com/tags/att_input_accept.asp
     */
    protected static $accept;

    /**
     * {@inheritdoc}
     */
    public function getInfo() {
      $info = parent::getInfo();
      $info['#pre_render'][] = [get_class($this), 'preRenderWebformFileYukon'];
      $info['#process'][] = [get_class($this), 'processWebformFileYukon'];
      return $info;
    }

    /**
     * Processes a 'webform_file_yukon' element.
     */
    public static function processWebformFileYukon(&$element, FormStateInterface $form_state, &$complete_form) {
      // Here you can add and manipulate your element's properties and callbacks.
      $element = ManagedFile::processManagedFile($element, $form_state, $complete_form);
      return $element;
    }
  
    /**
     * Render API callback: Adds media capture to the managed_file element type.
     */
    public static function preRenderWebformFileYukon($element) {
      $element['#attributes']['type'] = 'file';
      Element::setAttributes($element, ['id', 'name', 'value', 'size', 'maxlength', 'placeholder']);
      static::setAttributes($element, ['form-text', 'webform-file-yukon']);

      // Set accept and capture attributes.
      if (isset($element['upload']) && static::$accept) {
        $element['upload']['#attributes']['accept'] = static::$accept;;
      }
      // Add class name to wrapper attributes.
      $class_name = str_replace('_', '-', $element['#type']);
      static::setAttributes($element, ['js-' . $class_name, $class_name]);
      return $element;
    }

  }

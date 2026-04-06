<?php

namespace Drupal\webform_file_yukon\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\Plugin\WebformElement\WebformManagedFileBase;


/**
 * Provides a 'webform_file_yukon' element.
 *
 * @WebformElement(
 *   id = "webform_file_yukon",
 *   label = @Translation("File - Yukon"),
 *   description = @Translation("Provides a webform file."),
 *   category = @Translation("File upload elements"),
 * )
 *
 * @see \Drupal\webform_example_element\Element\WebformExampleElement
 * @see \Drupal\webform\Plugin\WebformElementBase
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Annotation\WebformElement
 */
class WebformFileYukon extends WebformManagedFileBase {

  /**
   * {@inheritdoc}
   *
   * @deprecated in Webform 8.x-5.9-beta1. Use defineDefaultProperties as a
   * protected static function instead.
   * @see https://www.drupal.org/node/3106684
   */
  public function getDefaultProperties() {
    // Here you define your webform element's default properties,
    // which can be inherited.
    //
    // @see \Drupal\webform\Plugin\WebformElementBase::getDefaultProperties
    // @see \Drupal\webform\Plugin\WebformElementBase::getDefaultBaseProperties
    return parent::getDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);
     // Get current value and original value for this element.
    $key = $element['#webform_key'];

    $webform = $webform_submission->getWebform();
    if ($webform->isResultsDisabled()) {
      return;
    }
    $original_data = $webform_submission->getOriginalData();
    $data = $webform_submission->getData();
    $value = isset($data[$key]) ? $data[$key] : [];
    $fids = (is_array($value)) ? $value : [$value];
    if(isset($data["_". $key]) &&  empty($this->entityTypeManager->getStorage('file')->loadMultiple($fids))){
        $sub_data = $webform_submission->getData();
        if(is_array($data[$key])){
            $fids =  [];
            $value = [];
            foreach($data["_". $key] as $external_file){
                $file_data = $external_file["data"];
                $file_uri = $external_file["uri"];
                $file_ = file_save_data($file_data, $file_uri, FILE_EXISTS_REPLACE);
                $value[] = isset($external_file) ? $external_file : [];
                $fids[] = $file_->id();
            }
            if(isset($sub_data[$key])){
                $sub_data[$key] = $fids ;
                unset($sub_data["_".$key]);
                $webform_submission->setData($sub_data);
            }
        }else{
           //Save only one file 
            $file_data = $data["_".$key]["data"];
            $file_uri = $data["_".$key]["uri"];
            $file_ = file_save_data($file_data, $file_uri, FILE_EXISTS_REPLACE);
            $value = isset($data["_".$key]) ? $data["_".$key] : [];
            $fids =  [$file_->id()];

            if(isset($sub_data[$key])){
                $sub_data[$key] = $file_->id() ;
                unset($sub_data["_".$key]);
                $webform_submission->setData($sub_data);
            }
        }
    }
    $element["#default_value"] = $fids;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    // Here you can define and alter a webform element's properties UI.
    // Form element property visibility and default values are defined via
    // ::getDefaultProperties.
    //
    // @see \Drupal\webform\Plugin\WebformElementBase::form
    // @see \Drupal\webform\Plugin\WebformElement\TextBase::form
    return $form;
  }   
  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);
    $file = $this->getFile($element, $value, $options);

    if (empty($file)) {
      return '';
    }
    $format = $this->getItemFormat($element);
    switch ($format) {
      case 'id':
      case 'name':
      case 'url':
      case 'value':
      case 'raw':
        return $this->formatTextItem($element, $webform_submission, $options);
      case 'link':
      default:
        return [
          '#theme' => 'file_link',
          '#file' => $file,
        ];
    }
  }

}

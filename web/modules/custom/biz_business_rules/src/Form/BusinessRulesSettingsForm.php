<?php
namespace Drupal\biz_business_rules\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
* Configure example settings for this site.
*/
class BusinessRulesSettingsForm extends ConfigFormBase {

  /** 
  * Config settings.
  *
  * @var string
  */
  const SETTINGS = 'biz_business_rules.settings';

  /** 
  * Getting the form id for settings configurations
  */
  public function getFormId() {
    return 'biz_business_rules_admin_settings';
  }

  /** 
  * Getting the configuration's name for edition
  */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /** 
  * building forms from templates
  */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);
    $form['consultant_validate_start_date'] = [
      '#type' => 'textarea',
      '#title' => $this->t("Message after a new activity is created and start date is prior 15 days as of today"),
      '#default_value' => $config->get('consultant_validate_start_date'),
    ]; 

    $form['in_house_validate_end_calendar'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message after a new activity is created after January 31'),
      '#default_value' => $config->get('in_house_validate_end_calendar'),
    ];  

    $form['wave_for_not_validate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Not end the activities"),
      '#default_value' => $config->get('wave_for_not_validate'),
    ]; 
    
    return parent::buildForm($form, $form_state);
  }

  /** 
  * getting and settings values ready for submission
  */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->configFactory->getEditable(static::SETTINGS)
      // Set the submitted configuration setting. 
      ->set('consultant_validate_start_date', $form_state->getValue('consultant_validate_start_date'))
      ->set('in_house_validate_end_calendar', $form_state->getValue('in_house_validate_end_calendar'))
      ->set('wave_for_not_validate', $form_state->getValue('wave_for_not_validate'))
      ->save();
    parent::submitForm($form, $form_state);
  }
}
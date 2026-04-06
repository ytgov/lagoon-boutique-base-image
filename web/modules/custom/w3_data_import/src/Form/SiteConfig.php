<?php

namespace Drupal\w3_data_import\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements website configuration form.
 */
class SiteConfig extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'w3_data_import.theme_settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'w3_data_import';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('w3_data_import.theme_settings');

    $form['site_basic_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site Basic Path'),
      '#default_value' => $config->get('site_basic_path'),
      '#description' => $this->t('Add site basic path'),
    ];

    $form['import_link'] = [
      '#type' => 'markup',
      '#markup' => '<a href="/user-import">Import Users</a>',
      '#weight' => 3,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('w3_data_import.theme_settings')
      ->set('site_basic_path', $form_state->getValue('site_basic_path'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
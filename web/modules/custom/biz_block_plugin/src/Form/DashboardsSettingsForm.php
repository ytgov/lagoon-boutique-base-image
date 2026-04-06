<?php
namespace Drupal\biz_block_plugin\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class DashboardsSettingsForm extends ConfigFormBase {
    /** 
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'biz_block_plugin.settings';

  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'biz_block_plugin_admin_settings';
  }

  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);
    
    $form['header_all_organizations'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Header for Organization Table'),
      '#default_value' => $config->get('header_all_organizations'),
    ];

    $form['all_organizations'] = [
      '#type' => 'textfield',
      '#title' => $this->t('All organizartions'),
      '#default_value' => $config->get('all_organizations'),
    ];
    
    $form['header_activities'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Header for Activities Table'),
      '#default_value' => $config->get('header_activities'),
    ];

    $form['in_house_activities'] = [
      '#type' => 'textfield',
      '#title' => $this->t('In-house Activities'),
      '#default_value' => $config->get('in_house_activities'),
    ];
    
    $form['search_in_house_activities'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search In-house Activities per organization'),
      '#default_value' => $config->get('search_in_house_activities'),
    ];
    
    $form['search_consultant_activities'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search Consultant Activities per organization'),
      '#default_value' => $config->get('search_consultant_activities'),
    ];
    
    $form['in_house_activity'] = [
      '#type' => 'textfield',
      '#title' => $this->t('In-house Activity'),
      '#default_value' => $config->get('in_house_activity'),
    ];
    
    $form['header_in_house_lobbying'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Header for Lobbyist Table'),
      '#default_value' => $config->get('header_in_house_lobbying'),
    ];
    
    $form['in_house_lobbying_list'] = [
      '#type' => 'textfield',
      '#title' => $this->t('In-house Lobbying(List)'),
      '#default_value' => $config->get('in_house_lobbying_list'),
    ];
    
    $form['in_house_lobbying_single'] = [
      '#type' => 'textfield',
      '#title' => $this->t('In-house Lobbying(Single)'),
      '#default_value' => $config->get('in_house_lobbying_single'),
    ]; 
    $form['header_consultant_activities'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Header for Consultant Activities'),
      '#default_value' => $config->get('header_consultant_activities'),
    ]; 
    $form['consultant_activities'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Consultant Activities'),
      '#default_value' => $config->get('consultant_activities'),
    ]; 
    $form['consultant_activity'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Consultant Activity'),
      '#default_value' => $config->get('consultant_activity'),
    ];
    $form['current_user_info'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Current user'),
      '#default_value' => $config->get('current_user_info'),
    ]; 
    $form['all_activities'] = [
      '#type' => 'textfield',
      '#title' => $this->t('All Activities'),
      '#default_value' => $config->get('all_activities'),
    ]; 
    $form['header_all_activities'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Header for All Activities table'),
      '#default_value' => $config->get('header_all_activities'),
    ]; 
    $form['header_review_form'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Header for review form'),
      '#default_value' => $config->get('header_review_form'),
    ]; 
    $form['review_checkbox_acept'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Review checkbox acept'),
      '#default_value' => $config->get('review_checkbox_acept'),
    ]; 
    $form['get_user_by_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Get user by id'),
      '#default_value' => $config->get('get_user_by_id'),
    ]; 
    $form['get_node_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Get Activity node ID'),
      '#default_value' => $config->get('get_node_id'),
    ]; 
    $form['get_comments'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Get Messages'),
      '#default_value' => $config->get('get_comments'),
    ];
    
    $form['header_commissioner_activities'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Header commissioner activity'),
      '#default_value' => $config->get('header_commissioner_activities'),
    ];
    
    $form['commissioner_activities'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Commissioner activities'),
      '#default_value' => $config->get('commissioner_activities'),
    ];
    
    $form['get_new_messages'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Get New messages'),
      '#default_value' => $config->get('get_new_messages'),
    ];

    $form['consultant_content_instruct'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Content to instruct consultant lobbyists'),
      '#default_value' => $config->get('consultant_content_instruct')["value"],
    ];

    $form['in_house_content_instruct'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Content to instruct in-house lobbyists'),
      '#default_value' => $config->get('in_house_content_instruct')["value"],
    ];

    return parent::buildForm($form, $form_state);
  }

  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->configFactory->getEditable(static::SETTINGS)
      // Set the submitted configuration setting.
      ->set('header_activities', $form_state->getValue('header_activities'))
      ->set('in_house_activities', $form_state->getValue('in_house_activities'))
      ->set('in_house_activity', $form_state->getValue('in_house_activity'))
      ->set('header_in_house_lobbying', $form_state->getValue('header_in_house_lobbying'))
      ->set('in_house_lobbying_list', $form_state->getValue('in_house_lobbying_list'))
      ->set('in_house_lobbying_single', $form_state->getValue('in_house_lobbying_single'))
      ->set('header_consultant_activities', $form_state->getValue('header_consultant_activities')) 
      ->set('consultant_activities', $form_state->getValue('consultant_activities'))
      ->set('consultant_activity', $form_state->getValue('consultant_activity'))
      ->set('current_user_info', $form_state->getValue('current_user_info'))
      ->set('all_activities', $form_state->getValue('all_activities'))
      ->set('header_all_activities', $form_state->getValue('header_all_activities'))
      ->set('header_review_form', $form_state->getValue('header_review_form'))
      ->set('review_checkbox_acept', $form_state->getValue('review_checkbox_acept'))
      ->set('header_all_organizations', $form_state->getValue('header_all_organizations'))
      ->set('all_organizations', $form_state->getValue('all_organizations'))
      ->set('get_user_by_id', $form_state->getValue('get_user_by_id'))
      ->set('get_node_id', $form_state->getValue('get_node_id')) 
      ->set('get_comments', $form_state->getValue('get_comments'))
      ->set('header_commissioner_activities', $form_state->getValue('header_commissioner_activities'))
      ->set('commissioner_activities', $form_state->getValue('commissioner_activities'))
      ->set('get_new_messages', $form_state->getValue('get_new_messages'))
      ->set('search_in_house_activities', $form_state->getValue('search_in_house_activities'))
      ->set('search_consultant_activities', $form_state->getValue('search_consultant_activities'))

      ->set('consultant_content_instruct', $form_state->getValue('consultant_content_instruct'))
      ->set('in_house_content_instruct', $form_state->getValue('in_house_content_instruct'))

      ->save();
    parent::submitForm($form, $form_state);
  }
}
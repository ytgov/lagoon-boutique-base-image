<?php

namespace Drupal\search_api_field_map\Plugin\search_api\processor\Property;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Processor\ConfigurablePropertyBase;

/**
 * Defines a "site name" property.
 *
 * @see \Drupal\search_api\Plugin\search_api\processor\SiteName
 */
class SiteNameProperty extends ConfigurablePropertyBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'site_name' =>  [\Drupal::config('system.site')->get('name')],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(FieldInterface $field, array $form, FormStateInterface $form_state) {
    $configuration = $field->getConfiguration();
    $form['#attached']['library'][] = 'search_api/drupal.search_api.admin_css';

    $form['site_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site Name'),
      '#description' => $this->t('The name of the site from which this content originated. This can be useful if indexing multiple sites with a single search index.'),
      '#default_value' => $configuration['site_name'],
      '#required' => TRUE,
    ];

    if ($this->useDomain()) {
      $form['#tree'] = TRUE;
      $form['domain'] = ['#type' => 'container'];
      $storage = \Drupal::service('entity_type.manager')->getStorage('domain');
      $domains = $storage->loadMultiple();
      foreach ($domains as $domain) {
        $form['domain'][$domain->id()] = [
          '#type' => 'textfield',
          '#title' => $this->t('%domain Domain Label', ['%domain' => $domain->label()]),
          '#description' => t('Map the Domain to a custom label for search.'),
          '#default_value' => !empty($configuration['domain'][$domain->id()]) ? $configuration['domain'][$domain->id()] : $domain->label(),
          '#required' => FALSE,
        ];
      }
      $form['site_name']['#title'] = $this->t('Default site name');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(FieldInterface $field, array &$form, FormStateInterface $form_state) {
    $values = [
      'site_name' => $form_state->getValue('site_name'),
    ];
    if ($domains = $form_state->getValue('domain')) {
      foreach ($domains as $id => $value) {
        $values['domain'][$id] = $value;
      }
    }
    $field->setConfiguration($values);
  }

  /**
   * Whether to use the values from Domain.
   *
   * @return bool
   */
  protected function useDomain() {
    return defined('DOMAIN_ADMIN_FIELD');
  }

}

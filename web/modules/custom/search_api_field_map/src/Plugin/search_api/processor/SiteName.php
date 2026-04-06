<?php

namespace Drupal\search_api_field_map\Plugin\search_api\processor;

use Drupal\Core\Entity\EntityInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\search_api_field_map\Plugin\search_api\processor\Property\SiteNameProperty;


/**
 * Adds the site name to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "site_name",
 *   label = @Translation("Site name"),
 *   description = @Translation("Adds the site name to the indexed data."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class SiteName extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Site Name'),
        'description' => $this->t('The name of the site from which this content originated.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
        'is_list' => TRUE,
      ];
      $properties['site_name'] = new SiteNameProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath($item->getFields(), NULL, 'site_name');
    if ($this->useDomain()) {
      $entity = $item->getOriginalObject()->getValue();
      if ($entity instanceof EntityInterface) {
        $this->addDomainName($item, $entity, $fields);
      }
    }
    else {
      foreach ($fields as $field) {
        $site_name = $field->getConfiguration()['site_name'];
        $field->addValue($site_name);
      }
    }
  }

  /**
   * Whether to use the canonical value from Domain Source.
   *
   * @return bool
   */
  protected function useDomain() {
    return defined('DOMAIN_ADMIN_FIELD');
  }

  /**
   * Process site names for Domains.
   *
   * @param Drupal\search_api\Item\ItemInterface $item
   *   The item being indexed.
   * @param Drupal\Core\Entity\EntityInterface $entity
   *   The original entity of the item.
   * @param array $fields
   *   The fields being processed for this item.
   *
   * @TODO: Allow this value to be configured.
   */
  protected function addDomainName(ItemInterface $item, EntityInterface $entity, array $fields) {
    $manager = \Drupal::service('domain_access.manager');
    $urls = $manager->getContentUrls($entity);
    if (!empty($urls)) {
      $storage = \Drupal::service('entity_type.manager')->getStorage('domain');
      $domains = $storage->loadMultiple();
      foreach ($fields as $field) {
        foreach ($urls as $domain_id => $url) {
          if (isset($domains[$domain_id])) {
            $site_name = !empty($field->getConfiguration()['domain'][$domains[$domain_id]->id()]) ?
              $field->getConfiguration()['domain'][$domains[$domain_id]->id()] :
              $domains[$domain_id]->label();
          }
          else {
            $site_name = $field->getConfiguration()['site_name'];
          }
          if (empty($site_name)) {
            $site_name = \Drupal::config('system.site')->get('name');
          }
          $field->addValue($site_name);
        }
      }
    }
    else {
      foreach ($fields as $field) {
        $site_name = $field->getConfiguration()['site_name'];
        if (empty($site_name)) {
          $site_name = \Drupal::config('system.site')->get('name');
        }
        $field->addValue($site_name);
      }
    }
  }

}

<?php

namespace Drupal\search_api_field_map\Plugin\search_api\processor;

use Drupal\Core\Entity\EntityInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\search_api_field_map\Plugin\search_api\processor\Property\UrlsProperty;


/**
 * Adds the Urls to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "search_api_urls",
 *   label = @Translation("Urls"),
 *   description = @Translation("Adds the Urls to the indexed data."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class Urls extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Urls'),
        'description' => $this->t('URLs pointing to this node on all sites containing.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
        'is_list' => TRUE,
      ];
      $properties['search_api_urls'] = new UrlsProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath($item->getFields(), NULL, 'search_api_urls');
    $use_domain = FALSE;
    if ($this->useDomainAccess()) {
      $entity = $item->getOriginalObject()->getValue();
      if ($entity instanceof EntityInterface) {
        $manager = \Drupal::service('domain_access.manager');
        $urls = $manager->getContentUrls($entity);
      }
    }
    else {
      $url = $item->getDatasource()->getItemUrl($item->getOriginalObject());
      if ($url) {
        $urls = [$url->setAbsolute()->toString()];
      }
    }
    if (!empty($urls)) {
      foreach ($fields as $field) {
        foreach ($urls as $url) {
          $field->addValue($url);
        }
      }
    }
  }

  /**
   * Whether to use the canonical value from Domain Source.
   *
   * @return bool
   */
  protected function useDomainAccess() {
    return defined('DOMAIN_ACCESS_FIELD');
  }
}

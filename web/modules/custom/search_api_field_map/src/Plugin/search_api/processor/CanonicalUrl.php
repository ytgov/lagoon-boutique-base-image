<?php

namespace Drupal\search_api_field_map\Plugin\search_api\processor;

use Drupal\Core\Entity\EntityInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\search_api_field_map\Plugin\search_api\processor\Property\CanonicalUrlProperty;


/**
 * Adds the Urls to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "search_api_canonical_url",
 *   label = @Translation("Canonical URL"),
 *   description = @Translation("Adds a canonical flag to the indexed data."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class CanonicalUrl extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Canonical URL'),
        'description' => $this->t('Preferred URL for this content'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['search_api_canonical_url'] = new CanonicalUrlProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath($item->getFields(), NULL, 'search_api_canonical_url');
    $use_source = FALSE;
    if ($this->useDomainAccess()) {
      $entity = $item->getOriginalObject()->getValue();
      if ($entity instanceof EntityInterface) {
        $source = domain_source_get($entity);
      }
      if (empty($source)) {
        foreach ($fields as $field) {
          $field->addValue('');
        }
        $use_source = TRUE;
      }
    }
    if (!$use_source) {
      $url = $item->getDatasource()->getItemUrl($item->getOriginalObject());
      if ($url) {
        $url = $url->setAbsolute()->toString();
        foreach ($fields as $field) {
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
    return defined('DOMAIN_SOURCE_FIELD');
  }
}

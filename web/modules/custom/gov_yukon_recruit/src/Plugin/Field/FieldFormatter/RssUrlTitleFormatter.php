<?php

namespace Drupal\gov_yukon_recruit\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Url;
use Drupal\gov_yukon_recruit\Rss\RssParser;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Format a RSS URL as a list of linked titles.
 *
 * @FieldFormatter(
 *   id = "rss_url_title_formatter",
 *   label = @Translation("RSS URL Title Formatter"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class RssUrlTitleFormatter extends FormatterBase {

  /**
   * Rss parser.
   *
   * @var \Drupal\gov_yukon_recruit\Rss\RssParser
   */
  protected $rssParser;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('gov_yukon_recruit.rss.parser'),
      $container->get('logger.factory')
    );
  }

  /**
   * Constructor.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\gov_yukon_recruit\Rss\RssParser $rss_parser
   *   The RSS parser.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    RssParser $rss_parser,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->rssParser = $rss_parser;
    $this->logger    = $logger_factory->get('rss_url');
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode = NULL) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $elements[$delta]['#theme'] = 'rss_url_titles';
      try {
        $data = $this->rssParser->getTitlesAndLinks($item->getUrl()->toString());
      }
      catch (\Exception $e) {
        $data = [];
        $this->logger->notice($e->getMessage());
      }
      foreach ($data as $rss_item) {
        $elements[$delta]['#content'][] = [
          '#type'    => 'link',
          '#title'   => $rss_item['title'],
          '#url'     => Url::fromUri($rss_item['url']),
          '#options' => [],
        ];
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    $elements = parent::view($items, $langcode);

    $elements['#cache']['max-age'] = 300;

    return $elements;
  }

}

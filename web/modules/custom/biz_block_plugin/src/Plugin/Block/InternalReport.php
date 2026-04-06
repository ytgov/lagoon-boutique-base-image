<?php
namespace Drupal\biz_block_plugin\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\biz_webforms\BizWebformController;
use Drupal\biz_block_plugin\Controller\GeneralFunctions;
use Symfony\Component\HttpFoundation\RequestStack;

/**
  * Provides a custom block.
  *
  * @Block(
  *   id = "internal_report_block",
  *   admin_label = @Translation("Internal Report block"),
  *   category = @Translation("Bizont custom block")
  * )
*/
class InternalReport extends BlockBase implements BlockPluginInterface{

    /**
     * Display all activity information
    */
    public function build(){
      $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
      //api/headers-for-public-report
      $header_activities_endpoint = 'api/headers-for-internal-report';//\Drupal::config('biz_block_plugin.settings')->get('header_activities');
      $header_response = GeneralFunctions::getHeadersTable($header_activities_endpoint);
      $content = [];
      $content[] =array(
              '#theme' => 'custom_table',
              '#header' => $header_response['header'],
              '#rows' => [],
              '#empty'=> t('No lobbying activities have been added.'),
              '#caption' => t('Lobbying activities you are associated with'),
              '#attributes' => ['id' => 'internal-report', 'class' => 'table-orange-header general-lobbyist-tables'], 
              '#sort_column' => '1',
              '#sort_order' => 'desc',
              '#language' => $language,
              '#url_api' =>  '/'. $language . '/get-report/internal-report',
              '#add_html_top' => '<div class="export-button"><a href="/'. $language . '/exports/activities/internal">Export</a></div>'
            );  
      return $content;  
    }
    /****
        * Disable caching for this block.
        */
    public function getCacheMaxAge() {
        return 0;
    }
}
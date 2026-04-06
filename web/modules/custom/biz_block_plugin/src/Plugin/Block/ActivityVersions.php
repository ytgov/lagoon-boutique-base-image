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
  *   id = "activity_versions_block",
  *   admin_label = @Translation("Activity versions block"),
  *   category = @Translation("Bizont custom block")
  * )
*/
class ActivityVersions extends BlockBase implements BlockPluginInterface{
    /**
     * Display all activity information
    */
    public function build(){
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $base_url = \Drupal::config('biz_lobbyist_registration.settings')->get('base_url');
        $param = \Drupal::request()->query->all();
        $content = [];
        //Get activity ID
        $id = isset($param['id']) ? $param['id'] : "0";
        $request_options = BizWebformController::get_request_options(FALSE);
        $url_get_endpoint = $base_url . $language .'/submission-history/' . $id . '/0/0?_format=json' ;
        $versions_endpoint = BizWebformController::execute_external_api($url_get_endpoint, [], "GET", $request_options);
        $changes = json_decode(json_decode($versions_endpoint['message'], TRUE), TRUE);
        $header_rev_endpoint = "api/headers-for-revisions";
        $header_response = GeneralFunctions::getHeadersTable($header_rev_endpoint);  
        $content[] = array(
              '#theme' => 'custom_table',
              '#header' => $header_response['header'],
              '#rows' => $changes,
              '#empty'=> t('No results found'),
              '#attributes' => ['id' => 'organizations', 'class' => 'table-orange-header general-lobbyist-tables']
            );
        return  $content;
    }
    /****
        * Disable caching for this block.
        */
    public function getCacheMaxAge() {
        return 0;
    }
}
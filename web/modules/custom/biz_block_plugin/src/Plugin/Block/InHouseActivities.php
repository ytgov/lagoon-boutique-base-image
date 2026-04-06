<?php
namespace Drupal\biz_block_plugin\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\biz_webforms\BizWebformController;
use Drupal\biz_block_plugin\Controller\GeneralFunctions;


/**
  * Provides a custom block.
  *
  * @Block(
  *   id = "inhouse_activities_block",
  *   admin_label = @Translation("In-house Activities block"),
  *   category = @Translation("Bizont custom block")
  * )
  */
class InHouseActivities extends BlockBase implements BlockPluginInterface{

    /****
        * Display all the user own in-house activities
        */
    public function build() {
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $email = \Drupal::currentUser()->getEmail();
        $header_activities_endpoint = \Drupal::config('biz_block_plugin.settings')->get('header_activities');
        $activities_endpoint = \Drupal::config('biz_block_plugin.settings')->get('in_house_activities');
        $header_response = GeneralFunctions::getHeadersTable($header_activities_endpoint);
        $activities_response = GeneralFunctions::getAllData($activities_endpoint, TRUE);  

        $activity_rows = [];
        $activity_row = [];
    
        if($header_response && $activities_response){
            $rows_response = json_decode($activities_response['message']);
            $activity_rows = GeneralFunctions::generateTableRows($header_response, $rows_response);
            $content[] =array(
              '#theme' => 'custom_table',
              '#header' => $header_response['header'],
              '#rows' => $activity_rows,
              '#empty'=> t('No lobbying activities have been added.'),
              '#caption' => t('Lobbying activities you are associated with'),
              '#attributes' => ['id' => 'in-house-activities', 'class' => 'table-orange-header general-lobbyist-tables'], 
              '#sort_column' => '1',
              '#sort_order' => 'desc',
              '#language' => $language
            ); 
        }
        if(empty($activity_rows)){
          $content_instruct = \Drupal::config('biz_block_plugin.settings')->get('in_house_content_instruct');
          if(isset($content_instruct['value']) && !empty($content_instruct['value'])){
            \Drupal::messenger()->addWarning(t($content_instruct['value']));
          }
        }
        return $content;
    }
    
    /****
        * Disable caching for this block.
        */
    public function getCacheMaxAge() {
        return 0;
    }
}
  
<?php
    
namespace Drupal\biz_block_plugin\Plugin\Block;
  
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\biz_webforms\BizWebformController;
use Drupal\biz_block_plugin\Controller\GeneralFunctions;

/**
  * Provides a custom block with the organizations
  * 
  * @Block(
  *   id = "search_organization_block",
  *   admin_label = @Translation("Search Organization block"),
  *   category = @Translation("Bizont custom block")
  * )
  */
class SearchOrganization extends BlockBase implements BlockPluginInterface{

    /****
        * Display all the organizations if at least one of then activities have status different from pending 
        */
    public function build() {
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $content = [];
        //Get params if exist and needs to be filtered
        $param = \Drupal::request()->query->all();
        $search_organization = trim(isset($param['organization']) ? $param['organization'] : '');
        $query_param = "";
        $url_base = \Drupal::config('biz_lobbyist_registration.settings')->get('base_url');
        $header_org_endpoint = \Drupal::config('biz_block_plugin.settings')->get('header_all_organizations');
        $org_endpoint = \Drupal::config('biz_block_plugin.settings')->get('all_organizations');

        $header_response = GeneralFunctions::getHeadersTable($header_org_endpoint);     
        $org_endpoint =  $language ."/organizations/";
        $query_param = !empty($search_organization) ? $search_organization: 'all' ;
        $url = $url_base . $org_endpoint . $query_param. "?_format=json" ;
        $request_options = BizWebformController::get_request_options(FALSE);
        $org_response = BizWebformController::execute_external_api($url, [], 'GET', $request_options);
        $org_rows = [];
        if($header_response && $org_response){
            if (!empty(json_decode($org_response['message']))) {
                $rows_response = json_decode(json_decode($org_response['message']));
                if(!empty($rows_response) && is_array($rows_response)){
                    $temp = array_unique(array_column($rows_response, 'organization_name')); 
                    $rows_response = array_intersect_key($rows_response, $temp);
                    $org_rows = GeneralFunctions::generateTableRows($header_response, $rows_response);
                }
            }

            $content[] = array(
              '#theme' => 'custom_table',
              '#header' => $header_response['header'],
              '#rows' => $org_rows,
              '#empty'=> t('No results found'),
              '#attributes' => ['id' => 'organizations', 'class' => 'table-orange-header general-lobbyist-tables'],
              '#cache' => array('max-age' => 0),
            );
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
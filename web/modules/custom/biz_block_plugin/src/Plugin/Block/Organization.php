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
use Drupal\Core\Database\Database;
use Drupal\Core\Database\ConnectionNotDefinedException;
use Drupal\Core\Database\DatabaseExceptionWrapper;

/**
  * Provides a custom block.
  *
  * @Block(
  *     id = "organization_view_block",
  *     admin_label = @Translation("Organization view block"),
  *     category = @Translation("Bizont custom block")
  * )
  */
class Organization extends BlockBase implements BlockPluginInterface{

    /****
        * Display the public information for specific organization
        */
    public function build(){
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $url_base = \Drupal::config('biz_lobbyist_registration.settings')->get('base_url');
        $header_activities_endpoint = \Drupal::config('biz_block_plugin.settings')->get('header_activities');
        $request_options = BizWebformController::get_request_options(FALSE);
        $content = [];
        $email = \Drupal::currentUser()->getEmail();

        //Get params if exist and needs to be filtered
        $param = \Drupal::request()->query->all();
        $id_organization = trim(isset($param['id']) ? $param['id'] : '');
        //Generate the URL for get backend user information
        $url = $url_base .'public/api/user?id='. $id_organization;
        //Get organization data
        $get_organization = BizWebformController::execute_external_api($url, [], "GET", $request_options);

        if($get_organization['code'] == 400){
            \Drupal::logger('Organization')->error(json_encode($get_organization));
            \Drupal::messenger()->addMessage(t('The website encountered an unexpected error. Please try again later.'), 'error');
            return FALSE;
        }
        $organization = isset(json_decode($get_organization['message'])[0]) ? json_decode($get_organization['message'])[0] : [];

        if(isset($organization->roles_target_id)){
            if(strpos($organization->roles_target_id , 'in_house_lobbyist') !== FALSE){
                $content[] =['#theme' => 'in_house_account_info',
                              '#organization' => $organization,
                              "#link_edit_org" => "",
                              "#description" => t('In-house lobbyist')
                            ]; 
                $activities_endpoint = \Drupal::config('biz_block_plugin.settings')->get('search_in_house_activities');
                $lobbyist_endpoint = 'public/api/in-house-lobbyist-list';
                $header_lobbyist_endpoint = \Drupal::config('biz_block_plugin.settings')->get('header_in_house_lobbying');
                $header_lobbyist = GeneralFunctions::getHeadersTable($header_lobbyist_endpoint, FALSE);
                //Generate the URL for get backend user information
                $url = $url_base . $language ."/" . $lobbyist_endpoint . '?_format=json&id='. $id_organization;
                $lobbyist_rows = BizWebformController::execute_external_api($url, [], "GET", $request_options);
                if($lobbyist_rows['code'] == 400){
                    \Drupal::logger('Organization')->error(json_encode($lobbyist_rows));
                    \Drupal::messenger()->addMessage(t('The website encountered an unexpected error. Please try again later.'), 'error');
                    return FALSE;
                }
                $lobbyist_rows = json_decode($lobbyist_rows['message']);
                $lobbyist_rows = GeneralFunctions::generateTableRows($header_lobbyist, $lobbyist_rows);

                if(empty($edit_organization) || $edit_organization === NULL){
                    unset($header_lobbyist['header'][2]);
                    unset($header_lobbyist['fields'][2]);
                    unset($header_lobbyist['header'][3]);
                    unset($header_lobbyist['fields'][3]);
                    foreach ($lobbyist_rows as $key => $value) {
                        unset($lobbyist_rows[$key]['data']->email);
                        unset($lobbyist_rows[$key]['data']->telephone);
                    }
                }

                $content[] = array(
                    '#theme' => 'custom_table',
                    '#header' => $header_lobbyist['header'],
                    '#rows' => $lobbyist_rows,
                    '#empty'=> t('This organization does not have any additional lobbyists.'),
                    '#caption' => t('In-house lobbyist'),
                    '#attributes' => ['id' => 'org-lobbyist', 'class' => 'table-purple-header general-lobbyist-tables']
                );
            }
            elseif(strpos($organization->roles_target_id , 'consultant_lobbyist') !== FALSE){
                $content[] = array( '#theme' => 'consultant_account_info', 
                                    '#organization'    => $organization,    
                                    "#link_edit_org" => "", 
                                    '#description' => 'Organization');
                $activities_endpoint = \Drupal::config('biz_block_plugin.settings')->get('search_consultant_activities');
            }
        }
        $header_response = GeneralFunctions::getHeadersTable($header_activities_endpoint, 'header-orange');
        //Generate the URL for get backend activities
        $url = $url_base . $language ."/". $activities_endpoint . "?_format=json&id=" . $id_organization;
        $activities_response = BizWebformController::execute_external_api($url, [], "GET", $request_options);
        if($activities_response['code'] == 400){
            \Drupal::logger('Organization')->error(json_encode($activities_response));
            \Drupal::messenger()->addMessage(t('The website encountered an unexpected error. Please try again later.'), 'error');
            return FALSE;
        }
        $activity_rows = [];
        $rows_response = json_decode($activities_response['message']);
        $activity_rows = GeneralFunctions::generateTableRows($header_response, $rows_response);    
        $tableHeader = $header_response['header'];
        $content[] = ['#theme' => 'custom_table',
                      '#header' => $tableHeader,
                      '#rows' => $activity_rows,
                      '#empty'=> t('This company doesn’t have activities.'),
                      '#caption' => t('Activities'),
                      '#attributes' => ['id' => 'org-activities', 'class' => 'table-purple-header general-lobbyist-tables']
        ];
        return $content;
    }

    /****
        * Disable caching for this block.
        */
    public function getCacheMaxAge() {
        return 0;
    }   
}

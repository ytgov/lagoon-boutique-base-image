<?php

namespace Drupal\biz_block_plugin\Controller;

use Drupal\biz_webforms\BizWebformController;
use Drupal\Component\Render\FormattableMarkup; 
use Drupal\Core\Render\Markup;
use Symfony\Component\HttpFoundation\Response;

/****
    * Functions for display activities
    */
class GeneralFunctions{

    /****
        *  Get specific information using user email
        */
    public static function getAllData($endpoint, $basic_auth = FALSE){
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId(); 
        $email = urlencode(\Drupal::currentUser()->getEmail());
        $url_base = \Drupal::config('biz_lobbyist_registration.settings')->get('base_url');
        if(!empty($url_base) && !empty($endpoint) && !empty($email)){
            $url = $url_base . $language . "/" .$endpoint    . "?_format=json&email=".$email ;
            $data = [];
            $headers = [];
            $method = "GET";           
            $request_options = BizWebformController::get_request_options($basic_auth);
            
            $response = BizWebformController::execute_external_api($url, $data, "GET", $request_options);
            if($response['code'] !== "400"){
                return $response;
            }
            \Drupal::logger('GeneralFunctions')->error($endpoint . ': ' . json_encode($response['message']));
            return FALSE;
        }
    }

    /****
        * Get specific submission by ID
        */
    public static function getSubmission($endpoint, $id, $basic_auth = FALSE, $language = ''){
        if(empty($language)){
           $language = \Drupal::languageManager()->getCurrentLanguage()->getId(); 
        }
        $email = \Drupal::currentUser()->getEmail();
        $url_base = \Drupal::config('biz_lobbyist_registration.settings')->get('base_url');
        if(!empty($url_base) && !empty($endpoint) && !empty($id)){
            $url = $url_base . $language . "/" . $endpoint    . "?_format=json&id=".$id ;
            $request_options = BizWebformController::get_request_options($basic_auth);
            $response = BizWebformController::execute_external_api($url, [], "GET", $request_options);
            if(isset($response['code']) && $response['code'] !== "400"){
                return $response;
            }
            \Drupal::logger('GeneralFunctions')->error($endpoint . ': ' . json_encode($response['message']));
            return FALSE;
        }
    }

    /****
        * Getting titles for table headers and name of fields
        */
    public static function getHeadersTable($endpoint, $edit = TRUE){
        $url_base = \Drupal::config('biz_lobbyist_registration.settings')->get('base_url');
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $header = [];
        $fields = [];
        if(!empty($url_base) && !empty($endpoint)){
            $url = $url_base . $endpoint    . '?_format=json&language=' . $language ;
            $request_options = BizWebformController::get_request_options(FALSE);
            $response = BizWebformController::execute_external_api($url, [], "GET", $request_options);
            if($response['code'] !== "400"){
                $taxonony= json_decode($response['message']);
                if(is_array($taxonony)){
                    $count = 0;
                    foreach($taxonony as $taxonony_value){
                        $taxonony_value->title = $taxonony_value->title == "empty" ? "" : $taxonony_value->title;                            
                        $field = trim(strip_tags($taxonony_value->field));
                        if($field !== 'actions' || ($edit &&    $field === 'actions') ){
                            $header [$count]['data']= trim(strip_tags($taxonony_value->title));
                            $header[$count]['field'] = $field;
                            $header[$count]['data-data'] = $field;
                            $fields[] = trim(strip_tags($taxonony_value->field));
                        }
                        $count++;
                    }
                    return array('header' => $header, 'fields' =>$fields);
                }
            }
            \Drupal::logger('GeneralFunctions')->error($endpoint . ': ' . json_encode($response['message']));
        }
        else{
            \Drupal::logger('GeneralFunctions')->error('Check the Lobbyist configuration: admin/config/biz_lobbyist_registration');
        }
        return FALSE; 
    }

    /****
        * Create the structure for generating tables
        */
    public static function generateTableRows($header_info, $rows){
        $activity_rows = [];
        if(is_array($rows) && !empty($rows)){
            foreach($rows as $row){
             $activity_row = new \StdClass();
                foreach($header_info['fields'] as $field){
                    $row->{$field} = isset($row->{$field}) ? $row->{$field} : "" ;
                    if(strpos($row->{$field}, "</a>") === FALSE && strpos($row->{$field}, "</div>") === FALSE){
                        $activity_row->{$field} = $row->{$field};
                    }else{
                        $activity_row->{$field} = Markup::create($row->{$field}) ;
                    }
                }
                $activity_rows[] = array ('data' => $activity_row);
            }
        }
        return $activity_rows;                
    }
    public function getActivityVersion($sid, $date, $count, $current_count){
      $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
      $base_url = \Drupal::config('biz_lobbyist_registration.settings')->get('base_url');
      $request_options = BizWebformController::get_request_options(FALSE);
      $url_get_endpoint = $base_url . $language .'/submission-history/' . $sid . '/'.$date. '/'.$count .'?_format=json' ;
      $versions_endpoint = BizWebformController::execute_external_api($url_get_endpoint, [], "GET", $request_options);
      $changes = json_decode(json_decode($versions_endpoint['message'], TRUE), TRUE);
      $keys = array_keys($changes['all-info']);
      $changes['old_version']['count'] = $count;
      $changes['current']['count']=$current_count;
      $renderable = [
        '#theme' => 'modal_compare_versions_consultant',
        '#changes' =>  $changes['old_version'], '#current_version' => $changes['current'], '#type_lobbyist' => $changes['webform_id']

      ];
      $output = \Drupal::service('renderer')->renderRoot($renderable);
      $response = new Response();
      $response->setContent($output);
      return $response;
    }
    public function getReport($type){
      $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
      $param = \Drupal::request()->query->all();
      $start = isset($param['start']) ? $param['start'] : "0";
      $draw = isset($param['draw']) ? $param['draw'] : "0";
      $base_url = \Drupal::config('biz_lobbyist_registration.settings')->get('base_url');
      $order_by = isset($param['order']) && isset($param['columns'][$param['order'][0]["column"]]["data"]) ?  $param['columns'][$param['order'][0]["column"]]["data"] : '';
      $order_dir =  isset($param["order"][0]['dir']) ? $param["order"][0]['dir'] : 'ASC';

      if($type == 'public-report'){
        $request_options = BizWebformController::get_request_options(FALSE);
        $url_get_endpoint = $base_url . $language .'/reports/public_report?_format=json&page=0&start='.$start. '&draw=' . $draw. '&orderby=' . $order_by . '&orderdir=' . $order_dir;
      }else{
        $request_options = BizWebformController::get_request_options(TRUE);
        $url_get_endpoint = $base_url . $language .'/reports/internal_report?_format=json&page=0&start='.$start. '&draw=' . $draw . '&orderby=' . $order_by . '&orderdir=' . $order_dir;
      }
      $rows = BizWebformController::execute_external_api($url_get_endpoint, [], "GET", $request_options);
      $data = json_decode($rows['message'], TRUE);
      $response = new Response();
      $response->setContent(json_encode($data));
      return $response;
    }
}
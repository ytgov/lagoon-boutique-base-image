<?php
namespace Drupal\biz_sync_users;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\ConnectionNotDefinedException;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\external_db_login\ExternalDBLoginService;


/**
* 
*/
class GeneralCustomController {
  
  /**
   * Execute the api
   */
  static function get_endpoint($url, $data = array(), $method, $headers = array() ) {

    if($method !== "GET" && empty($headers)){
      $front_base_url = \Drupal::config('biz_business_rules.settings')->get('front_base_url');
      $response = \Drupal::httpClient()->get(($front_base_url . 'session/token'));
      $csrf = (string) $response->getBody();
      $headers =  array('X-CSRF-Token' => $csrf, "Content-Type" =>"application/json");
    }
    //Creating a httpClient Object.
    $client = \Drupal::httpClient();
    try {
      $request_options = [];
      if(!empty($headers)){
        $request_options =  array('headers' => $headers);
      }
      if(!empty($data)){
        $request_options['json'] = $data;
      }
      $response = $client->$method($url, $request_options);

      return ["code" => $response->getStatusCode(), "message" => $response->getBody()->getContents()];
    }
    catch (\Exception $e) {
      if ($e->hasResponse()) {
        $response = $e->getMessage();
        \Drupal::logger('GeneralCustomController')->error($url);
        \Drupal::logger('GeneralCustomController')->error(json_encode($e->getMessage()));          
        return ["code" => 400, "message" => $response];
        }
    }
  }
}

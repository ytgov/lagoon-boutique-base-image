<?php

namespace Drupal\biz_webform_rest\Plugin\rest\resource;

use Drupal\webform\Entity\Webform;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ModifiedResourceResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Creates a resource for retrieving webform elements.
 *
 * @RestResource(
 *   id = "biz_webform_rest_organizations",
 *   label = @Translation("Search Organizations"),
 *   uri_paths = {
 *     "canonical" = "/organizations/{word}"
 *   }
 * )
 */
class SearchOrganizations extends ResourceBase {

  /**
   * Responds to GET requests, returns organizations.
   *
   * @param string $word
   *   Contains this word in any activity.
   *
   * @return \Drupal\rest\ResourceResponse
   *   HTTP response object containing webform elements.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws HttpException in case of error.
   */
  public function get($word) {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();  
    $host = \Drupal::request()->getSchemeAndHttpHost();
    global $base_url;
    $host = $base_url;
    if($word == 'all'){
      $url =  $host .'/' . $language . "/api/search-organizations?_format=json"; 
      \Drupal::logger("SearchOrganizations")->notice(($url));
      $response = self::get_endpoint($url);
    }
    else{
      $url =  $host .'/' . $language . "/api/search-org?_format=json&combine=" . $word; 
      \Drupal::logger("SearchOrganizations")->notice(($url));
      $response = self::get_endpoint($url);
    }
    return new ModifiedResourceResponse($response['message']);
  }
  /**
    * Execute endpoint
    */
  static function get_endpoint($url) { 
    //Creating a httpClient Object.
    $client = \Drupal::httpClient();
    try {
      $request_options = [];
      $response = $client->GET($url, $request_options);
      return ["code" => $response->getStatusCode(), "message" => $response->getBody()->getContents()];
    }
    catch (\Exception $e) {
     if ($e->hasResponse()) {
        $response = $e->getResponse()->getBody();
        return ["code" => 400, "message" => $response];
      }
    }
  }
}

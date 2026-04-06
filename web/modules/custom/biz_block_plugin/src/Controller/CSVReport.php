<?php

namespace Drupal\biz_block_plugin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\biz_webforms\BizWebformController;
/**
* Class CSVReport.
*
* @package Drupal\biz_block_plugin\Controller
*/
class CSVReport extends ControllerBase implements ContainerInjectionInterface {

 /**
  * Export a CSV of data.
  */
 public function build($type) {
   $url_get_endpoint = '';
   $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
   $base_url = \Drupal::config('biz_lobbyist_registration.settings')->get('base_url');
   // Start using PHP's built in file handler functions to create a temporary file.
   $handle = fopen('php://temp', 'w+');
   switch($type){
     case 'internal':
       $header_activities_endpoint = 'api/headers-for-internal-report';
       $request_options = BizWebformController::get_request_options(TRUE);
       $url_get_endpoint = $base_url . $language .'/api/export-internal-report?_format=json';
     break;
     case 'public':
     default:
       $header_activities_endpoint = 'api/headers-for-public-report';
       $request_options = BizWebformController::get_request_options(FALSE);
       $url_get_endpoint = $base_url . $language .'/api/export-public-report?_format=json';
       $type = 'public';
     break;
   }
   if(!empty($url_get_endpoint)){
      $header_response = GeneralFunctions::getHeadersTable($header_activities_endpoint);
      $header_keys = array_column($header_response['header'], 'field');
      $header = array_column($header_response['header'], 'data');
      // Add the header as the first line of the CSV.
      fputcsv($handle, $header);
      // Find and load all of the Article nodes we are going to include
      $rows = BizWebformController::execute_external_api($url_get_endpoint, [], "GET", $request_options);
      $rows = json_decode($rows['message'], TRUE);  
      // Iterate through the nodes.  We want one row in the CSV per Article.
      foreach ($rows as $row) {
       foreach($row as $key_id => $item){
          $row[$key_id] =  htmlspecialchars($item);
       }
        // Add the data we exported to the next line of the CSV>
        fputcsv($handle, array_values($row));
      }
      // Reset where we are in the CSV.
      rewind($handle);
     
      // Retrieve the data from the file handler.
      $csv_data = stream_get_contents($handle);
  
      // Close the file handler since we don't need it anymore.  We are not storing
      // this file anywhere in the filesystem.
      fclose($handle);
  
     // This is the "magic" part of the code.  Once the data is built, we can
     // return it as a response.
     $response = new Response();
  
     // By setting these 2 header options, the browser will see the URL
     // used by this Controller to return a CSV file called "article-report.csv".
     $response->headers->set('Content-Type', 'text/csv');
     $response->headers->set('Content-Disposition', 'attachment; filename="'.$type.'-report.csv"');
  
     // This line physically adds the CSV data we created 
     $response->setContent($csv_data);
  
     return $response;

   }
 }
}
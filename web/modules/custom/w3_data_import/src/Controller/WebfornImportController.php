<?php

namespace Drupal\w3_data_import\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Drupal\user\Entity\User;
use Drupal\biz_webforms\BizWebformController;
use Drupal\webform\WebformSubmissionForm;
use Drupal\oauth2_client\Service\OAuth2Client;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\Core\Database\Database;

class WebfornImportController extends ControllerBase {

  public function content() {
    $site_link = \Drupal::config('w3_data_import.theme_settings')->get('site_basic_path');
    if (empty($site_link)) {
        echo "Please add the site basic path to continue data import"; die;
    }
    $http_client = \Drupal::service('http_client');

      try {
        // Send a GET request to the URL.
        $response = $http_client->get($site_link . '/api/webform/add_a_lobbying_activity_consulta');
    
        // Get the body of the response.
        $body = $response->getBody();
        $data = json_decode($body);
        
      }
      catch (RequestException $e) {
        // Log error if the request fails.
        \Drupal::logger('my_module')->error('Error fetching data from @url: @message', [
          '@url' => $url,
          '@message' => $e->getMessage(),
        ]);
        return NULL;
      }

      foreach ($data as $webform) {
          $final_data = (array) $webform->data;
          unset($final_data['form_build_id']);
          unset($final_data['form_id']);
          unset($final_data['form_token']);
          unset($final_data['op']);
          unset($final_data['preview_prev']);
          unset($final_data['submit']);
          unset($final_data['webform_preview']);
          unset($final_data['webform_start']);

          $topics = $final_data['which_topic_do_you_want_to_lobby_government_about_'];
          $all_topics = [];
          foreach ($topics as $topic) {
              $query = \Drupal::entityQuery('taxonomy_term')
                ->condition('name', $topic)
                ->accessCheck(TRUE)
                ->condition('vid', 'lobby_s_topics')
                ->range(0, 1); // Return only one term if it exists.
              
              $term_ids = reset($query->execute());
              $all_topics[] =  $term_ids;
          }
          
          $final_data['which_topic_do_you_want_to_lobby_government_about_'] = $all_topics;
          
          $plans = $final_data['who_are_you_lobbying_or_plan_to_lobby_'];
          $all_plan = [];
          foreach ($plans as $plan) {
              $query = \Drupal::entityQuery('taxonomy_term')
                ->condition('name', $plan)
                ->accessCheck(TRUE)
                ->condition('vid', 'lobbyist_s_portfolios')
                ->range(0, 1); // Return only one term if it exists.
              
              $term_ids = reset($query->execute());
              $all_plan[] =  $term_ids;
          }
          
          $final_data['who_are_you_lobbying_or_plan_to_lobby_'] = $all_plan;
          
           $connection = Database::getConnection();
          $query = $connection->select('users_field_data', 'u')
            ->fields('u', ['uid'])  // Get the 'uid' field (user ID)
            ->condition('u.name', $final_data['uid'])
            ->range(0, 1);  // Limiting the result to 1 record
        
          // Execute the query and fetch the result.
          $result = $query->execute()->fetchAssoc();
  
          // Check if the user exists and return the user ID.
          if ($result) {
            // We assume that the result will contain only one user.
            $uid = $result['uid'];
            $final_data['user_uid'] = $uid;
          }
          else {
            // Return NULL if the user is not found.
            $uid = '';
          }
          
          $address = (array) $final_data['custom_address'];
          $final_data['custom_address'] = $address;

          // Create submission
            $submission = WebformSubmission::create([
              'webform_id' => 'add_a_lobbying_activity_consulta',
              'entity_type' => NULL,
              'entity_id' => NULL,
              'uid' => $uid,
              'created' => strtotime($webform->created),
              //'changed' => strtotime($webform->changed),
              'data' => $final_data,
            ]);
            
            $submission->save();
            $connection = Database::getConnection();

            $connection->update('webform_submission')
              ->fields([
                'changed' => strtotime($webform->changed), // Your new timestamp (integer)
                'Completed' => strtotime($webform->changed), // Your new timestamp (integer)
              ])
              ->condition('webform_id', 'add_a_lobbying_activity_consulta')
              ->condition('serial', $submission->serial->value) // Submission ID
              ->execute();
            
            //dump($submission); die;
      }
      echo "all submission imported;"; die;
      
  }
  
  
  public function content_in_house() {
    $site_link = \Drupal::config('w3_data_import.theme_settings')->get('site_basic_path');
    if (empty($site_link)) {
        echo "Please add the site basic path to continue data import"; die;
    }
    $http_client = \Drupal::service('http_client');

      try {
        // Send a GET request to the URL.
        $response = $http_client->get($site_link . '/api/webform/add_a_lobbying_activity');
    
        // Get the body of the response.
        $body = $response->getBody();
        $data = json_decode($body);
        
      }
      catch (RequestException $e) {
        // Log error if the request fails.
        \Drupal::logger('my_module')->error('Error fetching data from @url: @message', [
          '@url' => $url,
          '@message' => $e->getMessage(),
        ]);
        return NULL;
      }
      
      foreach ($data as $webform) {
          $final_data = (array) $webform->data;
          $webform_id = 'add_a_lobbying_activity';
            $title = trim($final_data['title']); // The title value you're matching
           
            
                  unset($final_data['form_build_id']);
                  unset($final_data['form_id']);
                  unset($final_data['form_token']);
                  unset($final_data['op']);
                  unset($final_data['preview_prev']);
                  unset($final_data['submit']);
                  unset($final_data['webform_preview']);
                  unset($final_data['webform_start']);
        
                  $topics = $final_data['which_topic_do_you_want_to_lobby_government_about_'];
                  $all_topics = [];
                  foreach ($topics as $topic) {
                      $query = \Drupal::entityQuery('taxonomy_term')
                        ->condition('name', $topic)
                        ->accessCheck(TRUE)
                        ->condition('vid', 'lobby_s_topics')
                        ->range(0, 1); // Return only one term if it exists.
                      
                      $term_ids = reset($query->execute());
                      $all_topics[] =  $term_ids;
                  }
                  
                  $final_data['which_topic_do_you_want_to_lobby_government_about_'] = $all_topics;
                  
                  $plans = $final_data['who_are_you_lobbying_or_plan_to_lobby_'];
                  $all_plan = [];
                  foreach ($plans as $plan) {
                      $query = \Drupal::entityQuery('taxonomy_term')
                        ->condition('name', $plan)
                        ->accessCheck(TRUE)
                        ->condition('vid', 'lobbyist_s_portfolios')
                        ->range(0, 1); // Return only one term if it exists.
                      
                      $term_ids = reset($query->execute());
                      $all_plan[] =  $term_ids;
                  }
                  
                  $final_data['who_are_you_lobbying_or_plan_to_lobby_'] = $all_plan;
                  
                   $connection = Database::getConnection();
                  $query = $connection->select('users_field_data', 'u')
                    ->fields('u', ['uid'])  // Get the 'uid' field (user ID)
                    ->condition('u.name', $final_data['uid'])
                    ->range(0, 1);  // Limiting the result to 1 record
                
                  // Execute the query and fetch the result.
                  $result = $query->execute()->fetchAssoc();
          
                  // Check if the user exists and return the user ID.
                  if ($result) {
                    // We assume that the result will contain only one user.
                    $uid = $result['uid'];
                    $final_data['user_uid'] = $uid;
                  }
                  else {
                    // Return NULL if the user is not found.
                    $uid = '';
                  }
        
                  // Create submission
                    $submission = WebformSubmission::create([
                      'webform_id' => 'add_a_lobbying_activity',
                      'entity_type' => NULL,
                      'entity_id' => NULL,
                      'uid' => $uid,
                      'created' => strtotime($webform->created),
                      //'changed' => strtotime($webform->changed),
                      'data' => $final_data,
                    ]);
                    
                    $submission->save();
                    $connection = Database::getConnection();
                    
                    $connection->update('webform_submission')
                      ->fields([
                        'changed' => strtotime($webform->changed), // Your new timestamp (integer)
                        'Completed' => strtotime($webform->changed), // Your new timestamp (integer)
                      ])
                      ->condition('webform_id', 'add_a_lobbying_activity')
                      ->condition('serial', $submission->serial->value) // Submission ID
                      ->execute();
                      
      }
      echo "all submission imported;"; die;
      
  }
  
  
  public function content_in_house_org() {
    $site_link = \Drupal::config('w3_data_import.theme_settings')->get('site_basic_path');
    if (empty($site_link)) {
        echo "Please add the site basic path to continue data import"; die;
    }
    $http_client = \Drupal::service('http_client');

      try {
        // Send a GET request to the URL.
        $response = $http_client->get($site_link . '/api/webform/add_new_in_house_lobbyist');
    
        // Get the body of the response.
        $body = $response->getBody();
        $data = json_decode($body);
        
      }
      catch (RequestException $e) {
        // Log error if the request fails.
        \Drupal::logger('my_module')->error('Error fetching data from @url: @message', [
          '@url' => $url,
          '@message' => $e->getMessage(),
        ]);
        return NULL;
      }

      foreach ($data as $webform) {
          $final_data = (array) $webform->data;
          unset($final_data['form_build_id']);
          unset($final_data['form_id']);
          unset($final_data['form_token']);
          unset($final_data['op']);
          unset($final_data['preview_prev']);
          unset($final_data['submit']);
          unset($final_data['webform_preview']);
          unset($final_data['webform_start']);
          unset($final_data['who_are_you_lobbying_or_plan_to_lobby_']);
          unset($final_data['which_topic_do_you_want_to_lobby_government_about_']);

          $connection = Database::getConnection();
          $query = $connection->select('users_field_data', 'u')
            ->fields('u', ['uid'])  // Get the 'uid' field (user ID)
            ->condition('u.name', $final_data['uid'])
            ->range(0, 1);  // Limiting the result to 1 record
        
          // Execute the query and fetch the result.
          $result = $query->execute()->fetchAssoc();
  
          // Check if the user exists and return the user ID.
          if ($result) {
            // We assume that the result will contain only one user.
            $uid = $result['uid'];
            $final_data['user_uid'] = $uid;
          }
          else {
            // Return NULL if the user is not found.
            $uid = '';
          }

          // Create submission
            $submission = WebformSubmission::create([
              'webform_id' => 'add_new_in_house_lobbyist',
              'entity_type' => NULL,
              'entity_id' => NULL,
              'uid' => $uid,
              'created' => strtotime($webform->created),
              //'changed' => strtotime($webform->changed),
              'data' => $final_data,
            ]);
            
            $submission->save();
            
            $connection = Database::getConnection();

            $connection->update('webform_submission')
              ->fields([
                'changed' => strtotime($webform->changed), // Your new timestamp (integer)
                'Completed' => strtotime($webform->changed), // Your new timestamp (integer)
              ])
              ->condition('webform_id', 'add_new_in_house_lobbyist')
              ->condition('serial', $submission->serial->value) // Submission ID
              ->execute();
            //dump($submission); die;
      }
      echo "all submission imported;"; die;
      
  }

}

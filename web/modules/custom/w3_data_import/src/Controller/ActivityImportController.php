<?php

namespace Drupal\w3_data_import\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Drupal\user\Entity\User;
use Drupal\biz_webforms\BizWebformController;
use Drupal\webform\WebformSubmissionForm;
use Drupal\oauth2_client\Service\OAuth2Client;
use Drupal\Core\Database\Database;
use Drupal\node\Entity\Node;

class ActivityImportController extends ControllerBase {

  public function content() {
    $site_link = \Drupal::config('w3_data_import.theme_settings')->get('site_basic_path');
    if (empty($site_link)) {
        echo "Please add the site basic path to continue data import"; die;
    }
    $http_client = \Drupal::service('http_client');
      try {
        // Send a GET request to the URL.
        $response = $http_client->get($site_link . '/api/activity?_format=json');
    
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
      
      global $default_username;
        foreach ($data as $user_values) {
           $title = trim($final_data['title']); // The title value you're matching
           
            
          $connection = Database::getConnection();
          
        //   if ($user_values->title == "In-house activity") {
        //       $query = $connection->select('webform_submission_data', 'u')
        //         ->fields('u', ['sid'])  // Get the 'uid' field (user ID)
        //         ->condition('u.webform_id', 'add_a_lobbying_activity')
        //         ->condition('u.value', $user_values->field_title)
        //         ->range(0, 1);  // Limiting the result to 1 record
            
        //       // Execute the query and fetch the result.
        //       $result = $query->execute()->fetchAssoc();
        //   }
        //   else if ($user_values->title == "Consultant Activity") {
        //       $query = $connection->select('webform_submission_data', 'u')
        //         ->fields('u', ['sid'])  // Get the 'uid' field (user ID)
        //         ->condition('u.webform_id', 'add_a_lobbying_activity_consulta')
        //         ->condition('u.value', $user_values->field_title)
        //         ->range(0, 1);  // Limiting the result to 1 record
            
        //       // Execute the query and fetch the result.
        //       $result = $query->execute()->fetchAssoc();
        //   }
          
          if ($user_values->title == "In-house activity") {
            $query = $connection->select('webform_submission_data', 'u');
            $query->fields('u', ['sid']);
            
            $query->condition('u.webform_id', 'add_a_lobbying_activity');
            $query->condition('u.value', $user_values->field_title);
            $query->condition('u.name', 'title');
            $query->orderRandom();
            $query->orderBy('u.sid', 'ASC');
            $query->range(0, 1);
            
            $result1 = $query->execute()->fetchAssoc();
          }
          else if ($user_values->title == "Consultant Activity") {
            $query = $connection->select('webform_submission_data', 'u');
            $query->fields('u', ['sid']);
            
            $query->condition('u.webform_id', 'add_a_lobbying_activity_consulta');
            $query->condition('u.value', $user_values->field_title);
            $query->condition('u.name', 'title');
            $query->orderRandom();
            $query->orderBy('u.sid', 'ASC');
            $query->range(0, 1);
            
            $result1 = $query->execute()->fetchAssoc();
          }

          if (!empty($result1)) {
             $sid = $result1['sid'];
              $output = $this->check_sid($sid);
              
              if (!empty($output)) {
                  $result3 = $this->get_sid($user_values->field_title, $user_values->title);
                  $sid = $result3['sid'];
                  
                  $output2 = $this->check_sid($sid);
              
                  if (!empty($output2)) {
                      $result4 = $this->get_sid($user_values->field_title, $user_values->title);
                      $sid = $result4['sid'];
                  }
              }
              
              $query = $connection->select('users_field_data', 'u')
                ->fields('u', ['uid'])  // Get the 'uid' field (user ID)
                ->condition('u.name', $user_values->uid)
                ->range(0, 1);  // Limiting the result to 1 record
            
              // Execute the query and fetch the result.
              $result = $query->execute()->fetchAssoc();
      
              // Check if the user exists and return the user ID.
              if ($result) {
                // We assume that the result will contain only one user.
                $uid = $result['uid'];
              }
              else {
                // Return NULL if the user is not found.
                $uid = '';
              }
              //$sid = isset($result['sid']) ? (int) $result['sid'] : NULL;

                $node = Node::create([
                  'type' => 'activity', // Content type machine name
                  'title' => $user_values->title,
                  'field_email' => $user_values->field_email,
                  'field_d9_entity_id' => $user_values->nid,
                  'field_status' => $user_values->field_status,
                  'field_submission_id' => $sid,
                  'field_title' => $user_values->field_title,
                  'status' => 1, // 1 = Published, 0 = Unpublished
                  'uid' => $uid, // Author user ID
                  'created' => $user_values->created,
                  'changed' => $user_values->changed,
                ]);

                $node->save();
                
                $nid = $node->id();
                //dump($node); die;
          }
        }
          echo "all activity imported;"; die;
  }
  
  public function check_sid($sid) {
      $connection = Database::getConnection();
      $query2 = $connection->select('node__field_submission_id', 'u')
                ->fields('u', ['field_submission_id_value'])  // Get the 'uid' field (user ID)
                ->condition('u.field_submission_id_value', $sid)
                ->range(0, 1);  // Limiting the result to 1 record
            
              // Execute the query and fetch the result.
              $result2 = $query2->execute()->fetchAssoc();
      return $result2;
  }
  
  public function get_sid($title, $type) {
      $connection = Database::getConnection();
      if ($type == "In-house activity") {
            
            $query = $connection->select('webform_submission_data', 'u');
            $query->fields('u', ['sid']);
            
            $query->condition('u.webform_id', 'add_a_lobbying_activity');
            $query->condition('u.value', $title);
            $query->condition('u.name', 'title');
            $query->orderRandom();
            $query->orderBy('u.sid', 'ASC');
            $query->range(0, 1);
            
            $result1 = $query->execute()->fetchAssoc();
          }
          else if ($type == "Consultant Activity") {
            $query = $connection->select('webform_submission_data', 'u');
            $query->fields('u', ['sid']);
            
            $query->condition('u.webform_id', 'add_a_lobbying_activity_consulta');
            $query->condition('u.value', $title);
            $query->condition('u.name', 'title');
            $query->orderRandom();
            $query->orderBy('u.sid', 'ASC');
            $query->range(0, 1);
            
            $result1 = $query->execute()->fetchAssoc();
          }
          return $result1;
  }

}

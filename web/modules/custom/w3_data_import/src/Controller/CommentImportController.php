<?php

namespace Drupal\w3_data_import\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Drupal\comment\Entity\Comment;
use Drupal\node\Entity\Node;
use Drupal\Core\Database\Database;

class CommentImportController extends ControllerBase {

  public function content() {
    $site_link = \Drupal::config('w3_data_import.theme_settings')->get('site_basic_path');
    if (empty($site_link)) {
        echo "Please add the site basic path to continue data import"; die;
    }
    
     $original_notify = \Drupal::config('comment.settings')->get('comment_notify');
    \Drupal::configFactory()->getEditable('comment.settings')->set('comment_notify', FALSE)->save();


    $http_client = \Drupal::service('http_client');

      try {
        // Send a GET request to the URL.
        $response = $http_client->get($site_link . '/api/comment-export');
    
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
      
    //dump($data); die;

    foreach ($data as $item) {
      $connection = Database::getConnection();
      $query2 = $connection->select('node__field_d9_entity_id', 'u')
        ->fields('u', ['entity_id'])  // Get the 'uid' field (user ID)
        ->condition('u.field_d9_entity_id_value', $item->entity_id)
        ->condition('u.bundle', 'activity')
        ->range(0, 1);  // Limiting the result to 1 record
    
      // Execute the query and fetch the result.
      $result2 = $query2->execute()->fetchAssoc();

      // Avoid duplicates
      $existing = \Drupal::entityQuery('comment')
        ->condition('subject', $item->subject)
        ->condition('entity_id', $result2['entity_id'])
        ->accessCheck(FALSE)
        ->execute();

      if (!empty($existing)) {
        continue;
      }
      
      $query = $connection->select('users_field_data', 'u')
        ->fields('u', ['uid'])  // Get the 'uid' field (user ID)
        ->condition('u.name', $item->uid)
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

      $comment = Comment::create([
        'entity_type' => 'node',
        'entity_id' => $result2['entity_id'],
        'field_name' => 'comment',
        'subject' => $item->subject,
        'comment_body' => [
          'value' => $item->body,
          'format' => 'basic_html',
        ],
        'uid' => $uid,
        'status' => $item->status,
        'created' => $item->created,
      ]);

      $comment->save();
    }
    \Drupal::configFactory()->getEditable('comment.settings')->set('comment_notify', $original_notify)->save();

    return new Response('Comments imported successfully.');
  }
}
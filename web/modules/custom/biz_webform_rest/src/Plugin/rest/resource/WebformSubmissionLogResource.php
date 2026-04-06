<?php
namespace Drupal\biz_webform_rest\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
/**
 * Provides a Webform submission log Resource
 *
 * @RestResource(
 *   id = "webform_submission_log",
 *   label = @Translation("Webform Submission Log Resource"),
 *   uri_paths = {
 *     "canonical" = "/biz_custom_rest_api/custom_resource/{webform_id}/{submission_id}/{user_id}"
 *   }
 * )
 */
class WebformSubmissionLogResource extends ResourceBase {
  
  public function get($webform_id, $submission_id, $user_id) {
    $last_updated = self::getLastUpdatedByOwner($webform_id, $submission_id, $user_id);
    $response = ['last_updated' => $last_updated[0]->last_updated];
    return new ResourceResponse($response);
  }

  static function getLastUpdatedByOwner($webform_id, $submission_id, $user_id){
    $query = \Drupal::database()->select('webform_submission_log', 'wsl')
      ->condition('wsl.sid', $submission_id,'=')
      ->condition('wsl.uid', $user_id,'=')
      ->condition('wsl.webform_id', $webform_id,'=' )
      ->orderBy('wsl.timestamp', 'DESC');
    $query->addExpression("DATE_FORMAT(FROM_UNIXTIME(wsl.timestamp), '%Y-%m-%d')", 'last_updated');
    $result = $query->execute()->fetchAll();
    return $result;
  }
}
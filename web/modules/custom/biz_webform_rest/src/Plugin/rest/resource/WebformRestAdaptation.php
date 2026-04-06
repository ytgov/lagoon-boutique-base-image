<?php 

namespace Drupal\biz_webform_rest\Plugin\rest\resource;

use Drupal\webform\Entity\WebformSubmission;
use Drupal\biz_webform_rest\Plugin\rest\resource\WebformSubmissionResource;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RequestStack;


/**
 * Creates a resource for retrieving webform submission data.
 *
 * @RestResource(
 *   id = "biz_webform_rest_submission_adaptation",
 *   label = @Translation("Webform Submission"),
 *   uri_paths = {
 *     "canonical" = "/webform-rest-adaptation/{webform_id}/submission/{sid}"
 *   }
 * )
 */
class WebformRestAdaptation  extends ResourceBase {


  public function patch($webform_id, $sid) {
    $request = new RequestStack();
    $container = new Container();
    $configuration = [];
    $plugin_id = '';
    $plugin_definition =  \Drupal::logger('notice');
    
    $submission_resource = new WebformSubmissionResource([], $configuration, $plugin_id, [], $plugin_definition);
    $webform_submission = WebformSubmission::load($sid);
    $uuid = $webform_submission->uuid();
    $webform_data = \Drupal::request()->getContent();
    if (empty($webform_data)) {
      $errors = [
        'error' => [
          'message' => t('No data has been submitted.'),
        ],
      ];
      return new ModifiedResourceResponse($errors, 400);
    }
    $webform_data = json_decode($webform_data, TRUE);
    return $submission_resource->patch($webform_id, $uuid, $webform_data);
  }

}
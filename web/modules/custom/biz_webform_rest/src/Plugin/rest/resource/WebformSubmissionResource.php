<?php

namespace Drupal\biz_webform_rest\Plugin\rest\resource;

use Drupal\webform\WebformSubmissionForm;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ModifiedResourceResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\biz_business_rules\Controller\BusinessRulesFunctions;
use Drupal\user\Entity\User;

/**
 * Creates a resource for retrieving webform submission data.
 *
 * @RestResource(
 *   id = "biz_webform_rest_submission",
 *   label = @Translation("Webform Submission"),
 *   uri_paths = {
 *     "canonical" = "/webform_rest/{webform_id}/submission/{uuid}"
 *   }
 * )
 */
class WebformSubmissionResource extends ResourceBase {
  /**
   * The entity type manager object.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->request = $container->get('request_stack');
    return $instance;
  }

  /**
   * Retrieve submission data.
   *
   * @param string $webform_id
   *   Webform ID.
   *
   * @param int $sid
   *   Submission ID.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   HTTP response object containing webform submission.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws HttpException in case of error.
   */
  public function get($webform_id, $sid) {
    if (empty($webform_id) || empty($sid)) {
      $errors = [
        'error' => ['message' => 'Both webform ID and submission ID are required.'],
      ];
      return new ModifiedResourceResponse($errors);
    }
    // Load the webform submission.
    $webform_submission = WebformSubmission::load($sid);
    // Check for a submission.
    if (!empty($webform_submission)) {
      $submission_webform_id = $webform_submission->get('webform_id')->getString();
      // Check webform_id.
      if ($submission_webform_id == $webform_id) {
        // Grab submission data.
        $data = $webform_submission->getData();
        $response = [
          'entity' => $webform_submission,
          'data' => $data,
        ];
        // Return the submission.
        return new ModifiedResourceResponse($response);
      }
    }
    throw new NotFoundHttpException(t("Can't load webform submission."));
  }

  /**
   * Update submission data.
   *
   * @param string $webform_id
   *   Webform ID.
   * @param string $uuid
   *   Webform Submission UUID.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   HTTP response object containing webform submission.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws HttpException in case of error.
   */
  public function patch($webform_id, $uuid, $webform_data = []) {
    if (empty($webform_id) || empty($uuid)) {
      $errors = [
        'error' => [
          'message' => $this->t('Both webform ID and submission UUID are required.'),
        ],
      ];
      return new ModifiedResourceResponse($errors, 400);
    }
    if (empty($webform_data)) {
      $webform_data = $this->request->getCurrentRequest()->getContent();
      $webform_data = json_decode($webform_data, TRUE);
    }
    if (empty($webform_data)) {
      $errors = [
        'error' => [
          'message' => $this->t('No data has been submitted.'),
        ],
      ];
      return new ModifiedResourceResponse($errors, 400);
    }

    // Load the webform submission.
    $webform_submission = \Drupal::entityTypeManager()->getStorage('webform_submission')->loadByProperties(['uuid' => $uuid]);
    if (empty($webform_submission)) {
      $errors = [
        'error' => [
          'message' => $this->t('Invalid submission UUID.'),
        ],
      ];
      return new ModifiedResourceResponse($errors, 400);
    }
    $webform_submission = reset($webform_submission);
    $current_user = \Drupal::currentUser();
    $roles = $current_user->getRoles();
    $business_rules = new BusinessRulesFunctions();
    // Check for a submission.
    if (!empty($webform_submission)) {
      $submission_webform_id = $webform_submission->get('webform_id')->getString();
      $today = date("Y-m-d");
      $dates = $business_rules->getDatesCalendarEnd();
      $user_id = $webform_submission->getOwnerId();
      // Check webform_id.
      if ($submission_webform_id == $webform_id) {
        if ($today >= $dates['from'] && $today <= $dates['to'] &&
        $submission_webform_id == 'add_a_lobbying_activity' && isset($webform_data['status'])) {
          $webform_data['status'] = 'Active';
        }
        foreach ($webform_data as $field => $value) {
          $webform_submission->setElementData($field, $value);
        }
        if ($current_user->id() == $user_id) {
          $version_number = $webform_submission->getElementData('version_number');
          $version_number = intval($version_number) + 1;
          $webform_submission->setElementData('version_number', $version_number);
        }

        $errors = WebformSubmissionForm::validateWebformSubmission($webform_submission);

        // Check there are no validation errors.
        if (!empty($errors)) {
          $errors = ['error' => $errors];
          return new ModifiedResourceResponse($errors);
        }
        else {
          $module = $business_rules->module;
          $key = $business_rules->key;
          $mail_manager = \Drupal::service('plugin.manager.mail');
          $owner = User::load($user_id);
          $mail = $owner->getEmail();
          $langcode = $owner->getPreferredLangcode();
          
          // Base config
          $config_lang = \Drupal::config('biz_business_rules.settings');
          if ($langcode === 'fr') {
            $config_lang = \Drupal::languageManager()->getLanguageConfigOverride($langcode, 'biz_business_rules.settings');
          }
				
          $is_first = FALSE;
          $login_url = \Drupal::config('biz_business_rules.settings')->get('front_base_url') .   $langcode . "/login?destination=/";
          switch ($submission_webform_id) {
            case 'add_a_lobbying_activity':
              $link_target =  $login_url . $langcode . "/in-house-account-home/in-house-activity-view?id=".  $webform_submission->id(). urlencode("&webform_id=add_a_lobbying_activity");
              $link_target_admin =  $login_url . "en/admin-dashboard/in-house-activity-view?id=".  $webform_submission->id() . urlencode("&webform_id=add_a_lobbying_activity");
              break;
            case 'add_a_lobbying_activity_consulta':
              $link_target = $login_url . $langcode . "/consultant-account-home/consultant-activity-view?id=".  $webform_submission->id(). urlencode("&webform_id=add_a_lobbying_activity_consulta");
              $link_target_admin = $login_url . "en/admin-dashboard/consultant-activity-view?id=" . $webform_submission->id() . urlencode("&webform_id=add_a_lobbying_activity_consulta");
              break;
            default:
              $link_target = "";
              $link_target_admin = "";
              break;
          }
          if(!empty($link_target)){
            $link_target = urlencode($link_target);
          }
          if(!empty($link_target_admin)){
            $link_target_admin = urlencode($link_target_admin);
          }


          // Commissioner updated status, send email to lobbyist only if it is the first activity approved.
          if (!$business_rules->isFirstActivity($submission_webform_id, $user_id, TRUE) &&
          $webform_data['status'] == 'Active' && in_array('role_administrator', $roles)
          && in_array($submission_webform_id, ['add_a_lobbying_activity', 'add_a_lobbying_activity_consulta'])) {
            $params_first['subject'] = $config_lang->get('commissioner_subject_approve_first_act');
            $params_first['body'] = $config_lang->get('commissioner_approve_first_act')["value"];
            $params_first['body'] = str_replace('{{link}}', $link_target, $params_first['body']);

            $is_first = TRUE;
            $result_commisioner = $mail_manager->mail($module, $key, $mail, $langcode, $params_first, NULL, TRUE);
          }
          // Return submission ID.
          $webform_submission = WebformSubmissionForm::submitWebformSubmission($webform_submission);
          switch ($submission_webform_id) {
            case 'add_a_lobbying_activity':
            	// Send email to lobbyist when updated previous calendar year.
              if ($today >= $dates['from'] && $today <= $dates['to'] && $current_user->id() == $user_id) {
                $params['subject'] = t("Activity Updated");
                $params['body'] = $config_lang->get('in_house_update_before_end_calendar')["value"];
                $params['body'] = str_replace('{{link}}', $link_target, $params['body']);
                $result = $mail_manager->mail($module, $key, $mail, $langcode, $params, NULL, TRUE);
              }
              // Send email when user updated an activity.
              elseif (!$is_first) {
                self::sendEmailsAfterUpdated($owner, $link_target, $link_target_admin);
              }
              break;

            case 'add_a_lobbying_activity_consulta':
              if (!isset($webform_data['start_date'])) {
                $webform_submission_data = WebformSubmission::load($webform_submission->id());
                $webform_data = $webform_submission_data->getData();
              }
              // Today.
              $today = date("Y-m-d");
              $new_start_dates = $business_rules->getDatesContract($webform_data['start_date']);
              $from = $new_start_dates['from'];
              $to = $new_start_dates['to'];
              // Send email to lobbyist whens certify their activity.
              if (($today >= $from and $today <= $to) && $current_user->id() == $user_id) {
                \Drupal::logger("biz_webform_rest")->notice('Send email consultant certify:' . $mail);
                $params['subject'] = t("Activity Updated");
                $params['body'] =$config_lang->get('consultant_certify')["value"];
                $params['body'] = str_replace('{{link}}', $link_target, $params['body']);
                $result = $mail_manager->mail($module, $key, $mail, $langcode, $params, NULL, TRUE);

                // Send email when user updated an activity.
              }
              elseif (!$is_first) {
                self::sendEmailsAfterUpdated($owner, $link_target, $link_target_admin);
              }
              break;
          }
        }
        // Return submission ID.
        return new ModifiedResourceResponse(['sid' => $webform_submission->id()]);
      }
    }
    throw new NotFoundHttpException(t("Can't load webform submission."));
  }

  /**
   *
   */
  public static function sendEmailsAfterUpdated($owner, $link_target = "", $link_target_admin = "") {
    $business_rules = new BusinessRulesFunctions();
    $module = $business_rules->module;
    $key = $business_rules->key;
    $mail_manager = \Drupal::service('plugin.manager.mail');
    $current_user = \Drupal::currentUser();
    $roles = $current_user->getRoles();
    $mail = $owner->getEmail();
    $langcode = $owner->getPreferredLangcode() ?? 'en';

    // Base config
    $config_lang = \Drupal::config('biz_business_rules.settings');
    if ($langcode === 'fr') {
      $config_lang = \Drupal::languageManager()->getLanguageConfigOverride($langcode, 'biz_business_rules.settings');
    }

    if (in_array('role_administrator', $roles)) {
      $params['subject'] = t("Activity Updated");
      $params['body'] = $config_lang->get('commissioner_updated_act')["value"];
      $params['body'] = str_replace('{{link}}', $link_target, $params['body']);
      $result = $mail_manager->mail($module, $key, $mail, $langcode, $params, NULL, TRUE);
      $link_target = $link_target_admin;
    }
    elseif ($current_user->id() == $owner->id()) {
      // Get all commissioners email.
      $commissioner_users = $business_rules->getAllMailFromRole('role_administrator');
      $params_commissioner['subject'] = t("Activity Updated");
      $owner_roles = $owner->getRoles();
      $organization_name = "";
      if (in_array('in_house_lobbyist', $owner_roles)) {
        $organization_name = $owner->get('field_first_name')->value . ' ' . $owner->get('field_last_name')->value;
      }
      elseif (in_array('consultant_lobbyist', $owner_roles)) {
        $organization_name = $owner->get('field_first_name_consultant_')->value . ' ' . $owner->get('field_last_name_consultant_')->value;
      }

      $params_commissioner['body'] = str_replace('{{organization}}', $organization_name, \Drupal::config('biz_business_rules.settings')->get('lobbyist_updated_act')["value"]);
      $params_commissioner['body'] = str_replace('{{link}}', $link_target_admin, $params_commissioner['body']);

      if (!empty($commissioner_users)) {
        foreach ($commissioner_users as $commissioner_user) {
          $result = $mail_manager->mail($module, $key, $commissioner_user['mail'], $commissioner_user['langcode'], $params_commissioner, NULL, TRUE);
          \Drupal::logger("biz_webform_rest")->notice("Send email to commisioner after user updated activity " . json_encode($commissioner_user));
        }
      }
    }
  }

}

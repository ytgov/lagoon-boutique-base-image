<?php

namespace Drupal\biz_webform_rest\Plugin\rest\resource;

use Drupal\webform\Entity\WebformSubmission;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ModifiedResourceResponse;

/**
 * Creates a resource for retrieving webform elements.
 *
 * @RestResource(
 *   id = "biz_get_submission_history_change",
 *   label = @Translation("Get submission change history"),
 *   uri_paths = {
 *     "canonical" = "/submission-history/{sid}/{date}/{count}"
 *   }
 * )
 */
class GetSubmissionChangeHistory extends ResourceBase {

  /**
   * Responds to GET requests, returns organizations.
   *
   * @param string sid
   *   Webform submission id
   *
   * @param string $date
   *   Get all the changes since 'date'.
   *
   * @param string $count
   *   Registartion number to get.
   *
   * @return \Drupal\rest\ResourceResponse
   *   HTTP response object containing webform elements.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws HttpException in case of error.
   */
  public function get($sid, $date = NULL, $count = NULL) {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $host = \Drupal::request()->getSchemeAndHttpHost();
    $database = \Drupal::database();
    // Change to correct format date.
    $date = str_replace('-', '/', $date);
    // Load the current submission.
    $webform_submission = WebformSubmission::load($sid);
    $created_date = $webform_submission->getCreatedTime();
    $last_updated = $webform_submission->getChangedTime();
    $created_date = !empty($created_date) ? date('Y/m/d', $created_date) : $created_date;
    $last_updated = !empty($last_updated) ? date('Y/m/d', $last_updated) : $last_updated;
    $owner = $webform_submission->getOwnerId();
    if (!empty($date)) {
      $string = " SELECT  wsl.data, wsl.webform_id, DATE_FORMAT(FROM_UNIXTIME(wsl.timestamp), '%Y-%m-%d') AS 'date'" .
              " FROM webform_submission_log as wsl " .
              " WHERE " .
              " DATE_FORMAT(FROM_UNIXTIME(wsl.timestamp), '%Y/%m/%d') = '" . $date . "' AND  wsl.sid = " . $sid .
              " AND data NOT LIKE '%{a:0}%' AND operation = 'submission updated'" .
              " AND  wsl.uid = " . $owner;
    }
    else {
      $string = " SELECT wsl.sid, CONCAT(wsl.sid, '-' ) as number, " .
                    "  DATE_SUB(DATE_FORMAT(FROM_UNIXTIME(wsl.timestamp), '%Y/%m/%d'), INTERVAL 1 DAY) AS 'date_before', " .
                    "  DATE_FORMAT(FROM_UNIXTIME(wsl.timestamp), '%Y/%m/%d') AS 'date' " .
                    "  FROM webform_submission_log as wsl " .
                    "  INNER JOIN webform_submission wsd ON wsd.sid = wsl.sid " .
                    "  WHERE wsl.sid = " . $sid .
                    "   AND  data NOT LIKE '%{a:0}%' AND operation = 'submission updated'" .
                    "   AND  (data LIKE '%start_date%' OR data LIKE '%end_date%' OR data LIKE '%year%')" .
                    "   AND  wsl.uid = " . $owner .
                    "  GROUP BY wsl.sid , DATE_SUB(DATE_FORMAT(FROM_UNIXTIME(wsl.timestamp), '%Y/%m/%d'), INTERVAL 1 DAY), DATE_FORMAT(FROM_UNIXTIME(wsl.timestamp), '%Y/%m/%d')" .
                    "  ORDER BY DATE_FORMAT(FROM_UNIXTIME(wsl.timestamp), '%Y/%m/%d') ASC";
    }
    $query = $database->query($string);
    $result = $query->fetchAll();
    $versions = self::processDbResult($result, $created_date, $last_updated);
    if (empty($date)) {
      // If the date parameter is empty, the general information must be returned to the versions.
      $versions = $versions['rows'];
    }
    else {
      $webform_ = \Drupal::entityTypeManager()->getStorage('webform')->load('add_a_lobbying_activity_consulta');
      $webform_submission__ = $webform_->getSubmissionForm();
      $check_options = [];
      $radio_options = [];
      $taxonomies = [];
      foreach ($webform_submission__['elements'] as $element_id => $element_info) {
        if (isset($element_info['#vocabulary'])) {
          $taxonomies[] = $element_id;
        }
        elseif (isset($element_info["#type"])) {
          switch ($element_info["#type"]) {
            case 'radios':
              $radio_options[] = $element_id;
              break;

            case 'webform_checkboxes_other':
              $check_options[] = $element_id;
              break;
          }
        }
      }
      // Return all information to fill the modal.
      $versions['old_version']['data'] = [];
      // $taxonomies = [ 'who_are_you_lobbying_or_plan_to_lobby_', 'which_topic_do_you_want_to_lobby_government_about_'];.
      $versions['current']['data'] = $webform_submission->getData();
      foreach ($versions['current']['data'] as $field_key => $field_value) {
        // Check if the field is a taxonomy.
        if (in_array($field_key, $taxonomies)) {
          // If the field is on $versions['all-info']['data'], it means that the field changed in this version and you need to load the names of the taxonomy terms in the variable instead of the ids for the previous version.
          if (isset($versions['all-info']['data'][$field_key])) {
            $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadMultiple($versions['all-info']['data'][$field_key]);
            foreach ($terms as $term) {
              if ($term->hasTranslation($language)) {
                $tid = $term->id();
                $translated_term = \Drupal::service('entity.repository')->getTranslationFromContext($term, $language);
                $old_terms[] = $translated_term->getName();
              }
              else {
                // Return title of term.
                $old_terms[] = $term->name->value;
              }
            }
          }
          // Load the names of the taxonomy terms into the variable instead of the ids for the current version.
          $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadMultiple($field_value);
          foreach ($terms as $term) {
            if ($term->hasTranslation($language)) {
              $tid = $term->id();
              $translated_term = \Drupal::service('entity.repository')->getTranslationFromContext($term, $language);
              $new_terms[] = $translated_term->getName();
            }
            else {
              // Return title of term.
              $new_terms[] = $term->name->value;
            }
          }
        }
        if (in_array($field_key, $radio_options)) {
          if (gettype($field_value) == 'string' && isset($webform_submission__['elements'][$field_key]['#options'][$field_value])) {
            $field_value = $webform_submission__['elements'][$field_key]['#options'][$field_value];
            if (isset($versions['all-info']['data'][$field_key]) && gettype($versions['all-info']['data'][$field_key]) == 'string') {
              $versions['all-info']['data'][$field_key] = $webform_submission__['elements'][$field_key]['#options'][$versions['all-info']['data'][$field_key]];
            }
          }
        }
        if (in_array($field_key, $check_options)) {
          foreach ($field_value as $option_id => $option_desc) {
            if (isset($webform_submission__['elements'][$field_key]['checkboxes']['#options'][$option_desc])) {
              $field_value[$option_id] = $webform_submission__['elements'][$field_key]['checkboxes']['#options'][$option_desc];
            }
          }
          if(isset($versions['all-info']['data'][$field_key])){
	          foreach ($versions['all-info']['data'][$field_key] as $v_option_id => $v_option_desc) {
	            if (isset($webform_submission__['elements'][$field_key]['checkboxes']['#options'][$v_option_desc])) {
	              $versions['all-info']['data'][$field_key][$v_option_id] = $webform_submission__['elements'][$field_key]['checkboxes']['#options'][$v_option_desc];
	            }
	          }
          }
        }
        // Check if field changed to create key for array, with old value if not keep current value.
        if (array_key_exists($field_key, $versions['all-info']['data'])) {
          $versions['old_version']['data'][$field_key]['value'] = empty($old_terms) ? $versions['all-info']['data'][$field_key] : implode(', ', $old_terms);
          $versions['old_version']['data'][$field_key]['class'] = 'changed-info';
        }
        else {
          $versions['old_version']['data'][$field_key]['class'] = '';
          $versions['old_version']['data'][$field_key]['value'] = empty($new_terms) ? $field_value : implode(', ', $new_terms);
        }
        // Create a new structure with the current submission data.
        $versions['current']['data'][$field_key] = empty($new_terms) ? $field_value : implode(', ', $new_terms);
        // Init taxonomy variables.
        $old_terms = [];
        $new_terms = [];
      }
      // Remove all-info key to return cleaner array.
      unset($versions['all-info']);
      $versions['current']['date'] = $last_updated;
      $version_date = date('Y/m/d', strtotime('-1 day', strtotime($date)));
      $versions['old_version']['date'] = $count == 1 ? $created_date : $version_date;
      $versions['old_version']['id'] = $date;
    }
    return new ModifiedResourceResponse(json_encode($versions));
  }

  /**
   * Function to process the info getting from database.
   *
   * @param array $result
   *   A result from the database.
   * @param mixed $created_date
   *   Submission creation date.
   * @param mixed $last_updated
   *   Last submission update date.
   *
   * @return array
   *   An array of Change objects.
   */
  public function processDbResult(array $result, $created_date = '', $last_updated = '') : array {
    $return = [];
    $all_info = [];
    $rows = [];
    $count_rows = count($result);
    $webform_id = '';
    $date_before = '';
    $count = 1;
    foreach ($result as $row) {
      $return['date'] = $row->date;
      if (isset($row->data)) {
        $unserialized = unserialize($row->data);
        $webform_id = $row->webform_id;
        if (isset($unserialized['changed'])) {
          foreach ($unserialized['changed'] as $key => $data) {
            if (array_key_exists('from', $data) && array_key_exists('to', $data)) {
              $all_info['data'][$key] = $data['from'];
            }
          }
        }
      }
      else {
        if (!array_key_exists($row->date, $all_info)) {
          $link = '<div class="compare-versions"><div class="btn btn-primary orange-button" data="' . $row->date . '&amp;' . $row->sid . '&amp;' . $count . '&amp;' . t('Inactive') . '&amp;' . strval((count($result)) + 1) . '">' . t('Compare') . '</div></div>';
          $created_date = str_replace('-', '/', $created_date);
          $row->date_before = str_replace('-', '/', $row->date_before);
          $date_before = str_replace('-', '/', $row->date) . ' - ';
          $effective = $count == 1 ? $created_date . ' - ' . $row->date_before : $date_before . $row->date_before;
          $rows[] = ['number' => $count, 'dates' => $effective , 'status' => t('Inactive'), 'view' => $link];
          $count++;
        }
      }
    }
    if (!empty($rows)) {
      $rows[] = ['number' => $count, 'dates' => $last_updated, 'status' => t('Active'), 'view' => ''];
    }

    $return['all-info'] = $all_info;
    $return['rows'] = $rows;
    $return['webform_id'] = $webform_id;

    return $return;
  }

}

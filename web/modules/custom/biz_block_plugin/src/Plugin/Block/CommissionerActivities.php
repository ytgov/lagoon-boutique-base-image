<?php

namespace Drupal\biz_block_plugin\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\biz_block_plugin\Controller\GeneralFunctions;

/**
 * Provides a custom block.
 *
 * @Block(
 *   id = "commissioner_act_block",
 *   admin_label = @Translation("Commissioner Activities block"),
 *   category = @Translation("Bizont custom block")
 * )
 */
class CommissionerActivities extends BlockBase implements BlockPluginInterface {

  /**
   * Display all activities.
   */
  public function build() {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $current_user = \Drupal::currentUser();
    $email = $current_user->getEmail();
    $roles = $current_user->getRoles();
    $base_url = \Drupal::config('biz_lobbyist_registration.settings')->get('base_url');
    $content = [];
    if (in_array("role_administrator", $roles)) {
      $content[] = [
        '#theme' => 'modal_confirmation',
        '#title' => t('Accept'),
        '#label_yes' => t('Accept'),
        '#label_cancel' => t('Cancel'),
        '#body_message' => t('Are you sure you want to accept this activity?'),
      ];
      $header_activities_endpoint = \Drupal::config('biz_block_plugin.settings')->get('header_commissioner_activities');
      $activities_endpoint = \Drupal::config('biz_block_plugin.settings')->get('commissioner_activities');
      $header_response = GeneralFunctions::getHeadersTable($header_activities_endpoint, 'header-orange');
      $activities_response = GeneralFunctions::getAllData($activities_endpoint, TRUE);
      $activity_rows = [];
      if ($header_response && $activities_response) {
        $rows_response = json_decode($activities_response['message']);
        $activity_rows = GeneralFunctions::generateTableRows($header_response, $rows_response);
      }
      $content[] = [
        '#theme' => 'custom_table',
        '#header' => $header_response['header'],
        '#rows' => $activity_rows,
        '#empty' => "Currently you don’t have activities",
        '#caption' => t('Activities'),
        '#attributes' => ['id' => 'commissioner-activities', 'class' => 'table-orange-header general-lobbyist-tables'],
        '#sort_column' => '3',
        '#sort_order' => 'desc',
        '#base_url' => $base_url . $language . "/",
        '#language' => $language,
      ];
    }
    return $content;
  }

  /**
   * Disable caching for this block.
   */
  public function getCacheMaxAge() {
    return 0;
  }

}

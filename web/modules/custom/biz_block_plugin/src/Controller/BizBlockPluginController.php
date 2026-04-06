<?php
    
namespace Drupal\biz_block_plugin\Controller;  
 
use Drupal\Core\Controller\ControllerBase;
use Drupal\biz_webforms\BizWebformController;

/****
    * @file
    * Contains \Drupal\biz_block_plugin\Controller\BizBlockPluginController.
    */

class BizBlockPluginController extends ControllerBase {
    /****
        * Prepares variables for a custom table element.
        * Default template: custom-table.html.twig
        */
    public function tablesContent() {
        return [
            '#theme' => 'custom_table',
            '#attributes' => [], '#colgroups' => [], '#header' => [], '#rows' => [],'#footer' =>    '', 
            '#empty'    => '','#header_columns' => 0, '#bordered' => FALSE, '#condensed' => FALSE, '#hover' => FALSE, '#striped' => FALSE,
            '#responsive' => TRUE, '#header_color' => '', '#caption' => '' , '#sort_column' => '','#sort_order' => '', '#base_url' => '','render element' => 'elements','variables'=>[], '#language' => '','#url_api' => '', '#add_html_top' => ''
        ];
    }
    /****
        * Prepares variables for a webform header about organization info.
        * Default template: organization-info-form-header-action.html.twig
        */
    public function organizationInfoFormHeaderAction() {
        return [
            '#theme' => 'organization-info-form-header-action',
            '#link_to_action' => []
        ];
    }
    /****
        * Prepares variables for a webform header about organization info.
        * Default template: organization-info-form-header.html.twig
        */
    public function organizationInfoFormHeader() {
        return [
            '#theme' => 'organization-info-form-header',
            '#organization' => []
        ];
    }
     /****
        * Prepares variables for a consultant activity element.
        * Default template: consultant-activity.html.twig
        */
    public function consultantActivityContent() {
        return [
            '#theme' => 'consultant_activity',
            '#activity' => [],
            '#link_edit_act' => '' 
        ];
    }
    
    /****
        * Prepares variables for a in-house activity element.
        * Default template: in-house-activity.html.twig
        */
    public function inHouseActivityContent() {
        return [
            '#theme' => 'in_house_activity',
            '#activity' => [],
            '#link_edit_act' => '' ,
            "#base_url" => ''
        ];
    }
    
    /****
        * Prepares variables for a consultant account info element.
        * Default template: consultant-account-info.html.twig
        */
    public function consultantAccountInfo() {
        return [
            '#theme' => 'consultant_account_info',
            '#organization' => [],
            '#link_edit_org' => '',
            '#description'    => ''
        ];
    }
    
    /****
        * Prepares variables for a in-house account info element.
        * Default template: in-house-account-info.html.twig
        */
    public function inHouseAccountOrganizationInfo() {
        return [
            '#theme' => 'in_house_account_organization_info',
            '#organization' => [],
            '#link_edit_org' => '',
            '#description'    => ''
        ];
    }
    
    /****
        * Prepares variables for search activities element.
        * Default template: search-activities-general.html.twig
        */
    public function searchActivitiesGeneral() {
        return [
            '#theme' => 'search_activities_general',
            '#allowed_tags' => ['button','form', 'input', 'div', 'label'],
            '#language' => '',
        ];
    }
    
    /****
        * Prepares variables for search block in home element.
        * Default template: search-block-home.html.twig
        */
    public function searchBlockHome() {
        return [
            '#theme' => 'search_block_home',
            '#allowed_tags' => ['button','form', 'input', 'div', 'label'],
            '#language' => ''
        ];
    }
    
    /****
        * Prepares variables for activity message element.
        * Default template: activity-messages.html.twig
        */
    public function activityMessages() {
        return [
            '#theme' => 'activity_messages',
            '#subject' => '',
            '#user_name'    => '',
            '#message'    => '',
            '#date'    => ''
        ];
    }
    
    /****
        * Prepares variables for modal confirmation element.
        * Default template: modal-confirmation.html.twig
        */
    public function modalConfirmation() {
        return [
            '#theme' => 'modal_confirmation',
            '#title' => '',
            '#body_message' => '',
            '#label_yes'    => '',
            '#label_cancel'    => '',
        ];
    }

    public function modalCompareVersionsConsultant(){
      return [
            '#theme' => 'modal_compare_versions_consultant',
            '#changes' => [], '#current_version' => [],'#type_lobbyist' => ''
        ];
    }
}

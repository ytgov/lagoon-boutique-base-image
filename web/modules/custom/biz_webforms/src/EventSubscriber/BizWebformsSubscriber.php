<?php
namespace Drupal\biz_webforms\EventSubscriber;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\biz_block_plugin\Controller\GeneralFunctions;
use Drupal\biz_webforms\BizWebformController;

class BizWebformsSubscriber implements EventSubscriberInterface {

    public function checkForRedirection(RequestEvent $event) {
        $current_uri = \Drupal::request()->getRequestUri(); 
        $current_user = \Drupal::currentUser();
        $roles = $current_user->getRoles();
        $is_commissioner =  in_array("role_administrator", $roles) ? TRUE : FALSE;
        switch (TRUE) {
            case strpos($current_uri, '/in-house-account-home/in-house-add-activity-edit') !== FALSE:
            case strpos($current_uri, '/in-house-account-home/in-house-activity-view') !== FALSE :            
                self::validateOwnerInHouseActivity(); 
            break;
            case strpos($current_uri, '/consultant-account-home/consultant-add-activity-edit') !== FALSE :
            case strpos($current_uri, '/consultant-account-home/consultant-activity-view') !== FALSE :
                self::validateOwnerConsultantActivity();
              break;
        }
        
        //Validate if user has the correct tokens if not, force logout and redirect to login page
        if($current_user->isAuthenticated() && strpos($current_uri, '/user/' . $current_user->id() . '/edit') !== FALSE){
            $user_entity = \Drupal::entityTypeManager()->getStorage('user')->load($current_user->id());
            $auth_value = $user_entity->get('field_token_auth_')->getString();
            if(empty($auth_value)){
                $session_manager = \Drupal::service('session_manager');
                $session_manager->delete($current_user->id());
                BizWebformController::redirect_to('/login');
            }
        }
    }

    /**
      * {@inheritdoc}
    */
    static function getSubscribedEvents() {
        $events[KernelEvents::REQUEST][] = array('checkForRedirection');
        return $events;
    }
    
    public function validateOwnerConsultantActivity() {
        $current_user = \Drupal::currentUser();
        $roles = $current_user->getRoles();
        $email = $current_user->getEmail();
        $base_url =  \Drupal::config('biz_lobbyist_registration.settings')->get('base_url');
        $activity_endpoint = \Drupal::config('biz_block_plugin.settings')->get('consultant_activity');
        //Get all query params
        $param = \Drupal::request()->query->all();
        //Get activity ID
        $id = isset($param['sid']) ? $param['sid'] : null;
        if (empty($id) && isset($param['id'])) {
          $id = $param['id'];
        }

        if (empty($id)) {
          \Drupal::logger('BizWebformsSubscriber')->warning('Missing activity id or sid during validation. Skipping.');
          return;
        }
        //Get activity data
        \Drupal::logger('BizWebformsSubscriber::validateOwnerConsultantActivity')->notice($id);
        $activity_response = GeneralFunctions::getSubmission($activity_endpoint, $id, TRUE);
        
        if($activity_response['code'] !== 400){
            \Drupal::logger('BizWebformsSubscriber::validateOwnerConsultantActivity')->notice(json_encode($activity_response['message']));
          $activity_data = isset(json_decode($activity_response['message'])[0]) ? json_decode($activity_response['message'])[0] : [];
        }else{
            \Drupal::logger('BizWebformsSubscriber::validateOwnerConsultantActivity')->notice(json_encode($activity_response));
        }     
        $activity_email = isset($activity_data->mail) ? $activity_data->mail : "";
    
        if ($email !== $activity_email && !in_array("role_administrator", $roles)) {
          $response = new RedirectResponse('/system/403');
          $response->send();
        }
    }
    
    public function validateOwnerInHouseActivity() {
        $current_user = \Drupal::currentUser();
        $roles = $current_user->getRoles();
        $email = $current_user->getEmail();
        $base_url =  \Drupal::config('biz_lobbyist_registration.settings')->get('base_url');
        $activity_endpoint = \Drupal::config('biz_block_plugin.settings')->get('in_house_activity');
        //Get all query params
        $param = \Drupal::request()->query->all();
        \Drupal::logger('params')->notice(json_encode($param));
        //Get activity ID
        $id = isset($param['sid']) ? $param['sid'] : null;
        if (empty($id) && isset($param['id'])) {
          $id = $param['id'];
        }

        if (empty($id)) {
          \Drupal::logger('BizWebformsSubscriber')->warning('Missing activity id or sid during validation. Skipping.');
          return;
        }

        //Get activity data
        $activity_response = GeneralFunctions::getSubmission($activity_endpoint, $id, TRUE);
        if($activity_response['code'] !== 400){
          $activity_data = isset(json_decode($activity_response['message'])[0]) ? json_decode($activity_response['message'])[0] : [];
        }    
        $activity_email = isset($activity_data->mail) ? $activity_data->mail : "";
        if ($email !== $activity_email  && !in_array("role_administrator", $roles)) {
          $response = new RedirectResponse('/system/403');
          $response->send();
        }
    }
}
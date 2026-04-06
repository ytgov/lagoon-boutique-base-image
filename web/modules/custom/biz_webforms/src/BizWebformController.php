<?php
namespace Drupal\biz_webforms;

use Drupal\rest\ModifiedResourceResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use GuzzleHttp\Client;
use Drupal\Core\Cache\CacheableMetadata;


/**
*
*/
class BizWebformController {
    /**
      * Execute external api(backend)
      */
    static function execute_external_api($url, $data = array(), $method, $request_options ) {
        //Creating a httpClient Object.
        $client = \Drupal::httpClient();
        try {
          if(!empty($data)){
            $request_options['json'] = $data;
          }

          $response = $client->$method($url, $request_options);
          return ["code" => $response->getStatusCode(), "message" => $response->getBody()->getContents()];
        }
        catch (\Exception $e) {
            if ($e->hasResponse()) {
                $response = $e->getMessage();
                \Drupal::logger('BizWebformController')->error($url);
                \Drupal::logger('BizWebformController')->error(json_encode($request_options));
                \Drupal::logger('BizWebformController')->error(json_encode($e->getMessage()));
                if( $e->getCode() == 403){
                    \Drupal::messenger()->addMessage(t('Your session has expired, please login again.'), 'error');
                    BizWebformController::redirect_to('/login');
                } 
                return ["code" => 400, "message" => $response];
            }
        }
    }

    /*
     * Get the options for executing an external API
     */
    static function get_request_options($auth = FALSE){
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $user = \Drupal::currentUser();
        if(!empty($user->id())){
            $user_entity = \Drupal::entityTypeManager()->getStorage('user')->load($user->id());
            $auth_value = $user_entity->get('field_token_auth_')->getString();
        }
        $base_url = \Drupal::config('biz_lobbyist_registration.settings')->get('base_url');
        $response = \Drupal::httpClient()->get(($base_url . $language . '/session/token'));

      	$csrf = (string) $response->getBody();
        $request_options['headers']= [
	        'Content-Type' => 'application/json',
	        'X-CSRF-Token' => $csrf,
	    ];
	    if($auth && !empty($auth_value)){
    	    $request_options['headers']['Authorization']= 'Bearer ' . $auth_value ;
	    }
        return $request_options;
    }

    static function get_token_oauth2_client($username, $password){
        $curl = curl_init();
        $base_url = \Drupal::config('biz_lobbyist_registration.settings')->get('base_url');
        $url = $base_url . 'oauth2/token';
        $scope = \Drupal::config('biz_lobbyist_registration.settings')->get('scope');
        $client_id = \Drupal::config('biz_lobbyist_registration.settings')->get('client_id');
        $client_secret = \Drupal::config('biz_lobbyist_registration.settings')->get('client_secret');
        $grant_type = \Drupal::config('biz_lobbyist_registration.settings')->get('grant_type');
        try{
          curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "client_id=". $client_id ."&client_secret=". $client_secret ."&grant_type=" . $grant_type ."&scope=".$scope ."&username=".$username."&password=" .$password ,
            CURLOPT_HTTPHEADER => array(
              "Content-Type: application/x-www-form-urlencoded"
            ),
          ));
          $response = curl_exec($curl);
          if( ! $response){
              trigger_error(curl_error($curl));
          }
          curl_close($curl);
          return $response;
        }
        catch(Exception $e){
          $response = $e->getMessage();
          \Drupal::logger('BizWebformController')->error(json_encode($e));
          \Drupal::logger('BizWebformController')->error(json_encode($e->getMessage()));
        } 
    }

	/*
     * Function for update user information (only for system use)
     */	
	static function update_tokens($user_id, $user_fields ){
        $user_entity = \Drupal::entityTypeManager()
            ->getStorage('user')
            ->load($user_id);
        foreach($user_fields as $field => $value){
          $user_entity->set($field, $value);
        }
        $user_entity->save();
	}
    
    /*
     * Redirect to correct homepage depends on the user role
     */
    static function user_redirect($account, $tab = ''){
        $roles = $account->getRoles();
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId(); 
        switch(TRUE){
            case in_array('in_house_lobbyist', $roles):
                (new self)->redirect_to("/" .$language . "/in-house-account-home". $tab);
            break;
            case in_array('consultant_lobbyist', $roles):
                (new self)->redirect_to("/" .$language ."/consultant-account-home". $tab);
            break;
            case in_array('role_administrator', $roles):
                (new self)->redirect_to("/" .$language ."/admin-dashboard". $tab);
            break;
        }
    }
    
    static function get_user_path_redirect($account = NULL, $webform_id = "", $name = "", $only_path = FALSE){
        $roles = empty($account) ? [] : $account->getRoles();
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId(); 
        $path_redirect = '';
        switch(TRUE){
            case in_array('in_house_lobbyist', $roles):
                $path_redirect = "/" .$language . "/in-house-account-home";
            break;
            case in_array('consultant_lobbyist', $roles):
                $path_redirect = "/" .$language ."/consultant-account-home";
            break;
            case in_array('role_administrator', $roles):
                $path_redirect ="/" .$language ."/admin-dashboard";
            break;
            case $webform_id == 'login':
                $path_redirect ="/" .$language ."/login";
            break;
            case $webform_id == 'account-register':
                $path_redirect ="/" .$language ."/account-register";
            break;
            default:
                 $path_redirect = "/";
            break;
        }
        if(!$only_path){
            $url_object = \Drupal::service('path.validator') ->getUrlIfValid($path_redirect);
            $route_name = $url_object->getRouteName();
            $route_parameters = $url_object->getrouteParameters();
            $url_redirect = \Drupal\Core\Url::fromRoute($route_name, $route_parameters);
            switch($webform_id){
                case 'add_new_in_house_lobbyist':
                    $url_redirect->setOption('query', [
                        'qt-account_home' => '1'
                    ]);
                break;
                case $webform_id == 'account-register':
                    $url_redirect->setOption('query', [
                        'name' => $name
                    ]);
                break;
            }
        }
        else{
            $url_redirect = $path_redirect;
        }
        return $url_redirect;
    }

    /*
     * Function redirect to specific path
     */
    static function redirect_to($path) { 
	    $response = new RedirectResponse($path);
        $response->send();
	}
	
	static function get_comments_array($id){
    	$base_url = \Drupal::config('biz_lobbyist_registration.settings')->get('base_url');
    	$url = $base_url . \Drupal::config('biz_block_plugin.settings')->get('get_comments') ."?_format=json&id=" . $id;
        $request_options = (new self)->get_request_options(TRUE);
        $get_comments = (new self)->execute_external_api($url, [], 'GET', $request_options);
        $messages = [];
        if($get_comments['code'] !== 400){
                $get_comments = json_decode($get_comments["message"]);
                $title = '<div class="organization info-organization purple-header new-notifications-header">'
                .     '<div class="col-xs-12">'
                .         '<p><strong>'.t('Your messages').'</strong></p>'
                .     '</div>'
                . '</div>';
                $messages[] = array('#type' => 'markup', '#markup' => $title);
                if(!empty($get_comments) && is_array($get_comments)){
                    foreach($get_comments as $key => $comment){
                        if(isset($comment->comment_body) && !empty($comment->comment_body)){
                            $messages[] = array( '#theme' => 'activity_messages', '#subject' => $comment->subject, "#user_name" => $comment->user_name , "#message" => $comment->comment_body, "#date" =>    $comment->changed);
                        }
                    }
                }
                if(count($messages) == 1){
                  $empty_messages = '<div class="header-view info-organization gray-info-container gray-info-container-messages">'
                          .     '<div class="col-xs-12">'
                          .         '<p><strong>'.t("The activity doesn't have any comment").'</strong></p>'
                          .     '</div>'
                          . '</div>';
                  $messages[] = array('#type' => 'markup', '#markup' => $empty_messages);
                }
        }
        else{
            \Drupal::logger('BizWebformController')->error('Comments could not be obtained: '. $get_comments["message"]);
            return FALSE;
        }
        return $messages;
	}

	static function webforms_patching($webform_id, $submission_id, $data, $user_patch = NULL){
    	$language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    	$current_user = empty($user_patch) ?  \Drupal::currentUser() : $user_patch;
    	
        $email = $current_user->getEmail();
        $base_url = \Drupal::config('biz_lobbyist_registration.settings')->get('base_url');
        $endpoint = \Drupal::config('biz_lobbyist_registration.settings')->get('json_path');
        $url =  $base_url . $language .'/webform-rest-adaptation/' . $webform_id . '/submission/' . $submission_id;
        $url_user = $base_url . $language . '/'. $endpoint . urlencode(\Drupal::currentUser()->getEmail());

        $request_options = (new self)->get_request_options(TRUE);
        $user = (new self)->execute_external_api($url_user, [], 'GET', $request_options);

        if($user['code'] == 400){
            \Drupal::logger('BizWebformController')->error('Patch:' . json_encode($user));
            \Drupal::messenger()->addMessage(t('The website encountered an unexpected error. Please try again later.'), 'error');
            (new self)->user_redirect(\Drupal::currentUser());
        }
        $user = isset(json_decode($user['message'])[0]) ? json_decode($user['message'])[0] : [];
        if(empty($user_patch)){
            $data['uid']= $user->uid;
        }
        $data['user_uid']= $user->uid;

        $patch_activity = (new self)->execute_external_api($url, $data, 'PATCH', $request_options);
        if($patch_activity['code'] == 400){
            \Drupal::logger('BizWebformController')->error(json_encode($patch_activity));
            \Drupal::messenger()->addMessage(t('The website encountered an unexpected error. Please try again later.'), 'error');
        }
        else{
            \Drupal::messenger()->addMessage(t('The information has been updated.'), 'status');
        }

	}

	static function webforms_patching_from_js($webform_id, $submission_id, $data){
    	if(!is_array($data)){
        	$data = json_decode($data, true);
    	}
    	$user = \Drupal\user\Entity\User::load(json_encode( $data['user_uid']));
        (new self)->webforms_patching($webform_id, $submission_id, $data, $user);
        return new ModifiedResourceResponse();
  }
}

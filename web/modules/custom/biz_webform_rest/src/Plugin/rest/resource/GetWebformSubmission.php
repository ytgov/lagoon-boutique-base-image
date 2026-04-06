<?php

namespace Drupal\biz_webform_rest\Plugin\rest\resource;

use Drupal\webform\Entity\Webform;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ModifiedResourceResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Creates a resource for retrieving webform elements.
 *
 * @RestResource(
 *   id = "biz_webform_rest_get_endpoint",
 *   label = @Translation("Return endpoint for user"),
 *   uri_paths = {
 *     "canonical" = "/api/activity/{submission_id}/{webform_id}/{email}"
 *   }
 * )
 */
class GetWebformSubmission extends ResourceBase {

    /**
     * Responds to GET requests, returns information depends if is the owner or is a public user.
     *
     * @param string $submission_id
     *     Email.
     *
     * @param string $webform_id
     *     Webform ID.
     *
     * @param string $email
     *     Email.
     *
     * @return \Drupal\rest\ResourceResponse
     *     HTTP response object containing webform elements.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *     Throws HttpException in case of error.
     */
    public function get($submission_id, $webform_id, $email) {
        $is_commissioner = FALSE;
        $database = \Drupal::database();
        $query = $database->select('webform_submission', 'ws');
        $query->join('users_field_data', 'ufd', 'ws.uid = ufd.uid');
        $query->fields('ufd', ['mail']);
        $query->condition('ws.sid', $submission_id, '=');
        $query->condition('ws.webform_id', $webform_id, '=');
        $query->condition('ufd.mail', $email, '=');

        $num_rows = $query->countQuery()->execute()->fetchField(0);
        $is_owner = $num_rows > 0 ? TRUE : FALSE;
        $user = user_load_by_mail($email);
        if($user){
            $roles = $user->getRoles();
            $is_commissioner = in_array("role_administrator", $roles);
        }

        switch($webform_id){
            case 'add_a_lobbying_activity':
                $endpoint = $is_owner || $is_commissioner ? 'api/in-house-activity':'public/api/in-house-activity' ;
            break;
            case 'add_a_lobbying_activity_consulta':
                $endpoint = $is_owner || $is_commissioner  ? 'api/consultant-activity':'public/api/consultant-activity' ;
            break;
            case 'add_new_in_house_lobbyist':
                $endpoint = $is_owner || $is_commisssioner  ? 'api/in-house-lobbyist-list':'public/api/in-house-lobbyist-list' ;
            break;

        }
        $result = ['owner' => $is_owner, 'endpoint' => $endpoint];

        return new ModifiedResourceResponse($result);
    }
 }

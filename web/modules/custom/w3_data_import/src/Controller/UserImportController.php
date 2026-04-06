<?php

namespace Drupal\w3_data_import\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Response;

class UserImportController extends ControllerBase {

  public function content() {
    $site_link = \Drupal::config('w3_data_import.theme_settings')->get('site_basic_path');
    if (empty($site_link)) {
        echo "Please add the site basic path to continue data import"; die;
    }
    $http_client = \Drupal::service('http_client');

    try {
      $response = $http_client->get($site_link . '/api/users?_format=json');
      $data = json_decode($response->getBody());
    }
    catch (\Exception $e) {
      \Drupal::logger('w3_data_import')->error($e->getMessage());
      return new Response('Error fetching users.');
    }

    foreach ($data as $user_values1) {

      try {

        $email = trim($user_values1->mail);

        // ✅ Skip if user already exists
        if (user_load_by_mail($email)) {
          \Drupal::logger('w3_data_import')->notice("$email already exists.");
          continue;
        }

        // ✅ Create user
        $user = User::create([
          'name' => $user_values1->name,
          'mail' => $email,
          'status' => $user_values1->status ?? 1,
          'pass' => $user_values1->pass, // Plain password (Drupal hashes automatically)
        ]);
        

        // ✅ Set roles (avoid anonymous/authenticated manually)
        if (!empty($user_values1->roles)) {
          foreach ($user_values1->roles as $role) {
            if (!in_array($role, ['anonymous'])) {
              $user->addRole($role);
            }
          }
        }

        // ✅ Custom fields
        $user->set('field_first_name', $user_values1->field_first_name ?? '');
        $user->set('field_last_name', $user_values1->field_last_name ?? '');
        $user->set('field_telephone', $user_values1->field_telephone ?? '');
        $user->set('field_position', $user_values1->field_position ?? '');
        $user->set('field_legal_organization', $user_values1->field_legal_organization ?? '');
        $user->set('field_operating_organization', $user_values1->field_operating_organization ?? '');

        // ✅ Address field (SINGLE VALUE)
        if (!empty($user_values1->field_street_address)) {

          $address = [
            'country_code' => $user_values1->field_street_address->country,
            'address_line1' => $user_values1->field_street_address->address_line1,
            'locality' => $user_values1->field_street_address->city,
            'administrative_area' => $user_values1->field_street_address->state,
            'postal_code' => $user_values1->field_street_address->postal_code,
          ];

          $user->set('field_street_address', $address);
        }

        // ✅ Preferred language
        $user->set('preferred_langcode', \Drupal::languageManager()->getDefaultLanguage()->getId());

        // ✅ Save user
        $user->save();

        \Drupal::logger('w3_data_import')->notice("User {$email} imported successfully.");

      }
      catch (\Exception $e) {
        \Drupal::logger('w3_data_import')->error($e->getMessage());
      }
    }

    return new Response('User import completed.');
  }

}

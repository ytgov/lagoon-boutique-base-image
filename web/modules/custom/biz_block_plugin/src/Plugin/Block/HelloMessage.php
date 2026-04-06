<?php

namespace Drupal\biz_block_plugin\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;

/**
 * Provides a custom block.
 *
 * @Block(
 *   id = "hello_msg_block",
 *   admin_label = @Translation("Hello Message block"),
 *   category = @Translation("Bizont custom block")
 * )
 */
class HelloMessage extends BlockBase implements BlockPluginInterface {

  /**
   * Display the user name in the navbar.
   */
  public function build() {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $user = \Drupal::currentUser();
    $user_entity = \Drupal::entityTypeManager()->getStorage('user')->load($user->id());
    $roles = $user->getRoles();
    $name = '';
    $first_name = "";
    $last_name = "";
    switch (TRUE) {
      case in_array('in_house_lobbyist', $roles):
        $first_name = $user_entity->get('field_first_name')->getValue();
        $first_name = isset($first_name[0]) ? $first_name[0]['value'] : "";
        $last_name = $user_entity->get('field_last_name')->getValue();
        $last_name = isset($last_name[0]) ? $last_name[0]['value'] : "";
        $name = $first_name . ' ' . $last_name;
        $url = '/' . $language . '/in-house-account-home';
        break;

      case in_array('consultant_lobbyist', $roles):
        $first_name = $user_entity->get('field_first_name_consultant_')->getValue();
        $first_name = isset($first_name[0]) ? $first_name[0]['value'] : "";
        $last_name = $user_entity->get('field_last_name_consultant_')->getValue();
        $last_name = isset($last_name[0]) ? $last_name[0]['value'] : "";
        $name = $first_name . ' ' . $last_name;
        $url = '/' . $language . '/consultant-account-home';
        break;

      case in_array('role_administrator', $roles):
        $name = $user->getAccountName();
        $url = '/' . $language . '/admin-dashboard';
        break;
    }
    if (!empty($name)) {
      $tag = '<a href=' . $url . '>' . t('Hello') . ', ' . $name . '</a>';
      $content[] = ['#type' => 'markup', '#markup' => $tag];
      return $content;
    }
  }

  /**
   * Disable caching for this block.
   */
  public function getCacheMaxAge() {
    return 0;
  }

}

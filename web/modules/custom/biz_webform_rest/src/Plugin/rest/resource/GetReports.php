<?php

namespace Drupal\biz_webform_rest\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\views\Views;

/**
 * Creates a resource for retrieving webform elements.
 *
 * @RestResource(
 *   id = "biz_get_reports",
 *   label = @Translation("Get Reports"),
 *   uri_paths = {
 *     "canonical" = "/reports/{type}"
 *   }
 * )
 */
class GetReports extends ResourceBase {

  /**
   * Responds to GET reports, returns activities.
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
  public function get($type) {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $result = FALSE;
    $view = Views::getView('search_organization');
    $param = \Drupal::request()->query->all();
    $start = isset($param['start']) ? $param['start'] : "0";
    $draw = isset($param['draw']) ? $param['draw'] : "1";
    $order_by = isset($param['orderby']) ? $param['orderby'] : "0";
    $order_dir = isset($param['orderdir']) ? $param['orderdir'] : "asc";

    if (is_object($view)) {
      $view->setDisplay($type);
      $view->execute();
      $versions['recordsTotal'] = $view->total_rows;
      $versions['recordsFiltered'] = $view->total_rows;

      $view = Views::getView('search_organization');
      $view->setDisplay($type);

      $view->setItemsPerPage(50);
      $view->setOffset($start);
      $view->usePager();
      $view->setExposedInput(['sort_by' => $order_by, 'sort_order' => $order_dir]);

      $view->execute();
      // Render the view.
      $result = \Drupal::service('renderer')->render($view->render());
      $versions['data'] = json_decode($result, TRUE);
      $versions['draw'] = intval($draw);

    }

    return new ModifiedResourceResponse(json_encode($versions));
  }

}

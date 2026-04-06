<?php
  
  /**
 * @file
 * Preprocessors and helper functions to make theming easier.
 */

  use Drupal\Component\Utility\Html;
  use Drupal\Component\Utility\Crypt;
  use Drupal\Core\Link;
  use Drupal\Core\Render\Element;
  use Drupal\Core\Render\Markup;
  use Drupal\Core\Url;
  use Drupal\Core\Render\Element\RenderElement;
  use Drupal\Core\Template\Attribute;
  
/**
 * Prepares variables for webform templates.
 *
 * Default template: custom-table.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #action, #method, #attributes, #webform_children.
 */
function template_preprocess_custom_table(&$variables) {
  // Format the table columns:
  if (!empty($variables['colgroups'])) {
    foreach ($variables['colgroups'] as &$colgroup) {

      // Check if we're dealing with a simple or complex column
      if (isset($colgroup['data'])) {
        $cols = $colgroup['data'];
        unset($colgroup['data']);
        $colgroup_attributes = $colgroup;
      }
      else {
        $cols = $colgroup;
        $colgroup_attributes = array();
      }
      $colgroup = array();
      $colgroup['attributes'] = new Attribute($colgroup_attributes);
      $colgroup['cols'] = array();

      // Build columns.
      if (is_array($cols) && !empty($cols)) {
        foreach ($cols as $col_key => $col) {
          $colgroup['cols'][$col_key]['attributes'] = new Attribute($col);
        }
      }
    }
  }

  // Build an associative array of responsive classes keyed by column.
  $responsive_classes = array();

  // Format the table header:
  $ts = array();
  $header_columns = 0;
  if (!empty($variables['header'])) {
    // Use a separate index with responsive classes as headers
    // may be associative.
    $responsive_index = -1;
    foreach ($variables['header'] as $col_key => $cell) {

      // Increase the responsive index.
      $responsive_index++;
      if (!is_array($cell)) {
        $header_columns++;
        $cell_content = $cell;
        $cell_attributes = new Attribute();
        $is_header = TRUE;
      }
      else {
        if (isset($cell['colspan'])) {
          $header_columns += $cell['colspan'];
        }
        else {
          $header_columns++;
        }
        $cell_content = '';
        if (isset($cell['data'])) {
          $cell_content = $cell['data'];
          unset($cell['data']);
        }

        // Flag the cell as a header or not and remove the flag.
        $is_header = isset($cell['header']) ? $cell['header'] : TRUE;
        unset($cell['header']);

        // Track responsive classes for each column as needed. Only the header
        // cells for a column are marked up with the responsive classes by a
        // module developer or themer. The responsive classes on the header cells
        // must be transferred to the content cells.
        if (!empty($cell['class']) && is_array($cell['class'])) {
          if (in_array(RESPONSIVE_PRIORITY_MEDIUM, $cell['class'])) {
            $responsive_classes[$responsive_index] = RESPONSIVE_PRIORITY_MEDIUM;
          }
          elseif (in_array(RESPONSIVE_PRIORITY_LOW, $cell['class'])) {
            $responsive_classes[$responsive_index] = RESPONSIVE_PRIORITY_LOW;
          }
        }
        $cell_attributes = new Attribute($cell);
      }
      $variables['header'][$col_key] = array();
      $variables['header'][$col_key]['tag'] = $is_header ? 'th' : 'td';
      $variables['header'][$col_key]['attributes'] = $cell_attributes;
      $variables['header'][$col_key]['content'] = $cell_content;
    }
  }
  $variables['header_columns'] = $header_columns;

  // Rows and footer have the same structure.
  $sections = array(
    'rows',
    'footer',
  );
  foreach ($sections as $section) {
    if (!empty($variables[$section])) {
      foreach ($variables[$section] as $row_key => $row) {
        $cells = $row;
        $row_attributes = array();

        // Check if we're dealing with a simple or complex row
        if (isset($row['data'])) {
          $cells = $row['data'];
          $variables['no_striping'] = isset($row['no_striping']) ? $row['no_striping'] : FALSE;

          // Set the attributes array and exclude 'data' and 'no_striping'.
          $row_attributes = $row;
          unset($row_attributes['data']);
          unset($row_attributes['no_striping']);
        }

        // Build row.
        $variables[$section][$row_key] = array();
        $variables[$section][$row_key]['attributes'] = new Attribute($row_attributes);
        $variables[$section][$row_key]['cells'] = array();
        if (!empty($cells)) {

          // Reset the responsive index.
          $responsive_index = -1;
          foreach ($cells as $col_key => $cell) {

            // Increase the responsive index.
            $responsive_index++;
            if (!is_array($cell)) {
              $cell_content = $cell;
              $cell_attributes = array();
              $is_header = FALSE;
            }
            else {
              $cell_content = '';
              if (isset($cell['data'])) {
                $cell_content = $cell['data'];
                unset($cell['data']);
              }
              // Flag the cell as a header or not and remove the flag.
              $is_header = !empty($cell['header']);
              unset($cell['header']);
              $cell_attributes = $cell;
            }

            // Copy RESPONSIVE_PRIORITY_LOW/RESPONSIVE_PRIORITY_MEDIUM
            // class from header to cell as needed.
            if (isset($responsive_classes[$responsive_index])) {
              $cell_attributes['class'][] = $responsive_classes[$responsive_index];
            }
            $variables[$section][$row_key]['cells'][$col_key]['tag'] = $is_header ? 'th' : 'td';
            $variables[$section][$row_key]['cells'][$col_key]['attributes'] = new Attribute($cell_attributes);
            $variables[$section][$row_key]['cells'][$col_key]['content'] = $cell_content;
          }
        }
      }
    }
  }
  if (empty($variables['no_striping'])) {
    $variables['attributes']['data-striping'] = 1;
  }
}

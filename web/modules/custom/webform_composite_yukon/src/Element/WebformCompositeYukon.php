<?php
namespace Drupal\webform_composite_yukon\Element;
use Drupal\Component\Utility\NestedArray;
use Drupal\webform\Element\WebformMultiple;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\Core\DateTime\DrupalDateTime;
/**
 * Provides a 'webform_composite_yukon'.
 *
 * Webform composites contain a group of sub-elements.
 *
 *
 * IMPORTANT:
 * Webform composite can not contain multiple value elements (i.e. checkboxes)
 * or composites (i.e. webform_address)
 *
 * @FormElement("webform_composite_yukon")
 *
 * @see \Drupal\webform\Element\WebformCompositeBase
 * @see \Drupal\webform_composite_yukon\Element\WebformExampleComposite
 */
class WebformCompositeYukon extends WebformMultiple {
  public $add;
  private static $temp_items = [];
  private static $temp_keys = [];
  /**
   * Process items and build multiple elements widget.
   */
  public static function processWebformMultiple(&$element, FormStateInterface $form_state, &$complete_form) {
    // Set tree.
    $element['#tree'] = TRUE;

    // Remove 'for' from the element's label.
    $element['#label_attributes']['webform-remove-for-attribute'] = TRUE;

    // Set min items based on when the element is required.
    if (!isset($element['#min_items']) || $element['#min_items'] === '') {
      $element['#min_items'] = (empty($element['#required'])) ? 0 : 1;
    }

    // Make sure min items does not exceed cardinality.
    if (!empty($element['#cardinality']) && $element['#min_items'] > $element['#cardinality']) {
      $element['#min_items'] = $element['#cardinality'];
    }

    // Make sure empty items does not exceed cardinality.
    if (!empty($element['#cardinality']) && $element['#empty_items'] > $element['#cardinality']) {
      $element['#empty_items'] = $element['#cardinality'];
    }

    // Add validate callback that extracts the array of items.
    $element += ['#element_validate' => []];
    array_unshift($element['#element_validate'], [get_called_class(), 'validateWebformMultiple']);

    // Wrap this $element in a <div> that handle #states.
    WebformElementHelper::fixStatesWrapper($element);

    // Get unique key used to store the current number of items.
    $number_of_items_storage_key = static::getStorageKey($element, 'number_of_items');

    // Store the number of items which is the number of
    // #default_values + number of empty_items.
    if ($form_state->get($number_of_items_storage_key) === NULL) {
      if (empty($element['#default_value']) || !is_array($element['#default_value'])) {
        $number_of_default_values = 0;
      }
      else {
        $number_of_default_values = count($element['#default_value']);
      }
      $number_of_empty_items = (int) $element['#empty_items'];
      $number_of_items = $number_of_default_values + $number_of_empty_items;

      // Make sure number of items is greated than min items.
      $min_items = (int) $element['#min_items'];
      $number_of_items = ($number_of_items < $min_items) ? $min_items : $number_of_items;

      // Make sure number of (default) items does not exceed cardinality.
      if (!empty($element['#cardinality']) && $number_of_items > $element['#cardinality']) {
        $number_of_items = $element['#cardinality'];
      }

      $form_state->set($number_of_items_storage_key, $number_of_items);
    }

    $number_of_items = $form_state->get($number_of_items_storage_key);

    $table_id = implode('_', $element['#parents']) . '_table';

    // Disable add operation when #cardinality is met
    // and make sure to limit the number of items.
    if (!empty($element['#cardinality']) && $number_of_items >= $element['#cardinality']) {
      $element['#add'] = FALSE;
      $number_of_items = $element['#cardinality'];
      $form_state->set($number_of_items_storage_key, $number_of_items);
    }

    // Add wrapper to the element.
    $element += ['#prefix' => '', '#suffix' => ''];
    $element['#prefix'] = '<div id="' . $table_id . '">' . $element['#prefix'];
    $element['#suffix'] .= '</div>';

    // DEBUG:
    // Disable Ajax callback by commenting out the below callback and wrapper.
    $ajax_settings = [
      'callback' => [get_called_class(), 'ajaxCallback'],
      'wrapper' => $table_id,
      'progress' => ['type' => 'none'],
    ];

    // Initialize, prepare, and finalize sub-elements.
    static::initializeElement($element, $form_state, $complete_form);

    // Build (single) element header.
    $header = static::buildElementHeader($element);

    // Build (single) element rows.
    $row_index = 0;
    $weight = 0;
    $rows = [];

    if (!$form_state->isProcessingInput() && isset($element['#default_value']) && is_array($element['#default_value'])) {
      $default_values = $element['#default_value'];
    }
    elseif ($form_state->isProcessingInput() && isset($element['#value']) && is_array($element['#value'])) {
      $default_values = $element['#value'];
    }
    else {
      $default_values = [];
    }

    // When adding/removing elements we don't need to set any default values.
    $action_key = static::getStorageKey($element, 'action');
    if ($form_state->get($action_key)) {
      $form_state->set($action_key, FALSE);
      $default_values = [];
    }

    foreach ($default_values as $key => $default_value) {
      // If #key is defined make sure to set default value's key item.
      if (!empty($element['#key']) && !isset($default_value[$element['#key']])) {
        $default_value[$element['#key']] = $key;
      }
      $rows[$row_index] = static::buildElementRow($table_id, $row_index, $element, $default_value, $weight++, $ajax_settings);
      $row_index++;
    }

    while ($row_index < $number_of_items) {
      $rows[$row_index] = static::buildElementRow($table_id, $row_index, $element, NULL, $weight++, $ajax_settings);
      $row_index++;
    }

    // Build table.
    $attributes = ['class' => ['webform-multiple-table']];
    if (count($element['#element']) > 1) {
      $attributes['class'][] = 'webform-multiple-table-responsive';
    }
    $element['items'] = [
      '#prefix' => '<div' . new Attribute($attributes) . '>',
      '#suffix' => '</div>',
    ] + $rows;

    // Display table if there are any rows.
    if ($rows) {
      $element['items'] += [
        '#type' => 'table',
        '#header' => $header,
        '#attributes' => ['class' => ['slick-carousel']]
      ] + $rows;

      // Add sorting to table.
      if ($element['#sorting']) {
        $element['items']['#tabledrag'] = [
          [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => 'webform-multiple-sort-weight',
          ],
        ];
      }
    }
    elseif (!empty($element['#no_items_message'])) {
      $element['items'] += [
        '#type' => 'webform_message',
        '#message_message' => $element['#no_items_message'],
        '#message_type' => 'info',
        '#attributes' => ['class' => ['webform-multiple-table--no-items-message']],
      ];
    }

    if ($rows) {
      $element['records'] = array(
        '#type'       => 'container',
        '#attributes' => array(
          'class' => array(
            'record-wrapper',
          // NOTE: WxT grids are spec'd to have rows/cols "contained" with this
          // class but it adds left/right padding that's undesired. Omit for now.
          //'container-fluid',
          ),
        ),
      );

      $element['records']['header'] = array(
        '#type' => 'container',
        '#markup' => '<hr><h3>' . t('Records to be requested') . '</h3>',
      );
  

      $index = 0;
      if (!empty(self::$temp_items)) {
        foreach($rows as $value) {
          if ($index == count(self::$temp_items)) { break; }
          $element['records']['children-'.t(strval($index))] = array(
            '#type' => 'container',
            '#attributes' => array(
              'class' => array(
                'responses-table',
              ),
            ),
          );
          $element['records']['children-'.t(strval($index))]['remove'] = [
            '#type' => 'submit',
            '#title' => t('Remove'),
            '#value' => t("Don't request"),
            '#limit_validation_errors' => [],
            '#submit' => [[get_called_class(), 'removeItemSubmit']],
            '#row_index' => $index,
            '#name' => $table_id . '_remove_' . $index,
            '#attributes' => array(
              'class' => array(
                'btn btn-danger pull-right',
              ),
            ),
          ];
          foreach($value as $item) {
            $subIndex = 0;
            foreach ($item as $key => $data) {
              if (isset($data['#required'])) {
                if ($data['#required'] && empty(self::$temp_items[$index]['_item_'][$key])) {
                  $element['records']['children-'.t(strval($index))] = []; 
                  break 2;
                }
              }
              if (!empty(self::$temp_items[$index]['_item_'][$key])) {
                switch ($data['#type']) {
                  case 'select':
                    $element['records']['children-'.t(strval($index))]['child-'.t(strval($subIndex))] = [
                      '#type' => 'container',
                      '#markup' => '<p><strong>' . $data['#title'] . '</strong> ' . t(json_encode($data['#options'][self::$temp_items[$index]['_item_'][$key]])) . '</p>',
                    ];
                    break;
                  case 'radios':
                    $element['records']['children-'.t(strval($index))]['child-'.t(strval($subIndex))] = [
                      '#type' => 'container',
                      '#markup' => '<p><strong>' . $data['#title'] . '</strong> ' . t(json_encode($data['#options'][self::$temp_items[$index]['_item_'][$key]])) . '</p>',
                    ];
                    break;
                  case 'datelist':
                    $monthInt = self::$temp_items[$index]['_item_'][$key]['month'];
                    $monthName = DrupalDateTime::createFromFormat('m', $monthInt)->format('F');
                    $element['records']['children-'.t(strval($index))]['child-'.t(strval($subIndex))] = [
                      '#type' => 'container',
                      '#markup' => '<p><strong>' . $data['#title'] . '</strong> ' . t(self::$temp_items[$index]['_item_'][$key]['year']) . ' ' .t($monthName) . '</p>',
                    ];
                    break;
                  default:
                    $element['records']['children-'.t(strval($index))]['child-'.t(strval($subIndex))] = [
                      '#type' => 'container',
                      '#markup' => '<p><strong>' . $data['#title'] . '</strong> ' . t(json_encode(self::$temp_items[$index]['_item_'][$key])) . '</p>',
                    ];
                    break;
                }
                $subIndex += 1;
              }
            }
          }
          $index += 1;
        }
      }

    }

    // Build add more actions.
    if ($element['#add_more'] && (empty($element['#cardinality']) || ($number_of_items < $element['#cardinality']))) {
      $element['add'] = [
        '#prefix' => '<div class="webform-multiple-add js-webform-multiple-add container-inline">',
        '#suffix' => '</div>',
      ];
      $element['add']['submit'] = [
        '#type' => 'submit',
        '#value' => $element['#add_more_button_label'],
        '#limit_validation_errors' => [],
        '#submit' => [[get_called_class(), 'addItemsSubmit']],
        '#ajax' => $ajax_settings,
        '#name' => $table_id . '_add',
      ];
      $max = ($element['#cardinality']) ? $element['#cardinality'] - $number_of_items : 100;
      $element['add']['more_items'] = [
        '#type' => 'number',
        '#title' => $element['#add_more_button_label'] . ' ' . $element['#add_more_input_label'],
        '#title_display' => 'invisible',
        '#min' => 1,
        '#max' => 1,
        '#default_value' => $element['#add_more_items'],
        '#field_suffix' => $element['#add_more_input_label'],
        '#error_no_message' => TRUE,
      ];
    }

    $element['#attached']['library'][] = 'webform/webform.element.multiple';

    return $element;
  }

  protected static function buildElementRow($table_id, $row_index, array $element, $default_value, $weight, array $ajax_settings) {
    if ($element['#child_keys']) {
      static::setElementRowDefaultValueRecursive($element['#element'], (array) $default_value);
    }
    else {
      static::setElementDefaultValue($element['#element'], $default_value);
    }

    $hidden_elements = [];
    $row = [];

    if ($element['#sorting']) {
      $row['_handle_'] = [
        '#wrapper_attributes' => [
          'class' => ['webform-multiple-table--handle'],
        ],
      ];
    }

    if ($element['#child_keys'] && !empty($element['#header'])) {
      // Set #parents which is used for nested elements.
      // @see \Drupal\webform\Element\WebformMultiple::setElementRowParentsRecursive
      $parents = array_merge($element['#parents'], ['items', $row_index]);
      $hidden_parents = array_merge($element['#parents'], ['items', $row_index, '_hidden_']);
      foreach ($element['#child_keys'] as $child_key) {
        // Store hidden element in the '_handle_' column.
        // @see \Drupal\webform\Element\WebformMultiple::convertValuesToItems
        if (static::isHidden($element['#element'][$child_key])) {
          $hidden_elements[$child_key] = $element['#element'][$child_key];
          // ISSUE:
          // All elements in _handle_ with #access: FALSE are losing
          // their values.
          //
          // Moving these #access: FALSE and value elements outside of the
          // table does not work. What is even move baffling is manually adding
          // a 'value' element does work.
          //
          // $element['hidden'][$row_index][$child_key] = $element['#element'][$child_key];
          // $element['hidden'][1000]['test'] = ['#type' => 'value', '#value' => 'test'];
          //
          // WORKAROUND:
          // Convert element to rendered hidden element.
          if (!isset($element['#access']) || $element['#access'] !== FALSE) {
            $hidden_elements[$child_key]['#type'] = 'hidden';
            // Unset #access, #element_validate, and #pre_render.
            // @see \Drupal\webform\Plugin\WebformElementBase::prepare()
            // Unset #options to prevent An illegal choice has been detected.
            // @see \Drupal\Core\Form\FormValidator::performRequiredValidation
            unset(
              $hidden_elements[$child_key]['#access'],
              $hidden_elements[$child_key]['#element_validate'],
              $hidden_elements[$child_key]['#pre_render'],
              $hidden_elements[$child_key]['#options']
            );
          }
          static::setElementRowParentsRecursive($hidden_elements[$child_key], $child_key, $hidden_parents);
        }
        else {
          $row[$child_key] = $element['#element'][$child_key];
          static::setElementRowParentsRecursive($row[$child_key], $child_key, $parents);
        }
      }
    }
    else {
      $row['_item_'] = $element['#element'];
    }

    if ($element['#sorting']) {
      $row['weight'] = [
        '#type' => 'weight',
        '#delta' => 1000,
        '#title' => t('Item weight'),
        '#title_display' => 'invisible',
        '#attributes' => [
          'class' => ['webform-multiple-sort-weight'],
        ],
        '#wrapper_attributes' => [
          'class' => ['webform-multiple-table--weight'],
        ],
        '#default_value' => $weight,
      ];
    }

    // Allow users to add & remove rows if cardinality is not set.
    if ($element['#operations']) {
      $row['_operations_'] = [
        '#wrapper_attributes' => [
          'class' => ['webform-multiple-table--operations'],
        ],
      ];
      if ($element['#add'] && $element['#remove']) {
        $row['_operations_']['#wrapper_attributes']['class'][] = 'webform-multiple-table--operations-two';
      }
      if ($element['#add']) {
        $row['_operations_']['add'] = [
          '#type' => 'image_button',
          '#title' => t('Add'),
          '#src' => drupal_get_path('module', 'webform') . '/images/icons/plus.svg',
          '#limit_validation_errors' => [],
          '#submit' => [[get_called_class(), 'addItemSubmit']],
          '#ajax' => $ajax_settings,
          // Issue #1342066 Document that buttons with the same #value need a unique
          // #name for the Form API to distinguish them, or change the Form API to
          // assign unique #names automatically.
          '#row_index' => $row_index,
          '#name' => $table_id . '_add_' . $row_index,
          '#attributes' => array(
            'class' => array(
              'hidden',
            ),
          ),
        ];
      }
      if ($element['#remove']) {
        $row['_operations_']['remove'] = [
          '#type' => 'image_button',
          '#title' => t('Remove'),
          '#src' => drupal_get_path('module', 'webform') . '/images/icons/ex.svg',
          '#limit_validation_errors' => [],
          '#submit' => [[get_called_class(), 'removeItemSubmit']],
          '#ajax' => $ajax_settings,
          // Issue #1342066 Document that buttons with the same #value need a unique
          // #name for the Form API to distinguish them, or change the Form API to
          // assign unique #names automatically.
          '#row_index' => $row_index,
          '#name' => $table_id . '_remove_' . $row_index,
          '#attributes' => array(
            'class' => array(
              'hidden',
            ),
          ),
        ];
      }
    }

    // Add hidden element as a hidden row.
    if ($hidden_elements) {
      $row['_hidden_'] = $hidden_elements + [
        '#wrapper_attributes' => ['style' => 'display: none'],
      ];
    }

    if ($element['#sorting']) {
      $row['#attributes']['class'][] = 'draggable';
      $row['#weight'] = $weight;
    }

    return $row;
  }

  /****************************************************************************/
  // Callbacks.
  /****************************************************************************/

  /**
   * Webform submission handler for adding more items.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */

  public static function addItemsSubmit(array &$form, FormStateInterface $form_state) {

    // Get the webform list element by going up two levels.
    $button = $form_state->getTriggeringElement();
    $element =& NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));
    // Add more items to the number of items.
    $number_of_items_storage_key = static::getStorageKey($element, 'number_of_items');
    $number_of_items = $form_state->get($number_of_items_storage_key);
    $more_items = (int) $element['add']['more_items']['#value'];
    $form_state->set($number_of_items_storage_key, $number_of_items + $more_items);

    // Reset values.
    $items = (!empty($element['items']['#value'])) ? array_values($element['items']['#value']) : [];
    $element['items']['#value'] = $items;
    self::$temp_items = $items;
    self::$temp_keys = (!empty($element['add'])) ? array_values($element['add']) : [];
    $form_state->setValueForElement($element['items'], $items);
    NestedArray::setValue($form_state->getUserInput(), $element['items']['#parents'], $items);

    $action_key = static::getStorageKey($element, 'action');

    $form_state->set($action_key, TRUE);
    // Rebuild the form.
    $form_state->setRebuild();

  }

  public static function removeItemSubmit(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -4));
    $values = $element['items']['#value'];
   
    // Remove item.
    \Drupal::logger('before')->notice(json_encode($values));
    unset($values[$button['#row_index']]);
    // self::$temp_items = $values;
    $values = array_values($values);
    \Drupal::logger('after')->notice(json_encode($values));
    self::$temp_items = $values;
    // Remove one item from the 'number of items'.
    $number_of_items_storage_key = static::getStorageKey($element, 'number_of_items');
    $number_of_items = $form_state->get($number_of_items_storage_key);
    // Never allow the number of items to be less than #min_items.
    if ($number_of_items > $element['#min_items']) {
      $form_state->set($number_of_items_storage_key, $number_of_items - 1);
    }

    // Reset values.
    $form_state->setValueForElement($element['items'], $values);
    NestedArray::setValue($form_state->getUserInput(), $element['items']['#parents'], $values);

    $action_key = static::getStorageKey($element, 'action');
    $form_state->set($action_key, TRUE);

    // Rebuild the form.
    $form_state->setRebuild();
  }


  /**
   * Webform submission handler for adding an item.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
/*
  
  /**
   * Webform submission Ajax callback the returns the list table.
   */
  public static function ajaxCallback(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $parent_length = (isset($button['#row_index'])) ? -4 : -2;
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, $parent_length));

    return $element;
  }

}

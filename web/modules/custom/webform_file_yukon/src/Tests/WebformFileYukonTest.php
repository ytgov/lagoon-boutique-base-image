<?php

namespace Drupal\webform_file_yukon\Tests;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform example element.
 *
 * @group Webform
 */
class WebformFileYukonTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform_file_yukon'];

  /**
   * Tests webform example element.
   */
  public function testWebformFileYukon() {
    $webform = Webform::load('webform_file_yukon');

    // Check form element rendering.
    $this->drupalGet('/webform/webform_file_yukon');
    // NOTE:
    // This is a very lazy but easy way to check that the element is rendering
    // as expected.
    $this->assertRaw('<div class="js-form-item form-item js-form-type-webform-file-yukon form-type-webform-file-yukon js-form-item-webform-file-yukon form-item-webform-file-yukon">');
    $this->assertRaw('<label for="edit-webform-file-yukon">Webform Yoo Element</label>');
    $this->assertRaw('<input data-drupal-selector="edit-webform-file-yukon" type="text" id="edit-webform-file-yukon" name="webform_file_yukon" value="" size="60" class="form-text webform-file-yukon" />');

    // Check webform element submission.
    $edit = [
      'webform_file_yukon' => '{Test}',
      'webform_file_yukon_multiple[items][0][_item_]' => '{Test 01}',
    ];
    $sid = $this->postSubmission($webform, $edit);
    $webform_submission = WebformSubmission::load($sid);
    $this->assertEqual($webform_submission->getElementData('webform_file_yukon'), '{Test}');
    $this->assertEqual($webform_submission->getElementData('webform_file_yukon_multiple'), ['{Test 01}']);
  }

}

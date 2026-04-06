<?php

namespace Drupal\Tests\webform_composite_simple_person_info\Functional;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for composite simple person info.
 *
 * @group Webform
 */
class WebformCompositeSimplePersonInfoTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform_composite_simple_person_info'];

  /**
   * Tests webform example element.
   */
  public function testWebformCompositeSimplePersonInfo() {
    $webform = Webform::load('webform_composite_simple_person_info');

    // Check form element rendering.
    $this->drupalGet('/webform/webform_composite_simple_person_info');
    // NOTE:
    // This is a very lazy but easy way to check that the element is rendering
    // as expected.
    $this->assertRaw('<label for="edit-webform-composite-simple-person-info-first-name">First name</label>');
    $this->assertFieldById('edit-webform-composite-simple-person-info-first-name');
    $this->assertRaw('<label for="edit-webform-composite-simple-person-info-last-name">Last name</label>');
    $this->assertFieldById('edit-webform-composite-simple-person-info-last-name');
    $this->assertRaw('<label for="edit-webform-composite-simple-person-info-date-of-birth">Date of birth</label>');
    $this->assertFieldById('edit-webform-composite-simple-person-info-date-of-birth');
    $this->assertRaw('<label for="edit-webform-composite-simple-person-info-gender">Gender</label>');
    $this->assertFieldById('edit-webform-composite-simple-person-info-gender');

    // Check webform element submission.
    $edit = [
      'webform_composite_simple_person_info[first_name]' => 'John',
      'webform_composite_simple_person_info[last_name]' => 'Smith',
      'webform_composite_simple_person_info[gender]' => 'Male',
      'webform_composite_simple_person_info[date_of_birth]' => '1910-01-01',
      'webform_composite_simple_person_info_multiple[items][0][first_name]' => 'Jane',
      'webform_composite_simple_person_info_multiple[items][0][last_name]' => 'Doe',
      'webform_composite_simple_person_info_multiple[items][0][gender]' => 'Female',
      'webform_composite_simple_person_info_multiple[items][0][date_of_birth]' => '1920-12-01',
    ];
    $sid = $this->postSubmission($webform, $edit);
    $webform_submission = WebformSubmission::load($sid);
    $this->assertEqual($webform_submission->getElementData('webform_composite_simple_person_info'), [
      'first_name' => 'John',
      'last_name' => 'Smith',
      'gender' => 'Male',
      'date_of_birth' => '1910-01-01',
    ]);
    $this->assertEqual($webform_submission->getElementData('webform_composite_simple_person_info_multiple'), [
      [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'gender' => 'Female',
        'date_of_birth' => '1920-12-01',
      ],
    ]);
  }

}

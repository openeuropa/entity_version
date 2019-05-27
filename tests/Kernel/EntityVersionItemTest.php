<?php

namespace Drupal\Tests\entity_versions\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;

/**
 * Tests the new entity API for the entity version field type.
 */
class EntityVersionItemTest extends FieldKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_versions'];

  protected function setUp() {
    parent::setUp();

    // Create a generic field for validation.
    FieldStorageConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'entity_test',
      'type' => 'entity_version',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_test',
      'bundle' => 'entity_test',
    ])->save();
  }

  /**
   * Tests using entity fields of the entity version field type.
   */
  public function testEntityVersionItem() {
    // Create entity.
    $entity = EntityTest::create();
    $entity->field_test->title = 'Test';
    $entity->field_test->major = 1;
    $entity->field_test->minor = 1;
    $entity->field_test->patch = 1;
    $entity->save();

    // Verify that the field value is changed.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertEqual($entity->field_test->major, 1);
    $this->assertEqual($entity->field_test->minor, 1);
    $this->assertEqual($entity->field_test->patch, 1);
  }

}

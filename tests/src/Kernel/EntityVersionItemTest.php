<?php

namespace Drupal\Tests\entity_version\Kernel;

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
  public static $modules = ['entity_version'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a generic field for validation.
    FieldStorageConfig::create([
      'field_name' => 'version',
      'entity_type' => 'entity_test',
      'type' => 'entity_version',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'version',
      'bundle' => 'entity_test',
    ])->save();
  }

  /**
   * Tests using entity fields of the entity version field type.
   */
  public function testEntityVersionItem() {
    // Create entity.
    $entity = EntityTest::create();
    $entity->save();

    // Verify that the field default value is zero.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertEqual($entity->version->major, 0);
    $this->assertEqual($entity->version->minor, 0);
    $this->assertEqual($entity->version->patch, 0);

    // Use the field type method to increase the values.
    $entity->version->first()->increase('major');
    $entity->version->first()->increase('minor');
    $entity->version->first()->increase('patch');
    $entity->save();

    // Verify that the field value is changed.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertEqual($entity->version->major, 1);
    $this->assertEqual($entity->version->minor, 1);
    $this->assertEqual($entity->version->patch, 1);

    // Use the field type method to decrease the major number.
    $entity->version->first()->decrease('major');
    $entity->save();

    // Verify that the field value is changed.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertEqual($entity->version->major, 0);
    $this->assertEqual($entity->version->minor, 1);
    $this->assertEqual($entity->version->patch, 1);

    // Use the field type method to decrease values.
    $entity->version->first()->decrease('minor');
    $entity->version->first()->decrease('patch');
    $entity->save();

    // Verify that the field value is changed.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertEqual($entity->version->major, 0);
    $this->assertEqual($entity->version->minor, 0);
    $this->assertEqual($entity->version->patch, 0);

    // Use the field type method to increase the values.
    $entity->version->first()->increase('major');
    $entity->version->first()->increase('minor');
    $entity->version->first()->increase('patch');
    $entity->save();

    // Use the field type method to reset values to zero.
    $entity->version->first()->reset('major');
    $entity->version->first()->reset('minor');
    $entity->version->first()->reset('patch');
    $entity->save();

    // Verify that the field value is reset.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertEqual($entity->version->major, 0);
    $this->assertEqual($entity->version->minor, 0);
    $this->assertEqual($entity->version->patch, 0);

    // Use the field type method to decrease values.
    $entity->version->first()->decrease('major');
    $entity->version->first()->decrease('minor');
    $entity->version->first()->decrease('patch');
    $entity->save();

    // Verify that the field value has not changed.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertEqual($entity->version->major, 0);
    $this->assertEqual($entity->version->minor, 0);
    $this->assertEqual($entity->version->patch, 0);

    // Use the field type method to increase patch.
    $entity->version->first()->increase('patch');
    $entity->save();

    // Verify that the field value is changed.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertEqual($entity->version->major, 0);
    $this->assertEqual($entity->version->minor, 0);
    $this->assertEqual($entity->version->patch, 1);
  }

}

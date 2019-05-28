<?php

namespace Drupal\Tests\entity_version\Kernel;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the new formatter created for entity version field type.
 */
class EntityVersionFormatterTest extends KernelTestBase {

  /**
   * A field storage to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The field used in this test class.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_version',
    'entity_test',
    'field',
    'language',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');
    $this->installConfig(['system']);
    $this->installSchema('system', ['sequences', 'key_value']);

    // Create a generic field for validation.
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => 'version',
      'entity_type' => 'entity_test',
      'type' => 'entity_version',
    ]);
    $this->fieldStorage->save();

    $this->field = FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'version',
      'bundle' => 'entity_test',
    ]);
    $this->field->save();

    $display_options = [
      'type' => 'entity_version_formatter',
      'label' => 'hidden',
      'settings' => [
        'minimum_category' => 'patch',
      ],
    ];
    EntityViewDisplay::create([
      'targetEntityType' => $this->field->getTargetEntityTypeId(),
      'bundle' => $this->field->getTargetBundle(),
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent($this->fieldStorage->getName(), $display_options)
      ->save();
  }

  /**
   * Tests rendering entity fields of the entity version field type.
   */
  public function testEntityVersionFormatter() {
    // Create entity.
    $entity = EntityTest::create();
    $entity->save();

    // Verify the untranslated separator.
    $display = EntityViewDisplay::collectRenderDisplay($entity, 'default');
    $build = $display->build($entity);
    $output = $this->container->get('renderer')->renderRoot($build);
    $this->verbose($output);
    $this->assertContains('0.0.0', (string) $output);
  }

}

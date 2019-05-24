<?php

declare(strict_types = 1);

namespace Drupal\entity_versions\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'entity_version' field type.
 *
 * @FieldType(
 *   id = "entity_version",
 *   label = @Translation("Entity version"),
 *   module = "entity_versions",
 *   description = @Translation("Stores the versions of the entity.")
 * )
 */
class EntityVersion extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'major' => [
          'type' => 'int',
        ],
        'minor' => [
          'type' => 'int',
        ],
        'patch' => [
          'type' => 'int',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {

  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['major'] = DataDefinition::create('integer')
      ->setLabel(t('Major number'));
    $properties['minor'] = DataDefinition::create('integer')
      ->setLabel(t('Minor number'));
    $properties['patch'] = DataDefinition::create('integer')
      ->setLabel(t('Patch number'));

    return $properties;
  }

  /**
   * Increase the given version number category.
   *
   * @param string $category
   */
  public function increase(string $category): void {
    //$value = $this->get('value')->getValue();
  }

  /**
   * Decrease the given version number category.
   *
   * @param string $category
   */
  public function decrease(string $category): void {

  }

  /**
   * Reset the given version number category to zero.
   *
   * @param string $category
   */
  public function reset(string $category): void {

  }

}

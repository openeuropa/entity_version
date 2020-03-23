<?php

declare(strict_types = 1);

namespace Drupal\entity_version_history\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\entity_version_history\HistoryTabSettingsException;
use Drupal\entity_version_history\HistoryTabSettingsInterface;

/**
 * Defines the HistoryTabSettings entity.
 *
 * @ConfigEntityType(
 *   id = "entity_version_history_settings",
 *   label = @Translation("Entity Version History Settings"),
 *   label_collection = @Translation("Entity Version History Settings"),
 *   label_singular = @Translation("entity version history setting"),
 *   label_plural = @Translation("entity version history settings"),
 *   label_count = @PluralTranslation(
 *     singular = "@count entity version history setting",
 *     plural = "@count entity version history settings",
 *   ),
 *   admin_permission = "access entity version history configuration",
 *   config_prefix = "settings",
 *   entity_keys = {
 *     "id" = "id"
 *   },
 *   config_export = {
 *     "id",
 *     "target_entity_type_id",
 *     "target_bundle",
 *     "target_field",
 *   },
 *   list_cache_tags = { "rendered" }
 * )
 */
class HistoryTabSettings extends ConfigEntityBase implements HistoryTabSettingsInterface {

  /**
   * The id. Combination of $target_entity_type_id.$target_bundle.
   *
   * @var string
   */
  protected $id;

  /**
   * The entity type ID (machine name).
   *
   * @var string
   */
  protected $target_entity_type_id;

  /**
   * The bundle (machine name).
   *
   * @var string
   */
  protected $target_bundle;

  /**
   * The target field (machine name).
   *
   * @var string
   */
  protected $target_field = '';

  /**
   * Constructs a HistoryTabSettings object.
   *
   * @param array $values
   *   An array of the referring entity bundle with:
   *   - target_entity_type_id: The entity type.
   *   - target_bundle: The bundle.
   *   Other array elements will be used to set the corresponding properties on
   *   the class; see the class property documentation for details.
   * @param string $entity_type
   *   The entity type id.
   */
  public function __construct(array $values, string $entity_type = 'entity_version_history_settings') {
    if (empty($values['target_entity_type_id'])) {
      throw new HistoryTabSettingsException('Attempt to create entity version history settings without a target_entity_type_id.');
    }
    if (empty($values['target_bundle'])) {
      throw new HistoryTabSettingsException('Attempt to create entity version history settings without a target_bundle.');
    }
    parent::__construct($values, $entity_type);
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.ShortMethodName)
   */
  public function id(): string {
    return $this->target_entity_type_id . '.' . $this->target_bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityTypeId(): string {
    return $this->target_entity_type_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetBundle(): string {
    return $this->target_bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetField(): string {
    return $this->target_field;
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetBundle(string $target_bundle): ConfigEntityInterface {
    $this->target_bundle = $target_bundle;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetField(string $target_field): ConfigEntityInterface {
    $this->target_field = $target_field;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    $this->id = $this->id();
    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): ConfigEntityInterface {
    parent::calculateDependencies();

    // Create dependency on the bundle.
    $entity_type = \Drupal::entityTypeManager()->getDefinition($this->target_entity_type_id);
    $bundle_config_dependency = $entity_type->getBundleConfigDependency($this->target_bundle);
    $this->addDependency($bundle_config_dependency['type'], $bundle_config_dependency['name']);

    return $this;
  }

  /**
   * Loads a HistoryTabSettings config based on the entity type and bundle.
   *
   * @param string $entity_type_id
   *   ID of the entity type.
   * @param string $bundle
   *   Bundle name.
   *
   * @return null|$this
   *   The HistoryTabSettings config entity if one exists. Otherwise, NULL.
   */
  public static function loadByEntityTypeBundle(string $entity_type_id, string $bundle): ?EntityInterface {
    if ($entity_type_id === NULL || $bundle === NULL) {
      return NULL;
    }
    return \Drupal::entityTypeManager()->getStorage('entity_version_history_settings')->load($entity_type_id . '.' . $bundle);
  }

  /**
   * Loads all HistoryTabSettings config based on the entity type.
   *
   * @param string $entity_type_id
   *   ID of the entity type.
   *
   * @return array
   *   Array of HistoryTabSettings config entities if there is any.
   */
  public static function loadByEntityType(string $entity_type_id): array {
    $configs = [];
    if ($entity_type_id === NULL) {
      return $configs;
    }

    $definition = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
    $bundles = \Drupal::entityTypeManager()->getStorage($definition->getBundleEntityType())->loadMultiple();
    foreach ($bundles as $bundle_name => $bundle) {
      $configs[$entity_type_id . '.' . $bundle_name] = \Drupal::entityTypeManager()->getStorage('entity_version_history_settings')->load($entity_type_id . '.' . $bundle_name);
    }

    return $configs;
  }

}

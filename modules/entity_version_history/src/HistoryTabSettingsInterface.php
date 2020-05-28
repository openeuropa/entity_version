<?php

declare(strict_types = 1);

namespace Drupal\entity_version_history;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining history tabs settings for content entities.
 */
interface HistoryTabSettingsInterface extends ConfigEntityInterface {

  /**
   * Gets the entity type ID this config applies to.
   *
   * @return string
   *   Returns the target entity type id.
   */
  public function getTargetEntityTypeId(): string;

  /**
   * Gets the bundle this config applies to.
   *
   * @return string
   *   Returns the name of the target bundle.
   */
  public function getTargetBundle(): string;

  /**
   * Gets the target version field machine name.
   *
   * @return string
   *   Return the target version field machine name.
   */
  public function getTargetField(): string;

  /**
   * Sets the bundle this config applies to.
   *
   * @param string $target_bundle
   *   The bundle.
   *
   * @return $this
   *   Returns the HistoryTabSettings object.
   */
  public function setTargetBundle(string $target_bundle): ConfigEntityInterface;

  /**
   * Sets the target version field to read the versions from.
   *
   * @param string $target_field
   *   The target version field machine name.
   *
   * @return $this
   *   Returns the HistoryTabSettings object.
   */
  public function setTargetField(string $target_field): ConfigEntityInterface;

}

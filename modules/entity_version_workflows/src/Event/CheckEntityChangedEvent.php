<?php

declare(strict_types = 1);

namespace Drupal\entity_version_workflows\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event class to be dispatched from EntityVersionWorkflowManager service.
 */
class CheckEntityChangedEvent extends Event {

  const EVENT = 'entity_version_worfklows.check_entity_changed_event';

  /**
   * The array of field names to exclude from change check.
   *
   * @var array
   */
  protected $fieldBlacklist;

  /**
   * Get the array of blacklisted fields.
   *
   * @return array
   *   Return the array of blacklisted fields.
   */
  public function getFieldBlacklist() {
    return $this->fieldBlacklist;
  }

  /**
   * Set the black listed fields.
   *
   * @param array $field_blacklist
   *   The black listed field names.
   */
  public function setFieldBlacklist(array $field_blacklist) {
    $this->fieldBlacklist = $field_blacklist;
  }

}

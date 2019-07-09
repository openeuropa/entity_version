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
  protected $field_blacklist;

  /**
   * @return array
   */
  public function getFieldBlacklist() {
    return $this->field_blacklist;
  }

  /**
   * @param array $field_blacklist
   */
  public function setFieldBlacklist($field_blacklist) {
    $this->field_blacklist = $field_blacklist;
  }

}

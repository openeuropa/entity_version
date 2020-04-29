<?php

declare(strict_types = 1);

namespace Drupal\entity_version_history\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Dispatched by EntityVersionHistoryController::historyOverview().
 *
 * Allows to alter the render array of the history table after it has been
 * populated with data but before it's being rendered.
 */
class HistoryOverviewAlterEvent extends Event {

  const EVENT = 'entity_version_history.history_overview_alter_event';

  /**
   * The render array containing the history table.
   *
   * @var array
   */
  protected $historyTable;

  /**
   * Sets the history table render array.
   *
   * @param array $table
   *   The history table render array.
   */
  public function setHistoryTable(array $table): void {
    $this->historyTable = $table;
  }

  /**
   * Get the history table render array.
   *
   * @return array
   *   Return the history table render array.
   */
  public function getHistoryTable(): array {
    return $this->historyTable;
  }

}

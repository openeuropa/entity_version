<?php

declare(strict_types = 1);

namespace Drupal\entity_version_history_test\EventSubscriber;

use Drupal\Core\State\State;
use Drupal\entity_version_history\Event\HistoryOverviewAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Test event subscriber for the history overview table alter event.
 */
class TestHistoryOverviewAlterEventSubscriber implements EventSubscriberInterface {

  const STATE = 'entity_version_history_test.test_alter_table';

  /**
   * The state.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * TestHistoryOverviewAlterEventSubscriber constructor.
   *
   * @param \Drupal\Core\State\State $state
   *   The state.
   */
  public function __construct(State $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HistoryOverviewAlterEvent::EVENT => 'removeAuthor',
    ];
  }

  /**
   * Alters the history overview table and removes the user column.
   *
   * @param \Drupal\entity_version_history\Event\HistoryOverviewAlterEvent $event
   *   The event.
   */
  public function removeAuthor(HistoryOverviewAlterEvent $event): void {
    if ($this->state->get(static::STATE) !== TRUE) {
      return;
    }

    $history_table = $event->getHistoryTable();

    // Unset the last column with the revision user.
    unset($history_table['#header'][3]);

    $rows = [];
    foreach ($history_table['#rows'] as $row) {
      unset($row['data'][3]);
      $rows[] = $row;
    }
    $history_table['#rows'] = $rows;

    $event->setHistoryTable($history_table);
  }

}

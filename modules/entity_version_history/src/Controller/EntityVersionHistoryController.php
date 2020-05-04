<?php

declare(strict_types = 1);

namespace Drupal\entity_version_history\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Builds the version history table.
 */
class EntityVersionHistoryController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a EntityVersionHistoryController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, DateFormatterInterface $date_formatter, EventDispatcherInterface $event_dispatcher) {
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Generates an overview version history of older revisions of an entity.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return array
   *   A render array.
   */
  public function historyOverview(RouteMatchInterface $route_match): array {
    $header = [
      $this->t('Version'),
      $this->t('Title'),
      $this->t('Date'),
      $this->t('Created by'),
    ];
    $rows = [];

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntityFromRouteMatch($route_match);
    $langcode = $entity->language()->getId();
    $entity_type_id = $entity->getEntityTypeId();
    $entity_storage = $this->entityTypeManager->getStorage($entity_type_id);
    $default_revision = $entity->getRevisionId();
    $current_revision_displayed = FALSE;

    // Get the version field name from the corresponding history config.
    $history_storage = $this->entityTypeManager->getStorage('entity_version_history_settings');
    $history_setting = $history_storage->load($entity_type_id . '.' . $entity->bundle());
    $version_field = $history_setting->getTargetField();
    $revision_timestamp_field = $this->entityTypeManager->getDefinition($entity_type_id)->getRevisionMetadataKey('revision_created');

    foreach ($this->getRevisionIds($entity, $entity_storage) as $vid) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $revision */
      $revision = $entity_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed at the moment.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $version = $this->t('No version set.');
        if (!$revision->get($version_field)->isEmpty()) {
          $version = $revision->get($version_field)->getValue();
          $version = reset($version);
        }

        $date = $this->dateFormatter->format($revision->get($revision_timestamp_field)->value, 'short');

        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // We treat also the latest translation-affecting revision as current
        // revision, if it was the default revision, as its values for the
        // current language will be the same of the current default revision in
        // this case.
        $is_current_revision = $vid === $default_revision || (!$current_revision_displayed && $revision->wasDefaultRevision());
        if (!$is_current_revision) {
          $link = $revision->toLink($revision->label(), 'revision')->toRenderable();
        }
        else {
          $link = $entity->toLink()->toRenderable();
          $current_revision_displayed = TRUE;
        }

        // Populate the rows of the table with the data.
        $rows[] = [
          'data' => [
            implode('.', $version),
            ['data' => $link],
            $date,
            ['data' => $username],
          ],
        ];
      }
    }

    $build['entity_version_history_table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    $build['pager'] = ['#type' => 'pager'];

    return $build;
  }

  /**
   * Checks access to the history route.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account accessing the route.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The actual route match of the route.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkAccess(AccountInterface $account, RouteMatchInterface $route_match): AccessResultInterface {
    $entity = $this->getEntityFromRouteMatch($route_match);
    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['route']);

    if (!$entity) {
      return AccessResult::forbidden('No entity found in the route.')->addCacheableDependency($cache);
    }

    $cache->addCacheableDependency($entity);
    $bundle = $entity->bundle();
    $history_storage = $this->entityTypeManager->getStorage('entity_version_history_settings');
    $cache->addCacheTags($history_storage->getEntityType()->getListCacheTags());

    if (!$config_entity = $history_storage->load($entity->getEntityTypeId() . '.' . $bundle)) {
      return AccessResult::forbidden('No history settings found for this entity type and bundle.')->addCacheableDependency($cache);
    }

    $cache->addCacheableDependency($config_entity);
    $cache->addCacheContexts(['user.permissions']);

    if (!$account->hasPermission('access entity version history')) {
      return AccessResult::forbidden('Insufficient permissions to access the entity version history page.')->addCacheableDependency($cache);
    }

    return AccessResult::allowed()->addCacheableDependency($cache);
  }

  /**
   * Provides a title callback for the history page.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The title for the history page.
   */
  public function title(RouteMatchInterface $route_match): TranslatableMarkup {
    if ($entity = $this->getEntityFromRouteMatch($route_match)) {
      return $this->t('History for @entity', ['@entity' => $entity->label()]);
    }

    return $this->t('History');
  }

  /**
   * Returns the current entity from a given route match.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The entity or NULL if none exists.
   */
  protected function getEntityFromRouteMatch(RouteMatchInterface $route_match): ?ContentEntityInterface {
    $route = $route_match->getRouteObject();
    if (!$entity_type_id = $route->getOption('_entity_type_id')) {
      return NULL;
    }

    return $route_match->getParameter($entity_type_id);
  }

  /**
   * Gets a list of revision IDs for a specific entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The content entity storage.
   *
   * @return int[]
   *   Revision IDs (in descending order).
   */
  protected function getRevisionIds(ContentEntityInterface $entity, EntityStorageInterface $storage): array {
    $result = $storage->getQuery()
      ->allRevisions()
      ->condition($entity->getEntityType()->getKey('id'), $entity->id())
      ->sort($entity->getEntityType()->getKey('revision'), 'DESC')
      ->pager(50)
      ->execute();

    return array_keys($result);
  }

}

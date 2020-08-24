<?php

declare(strict_types = 1);

namespace Drupal\entity_version_history\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the version history table.
 */
class EntityVersionHistoryController extends ControllerBase {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a EntityVersionHistoryController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
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

    $build['entity_version_history_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
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

}

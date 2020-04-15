<?php

declare(strict_types = 1);

namespace Drupal\entity_version_history\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\NodeStorageInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds version history table.
 */
class EntityVersionHistoryController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a NodeController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer, EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('entity_type.manager'),
      $container->get('entity.repository')
    );
  }

  /**
   * Generates an overview version history of older revisions of an entity.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return array
   *   An array as expected by \Drupal\Core\Render\RendererInterface::render().
   */
  public function historyOverview(RouteMatchInterface $route_match): array {

    return [];
  }

  /**
   * Checks access for to the History route for a given entity.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account accessing the route.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The actual route match of the route. This can be different than the
   *   current one.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkAccess(AccountInterface $account, RouteMatchInterface $routeMatch): AccessResultInterface {
    $parameters = $routeMatch->getParameters();
    $entity_type_id = $parameters->keys()[0];
    $entity = $parameters->get($entity_type_id);
    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['url']);

    if (!$entity) {
      return AccessResult::forbidden('No entity found in the route.')->addCacheableDependency($cache);
    }

    $cache->addCacheableDependency($entity);

    $bundle = $entity->bundle();
    $history_storage = $this->entityTypeManager->getStorage('entity_version_history_settings');
    if (!$config = $history_storage->load("$entity_type_id.$bundle")){
      return AccessResult::forbidden('Insufficient permission to access the history tab.')->addCacheableDependency($cache);
    }

    $cache->addCacheableDependency($config);
    $cache->addCacheContexts(['user.permissions']);
    $cache->addCacheableDependency($account);

    if (!$account->hasPermission('access history tab')) {
      return AccessResult::forbidden('Insufficient permission to access the history tab.')->addCacheableDependency($cache);
    }

    return AccessResult::allowed()->addCacheableDependency($cache);
  }

  /**
   * Gets a list of revision IDs for a specific content entity.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   * @param \Drupal\node\NodeStorageInterface $node_storage
   *   The node storage handler.
   *
   * @return int[]
   *   Node revision IDs (in descending order).
   */
  protected function getRevisionIds(NodeInterface $node, NodeStorageInterface $node_storage) {
    $result = $node_storage->getQuery()
      ->allRevisions()
      ->condition($node->getEntityType()->getKey('id'), $node->id())
      ->sort($node->getEntityType()->getKey('revision'), 'DESC')
      ->pager(50)
      ->execute();
    return array_keys($result);
  }

}

<?php

declare(strict_types = 1);

namespace Drupal\Tests\entity_version_history\Kernel;

use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;

/**
 * Tests the entity version history local task.
 *
 * @group entity_version_history
 */
class HistoryTabTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'node',
    'field',
    'text',
    'system',
    'entity_version',
    'entity_version_history',
    'entity_version_history_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_version_history_settings');
    $this->installSchema('system', 'sequences');
    $this->installSchema('node', 'node_access');

    $this->installConfig([
      'user',
      'node',
      'system',
      'field',
      'entity_version',
      'entity_version_history',
      'entity_version_history_test',
    ]);

    // Create a history tab setting for the corresponding entity type
    // and bundle.
    $history_storage = $this->container->get('entity_type.manager')->getStorage('entity_version_history_settings');
    $history_storage->create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'first_bundle',
      'target_field' => 'field_entity_version',
    ])->save();

    $this->container->get('entity_type.manager')->clearCachedDefinitions();
  }

  /**
   * Tests the History routes.
   */
  public function testHistoryRoutes(): void {
    $route_provider = $this->container->get('router.route_provider');
    $local_task_manager = $this->container->get('plugin.manager.menu.local_task');

    /** @var \Drupal\Core\Entity\EntityTypeInterface $definition */
    foreach ($this->container->get('entity_type.manager')->getDefinitions() as $definition) {
      if ($definition->id() === 'node') {
        $this->assertTrue($definition->hasLinkTemplate('drupal:entity-version-history'));
        $this->assertInstanceOf(Route::class, $route_provider->getRouteByName('entity.' . $definition->id() . '.history'));
        $history_local_task = $local_task_manager->getDefinition('entity_version_history.entity.history:' . $definition->id());
        $this->assertEquals('entity.' . $definition->id() . '.history', $history_local_task['route_name']);
        $this->assertEquals('entity.' . $definition->id() . '.canonical', $history_local_task['base_route']);

        continue;
      }

      $this->assertFalse($definition->hasLinkTemplate('drupal:entity-version-history'));

      $exception = NULL;
      try {
        $route_provider->getRouteByName('entity.' . $definition->id() . '.history');
      }
      catch (\Exception $e) {
        $exception = $e;
      }
      $this->assertInstanceOf(RouteNotFoundException::class, $exception);
    }
  }

  /**
   * Tests access to the entity version history page.
   */
  public function testHistoryPageAccess(): void {
    // Define the anonymous user first. User id with 0 has to exist in order
    // to avoid "ContextException: The 'entity:user' context is required and
    // not present" error.
    User::create([
      'name' => 'Anonymous',
      'uid' => 0,
    ])->save();

    $entity_type_manager = $this->container->get('entity_type.manager');

    $node = $entity_type_manager->getStorage('node')->create([
      'type' => 'first_bundle',
      'title' => 'My node',
    ]);
    $node->save();

    $user_with_permission = $this->createUser(['access entity version history']);
    $user_without_permission = $this->createUser();

    $history_url = Url::fromRoute('entity.node.history', [
      'node' => $node->id(),
    ]);

    // Assert that we can't access the history page when no permissions are
    // assigned.
    $this->assertFalse($history_url->access($user_without_permission));

    // A user with permissions can access the history page.
    $this->assertTrue($history_url->access($user_with_permission));

    // We can't access the history page without a corresponding
    // history settings.
    $entity_type_manager
      ->getStorage('entity_version_history_settings')
      ->load('node.first_bundle')
      ->delete();

    $this->assertFalse($history_url->access($user_with_permission));
  }

}

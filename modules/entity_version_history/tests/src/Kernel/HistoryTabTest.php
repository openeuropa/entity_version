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
 * Tests the entity version history tab.
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
    $this->installEntitySchema('entity_version_history_settings');

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
    $history_storage = $this->container->get('entity_type.manager')
      ->getStorage('entity_version_history_settings');
    $history_storage->create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'first_bundle',
      'target_field' => 'field_entity_version',
    ])->save();

    $this->container->get('entity_type.manager')->clearCachedDefinitions();
  }

  /**
   * Tests that the History tab is correctly applied.
   */
  public function testHistoryTabApplied(): void {
    $route_provider = $this->container->get('router.route_provider');

    /** @var \Drupal\Core\Entity\EntityTypeInterface $definition */
    foreach ($this->container->get('entity_type.manager')->getDefinitions() as $definition) {
      if ($definition->id() === 'node') {
        $this->assertTrue($definition->hasLinkTemplate('drupal:entity-version-history'));
        $this->assertInstanceOf(Route::class, $route_provider->getRouteByName('entity.' . $definition->id() . '.history'));
        continue;
      }

      $this->assertFalse($definition->hasLinkTemplate('drupal:entity-version-history'));
      try {
        $route_provider->getRouteByName('entity.' . $definition->id() . '.history');
      }
      catch (\Exception $exception) {
        $this->assertInstanceOf(RouteNotFoundException::class, $exception);
      }
    }
  }

  /**
   * Tests access to the history tab.
   */
  public function testHistoryTabAccess(): void {
    $this->installEntitySchema('user');
    // Define the anonymous user first.
    User::create([
      'name' => 'Anonymous',
      'uid' => 0,
    ])->save();

    $this->installSchema('system', 'sequences');
    $this->installSchema('node', 'node_access');

    $entity_type_manager = $this->container->get('entity_type.manager');

    $node = $entity_type_manager->getStorage('node')->create([
      'type' => 'first_bundle',
      'title' => 'My node',
    ]);
    $node->save();

    /** @var \Drupal\user\RoleInterface $role_storage */
    $role_storage = $this->container->get('entity_type.manager')->getStorage('user_role');
    $role = $role_storage->create(['id' => 'test_role']);
    $role->save();

    /** @var \Drupal\user\UserInterface $user */
    $user = User::create([
      'name' => 'Test user',
      'uid' => 2,
      'roles' => [
        'test_role',
      ],
    ]);
    $user->save();

    $history_url = Url::fromRoute('entity.node.history', [
      'node' => $node->id(),
    ]);

    // Assert that we can't access the history page when no permissions are
    // assigned.
    $this->assertFalse($history_url->access($user));

    // Assign the required permission.
    $this->grantPermissions($role, ['access history tab']);

    // A user with the permission can access the history page.
    $this->assertTrue($history_url->access($user));

    // We can't access the history tab without a corresponding
    // history tab configuration.
    $entity_type_manager
      ->getStorage('entity_version_history_settings')
      ->load('node.first_bundle')
      ->delete();

    $this->assertFalse($history_url->access($user));
  }

}

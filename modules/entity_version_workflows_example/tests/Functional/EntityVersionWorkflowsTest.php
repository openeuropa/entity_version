<?php

declare(strict_types = 1);

namespace Drupal\Tests\entity_version_workflows_example\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests the EntityVersionWorkflowHandler service.
 */
class EntityVersionWorkflowsTest extends BrowserTestBase {

  /**
   * Admin user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'administer workflows',
    'access administration pages',
    'administer content types',
    'administer nodes',
    'view latest version',
    'view any unpublished content',
    'access content overview',
    'use example_workflow transition create_new_draft',
    'use example_workflow transition publish',
  ];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'path',
    'node',
    'user',
    'system',
    'workflows',
    'content_moderation',
    'entity_version_workflows_example',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->drupalLogin($this->adminUser);
    $this->grantUserPermissionToCreateContentOfType($this->adminUser, 'entity_version_workflows_example');
  }

  /**
   * Tests the entity version field is controlled by the moderation transitions.
   */
  public function testEntityVersionWorkflows(): void {
    $this->drupalPostForm('node/add/entity_version_workflows_example', [
      'title[0][value]' => 'moderated content',
      'moderation_state[0][state]' => 'draft',
    ], t('Save'));
    $node = $this->getNodeByTitle('moderated content');
    if (!$node) {
      $this->fail('Test node was not saved correctly.');
    }
    // Make sure that the default values are correct after saving a new node.
    $this->assertEqual('draft', $node->moderation_state->value);
    $this->assertEqual('0', $node->field_version->major);
    $this->assertEqual('0', $node->field_version->minor);
    $this->assertEqual('0', $node->field_version->patch);

    $path = 'node/' . $node->id() . '/edit';

    // Create a new draft revision.
    $this->drupalPostForm($path, [
      'moderation_state[0][state]' => 'draft',
    ], t('Save'));
    \Drupal::entityTypeManager()->getStorage('node')->resetCache([$node->id()]);
    /* @var \Drupal\node\NodeInterface $node */
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($node->id());
    $this->assertEqual('draft', $node->moderation_state->value);
    $this->assertEqual('0', $node->field_version->major);
    $this->assertEqual('1', $node->field_version->minor);
    $this->assertEqual('0', $node->field_version->patch);

    // Create a new draft revision.
    $this->drupalPostForm($path, [
      'moderation_state[0][state]' => 'draft',
    ], t('Save'));
    \Drupal::entityTypeManager()->getStorage('node')->resetCache([$node->id()]);
    /* @var \Drupal\node\NodeInterface $node */
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($node->id());
    $this->assertEqual('draft', $node->moderation_state->value);
    $this->assertEqual('0', $node->field_version->major);
    $this->assertEqual('2', $node->field_version->minor);
    $this->assertEqual('0', $node->field_version->patch);

    // Create a new draft revision.
    $this->drupalPostForm($path, [
      'moderation_state[0][state]' => 'draft',
    ], t('Save'));
    \Drupal::entityTypeManager()->getStorage('node')->resetCache([$node->id()]);
    /* @var \Drupal\node\NodeInterface $node */
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($node->id());
    $this->assertEqual('draft', $node->moderation_state->value);
    $this->assertEqual('0', $node->field_version->major);
    $this->assertEqual('3', $node->field_version->minor);
    $this->assertEqual('0', $node->field_version->patch);

    // Create a new published revision.
    $this->drupalPostForm($path, [
      'moderation_state[0][state]' => 'published',
    ], t('Save'));
    \Drupal::entityTypeManager()->getStorage('node')->resetCache([$node->id()]);
    /* @var \Drupal\node\NodeInterface $node */
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($node->id());
    $this->assertEqual('published', $node->moderation_state->value);
    $this->assertEqual('1', $node->field_version->major);
    $this->assertEqual('0', $node->field_version->minor);
    $this->assertEqual('0', $node->field_version->patch);

    // Create a new draft revision after the published revision.
    $this->drupalPostForm($path, [
      'moderation_state[0][state]' => 'draft',
    ], t('Save'));
    \Drupal::entityTypeManager()->getStorage('node')->resetCache([$node->id()]);
    // By default always the published revision is loaded, we need to load the
    // latest draft revision.
    $revision_ids = \Drupal::entityTypeManager()->getStorage('node')->revisionIds($node);
    /* @var \Drupal\node\NodeInterface $node */
    $node = \Drupal::entityTypeManager()->getStorage('node')->loadRevision(end($revision_ids));
    $this->assertEqual('draft', $node->moderation_state->value);
    $this->assertEqual('1', $node->field_version->major);
    $this->assertEqual('1', $node->field_version->minor);
    $this->assertEqual('0', $node->field_version->patch);

    // Create a new draft revision.
    $this->drupalPostForm($path, [
      'moderation_state[0][state]' => 'draft',
    ], t('Save'));
    \Drupal::entityTypeManager()->getStorage('node')->resetCache([$node->id()]);
    // By default always the published revision is loaded, we need to load the
    // latest draft revision.
    $revision_ids = \Drupal::entityTypeManager()->getStorage('node')->revisionIds($node);
    $node = \Drupal::entityTypeManager()->getStorage('node')->loadRevision(end($revision_ids));
    $this->assertEqual('draft', $node->moderation_state->value);
    $this->assertEqual('1', $node->field_version->major);
    $this->assertEqual('2', $node->field_version->minor);
    $this->assertEqual('0', $node->field_version->patch);

    // Create a new published revision.
    $this->drupalPostForm($path, [
      'moderation_state[0][state]' => 'published',
    ], t('Save'));
    \Drupal::entityTypeManager()->getStorage('node')->resetCache([$node->id()]);
    /* @var \Drupal\node\NodeInterface $node */
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($node->id());
    $this->assertEqual('published', $node->moderation_state->value);
    $this->assertEqual('2', $node->field_version->major);
    $this->assertEqual('0', $node->field_version->minor);
    $this->assertEqual('0', $node->field_version->patch);
  }

  /**
   * Grants given user permission to create content of given type.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User to grant permission to.
   * @param string $content_type_id
   *   Content type ID.
   */
  protected function grantUserPermissionToCreateContentOfType(AccountInterface $account, $content_type_id) {
    $role_ids = $account->getRoles(TRUE);
    /* @var \Drupal\user\RoleInterface $role */
    $role_id = reset($role_ids);
    $role = Role::load($role_id);
    $role->grantPermission(sprintf('create %s content', $content_type_id));
    $role->grantPermission(sprintf('edit any %s content', $content_type_id));
    $role->grantPermission(sprintf('delete any %s content', $content_type_id));
    $role->save();
  }

}

<?php

namespace Drupal\Tests\entity_version_workflows\Kernel;

use Drupal\entity_version_workflows_example\EventSubscriber\TestCheckEntityChangedSubscriber;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\NodeInterface;

/**
 * Test the entity version numbers with workflow transitions.
 */
class EntityVersionWorkflowsTest extends KernelTestBase {

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'path',
    'field',
    'text',
    'node',
    'user',
    'system',
    'filter',
    'workflows',
    'content_moderation',
    'entity_version',
    'entity_version_workflows',
    'entity_version_workflows_example',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installConfig([
      'system',
      'node',
      'field',
      'user',
      'workflows',
      'content_moderation',
      'entity_version_workflows_example',
    ]);

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('content_moderation_state');

    // In Drupal 8.8, paths have been moved to an entity type.
    // @todo remove this when the component will depend on 8.8.
    if ($this->container->get('entity_type.manager')->hasDefinition('path_alias')) {
      $this->container->get('module_installer')->install(['path_alias']);
      $this->installEntitySchema('path_alias');
    }

    $this->installSchema('node', 'node_access');

    $this->nodeStorage = $this->container->get('entity_type.manager')->getStorage('node');
  }

  /**
   * Test the entity version numbers with workflow transitions.
   */
  public function testEntityVersionWorkflow() {
    $values = [
      'title' => 'Workflow node',
      'type' => 'entity_version_workflows_example',
      'moderation_state' => 'draft',
    ];
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->nodeStorage->create($values);
    $node->save();

    // There is no default value so all versions should be 0.
    $this->assertEqual('draft', $node->moderation_state->value);
    $this->assertNodeVersion($node, 0, 0, 0);

    // Save to increase the patch number (stay in draft).
    $node->set('title', 'New title');
    $node->save();
    $this->assertNodeVersion($node, 0, 0, 1);
    $node->save();
    // Since the "check values changed" is enabled the version remains the same.
    $this->assertNodeVersion($node, 0, 0, 1);

    // Validate the content to increase the minor and reset the patch.
    $node->set('moderation_state', 'validated');
    $node->save();
    $this->assertNodeVersion($node, 0, 1, 0);

    // Make a new draft to increase patch on the new minor.
    $node->set('moderation_state', 'draft');
    $node->set('title', 'New title 1');
    $node->save();
    $this->assertNodeVersion($node, 0, 1, 1);
    $node->set('title', 'New title 2');
    $node->save();
    $this->assertNodeVersion($node, 0, 1, 2);

    // Publish the node to increase the major.
    $node->set('moderation_state', 'validated');
    $node->save();
    $this->assertNodeVersion($node, 0, 2, 0);
    $node->set('moderation_state', 'published');
    $node->save();
    $this->assertNodeVersion($node, 1, 0, 0);

    // Make a new draft to increase patch on the new major.
    $node->set('moderation_state', 'draft');
    $node->save();
    $this->assertNodeVersion($node, 1, 0, 1);
    // We change value so the version will change.
    $node->set('title', 'New title 3');
    $node->save();
    $this->assertNodeVersion($node, 1, 0, 2);
    // Check if values are not changed no version is changed.
    $node->save();
    $this->assertNodeVersion($node, 1, 0, 2);
    $node->save();
    $this->assertNodeVersion($node, 1, 0, 2);

    // Validate to increase minor version on the new major.
    $node->set('moderation_state', 'validated');
    $node->save();
    $this->assertNodeVersion($node, 1, 1, 0);
    // Move back to draft without changing the value and version.
    $node->set('moderation_state', 'draft');
    $node->save();
    $this->assertNodeVersion($node, 1, 1, 0);
  }

  /**
   * Tests that we can alter the blacklisted fields that are skipped.
   */
  public function testCheckEntityChangedEvent() {
    $values = [
      'title' => 'Workflow node',
      'type' => 'entity_version_workflows_example',
      'moderation_state' => 'draft',
    ];
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->nodeStorage->create($values);
    $node->save();

    // There is no default value so all versions should be 0.
    $this->assertEqual('draft', $node->moderation_state->value);
    $this->assertNodeVersion($node, 0, 0, 0);

    // Save to increase the patch number (stay in draft).
    $node->set('title', 'New title');
    $node->save();
    $this->assertNodeVersion($node, 0, 0, 1);
    $node->save();
    // Since the "check values changed" is enabled the version remains the same.
    $this->assertNodeVersion($node, 0, 0, 1);

    // Set the state to trigger our test event subscriber.
    $this->container->get('state')->set(TestCheckEntityChangedSubscriber::STATE, TRUE);
    $node->set('title', 'Another new title');
    $node->save();
    // The version stays the same even if we changed the title because of the
    // test subscriber which skips the title field.
    $this->assertNodeVersion($node, 0, 0, 1, 'The version changed because the node title changed and it was not skipped.');
  }

  /**
   * Test we can flag entities to not increase the version.
   */
  public function testEntityVersionNoIncrease() {
    $values = [
      'title' => 'Workflow node',
      'type' => 'entity_version_workflows_example',
      'moderation_state' => 'draft',
    ];
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->nodeStorage->create($values);
    $node->save();

    // There is no default value so all versions should be 0.
    $this->assertEqual('draft', $node->moderation_state->value);
    $this->assertNodeVersion($node, 0, 0, 0);

    // Alter the node to increase the patch number (stay in draft).
    $node->set('title', 'New title');
    $node->save();
    $this->assertNodeVersion($node, 0, 0, 1);
    $node->save();

    // Alter the node again but flag it to not increase the number.
    $node->set('title', 'Newer title');
    $node->entity_version_no_update = TRUE;
    $node->save();
    $this->assertNodeVersion($node, 0, 0, 1);
    $node->save();
  }

  /**
   * Assert the entity version field value.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   * @param string $major
   *   The major version number.
   * @param string $minor
   *   The minor version number.
   * @param string $patch
   *   The patch version number.
   * @param string $message
   *   An option error message.
   */
  protected function assertNodeVersion(NodeInterface $node, string $major, string $minor, string $patch, $message = '') {
    $this->assertEqual($major, $node->get('field_version')->major, $message);
    $this->assertEqual($minor, $node->get('field_version')->minor, $message);
    $this->assertEqual($patch, $node->get('field_version')->patch, $message);
  }

}

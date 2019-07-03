<?php

namespace Drupal\Tests\entity_version_workflows\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\NodeInterface;

/**
 * Test the entity version numbers with workflow transitions.
 */
class EntityVersionWorkflowsTest extends KernelTestBase {

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

    $this->installSchema('node', 'node_access');
  }

  /**
   * Test the entity version numbers with workflow transitions.
   */
  public function testEntityVersionWorkflow() {
    /** @var \Drupal\node\NodeStorageInterface $node_storage */
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');

    $values = [
      'title' => 'Workflow node',
      'type' => 'entity_version_workflows_example',
      'moderation_state' => 'draft',
    ];
    /** @var \Drupal\node\NodeInterface $node */
    $node = $node_storage->create($values);
    $node->save();

    // There is no default value so all versions should be 0.
    $this->assertEqual('draft', $node->moderation_state->value);
    $this->assertNodeVersion($node, 0, 0, 0);

    // Save to increase the patch number (stay in draft).
    $node->set('title', 'New title');
    $node->save();
    $this->assertNodeVersion($node, 0, 0, 1);
    $node->save();
    // Since Check values changed is enabled the version remains the same.
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
    $node->set('title', 'New title 3');
    $node->save();
    $this->assertNodeVersion($node, 1, 0, 2);

    // Validate to increase minor version on the new major.
    $node->set('moderation_state', 'validated');
    $node->save();
    $this->assertNodeVersion($node, 1, 1, 0);
    $node->set('moderation_state', 'draft');
    $node->save();
    $this->assertNodeVersion($node, 1, 1, 1);
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
   */
  protected function assertNodeVersion(NodeInterface $node, string $major, string $minor, string $patch) {
    $this->assertEqual($major, $node->get('field_version')->major);
    $this->assertEqual($minor, $node->get('field_version')->minor);
    $this->assertEqual($patch, $node->get('field_version')->patch);
  }

}

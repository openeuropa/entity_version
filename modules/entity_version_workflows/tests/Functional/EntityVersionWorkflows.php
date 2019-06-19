<?php

declare(strict_types = 1);

namespace Drupal\Tests\entity_version_workflows\Functional;

use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the EntityVersionWorkflowHandler service.
 */
class EntityVersionWorkflows extends BrowserTestBase {

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
  }

  /**
   * Tests the entity version field is controlled by the workflow transitions.
   */
  public function testEntityVersionWorkflows(): void {
    $node = Node::create([
      'title' => 'Testing page',
      'type' => 'entity_version_workflows_example',
      'uid' => 0,
    ]);
    $node->save();

    $node = Node::load($node->id());

  }

}

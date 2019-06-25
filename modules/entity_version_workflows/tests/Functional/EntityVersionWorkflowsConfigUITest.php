<?php

declare(strict_types = 1);

namespace Drupal\Tests\entity_version_workflows\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the entity version workflows configuration UI.
 */
class EntityVersionWorkflowsConfigUITest extends BrowserTestBase {

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
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests the entity version field behaviour can be configured per transition.
   */
  public function testEntityVersionWorkflowsConfigUi(): void {
    $this->drupalGet('admin/config/workflow/workflows/add');
    $this->getSession()->getPage()->fillField('Label','Content moderation');
    $this->getSession()->getPage()->selectFieldOption('Workflow type','Content moderation');
    $this->getSession()->getPage()->pressButton('Save');
    $this->drupalGet('admin/config/workflow/workflows/manage/content_moderation/add_transition');

    // Nothing option.
    $this->assertSession()->optionExists('Major', '');
    // Increase option.
    $this->assertSession()->optionExists('Major', 'increase');
    // Decrease option.
    $this->assertSession()->optionExists('Major', 'decrease');
    // Reset option.
    $this->assertSession()->optionExists('Major', 'reset');

    $this->assertSession()->selectExists('Minor');
    // Nothing option.
    $this->assertSession()->optionExists('Minor', '');
    // Increase option.
    $this->assertSession()->optionExists('Minor', 'increase');
    // Decrease option.
    $this->assertSession()->optionExists('Minor', 'decrease');
    // Reset option.
    $this->assertSession()->optionExists('Minor', 'reset');

    $this->assertSession()->selectExists('Patch');
    // Nothing option.
    $this->assertSession()->optionExists('Patch', '');
    // Increase option.
    $this->assertSession()->optionExists('Patch', 'increase');
    // Decrease option.
    $this->assertSession()->optionExists('Patch', 'decrease');
    // Reset option.
    $this->assertSession()->optionExists('Patch', 'reset');
  }

}
<?php

declare(strict_types = 1);

namespace Drupal\Tests\entity_version_workflows\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\workflows\Entity\Workflow;

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
   * {@inheritdoc}
   */
  protected static $modules = [
    'path',
    'node',
    'user',
    'system',
    'workflows',
    'content_moderation',
    'entity_version',
    'entity_version_workflows',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'administer workflows',
      'access administration pages',
      'administer content types',
      'administer nodes',
      'view latest version',
      'view any unpublished content',
      'access content overview',
    ]);

    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests the entity version field behaviour can be configured per transition.
   */
  public function testEntityVersionWorkflowsConfigUi(): void {
    $this->drupalGet('admin/config/workflow/workflows/add');
    $this->getSession()->getPage()->fillField('Label', 'Content moderation');
    $this->getSession()->getPage()->fillField('Machine-readable name', 'content_moderation');
    $this->getSession()->getPage()->selectFieldOption('Workflow type', 'Content moderation');
    $this->getSession()->getPage()->pressButton('Save');
    $this->drupalGet('admin/config/workflow/workflows/manage/content_moderation/add_transition');

    $this->assertSession()->selectExists('Major');
    $this->assertSession()->optionExists('Major', 'increase');
    $this->assertSession()->optionExists('Major', 'decrease');
    $this->assertSession()->optionExists('Major', 'reset');

    $this->assertSession()->selectExists('Minor');
    $this->assertSession()->optionExists('Minor', '');
    $this->assertSession()->optionExists('Minor', 'increase');
    $this->assertSession()->optionExists('Minor', 'decrease');
    $this->assertSession()->optionExists('Minor', 'reset');

    $this->assertSession()->selectExists('Patch');
    $this->assertSession()->optionExists('Patch', '');
    $this->assertSession()->optionExists('Patch', 'increase');
    $this->assertSession()->optionExists('Patch', 'decrease');
    $this->assertSession()->optionExists('Patch', 'reset');

    // Assert that we have no third party settings from our module on the
    // created workflow.
    $workflow = Workflow::load('content_moderation');
    $this->assertEmpty($workflow->getThirdPartySettings('entity_version_workflows'));

    // Edit an existing transition from the workflow and specify version rules.
    $this->drupalGet('admin/config/workflow/workflows/manage/content_moderation/transition/create_new_draft');
    file_put_contents('/var/www/html/print.html', $this->getSession()->getPage()->getHtml());
    $this->getSession()->getPage()->selectFieldOption('Major', 'increase');
    $this->getSession()->getPage()->selectFieldOption('Minor', 'decrease');
    $this->getSession()->getPage()->selectFieldOption('Patch', 'reset');
    $this->getSession()->getPage()->pressButton('Save');

    // Check that the third party settings have been saved correctly.
    $workflow = Workflow::load('content_moderation');
    $expected = [
      'major' => 'increase',
      'minor' => 'decrease',
      'patch' => 'reset',
    ];
    $this->assertEquals($expected, $workflow->getThirdPartySetting('entity_version_workflows', 'create_new_draft'));
  }

}

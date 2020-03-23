<?php

namespace Drupal\Tests\entity_version_history\Functional;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\SchemaCheckTestTrait;

/**
 * Ensures the entity version history config is correctly saved.
 *
 * @group entity_version_history
 */
class HistoryTabSettingsTest extends WebDriverTestBase {

  use SchemaCheckTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'entity_version',
    'entity_version_workflows',
    'entity_version_workflows_example',
    'entity_version_history',
    'menu_link_content',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with administrative permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create user.
    $this->adminUser = $this->drupalCreateUser([
      'access entity version history configuration',
      'access administration pages',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests whether the history settings form is correctly saving the settings.
   */
  public function testHistoryTabSettingsForm() {
    $this->drupalGet('admin/config/entity-version/history-tab');
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $entity_checkbox = $assert_session->elementExists('css', '#edit-entity-types-node');
    $entity_checkbox->check();
    $bundle_checkbox = $assert_session->waitForElementVisible('css', '#edit-node-entity-version-workflows-example');
    $bundle_checkbox->check();
    $assert_session->waitForElementVisible('css', 'select[name="node-entity_version_workflows_example"]');
    $page->pressButton('Save configuration');

    $this->assertTrue($entity_checkbox->isChecked());
    $this->assertTrue($bundle_checkbox->isChecked());

    // Remove the config by unchecking the entity checkbox.
    $entity_checkbox->uncheck();
    $page->pressButton('Save configuration');

    $this->assertFalse($entity_checkbox->isChecked());
    $this->assertFalse($bundle_checkbox->isChecked());
  }

  /**
   * Tests whether the history settings config schema is valid.
   */
  public function testValidHistoryTabSettingsSchema() {
    $this->drupalGet('admin/config/entity-version/history-tab');
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $checkbox = $assert_session->elementExists('css', '#edit-entity-types-node');
    $checkbox->check();
    $checkbox = $assert_session->waitForElementVisible('css', '#edit-node-entity-version-workflows-example');
    $checkbox->check();
    $assert_session->waitForElementVisible('css', 'select[name="node-entity_version_workflows_example"]');
    $page->pressButton('Save configuration');

    $config_data = $this->config('entity_version_history.settings.node.entity_version_workflows_example');
    // Make sure configuration saved correctly.
    $this->assertEquals($config_data->get('target_entity_type_id'), 'node');
    $this->assertEquals($config_data->get('target_bundle'), 'entity_version_workflows_example');
    $this->assertEquals($config_data->get('target_field'), 'field_version');

    $this->assertConfigSchema(\Drupal::service('config.typed'), $config_data->getName(), $config_data->get());
  }

}

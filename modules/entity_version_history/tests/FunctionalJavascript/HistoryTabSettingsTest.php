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
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'entity_version',
    'entity_version_history',
    'entity_version_history_test',
    'menu_link_content',
  ];

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
    $node_entity_checkbox = $assert_session->elementExists('css', '#edit-entity-types-node');
    $node_entity_checkbox->check();
    $history_entity_checkbox = $assert_session->elementExists('css', '#edit-entity-types-history-entity-test');
    $history_entity_checkbox->check();

    $first_bundle_checkbox = $assert_session->elementExists('css', '#edit-node-first-bundle');
    $first_bundle_checkbox->check();
    $second_bundle_checkbox = $assert_session->elementExists('css', '#edit-node-second-bundle');
    $second_bundle_checkbox->check();
    $history_bundle_checkbox = $assert_session->elementExists('css', '#edit-history-entity-test-history-entity-test');
    $history_bundle_checkbox->check();
    $page->pressButton('Save configuration');

    $status_message = $assert_session->waitForElement('css', '.messages--status');
    $this->assertEquals($status_message->getText(), 'Status message Configuration has been saved.');

    // Make sure that the settings are reflected in the form.
    $this->assertTrue($node_entity_checkbox->isChecked());
    $this->assertTrue($first_bundle_checkbox->isChecked());
    $this->assertTrue($second_bundle_checkbox->isChecked());
    $this->assertTrue($history_entity_checkbox->isChecked());
    $this->assertTrue($history_bundle_checkbox->isChecked());

    // Make sure configuration saved correctly and complies with the schema.
    $config_data = $this->config('entity_version_history.settings.node.first_bundle');
    $this->assertEquals($config_data->get('target_entity_type_id'), 'node');
    $this->assertEquals($config_data->get('target_bundle'), 'first_bundle');
    $this->assertEquals($config_data->get('target_field'), 'field_entity_version');
    $this->assertConfigSchema(\Drupal::service('config.typed'), $config_data->getName(), $config_data->get());

    $config_data = $this->config('entity_version_history.settings.node.second_bundle');
    $this->assertEquals($config_data->get('target_entity_type_id'), 'node');
    $this->assertEquals($config_data->get('target_bundle'), 'second_bundle');
    $this->assertEquals($config_data->get('target_field'), 'field_entity_version');
    $this->assertConfigSchema(\Drupal::service('config.typed'), $config_data->getName(), $config_data->get());

    $config_data = $this->config('entity_version_history.settings.history_entity_test.history_entity_test');
    $this->assertEquals($config_data->get('target_entity_type_id'), 'history_entity_test');
    $this->assertEquals($config_data->get('target_bundle'), 'history_entity_test');
    $this->assertEquals($config_data->get('target_field'), 'version');
    $this->assertConfigSchema(\Drupal::service('config.typed'), $config_data->getName(), $config_data->get());

    // Remove the config by unchecking the entity checkbox.
    $history_bundle_checkbox->uncheck();
    $page->pressButton('Save configuration');

    // Make sure that the settings are reflected in the form.
    $this->assertTrue($node_entity_checkbox->isChecked());
    $this->assertTrue($first_bundle_checkbox->isChecked());
    $this->assertTrue($second_bundle_checkbox->isChecked());
    $this->assertFalse($history_entity_checkbox->isChecked());
    $this->assertFalse($history_bundle_checkbox->isChecked());

    // Check the config is deleted.
    $this->container->get('config.factory')->clearStaticCache();
    $config_data = $this->config('entity_version_history.settings.history_entity_test.history_entity_test');
    $this->assertNull($config_data->get('target_entity_type_id'), 'history_entity_test');
    $this->assertNull($config_data->get('target_bundle'), 'history_entity_test');
    $this->assertNull($config_data->get('target_field'), 'version');
  }

}

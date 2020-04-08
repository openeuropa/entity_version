<?php

namespace Drupal\Tests\entity_version_history\Functional;

use Behat\Mink\Element\NodeElement;
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

    // Check we have the entity type checkboxes.
    $node_entity_checkbox = $assert_session->elementExists('css', '#edit-entity-types-node');
    $history_entity_checkbox = $assert_session->elementExists('css', '#edit-entity-types-history-entity-test');

    // Collect the bundle checkboxes and check that they are not visible.
    $first_bundle_checkbox = $assert_session->elementExists('css', '#edit-node-first-bundle');
    $second_bundle_checkbox = $assert_session->elementExists('css', '#edit-node-second-bundle');
    $history_bundle_checkbox = $assert_session->elementExists('css', '#edit-history-entity-test-history-entity-test');

    $this->assertFalse($first_bundle_checkbox->isVisible());
    $this->assertFalse($second_bundle_checkbox->isVisible());
    $this->assertFalse($history_bundle_checkbox->isVisible());

    // Check the content entity type checkbox.
    $node_entity_checkbox->check();

    // Check the bundle checkboxes are now visible except the history bundle.
    $this->assertTrue($first_bundle_checkbox->isVisible());
    $this->assertTrue($second_bundle_checkbox->isVisible());
    $this->assertFalse($history_bundle_checkbox->isVisible());

    // Check the history entity type checkbox.
    $history_entity_checkbox->check();

    // Check the bundle checkbox is visible for history.
    $this->assertTrue($history_bundle_checkbox->isVisible());

    // Check that there is only one select field and it's not visible.
    $selects = $page->findAll('css', 'details select');
    $this->assertCount(1, $selects);
    $select = reset($selects);
    $this->assertEquals('node_second_bundle', $select->getAttribute('name'));
    $this->assertFalse($select->isVisible());

    // Assert that the correct options are present in the field.
    $this->assertFieldSelectOptions('History second bundle', [
      'field_entity_version',
      'field_secondary_version',
    ]);

    // Check the bundle checkboxes.
    $first_bundle_checkbox->check();
    $second_bundle_checkbox->check();
    $history_bundle_checkbox->check();

    // Check that the select field has appeared.
    $this->assertTrue($select->isVisible());

    $page->pressButton('Save configuration');

    $status_message = $assert_session->waitForElement('css', '.messages--status');
    $this->assertEquals('Status message The Version history configuration has been saved.', $status_message->getText());

    // Check that there are only 3 config entities created.
    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $storage */
    $storage = \Drupal::service('entity_type.manager')->getStorage('entity_version_history_settings');
    $config_entities = $storage->loadMultiple();
    $this->assertCount(3, $config_entities);

    // Make sure that the settings are reflected in the form and all checked.
    $this->assertTrue($node_entity_checkbox->isChecked());
    $this->assertTrue($first_bundle_checkbox->isChecked());
    $this->assertTrue($second_bundle_checkbox->isChecked());
    $this->assertTrue($history_entity_checkbox->isChecked());
    $this->assertTrue($history_bundle_checkbox->isChecked());

    // Assert that the correct option is selected.
    $option_field = $assert_session->optionExists('node_second_bundle', 'field_entity_version');
    $this->assertTrue($option_field->hasAttribute('selected'));

    // Make sure configuration saved correctly and complies with the schema.
    $config = $this->config('entity_version_history.settings.node.first_bundle');
    $this->assertEquals('node', $config->get('target_entity_type_id'));
    $this->assertEquals('first_bundle', $config->get('target_bundle'));
    $this->assertEquals('field_entity_version', $config->get('target_field'));
    $this->assertConfigSchema($this->container->get('config.typed'), $config->getName(), $config->get());

    $config = $this->config('entity_version_history.settings.node.second_bundle');
    $this->assertEquals('node', $config->get('target_entity_type_id'));
    $this->assertEquals('second_bundle', $config->get('target_bundle'));
    $this->assertEquals('field_entity_version', $config->get('target_field'));
    $this->assertConfigSchema($this->container->get('config.typed'), $config->getName(), $config->get());

    $config = $this->config('entity_version_history.settings.history_entity_test.history_entity_test');
    $this->assertEquals('history_entity_test', $config->get('target_entity_type_id'));
    $this->assertEquals('history_entity_test', $config->get('target_bundle'));
    $this->assertEquals('version', $config->get('target_field'));
    $this->assertConfigSchema($this->container->get('config.typed'), $config->getName(), $config->get());

    // Remove configs by unchecking history entity checkbox and
    // the first_bundle checkbox from node entity.
    $history_entity_checkbox->uncheck();
    $first_bundle_checkbox->uncheck();

    $page->pressButton('Save configuration');

    // Make sure that the settings are reflected in the form.
    $this->assertTrue($node_entity_checkbox->isChecked());
    $this->assertTrue($second_bundle_checkbox->isChecked());

    $this->assertFalse($first_bundle_checkbox->isChecked());
    $this->assertFalse($history_entity_checkbox->isChecked());
    $this->assertFalse($history_bundle_checkbox->isChecked());

    // Check the configs are deleted. Only 1 should be left.
    $this->container->get('config.factory')->clearStaticCache();
    $config_entities = $storage->loadMultiple();
    $this->assertCount(1, $config_entities);

    // Select a different field for the remaining bundle config.
    $select->selectOption('Secondary version');
    $page->pressButton('Save configuration');

    // Assert that the correct option is selected.
    $option_field = $assert_session->optionExists('node_second_bundle', 'field_secondary_version');
    $this->assertTrue($option_field->hasAttribute('selected'));

    // Check the config is updated correctly.
    $this->container->get('config.factory')->clearStaticCache();
    $config_entities = $storage->loadMultiple();
    $this->assertCount(1, $config_entities);
    $config = $this->config('entity_version_history.settings.node.second_bundle');
    $this->assertEquals('node', $config->get('target_entity_type_id'));
    $this->assertEquals('second_bundle', $config->get('target_bundle'));
    $this->assertEquals('field_secondary_version', $config->get('target_field'));
    $this->assertConfigSchema($this->container->get('config.typed'), $config->getName(), $config->get());
  }

  /**
   * Checks if a select element contains the specified options.
   *
   * @param string $name
   *   The field name.
   * @param array $expected_options
   *   An array of expected options.
   */
  protected function assertFieldSelectOptions(string $name, array $expected_options): void {
    $select = $this->getSession()->getPage()->find('named', [
      'select',
      $name,
    ]);

    if (!$select) {
      $this->fail('Unable to find select ' . $name);
    }

    $options = $select->findAll('css', 'option');
    array_walk($options, function (NodeElement &$option) {
      $option = $option->getValue();
    });
    sort($options);
    sort($expected_options);
    $this->assertIdentical($options, $expected_options);
  }

}

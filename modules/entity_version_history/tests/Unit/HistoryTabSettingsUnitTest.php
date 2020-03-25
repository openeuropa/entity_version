<?php

declare(strict_types = 1);

namespace Drupal\Tests\entity_version_history\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\entity_version_history\Entity\HistoryTabSettings;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\UnitTestCase;

/**
 * Test HistoryTabSettings entity class.
 *
 * @coversDefaultClass \Drupal\entity_version_history\Entity\HistoryTabSettings
 * @group entity_version_history
 */
class HistoryTabSettingsUnitTest extends UnitTestCase {

  /**
   * The entity type used for testing.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityType;

  /**
   * The entity type manager used for testing.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The ID of the type of the entity under test.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The UUID generator used for testing.
   *
   * @var \Drupal\Component\Uuid\UuidInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $uuid;

  /**
   * The typed configuration manager used for testing.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $typedConfigManager;

  /**
   * The config entity storage used for testing.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorage|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configEntityStorageInterface;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->entityTypeId = $this->randomMachineName();
    $this->entityType = $this->createMock('\Drupal\Core\Entity\EntityTypeInterface');

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    $this->uuid = $this->createMock('\Drupal\Component\Uuid\UuidInterface');

    $this->typedConfigManager = $this->createMock('Drupal\Core\Config\TypedConfigManagerInterface');

    $this->configEntityStorageInterface = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('uuid', $this->uuid);
    $container->set('config.typed', $this->typedConfigManager);
    $container->set('config.storage', $this->configEntityStorageInterface);
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::calculateDependencies()
   */
  public function testCalculateDependencies(): void {
    // Mock the interfaces necessary to create a dependency on a bundle entity.
    $target_entity_type = $this->createMock('\Drupal\Core\Entity\EntityTypeInterface');
    $target_entity_type->expects($this->any())
      ->method('getBundleConfigDependency')
      ->will($this->returnValue(['type' => 'config', 'name' => 'test.test_entity_type.id']));

    $this->entityTypeManager->expects($this->any())
      ->method('getDefinition')
      ->with('test_entity_type')
      ->will($this->returnValue($target_entity_type));

    $field = new FieldConfig([
      'field_name' => 'field_test',
      'entity_type' => 'test_entity_type',
      'bundle' => 'test_bundle',
      'field_type' => 'test_field',
    ]);

    // Mock the field config entity and the dependency methods.
    $field_config = $this->createMock('\Drupal\field\Entity\FieldConfig');
    $field_config->expects($this->any())
      ->method('load')
      ->with($field->id())
      ->will($this->returnValue($field));
    $field_config->expects($this->any())
      ->method('getConfigDependencyName')
      ->will($this->returnValue('field.field.test_entity_type.test_field'));
    $field_config->expects($this->any())
      ->method('getConfigDependencyKey')
      ->will($this->returnValue('config'));

    // Mock the field config storage and make sure it returns the field config.
    $field_config_storage = $this->createMock('\Drupal\field\FieldConfigStorage');
    $field_config_storage->expects($this->any())
      ->method('load')
      ->with($field->id())
      ->will($this->returnValue($field_config));

    // Make sure we return the mocked field config.
    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('field_config')
      ->will($this->returnValue($field_config_storage));

    $history_config = new HistoryTabSettings([
      'target_entity_type_id' => 'test_entity_type',
      'target_bundle' => 'test_bundle',
      'target_field' => $field->getName(),
    ], 'entity_version_history_settings');

    $dependencies = $history_config->calculateDependencies()->getDependencies();

    // Assert that we have the required dependencies for our config entity.
    $this->assertContains('field.field.test_entity_type.test_field', $dependencies['config']);
    $this->assertContains('test.test_entity_type.id', $dependencies['config']);
  }

  /**
   * @covers ::id()
   */
  public function testId(): void {
    $config = new HistoryTabSettings([
      'target_entity_type_id' => 'test_entity_type',
      'target_bundle' => 'test_bundle',
      'target_field' => 'test_field',
    ], 'entity_version_history_settings');
    $this->assertSame('test_entity_type.test_bundle', $config->id());
  }

  /**
   * @covers ::getTargetEntityTypeId()
   */
  public function testTargetEntityTypeId(): void {
    $config = new HistoryTabSettings([
      'target_entity_type_id' => 'test_entity_type',
      'target_bundle' => 'test_bundle',
      'target_field' => 'test_field',
    ], 'entity_version_history_settings');
    $this->assertSame('test_entity_type', $config->getTargetEntityTypeId());
  }

  /**
   * @covers ::getTargetBundle()
   */
  public function testTargetBundle(): void {
    $config = new HistoryTabSettings([
      'target_entity_type_id' => 'test_entity_type',
      'target_bundle' => 'test_bundle',
      'target_field' => 'test_field',
    ], 'entity_version_history_settings');
    $this->assertSame('test_bundle', $config->getTargetBundle());
  }

  /**
   * @covers ::getTargetField()
   */
  public function testTargetField(): void {
    $config = new HistoryTabSettings([
      'target_entity_type_id' => 'test_entity_type',
      'target_bundle' => 'test_bundle',
      'target_field' => 'test_field',
    ], 'entity_version_history_settings');
    $this->assertSame('test_field', $config->getTargetField());
  }

  /**
   * @covers ::loadByEntityTypeBundle()
   */
  public function testLoadByEntityTypeBundle(): void {
    $config = new HistoryTabSettings([
      'target_entity_type_id' => 'test_entity_type',
      'target_bundle' => 'test_bundle',
      'target_field' => 'test_field',
    ], 'entity_version_history_settings');

    $this->configEntityStorageInterface
      ->expects($this->any())
      ->method('load')
      ->with($config->id())
      ->will($this->returnValue($config));

    $this->entityTypeManager
      ->expects($this->any())
      ->method('getStorage')
      ->with('entity_version_history_settings')
      ->will($this->returnValue($this->configEntityStorageInterface));

    $entity_type_repository = $this->getMockForAbstractClass(EntityTypeRepositoryInterface::class);
    $entity_type_repository->expects($this->any())
      ->method('getEntityTypeFromClass')
      ->with(HistoryTabSettings::class)
      ->willReturn('entity_version_history_settings');

    \Drupal::getContainer()->set('entity_type.repository', $entity_type_repository);

    $loaded_config = HistoryTabSettings::loadByEntityTypeBundle('test_entity_type', 'test_bundle');

    $this->assertSame($config, $loaded_config);
  }

  /**
   * @covers ::loadByEntityType()
   */
  public function testLoadByEntityType(): void {
    $config[] = new HistoryTabSettings([
      'target_entity_type_id' => 'test_entity_type',
      'target_bundle' => 'test_bundle',
      'target_field' => 'test_field',
    ], 'entity_version_history_settings');

    $config[] = new HistoryTabSettings([
      'target_entity_type_id' => 'test_entity_type',
      'target_bundle' => 'new_test_bundle',
      'target_field' => 'new_test_field',
    ], 'entity_version_history_settings');

    $this->configEntityStorageInterface
      ->expects($this->any())
      ->method('loadByProperties')
      ->with(['target_entity_type_id' => $config[0]->getTargetEntityTypeId()])
      ->will($this->returnValue($config));

    $this->entityTypeManager
      ->expects($this->any())
      ->method('getStorage')
      ->with('entity_version_history_settings')
      ->will($this->returnValue($this->configEntityStorageInterface));

    $entity_type_repository = $this->getMockForAbstractClass(EntityTypeRepositoryInterface::class);
    $entity_type_repository->expects($this->any())
      ->method('getEntityTypeFromClass')
      ->with(HistoryTabSettings::class)
      ->willReturn('entity_version_history_settings');

    \Drupal::getContainer()->set('entity_type.repository', $entity_type_repository);

    $loaded_config = HistoryTabSettings::loadByEntityType('test_entity_type');

    $this->assertSame($config, $loaded_config);
  }

}

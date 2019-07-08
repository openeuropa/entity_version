<?php

declare(strict_types = 1);

namespace Drupal\entity_version_workflows;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use InvalidArgumentException;

/**
 * Handler to control the entity version numbers for when workflows are used.
 */
class EntityVersionWorkflowManager {

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new EntityVersionWorkflowHandler.
   *
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_info
   *   The moderation information service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(ModerationInformationInterface $moderation_info, EntityTypeManagerInterface $entityTypeManager, ModuleHandlerInterface $moduleHandler) {
    $this->moderationInfo = $moderation_info;
    $this->entityTypeManager = $entityTypeManager;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Update the entity version field values of a content entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   * @param string $field_name
   *   The name of the entity version field.
   */
  public function updateEntityVersion(ContentEntityInterface $entity, $field_name): void {
    if ($entity->isNew()) {
      return;
    }

    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = $this->moderationInfo->getWorkflowForEntity($entity);
    if (!$workflow) {
      return;
    }

    /** @var \Drupal\workflows\WorkflowTypeInterface $workflow_plugin */
    $workflow_plugin = $workflow->getTypePlugin();

    // Compute the transition being used in order to get the version actions
    // from its config. For this, we need to load the latest revision of the
    // entity.
    $revision = $this->moderationInfo->getLatestRevision($entity->getEntityTypeId(), $entity->id());

    // Retrieve the configured actions to perform for the version field numbers
    // from the transition.
    $current_state = $revision->moderation_state->value;
    $next_state = $entity->moderation_state->value;

    // Try to get the transition or do nothing.
    try {
      /** @var \Drupal\workflows\TransitionInterface $transition */
      $transition = $workflow_plugin->getTransitionFromStateToState($current_state, $next_state);
    }
    catch (InvalidArgumentException $e) {
      return;
    }

    $config_values = $workflow->getThirdPartySetting('entity_version_workflows', $transition->id());
    if (!$config_values) {
      return;
    }

    // If the config is defined to check entity field values changes we don't
    // act if they did not change.
    if (!empty($config_values['check_values_changed'])) {
      if (!$this->isEntityChanged($entity)) {
        return;
      }
      // Remove this to leave the version settings only for the iteration.
      unset($config_values['check_values_changed']);
    }

    // Execute all the configured actions on all the values of the field.
    foreach ($config_values as $version => $action) {
      foreach ($entity->get($field_name)->getValue() as $delta => $value) {
        $entity->get($field_name)->get($delta)->$action($version);
      }
    }
  }

  /**
   * Check if the entity has changed.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity object.
   *
   * @return bool
   *   Return true if the entity has changed, otherwise return false.
   */
  protected function isEntityChanged(ContentEntityInterface $entity): bool {
    if (empty($entity->original)) {
      return FALSE;
    }
    $fields = array_keys($entity->toArray());
    // Blacklist dynamic fields from the check.
    $field_blacklist = [
      'vid',
      'revision_timestamp',
      'moderation_state',
    ];

    // Let others change the blacklist.
    $this->moduleHandler->alter('check_values_changed', $field_blacklist);
    // We consider the latest revision as original to compare with the entity.
    $original = $revision = $this->moderationInfo->getLatestRevision($entity->getEntityTypeId(), $entity->id());
    // Remove the blacklisted fields from checking.
    $fields = array_diff($fields, $field_blacklist);
    foreach ($fields as $field) {
      // Check if the values are changed in the entity.
      if ($entity->get($field)->hasAffectingChanges($original->get($field)->filterEmptyItems(), $entity->language()->getId())) {
        return TRUE;
      }
    }

    return FALSE;
  }

}

<?php

declare(strict_types = 1);

namespace Drupal\entity_version_workflows;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

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
   * Constructs a new EntityVersionWorkflowHandler.
   *
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_info
   *   The moderation information service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(ModerationInformationInterface $moderation_info, EntityTypeManagerInterface $entityTypeManager) {
    $this->moderationInfo = $moderation_info;
    $this->entityTypeManager = $entityTypeManager;
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
    /** @var \Drupal\workflows\TransitionInterface $transition */
    $transition = $workflow_plugin->getTransitionFromStateToState($current_state, $next_state);
    $values = $workflow->getThirdPartySetting('entity_version_workflows', $transition->id());
    if (!$values) {
      return;
    }

    // Execute all the configured actions on all the values of the field.
    foreach ($values as $version => $action) {
      foreach ($entity->get($field_name)->getValue() as $delta => $value) {
        $entity->get($field_name)->get($delta)->$action($version);
      }
    }
  }

}
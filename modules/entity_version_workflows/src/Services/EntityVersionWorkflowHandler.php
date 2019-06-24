<?php

declare(strict_types = 1);

namespace Drupal\entity_version_workflows\Services;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Handler to control the entity version numbers for when workflows are used.
 */
class EntityVersionWorkflowHandler {

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * Constructs a new EntityVersionWorkflowHandler.
   *
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_info
   *   The moderation information service.
   */
  public function __construct(ModerationInformationInterface $moderation_info) {
    $this->moderationInfo = $moderation_info;
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
    // from its config.
    $current_state = $entity->original->moderation_state->value;
    $next_state = $entity->moderation_state->value;
    /** @var \Drupal\workflows\TransitionInterface $transition */
    $transition = $workflow_plugin->getTransitionFromStateToState($current_state, $next_state);
    if ($values = $workflow->getThirdPartySetting('entity_version_workflows', $transition->id())) {
      $count_values = count($entity->get($field_name)->getValue());
      foreach ($values as $version => $action) {
        for ($i = 0; $i < $count_values; $i++) {
          $entity->get($field_name)->get($i)->$action($version);
        }
      }
    }
  }

}

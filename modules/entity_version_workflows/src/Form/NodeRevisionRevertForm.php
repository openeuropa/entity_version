<?php

namespace Drupal\entity_version_workflows\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Form\NodeRevisionRevertForm as CoreNodeRevisionRevertForm;

/**
 * Provides a form for reverting a node revision.
 */
class NodeRevisionRevertForm extends CoreNodeRevisionRevertForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node_revision = NULL) {
    $form = parent::buildForm($form, $form_state, $node_revision);
    $revision = $this->revision;
    $revision->entity_version_no_update = TRUE;
    $this->revision = $revision;
    return $form;
  }

}

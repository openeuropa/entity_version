<?php

declare(strict_types = 1);

namespace Drupal\entity_version_history\Form;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_version_history\Entity\HistoryTabSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure the history tab settings per content entity type and bundle.
 *
 * @internal
 */
class HistoryTabSettingsForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs an HistoryTabSettingsForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entityFieldManager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_version_history_settings_form';
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.NPathComplexity)
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $bundle_labels = [];
    $entity_labels = [];
    $field_labels = [];
    $history_configs = [];
    // Get entity types and bundles where the entity_version field is present.
    $versioned_entity_types = $this->entityFieldManager->getFieldMapByFieldType('entity_version');

    foreach ($versioned_entity_types as $entity_type_id => $fields) {
      $definition = $this->entityTypeManager->getDefinition($entity_type_id);
      if (!$definition instanceof ContentEntityTypeInterface) {
        // We are only interested in the content entity types.
        continue;
      }
      if (!$definition->hasLinkTemplate('canonical') || !$definition->hasHandlerClass('moderation')) {
        // We are only interested in the content entity types has canonical
        // links and are moderated.
        continue;
      }

      // We need a list of options with labels for the form checkboxes.
      $entity_labels[$entity_type_id] = $definition->getLabel() ?: $entity_type_id;

      foreach ($fields as $field_name => $bundle_info) {
        foreach ($bundle_info['bundles'] as $bundle_name) {
          // We need load up the bundle and the field to prepare the checkboxes
          // with values and labels.
          $bundle = $this->entityTypeManager->getStorage($definition->getBundleEntityType())->load($bundle_name);
          $bundle_labels[$entity_type_id][$bundle_name] = $bundle->label();
          $field_config = $this->entityTypeManager->getStorage('field_config')->load("$entity_type_id.$bundle_name.$field_name");
          $field_labels[$entity_type_id][$bundle_name][$field_name] = $field_config->label();

          if ($config = HistoryTabSettings::loadByEntityTypeBundle($entity_type_id, $bundle_name)) {
            // Get the existing configs to pre-fill the form fields with
            // default values.
            $history_configs[$entity_type_id][$bundle_name][$field_name] = $config->getTargetField();
          }
        }
      }
    }

    asort($entity_labels);

    // Create checkboxes for all entity types.
    $form['entity_types'] = [
      '#title' => $this->t('History tab settings'),
      '#type' => 'checkboxes',
      '#options' => $entity_labels,
      '#default_value' => is_array($history_configs) ? array_keys($history_configs) : [],
    ];

    // Create checkboxes for all bundles.
    foreach ($bundle_labels as $entity_type_id => $bundles) {
      $form['settings'][$entity_type_id] = [
        '#title' => $entity_labels[$entity_type_id],
        '#type' => 'checkboxes',
        '#options' => $bundles,
        '#states' => [
          'visible' => [
            ':input[name="entity_types[' . $entity_type_id . ']"]' => ['checked' => TRUE],
          ],
        ],
        '#default_value' => is_array($history_configs[$entity_type_id]) ? array_keys($history_configs[$entity_type_id]) : [],
      ];

      // Create select list of the version fields in the bundle.
      foreach ($bundles as $bundle_name => $label) {
        $form['settings']["$entity_type_id-$bundle_name"] = [
          '#title' => $label,
          '#type' => 'select',
          '#options' => $field_labels[$entity_type_id][$bundle_name],
          '#states' => [
            'visible' => [
              ':input[name="entity_types[' . $entity_type_id . ']"]' => ['checked' => TRUE],
              ':input[name="' . $entity_type_id . '[' . $bundle_name . ']"]' => ['checked' => TRUE],
            ],
          ],
          '#default_value' => is_array($history_configs[$entity_type_id][$bundle_name]) ? array_keys($history_configs[$entity_type_id][$bundle_name]) : [],
        ];
      }
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('entity_types') as $target_entity_type_id => $entity_id_value) {
      if (!$entity_id_value) {
        // Delete all existing config settings with this entity id if the
        // entity type (top level) checkbox is unchecked in the form.
        if ($configs = HistoryTabSettings::loadByEntityType($target_entity_type_id)) {
          $this->entityTypeManager->getStorage('entity_version_history_settings')->delete($configs);
          continue;
        }
      }

      foreach ($form_state->getValue($target_entity_type_id) as $target_bundle => $bundle_value) {
        $config = HistoryTabSettings::loadByEntityTypeBundle($target_entity_type_id, $target_bundle);
        if (!$bundle_value && $config) {
          // Delete existing config setting with this entity id and bundle
          // if the bundle checkbox is unchecked in the form.
          $config->delete();
          continue;
        }

        if ($config) {
          // If the config exist already, skip creating the same config.
          continue;
        }

        $target_field = $form_state->getValue("$target_entity_type_id-$target_bundle");
        if ($target_field && $bundle_value) {
          // If we have a target field and a bundle is checked, we create
          // the new config entity.
          $config = HistoryTabSettings::create([
            'target_entity_type_id' => $target_entity_type_id,
            'target_bundle' => $target_bundle,
            'target_field' => $target_field,
          ]);
          $config->save();
        }
      }
    }
  }

}

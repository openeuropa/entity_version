<?php

/**
 * @file
 * Entity version post updates.
 */

declare(strict_types = 1);

/**
 * Apply entity version settings for bundles with version field.
 */
function entity_version_post_update_configure_settings() {
  // Introduced new configuration to select a version field of an entity bundle
  // as the main version field to use by the (sub)module functionalities,
  // instead of searching for all version fields on the bundle and using the
  // first one.
  $versioned_entity_types = \Drupal::service('entity_field.manager')->getFieldMapByFieldType('entity_version');
  // Get entity types and bundles where the entity_version field is present.
  $version_settings_storage = \Drupal::entityTypeManager()->getStorage('entity_version_settings');

  // Loop through the mapping and create config entities if they don't exist.
  foreach ($versioned_entity_types as $entity_type_id => $fields) {
    foreach ($fields as $field_name => $bundle_info) {
      foreach ($bundle_info['bundles'] as $bundle_name) {
        if ($version_settings_storage->load("$entity_type_id.$bundle_name")) {
          continue;
        }

        // Create the entity version setting.
        $version_settings_storage->create([
          'target_entity_type_id' => $entity_type_id,
          'target_bundle' => $bundle_name,
          'target_field' => $field_name,
        ])->save();
      }
    }
  }
}

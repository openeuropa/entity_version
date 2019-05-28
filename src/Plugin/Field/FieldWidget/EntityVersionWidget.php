<?php

declare(strict_types = 1);

namespace Drupal\entity_version\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity_version_widget' widget.
 *
 * @FieldWidget(
 *   id = "entity_version_widget",
 *   label = @Translation("Entity version widget"),
 *   field_types = {
 *     "entity_version"
 *   }
 * )
 */
class EntityVersionWidget extends WidgetBase implements WidgetInterface {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['version'] = [
      '#type' => 'details',
      '#title' => t('Version'),
    ] + $element;

    $element['version']['major'] = [
      '#type' => 'textfield',
      '#title' => t('Major'),
      '#default_value' => isset($items[$delta]->major) ? $items[$delta]->major : NULL,
      '#description' => '',
      '#size' => 5,
      '#required' => FALSE,
    ];

    $element['version']['minor'] = [
      '#type' => 'textfield',
      '#title' => t('Minor'),
      '#default_value' => isset($items[$delta]->minor) ? $items[$delta]->minor : NULL,
      '#description' => '',
      '#size' => 5,
      '#required' => FALSE,
    ];

    $element['version']['patch'] = [
      '#type' => 'textfield',
      '#title' => t('Patch'),
      '#default_value' => isset($items[$delta]->patch) ? $items[$delta]->patch : NULL,
      '#description' => '',
      '#size' => 5,
      '#required' => FALSE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {

    foreach ($values as &$item) {
      $version = $item['version'];
      $item['major'] = $version['major'];
      $item['minor'] = $version['minor'];
      $item['patch'] = $version['patch'];
    }

    return $values;
  }

}

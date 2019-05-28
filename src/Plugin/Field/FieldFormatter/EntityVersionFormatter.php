<?php

namespace Drupal\entity_version\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity_version' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_version_formatter",
 *   label = @Translation("Version"),
 *   field_types = {
 *     "entity_version"
 *   }
 * )
 */
class EntityVersionFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'minimum_category' => 'patch',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      'minimum_category' => [
        '#type' => 'select',
        '#title' => $this->t('Minimum version'),
        '#description' => $this->t('The minimum version number category to show.'),
        '#default_value' => $this->getSetting('minimum_category'),
        '#options' => [
          'major' => 'Major',
          'minor' => 'Minor',
          'patch' => 'Patch',
        ],
      ],
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = t('Minimum category: @value', ['@value' => $this->getSetting('minimum_category')]);
    return parent::settingsSummary();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = $this->viewValue($item);
    }

    return $elements;
  }

  /**
   * Generate the output with the desired version category numbers.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return array
   *   The render array.
   */
  protected function viewValue(FieldItemInterface $item) {
    $categories = ['major', 'minor', 'patch'];
    $minimum_category = $this->getSetting('minimum_category');
    $text_value = [];

    foreach ($categories as $category) {
      $value = $item->get($category)->getValue();

      $text_value[] = $value;
      if ($category === $minimum_category) {
        $text_value = implode('.', $text_value);;
        break;
      }
    }

    return [
      '#markup' => $text_value,
    ];
  }

}

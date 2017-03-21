<?php

namespace Drupal\contacts\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block to view a custom text content.
 *
 * @Block(
 *   id = "contacts_theme_logo",
 *   admin_label = @Translation("Theme logo"),
 *   category = @Translation("Global"),
 * )
 */
class ThemeLogo extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      'site_logo' => [
        '#theme' => 'image',
        '#uri' => theme_get_setting('logo.url'),
        '#alt' => $this->t('Home'),
      ]
    ];
  }

}

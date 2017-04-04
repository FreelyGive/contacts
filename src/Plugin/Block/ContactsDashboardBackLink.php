<?php

namespace Drupal\contacts\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a block to view a contact dashboard summary.
 *
 * @Block(
 *   id = "contacts_back",
 *   admin_label = @Translation("Back to contacts dashboard link"),
 *   category = @Translation("Contacts"),
 * )
 */
class ContactsDashboardBackLink extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#type' => 'link',
      '#url' => Url::fromRoute('page_manager.page_view_contacts_dashboard'),
      '#title' => $this->t('Back to search'),
      '#options' => [
        'attributes' => [
          'class' => ['button'],
        ],
      ],
    ];
  }

}

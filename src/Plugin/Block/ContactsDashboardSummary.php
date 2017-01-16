<?php

namespace Drupal\contacts\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block to view a contact dashboard summary.
 *
 * @Block(
 *   id = "summary",
 *   category = @Translation("Contacts"),
 *   deriver = "Drupal\contacts\Plugin\Deriver\ContactsDashboardSummaryDeriver",
 * )
 */
class ContactsDashboardSummary extends BlockBase {

  /**
   * The contact user object.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public function build() {
    $this->user = $this->getContextValue('user');

    $build = [
      '#theme' => 'contacts_dash_summary',
      '#weight' => 1,
      '#user' => $this->user,
      '#attached' => [
        'library' => ['contacts/contacts-dashboard'],
      ],
    ];

    return $build;
  }

}

<?php

namespace Drupal\contacts\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Context\ContextDefinition;

/**
 * Provides the contact dashboard tabs with required context.
 */
class ContactsDashboardTabsDeriver extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives['contacts_dashboard'] = $base_plugin_definition;
    $this->derivatives['contacts_dashboard']['admin_label'] = 'Contacts Dashboard Tabs';
    $this->derivatives['contacts_dashboard']['context'] = [
      'subpage' => new ContextDefinition('string'),
      'user' => new ContextDefinition('entity:user'),
    ];

    return $this->derivatives;
  }

}

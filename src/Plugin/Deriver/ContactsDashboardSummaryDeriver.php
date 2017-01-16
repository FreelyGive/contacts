<?php

namespace Drupal\contacts\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Context\ContextDefinition;

/**
 * Provides the contact dashboard summary with required context.
 */
class ContactsDashboardSummaryDeriver extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives['contacts_dashboard'] = $base_plugin_definition;
    $this->derivatives['contacts_dashboard']['admin_label'] = 'Contacts Dashboard User Summary';
    $this->derivatives['contacts_dashboard']['context'] = [
      'user' => new ContextDefinition('entity:user'),
    ];

    return $this->derivatives;
  }

}

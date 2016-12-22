<?php

namespace Drupal\contacts\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContextAwarePluginInterface;

/**
 * Provides a block to view a custom text content.
 *
 * @Block(
 *   id = "contacts_entity_form",
 *   admin_label = @Translation("Entity Form"),
 *   category = @Translation("CRM"),
 * )
 */
class ContactsEntityForm extends BlockBase implements ContextAwarePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $entity = $this->getContextValue('entity');
    $operation = $this->getContextValue('operation') ? $this->getContextValue('operation') : 'default';
    return \Drupal::getContainer()->get('entity.form_builder')
      ->getForm($entity, $operation);
  }

}

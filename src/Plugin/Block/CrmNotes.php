<?php

namespace Drupal\contacts\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContextAwarePluginInterface;

/**
 * Provides a block to view a custom text content.
 *
 * @Block(
 *   id = "crm_notes",
 *   admin_label = @Translation("Notes"),
 *   category = @Translation("CRM"),
 * )
 */
class CrmNotes extends BlockBase implements ContextAwarePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $entity = $this->getContextValue('entity');
    return \Drupal::formBuilder()->getForm('Drupal\contacts\Form\NotesForm', $entity);
  }

}

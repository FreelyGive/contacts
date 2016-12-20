<?php

namespace Drupal\contacts\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for notes form.
 */
class NotesForm extends FormBase {

  /**
   * The profile entity being rendered.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  private $entity;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'crm_notes_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#parents'] = [];
    $this->entity = $form_state->getBuildInfo()['args'][0];

    $entity_form_display = \Drupal::service('entity_type.manager')
      ->getStorage('entity_form_display')
      ->load('profile.crm_notes.default');

    if ($widget = $entity_form_display->getRenderer('crm_notes')) {
      $items = $this->entity->get('crm_notes');
      $form['crm_notes'] = $widget->form($items, $form, $form_state);
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Save',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->crm_notes = $form_state->getValue('crm_notes');
    $this->entity->save();

    drupal_set_message($this->t('Notes for %label have been saved.', ['%label' => $this->entity->getOwnerId()]));

    $form_state->setRedirect('page_manager.page_view_contacts_dashboard_contact', [
      'user' => $this->entity->getOwnerId(),
      'subpage' => 'notes',
    ]);
  }

}

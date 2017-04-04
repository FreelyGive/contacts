<?php

namespace Drupal\contacts\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * The Add Individual form.
 */
class AddIndivForm extends AddContactBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contacts_add_indiv_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $profile_fields = $this->entityFieldManager->getFieldDefinitions('profile', 'crm_indiv');
    $profile = $this->entityTypeManager->getStorage('profile')->create(['type' => 'crm_indiv']);
    $profile_fields['crm_name']->setRequired(TRUE);
    $form['crm_name'] = $this->getWidget($profile, $profile_fields, 'crm_name', $form, $form_state);
    $form['crm_name']['#weight'] = 0;

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Add person'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $this->profileViolations->filterByFields(['crm_name']);
    $this->flagViolations($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntities(array $form, FormStateInterface $form_state) {
    parent::buildEntities($form, $form_state);

    /* @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = $this->entityTypeManager->getStorage('profile')->create([
      'type' => 'crm_indiv',
      'status' => TRUE,
      'is_default' => TRUE,
    ]);
    $profile->setValidationRequired(!$form_state->getTemporaryValue('entity_validated'));
    $profile->set('crm_name', $form_state->getValue('crm_name'));
    $this->profile = $profile;

    // Add our relevant role.
    $this->user->addRole('crm_indiv');
  }

  /**
   * {@inheritdoc}
   */
  protected function flagViolations(array $form, FormStateInterface $form_state) {
    parent::flagViolations($form, $form_state);

    // Flag profile specific violations.
    foreach ($this->profileViolations->getByField('crm_name') as $violation) {
      $form_state->setErrorByName('crm_name', $violation->getMessage());
    }
  }

}

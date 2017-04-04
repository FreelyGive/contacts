<?php

namespace Drupal\contacts\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\profile\Form\ProfileForm;

/**
 * Contacts wrapper around the profile form for use on the contact dashboard.
 */
class ContactsProfileForm extends ProfileForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    $profile_type = $this->entityTypeManager->getStorage('profile_type')->load($this->entity->bundle());
    drupal_set_message($this->t('@label saved.', ['@label' => $profile_type->label()]));
  }

}

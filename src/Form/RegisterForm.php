<?php

namespace Drupal\contacts\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\user\RegisterForm as CoreRegisterForm;

/**
 * Contacts compatible user registration form.
 */
class RegisterForm extends CoreRegisterForm {

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    /* @var \Drupal\user\UserInterface $entity */
    $entity = parent::getEntityFromRouteMatch($route_match, $entity_type_id);

    // Add the individual role as the default. Organisations are unlikely to be
    // created through register forms and in the rare cases they are, roles will
    // be exposed allowing you to switch.
    $entity->addRole('crm_indiv');

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Core doesn't use the entity's roles if we're registering, but we need to
    // preserver any roles set by self::getEntityFromRouteMatch, but we do want
    // to exclude the locked roles.
    $form['account']['roles']['#default_value'] = $this->entity->getRoles(TRUE);

    // If we have the name field (as per our optional install config), make it
    // required depending on the user state..
    if (isset($form['individual:crm_name'])) {
      if ($form['account']['roles']['#access'] ?? TRUE) {
        $form['individual:crm_name']['#states']['visible'] = [
          ':input[name="roles[crm_indiv]"]' => ['checked' => TRUE],
        ];
      }
      elseif ($this->entity->hasRole('crm_indiv')) {
        $form['individual:crm_name']['widget'][0]['#required'] = TRUE;
      }
    }

    return $form;
  }

}

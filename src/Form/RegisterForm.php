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

    // If this is the user login/register route add the individual role.
    if (in_array($route_match->getRouteName(), ['user.login', 'user.register'])) {
      $entity->addRole('crm_indiv');
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // If we have the name field (as per our optional install config), make it
    // required depending on the user state..
    if (isset($form['individual:crm_name'])) {
      if ($this->entity->hasRole('crm_indiv')) {
        $form['individual:crm_name']['widget'][0]['#required'] = TRUE;
      }
      else {
        $form['individual:crm_name']['#states']['visible'] = [
          ':input[name="roles[crm_indiv]"]' => ['checked' => TRUE],
        ];
      }
    }

    return $form;
  }

}

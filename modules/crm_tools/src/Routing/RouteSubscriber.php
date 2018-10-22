<?php

namespace Drupal\crm_tools\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\crm_tools\Controller\LoginController;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Unify the login/register pages.
    $login_route = $collection->get('user.login');
    $register_route = $collection->get('user.register');
    if ($login_route && $register_route) {
      $login_route->setDefault('_controller', LoginController::class . '::page');
      $login_route->setDefault('_login_form', $login_route->getDefault('_form'));
      $login_route->setDefault('_register_form', $register_route->getDefault('_entity_form'));

      $register_route->setDefault('_controller', LoginController::class . '::page');
      $register_route->setDefault('_login_form', $login_route->getDefault('_form'));
      $register_route->setDefault('_register_form', $register_route->getDefault('_entity_form'));
    }

    // Override the core roles listing with our form.
    if ($route = $collection->get('entity.user_role.collection')) {
      $defaults = $route->getDefaults();
      unset($defaults['_entity_form']);
      $defaults['_form'] = 'Drupal\crm_tools\Form\OverviewRoles';
      $route->setDefaults($defaults);
    }
  }

}

<?php

namespace Drupal\contacts_user_dashboard\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Override the /user route to go to summary page.
    if ($route = $collection->get('user.page')) {
      $defaults = $route->getDefaults();
      $defaults['_controller'] = '\Drupal\contacts_user_dashboard\Controller\UserDashboardController::userPage';
      $route->setDefaults($defaults);
    }

    // Override the /user/{user} route to go to summary page.
    if ($route = $collection->get('entity.user.canonical')) {
      $defaults = $route->getDefaults();
      $defaults['_controller'] = '\Drupal\contacts_user_dashboard\Controller\UserDashboardController::userPage';
      $route->setDefaults($defaults);
    }

    // Never use the admin theme for editing users.
    if ($route = $collection->get('entity.user.edit_form')) {
      $route->setOption('_admin_route', FALSE);
    }
  }

}

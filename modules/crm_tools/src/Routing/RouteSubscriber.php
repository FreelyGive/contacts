<?php

namespace Drupal\crm_tools\Routing;

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
    // Remove the core roles listing.
    if ($route = $collection->get('entity.user_role.collection')) {
      $defaults = $route->getDefaults();
      unset($defaults['_entity_form']);
      $defaults['_form'] = 'Drupal\crm_tools\Form\OverviewRoles';
      $route->setDefaults($defaults);
    }
  }

}
<?php

namespace Drupal\crm_tools\Routing;

use Drupal\Core\Routing\EnhancerInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Enhancer to unify login and registration pages.
 */
class RouteEnhancer implements EnhancerInterface {

  /**
   * Returns whether the enhancer runs on the current route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The current route.
   *
   * @return bool
   *   Apply the enhancer.
   */
  protected function applies(Route $route) {
    return $route->getPath() == '/user/login';
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    $route = $defaults[RouteObjectInterface::ROUTE_OBJECT];
    if (!$this->applies($route)) {
      return $defaults;
    }

    // @todo Generate a whole new route to prevent changes in core.
    unset($defaults['_form']);
    $defaults['_controller'] = '\Drupal\crm_tools\Controller\LoginController::page';
    return $defaults;
  }

}

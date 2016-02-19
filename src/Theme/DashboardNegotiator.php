<?php

/**
 * @file
 * Contains \Drupal\contacts\Theme\DashboardNegotiator.
 */

namespace Drupal\contacts\Theme;

use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Switches theme to Dashboard theme on contact dashboard pages.
 */
class DashboardNegotiator implements ThemeNegotiatorInterface {
  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    if (is_null($route_match->getRouteObject())) {
      return FALSE;
    }

    // Use this theme on a certain route.
    return substr($route_match->getRouteObject()->getPath(), 0, 15) == '/admin/contacts';
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    // Here you return the actual theme name.
    return 'dashboard';
  }

}

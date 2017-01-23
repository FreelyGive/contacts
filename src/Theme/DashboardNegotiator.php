<?php

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
    $route = $route_match->getRouteObject();
    if (is_null($route)) {
      return FALSE;
    }

    // Use this theme on a certain route.
    return substr($route->getPath(), 0, 15) == '/admin/contacts';
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    // Here you return the actual theme name.
    return 'contacts_theme';
  }

}

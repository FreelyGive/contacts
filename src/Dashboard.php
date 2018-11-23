<?php

namespace Drupal\contacts;

use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Url;

/**
 * Helper for Contacts Dashboard.
 */
class Dashboard {

  /**
   * A fast static cache of the service instances, keyed by service name.
   *
   * Values may be FALSE if we are not on the dashboard page.
   *
   * @var array
   */
  protected static $fastStatic;

  /**
   * Indicates we are not on the Contacts Dashboard.
   */
  const MODE_NOT = 'not';

  /**
   * Indicates we are on the Contacts Dashboard via an full page request.
   */
  const MODE_FULL = 'full';

  /**
   * Indicates we are on the Contacts Dashboard via an AJAX request.
   */
  const MODE_AJAX = 'ajax';

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * The dashboard full page route name.
   *
   * @var string
   */
  protected $fullRouteName;

  /**
   * The dashboard AJAX page route name.
   *
   * @var string
   */
  protected $ajaxRouteName;

  /**
   * The dashboard mode.
   *
   * @var string
   */
  protected $mode;

  /**
   * Construct the dashboard helper.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   The route match service.
   * @param string $full_route_name
   *   The route name for the dashboard full page.
   * @param string $ajax_route_name
   *   The route name for the dashboard AJAX page.
   */
  public function __construct(CurrentRouteMatch $route_match, $full_route_name, $ajax_route_name) {
    $this->routeMatch = $route_match;
    $this->fullRouteName = $full_route_name;
    $this->ajaxRouteName = $ajax_route_name;
  }

  /**
   * Get the dashboard mode for a Url or the current request.
   *
   * @param \Drupal\Core\Url|null $url
   *   Optionally a URL to check. Otherwise we use the current request.
   *
   * @return string
   *   One of the self::MODE_* constants.
   */
  public function getDashboardMode(Url $url = NULL) {
    // If we're not checking a specific URL, see if we can return a cache.
    if (!$url && isset($this->mode)) {
      return $this->mode;
    }

    // Get the route name we're checking.
    if ($url) {
      $route = $url->isRouted() ? $url->getRouteName() : '';

      // If the route is <current>, we can use the cache.
      if ($route == '<current>') {
        return $this->getDashboardMode();
      }
    }
    else {
      $route = $this->routeMatch->getRouteName();
    }

    // Check the mode.
    switch ($route) {
      case $this->fullRouteName:
        $mode = self::MODE_FULL;
        break;

      case $this->ajaxRouteName:
        $mode = self::MODE_AJAX;
        break;

      default:
        $mode = self::MODE_NOT;
        break;
    }

    // If we're checking the current request, cache the value.
    if (!$url) {
      $this->mode = $mode;
    }

    return $mode;
  }

  /**
   * Check if the Url or the current request is the dashboard.
   *
   * @param \Drupal\Core\Url|null $url
   *   Optionally a URL to check. Otherwise we use the current request.
   *
   * @return bool
   *   TRUE if it is the dashboard.
   */
  public function isDashboard(Url $url = NULL) {
    return $this->getDashboardMode($url) !== self::MODE_NOT;
  }

  /**
   * Check if the Url or the current request is the full dashboard.
   *
   * @param \Drupal\Core\Url|null $url
   *   Optionally a URL to check. Otherwise we use the current request.
   *
   * @return bool
   *   TRUE if it is the full dashboard.
   */
  public function isDashboardFull(Url $url = NULL) {
    return $this->getDashboardMode($url) === self::MODE_FULL;
  }

  /**
   * Check if the Url or the current request is the AJAX dashboard.
   *
   * @param \Drupal\Core\Url|null $url
   *   Optionally a URL to check. Otherwise we use the current request.
   *
   * @return bool
   *   TRUE if it is the AJAX dashboard.
   */
  public function isDashboardAjax(Url $url = NULL) {
    return $this->getDashboardMode($url) === self::MODE_AJAX;
  }

  /**
   * Get a full page equivalent for the Url or current page.
   *
   * @param \Drupal\Core\Url|null $url
   *   The URL or NULL to use the current page.
   *
   * @return \Drupal\Core\Url
   *   The full page equivalent URL.
   */
  public function getFullUrl(Url $url = NULL) {
    if ($url) {
      $params = $url->getRouteParameters();
      $options = $url->getOptions();
      unset($options['query']['_wrapper_format']);
      if (isset($options['query']['_format'])) {
      }
    }
    else {
      $param_bag = $this->routeMatch->getParameters();
      $params = [
        'user' => $param_bag->get('user')->id(),
        'subpage' => $param_bag->get('subpage'),
      ];
      $options = [];
    }
    return Url::fromRoute($this->fullRouteName, $params, $options);
  }

  /**
   * Get a AJAX page equivalent for the Url or current page.
   *
   * @param \Drupal\Core\Url|null $url
   *   The URL or NULL to use the current page.
   *
   * @return \Drupal\Core\Url
   *   The AJAX page equivalent URL.
   */
  public function getAjaxUrl(Url $url = NULL) {
    if ($url) {
      $params = $url->getRouteParameters();
      $options = $url->getOptions();
    }
    else {
      $param_bag = $this->routeMatch->getParameters();
      $params = [
        'user' => $param_bag->get('user')->id(),
        'subpage' => $param_bag->get('subpage'),
      ];
      $options = [];
    }
    return Url::fromRoute($this->ajaxRouteName, $params, $options);
  }

  /**
   * Fast implementation of hook_link_alter() for dashboard services.
   *
   * Implements a similar pattern to the drupal fast static to optimise the
   * speed of this method and only do processing where required.
   *
   * @param array $variables
   *   The link variables.
   * @param string $service_name
   *   The service name for the dashboard helper.
   */
  public static function fastHookLinkAlter(array &$variables, $service_name) {
    // Escape early if we are explicitly skipping AJAX conversion.
    if (!empty($variables['options']['contacts_no_ajax'])) {
      return;
    }

    // Instantiate our fast static if it's not already set.
    if (!isset(self::$fastStatic[$service_name])) {
      $dashboard = \Drupal::service($service_name);
      self::$fastStatic[$service_name] = $dashboard->isDashboard() ? $dashboard : FALSE;
    }

    // If we have a service, pass off to the alter method.
    $dashboard = self::$fastStatic[$service_name];
    if ($dashboard) {
      /* @var self $dashboard */
      $dashboard->hookLinkAlter($variables);
    }
  }

  /**
   * Service implementation of hook_link_alter().
   *
   * @param array $variables
   *   The link variables.
   */
  public function hookLinkAlter(array &$variables) {
    /* @var \Drupal\Core\Url $url */
    $url = &$variables['url'];

    // Change links within the dashboard.
    if ($this->isDashboardFull($url)) {
      $variables['options']['attributes'] += [
        'data-ajax-progress' => 'fullscreen',
        'data-ajax-url' => $this->getAjaxUrl($url)->toString(),
      ];
      if (!isset($variables['options']['attributes']['class']) || !in_array('use-ajax', $variables['options']['attributes']['class'])) {
        $variables['options']['attributes']['class'][] = 'use-ajax';
      }
    }

    // Update links indicated to use a modal. Check the target as a workaround
    // for views, which doesn't allow more specific options to be set.
    $use_modal = !empty($variables['options']['contacts_modal'])
      || ($variables['options']['attributes']['target'] ?? NULL) == '_contacts_modal';
    if ($use_modal) {
      unset($variables['options']['attributes']['target']);
      $variables['options']['attributes'] += [
        'data-ajax-progress' => 'fullscreen',
        'data-dialog-type' => 'modal',
      ];
      $variables['options']['attributes']['class'][] = 'use-ajax';

      // Set a redirect URL if there isn't one.
      if (empty($variables['options']['query']['destination'])) {
        $variables['options']['query']['destination'] = $this->getFullUrl()->toString();
      }
    }

    // Update destinations on links to always use the non-AJAX links. We only
    // need to do this if we're currently on the AJAX dashboard.
    if (isset($variables['options']['query']['destination']) && $this->isDashboardAjax()) {
      $destination = Url::fromUserInput($variables['options']['query']['destination']);
      if ($this->isDashboardAjax($destination)) {
        $variables['options']['query']['destination'] = $this->getFullUrl($destination)->toString();
      }
    }

  }

}

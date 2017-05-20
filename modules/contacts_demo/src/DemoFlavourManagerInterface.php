<?php

namespace Drupal\contacts_demo;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Demo Flavour plugin manager interface.
 */
interface DemoFlavourManagerInterface extends PluginManagerInterface {

  /**
   * Checks if plugin has a definition and is supported.
   *
   * @param string $plugin_id
   *   The ID of the plugin to check.
   *
   * @return bool
   *   TRUE if the plugin is supported, FALSE otherwise.
   */
  public function isPluginSupported($plugin_id);

}

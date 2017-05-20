<?php

namespace Drupal\contacts_demo;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDirectoryDiscovery;

/**
 * Manages discovery and instantiation of demo flavour plugins.
 */
class DemoFlavourManager  extends DefaultPluginManager implements DemoFlavourManagerInterface {

  /**
   * Provides some default values for the definition of all flavour plugins.
   *
   * @var array
   */
  protected $defaults = array(
    'label' => '',
    'description' => '',
    'class' => 'Drupal\contacts_demo\DemoFlavour',
    'id' => '',
  );

  /**
   * The object that discovers plugins managed by this manager.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface
   */
  protected $discovery;

  /**
   * The object that instantiates plugins managed by this manager.
   *
   * @var \Drupal\Component\Plugin\Factory\FactoryInterface
   */
  protected $factory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;


  /**
   * Constructs a \Drupal\contacts_demo\DemoFlavourManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);
    $definition['id'] = $plugin_id;
  }

  /**
   * Gets the plugin discovery.
   *
   * @return \Drupal\Component\Plugin\Discovery\DiscoveryInterface
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $directories = array_map(function($directory) {
        return [$directory . '/contacts_flavours', $directory . '/flavours'];
      }, $this->moduleHandler->getModuleDirectories());

      $yaml_discovery = new YamlDirectoryDiscovery($directories, 'contacts_demo');
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($yaml_discovery);
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function isPluginSupported($plugin_id) {
    $definition = $this->getDefinition($plugin_id, FALSE);
    return $definition && $definition['supported'];
  }

}

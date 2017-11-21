<?php

namespace Drupal\crm_tools\Routing;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The crm_tools configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs a new RouteSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('crm_tools.tools');
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Override the core roles listing with our form.
    $tools = $this->config->get('active_tools');
    if (!empty($tools['hats'])) {
      if ($route = $collection->get('entity.user_role.collection')) {
        $defaults = $route->getDefaults();
        unset($defaults['_entity_form']);
        $defaults['_form'] = 'Drupal\crm_tools\Form\OverviewRoles';
        $route->setDefaults($defaults);
      }
    }
  }

}

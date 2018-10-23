<?php

namespace Drupal\contacts_user_dashboard\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local task definitions for all user dashboard local tasks.
 */
class UserDashboardLocalTask extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * The list of tasks relevant to the user dashboard.
   *
   * @var array
   */
  protected $dashboardTasks = [];

  /**
   * Constructs a UserDashboardLocalTask instance.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The module handler.
   */
  public function __construct(RouteProviderInterface $route_provider, ModuleHandler $module_handler) {
    $this->routeProvider = $route_provider;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('router.route_provider'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $derivatives = [];

    $derivatives['user_summary'] = [
      'route_name' => 'contacts_user_dashboard.summary',
      'title' => $this->t('Summary'),
      'base_route' => 'entity.user.canonical',
      'weight' => -99,
    ] + $base_plugin_definition;

    $additional = $this->moduleHandler->invokeAll('contacts_user_dashboard_local_tasks', [$base_plugin_definition]);
    $this->derivatives = array_merge($derivatives, $additional);

    return $this->derivatives;
  }

  /**
   * Alters local tasks to hide unwanted routes.
   */
  public function alterLocalTasks(&$local_tasks) {
    $this->dashboardTasks = array_filter($local_tasks, [$this, 'filterTasks']);
    $this->hideLocalTasks($local_tasks);
    $this->renameLocalTasks($local_tasks);
    $this->moveLocalTasks($local_tasks);
    $this->orderLocalTasks($local_tasks);
  }

  /**
   * Hide local tasks that we do not want to show users.
   */
  protected function hideLocalTasks(&$local_tasks) {
    // @todo Automatically allow all derivatives of contacts_user_dashboard_tab.
    $allowed_items = [
      'contacts_user_dashboard.summary',
      'contacts_user_dashboard_tab:user_summary',
      'entity.profile.user_profile_form:profile.type.crm_indiv',
      'entity.user.edit_form',
    ];

    // Alter hook to add to the task items.
    $this->moduleHandler->alter('contacts_user_dashboard_local_tasks_allowed', $allowed_items);

    foreach (array_keys($this->dashboardTasks) as $task) {
      if (in_array($task, $allowed_items)) {
        continue;
      }
      unset($local_tasks[$task]);
    }
  }

  /**
   * Rename certain local tasks.
   */
  protected function renameLocalTasks(&$local_tasks) {
    $rename_items = [];

    // Alter hook to add to the task items.
    $this->moduleHandler->alter('contacts_user_dashboard_local_tasks_rename', $rename_items);

    foreach ($rename_items as $route_name => $title) {
      if (isset($local_tasks[$route_name])) {
        $local_tasks[$route_name]['title'] = $title;
      }
    }
  }

  /**
   * Move local tasks.
   */
  protected function moveLocalTasks(&$local_tasks) {
    $move_items = [];

    // Alter hook to add to the task items.
    $this->moduleHandler->alter('contacts_user_dashboard_local_tasks_move', $move_items);

    foreach ($move_items as $route_name => $new_base_route) {
      if (isset($local_tasks[$route_name])) {
        $local_tasks[$route_name]['parent_id'] = $new_base_route;
      }
    }
  }

  /**
   * Order local tasks.
   */
  protected function orderLocalTasks(&$local_tasks) {
    $order_items = [];

    // Alter hook to add to the task items.
    $this->moduleHandler->alter('contacts_user_dashboard_local_tasks_order', $order_items);

    foreach ($order_items as $route_name => $route_weight) {
      if (isset($local_tasks[$route_name])) {
        $local_tasks[$route_name]['weight'] = $route_weight;
      }
    }
  }

  /**
   * Determines if a task is based on the user route.
   *
   * @param array $task
   *   A local task definition.
   *
   * @return bool|null
   *   Whether to filter the task.
   */
  protected function filterTasks(array $task) {
    if (isset($task['base_route'])) {
      return $task['base_route'] == 'entity.user.canonical';
    }
  }

}

<?php

namespace Drupal\contacts_user_dashboard\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
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
   * The list of tasks relevant to the user dashboard.
   *
   * @var array
   */
  protected $dashboardTasks = [];

  /**
   * Constructs a \Drupal\contacts_user_dashboard\Plugin\Derivative\UserDashboardLocalTask instance.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   */
  public function __construct(RouteProviderInterface $route_provider) {
    $this->routeProvider = $route_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('router.route_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    $this->derivatives['user_summary'] = [
        'route_name' => 'contacts_user_dashboard.summary',
        'title' => $this->t('Summary'),
        'base_route' => 'entity.user.canonical',
        'weight' => -99,
      ] + $base_plugin_definition;

     $this->derivatives['user_edit_form.default'] = [
        'route_name' => 'entity.user.edit_form',
        'parent_id' => 'entity.user.edit_form',
        'title' => $this->t('Name & login'),
        'base_route' => 'entity.user.canonical',
      ] + $base_plugin_definition;

    $this->derivatives['user_bookings.default'] = [
        'route_name' => 'view.contacts_events_events.page_1',
        'parent_id' => 'views_view:view.contacts_events_events.page_1',
        'title' => $this->t('Bookings'),
        'base_route' => 'entity.user.canonical',
      ] + $base_plugin_definition;

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
  }

  /**
   * Hide local tasks that we do not want to show users.
   */
  protected function hideLocalTasks(&$local_tasks) {
    // @todo Abstract this into an event listener or hook.
    // @todo Automatically allow all derivatives of contacts_user_dashboard_tab.
    $allowed = [
      'contacts_user_dashboard_tab:user_edit_form.default',
      'contacts_user_dashboard_tab:user_summary',
      'contacts_user_dashboard_tab:user_bookings.default',
      'contacts_user_dashboard.summary',
      'gdpr.collected_user_data',
      'gdpr.collected_user_data_default',
      'gdpr_consent.agreements_tab',
      'view.gdpr_tasks_my_data_requests.page_1',
      'views_view:view.contacts_events_events.page_1',
      'entity.profile.user_profile_form:profile.type.crm_indiv',
      'entity.user.edit_form',
    ];

    foreach (array_keys($this->dashboardTasks) as $task) {
      if (in_array($task, $allowed)) {
        continue;
      }
      unset($local_tasks[$task]);
    }
  }

  /**
   * Rename certain local tasks.
   */
  protected function renameLocalTasks(&$local_tasks) {
    // @todo Abstract this into an event listener or hook.
    $rename = [
      'entity.user.edit_form' => $this->t('Personal details'),
      'entity.profile.user_profile_form:profile.type.crm_indiv' => $this->t('Contact & details'),
    ];

    foreach ($rename as $route_name => $title) {
      if (isset($local_tasks[$route_name])) {
        $local_tasks[$route_name]['title'] = $title;
      }
    }
  }

  /**
   * Move local tasks.
   */
  protected function moveLocalTasks(&$local_tasks) {

    // @todo Abstract this into an event listener or hook.
    $move = [
      'entity.profile.user_profile_form:profile.type.crm_indiv' => 'entity.user.edit_form',
    ];

    foreach ($move as $route_name => $new_base_route) {
      if (isset($local_tasks[$route_name])) {
        $local_tasks[$route_name]['parent_id'] = $new_base_route;
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
  protected function filterTasks($task) {
    if (isset($task['base_route'])) {
      return $task['base_route'] == 'entity.user.canonical';
    }
  }

}

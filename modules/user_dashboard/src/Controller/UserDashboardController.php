<?php

namespace Drupal\contacts_user_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for user routes.
 */
class UserDashboardController extends ControllerBase {

  /**
   * Constructs a UserDashboardController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Redirects users to their user dashboard page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a redirect to the profile of the currently logged in user.
   */
  public function userPage() {
    return $this->redirect('contacts_user_dashboard.summary', ['user' => $this->currentUser()->id()]);
  }

  /**
   * Summary page for user dashboard.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user context.
   *
   * @return array
   *   Render array for
   */
  public function userSummaryPage(UserInterface $user) {
    // @todo Find better way to add row class.
    $content = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['row'],
      ],
    ];

    $user_view_builder = \Drupal::entityTypeManager()->getViewBuilder('user');
    $profile_view_builder = \Drupal::entityTypeManager()->getViewBuilder('profile');
    $content['user'] = [
      '#type' => 'user_dashboard_summary',
      '#buttons' => [
        [
          'text' => $this->t('Update details'),
          'route_name' => 'entity.profile.type.user_profile_form',
          'route_parameters' => [
            'user' => $user->id(),
            'profile_type' => 'crm_indiv',
          ],
        ],
        [
          'text' => $this->t('Change password'),
          'route_name' => 'entity.user.edit_form',
          'route_parameters' => ['user' => $user->id()],
        ],
      ],
      '#title' => 'Your details',
      '#content' => $user_view_builder->view($user, 'teaser'),
    ];

    // @todo Move this to the events module.
    $content['bookings'] = [
      '#type' => 'user_dashboard_summary',
      '#buttons' => [
        [
          'text' => $this->t('View all bookings'),
          'route_name' => 'view.contacts_events_events.page_1',
          'route_parameters' => ['user' => $user->id()],
        ],
      ],
      '#title' => 'Recent bookings',
      '#content' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => "No active bookings.",
      ],
    ];

    $profile = $user->profile_crm_communications->entity;
    if ($profile) {
      $content['comms'] = [
        '#type' => 'user_dashboard_summary',
        '#buttons' => [
          [
            'text' => $this->t('Update preferences'),
            'route_name' => 'entity.profile.type.user_profile_form',
            'route_parameters' => [
              'user' => $user->id(),
              'profile_type' => 'crm_communications',
            ],
          ],
        ],
        '#title' => 'Communication preferences',
        '#content' => $profile_view_builder->view($profile, 'teaser'),
      ];
    }

    return $content;
  }

}

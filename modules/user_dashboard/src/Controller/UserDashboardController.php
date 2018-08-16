<?php

namespace Drupal\contacts_user_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserDataInterface;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for user routes.
 */
class UserDashboardController extends ControllerBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a UserController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(DateFormatterInterface $date_formatter, UserStorageInterface $user_storage, UserDataInterface $user_data, LoggerInterface $logger) {
    $this->dateFormatter = $date_formatter;
    $this->userStorage = $user_storage;
    $this->userData = $user_data;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('entity.manager')->getStorage('user'),
      $container->get('user.data'),
      $container->get('logger.factory')->get('user')
    );
  }

  /**
   * Redirects users to their profile page.
   *
   * This controller assumes that it is only invoked for authenticated users.
   * This is enforced for the 'user.page' route with the '_user_is_logged_in'
   * requirement.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a redirect to the profile of the currently logged in user.
   */
  public function userPage() {
    return $this->redirect('contacts_user_dashboard.summary', ['user' => $this->currentUser()->id()]);
  }

  /**
   * Redirects users to their profile page.
   *
   * This controller assumes that it is only invoked for authenticated users.
   * This is enforced for the 'user.page' route with the '_user_is_logged_in'
   * requirement.
   *
   * @return array
   *   Returns a redirect to the profile of the currently logged in user.
   */
  public function userSummaryPage(UserInterface $user) {
    $content = [];

    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('user');

    $content['user'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['col-md-6','pull-md-left','my-3'],
      ],
      'wrapper' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['p-3'],
        ],
        'title' => [
          '#type' => 'html_tag',
          '#tag' => 'h3',
          '#value' => 'Your details',
        ],
        'content' => $view_builder->view($user, 'teaser'),
        'actions' => [
          [
            [
              '#type' => 'html_tag',
              '#attributes' => ['class' => ['btn', 'btn-primary']],
              '#tag' => 'span',
              '#value' => 'Update details',
            ],
            [
              '#type' => 'html_tag',
              '#attributes' => ['class' => ['btn', 'btn-primary']],
              '#tag' => 'span',
              '#value' => 'Change password',
            ]
          ],
        ],
      ],

    ];

    $content['bookings'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['col-md-6','pull-md-right','my-3'],
      ],
      'wrapper' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['p-3'],
        ],
        'title' => [
          '#type' => 'html_tag',
          '#tag' => 'h3',
          '#value' => 'Recent bookings',
        ],
        'content' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => 'No active bookings',
          '#attributes' => [
            'class' => ['my-3'],
          ],
        ],
        'actions' => [
          [
            [
              '#type' => 'html_tag',
              '#attributes' => ['class' => ['btn', 'btn-primary']],
              '#tag' => 'span',
              '#value' => 'View all bookings',
            ],
          ],
        ],
      ],

    ];


    return $content;
  }

}

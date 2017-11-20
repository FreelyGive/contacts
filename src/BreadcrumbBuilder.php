<?php

namespace Drupal\contacts;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Build the breadcrumbs on the contacts dashboard.
 */
class BreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return in_array(
      $route_match->getRouteName(),
      [
        'page_manager.page_view_contacts_dashboard_contact',
        'page_manager.page_view_contacts_dashboard',
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $links = [];

    $links[] = Link::createFromRoute($this->t('Contacts'), 'page_manager.page_view_contacts_dashboard');

    if ($route_match->getRouteName() == 'page_manager.page_view_contacts_dashboard_contact') {
      $user = $route_match->getParameter('user');
      $links[] = Link::createFromRoute(
        $user->label(),
        'page_manager.page_view_contacts_dashboard_contact',
        [
          'user' => $user->id(),
        ]
      );
    }

    return $breadcrumb->setLinks($links);
  }

}

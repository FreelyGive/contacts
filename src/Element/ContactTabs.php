<?php

namespace Drupal\contacts\Element;

use Drupal\contacts\ContactsTabManager;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;

/**
 * Provides a dashboard tabs render element.
 *
 * Properties:
 * - #ajax: The tab entity being viewed.
 * - #user: The user entity being viewed.
 * - #subpage: The tab's dashboard subpage id.
 *
 * Usage example:
 * @code
 * $build['examples_tab_content'] = [
 *   '#type' => 'contact_tabs',
 *   '#ajax' => TRUE,
 *   '#subpage' => 'example',
 *   '#user' => $user,
 *   '#manage_mode' => TRUE,
 * ];
 * @endcode
 *
 * @RenderElement("contact_tabs")
 */
class ContactTabs extends RenderElement {

  /**
   * The tab manager service.
   *
   * @var \Drupal\contacts\ContactsTabManager
   */
  static protected $tabManager;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  static protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#pre_render' => [
        [$class, 'preRenderTabContent'],
      ],
    ];
  }

  /**
   * Pre-render callback: Renders content of a tab.
   *
   * @param array $element
   *   A structured array to build the tab content.
   *
   * @return array
   *   The passed-in element containing the renderable regions in '#content'.
   */
  public static function preRenderTabContent(array $element) {
    $tab_manager = static::getTabManager();

    // Build content array.
    $element['content'] = [
      '#theme' => 'contacts_dash_tabs',
      '#weight' => -1,
      '#tabs' => [],
      '#attached' => [
        'library' => ['contacts/tabs'],
      ],
    ];

    // Show manage link if user has permission.
    if (static::getCurrentUser()->hasPermission('manage contacts dashboard')) {
      $element['content']['#attached']['library'][] = 'contacts/dashboard.manage';
    }

    foreach ($tab_manager->getTabs() as $tab) {
      if (!$element['#manage_mode']) {
        if (!$tab_manager->verifyTab($tab, $element['#user'])) {
          continue;
        }
      }

      $url_stub = $tab->getPath();
      $element['content']['#tabs'][$url_stub] = [
        'text' => $tab->label(),
        'link' => Url::fromRoute('page_manager.page_view_contacts_dashboard_contact', [
          'user' => $element['#user']->id(),
          'subpage' => $url_stub,
        ]),
      ];

      // Swap links for AJAX request links.
      if ($element['#ajax']) {
        $element['content']['#tabs'][$url_stub]['link_attributes']['data-ajax-url'] = Url::fromRoute('contacts.ajax_subpage', [
          'user' => $element['#user']->id(),
          'subpage' => $url_stub,
        ])->toString();
        $element['content']['#tabs'][$url_stub]['link_attributes']['class'][] = 'use-ajax';
        $element['content']['#tabs'][$url_stub]['link_attributes']['data-ajax-progress'] = 'fullscreen';
      }

      // Add tab id to attributes.
      $element['content']['#tabs'][$url_stub]['link_attributes']['data-contacts-tab-id'] = $tab->getOriginalId();
    }

    // Add active class to current tab.
    if (isset($element['content']['#tabs'][$element['#subpage']])) {
      $element['content']['#tabs'][$element['#subpage']]['attributes']['class'][] = 'is-active';
      $element['content']['#tabs'][$element['#subpage']]['link_attributes']['class'][] = 'is-active';
    }

    return $element;
  }

  /**
   * Gets the tab manager service.
   *
   * @return \Drupal\contacts\ContactsTabManager
   *   The tab manager service.
   */
  protected static function getTabManager() {
    if (!isset(self::$tabManager)) {
      self::$tabManager = \Drupal::service('contacts.tab_manager');
    }
    return self::$tabManager;
  }

  /**
   * Sets the tab manager service to use.
   *
   * @param \Drupal\contacts\ContactsTabManager $tab_manager
   *   The tab manager service.
   */
  public static function setTabManager(ContactsTabManager $tab_manager) {
    self::$tabManager = $tab_manager;
  }

  /**
   * Gets the current user service.
   *
   * @return \Drupal\Core\Session\AccountProxy
   *   The current user service.
   */
  protected static function getCurrentUser() {
    if (!isset(self::$currentUser)) {
      self::$currentUser = \Drupal::service('current_user');
    }
    return self::$currentUser;
  }

  /**
   * Sets the current user service to use.
   *
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user service.
   */
  public static function setCurrentUser(AccountProxy $current_user) {
    self::$currentUser = $current_user;
  }

}

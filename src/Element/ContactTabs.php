<?php

namespace Drupal\contacts\Element;

use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Url;

/**
 * Provides a dashboard tabs render element.
 *
 * Properties:
 * - #tabs: Tab data array keyed by id containing label and path.
 * - #ajax: The tab entity being viewed.
 * - #user: The user entity being viewed.
 * - #subpage: The tab's dashboard subpage id.
 *
 * Usage example:
 * @code
 * $build['examples_tab_content'] = [
 *   '#type' => 'contact_tabs',
 *   '#tabs' => [],
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
    // Build content array.
    $element['content'] = [
      '#theme' => 'contacts_dash_tabs',
      '#weight' => -1,
      '#tabs' => [],
      '#manage_mode' => $element['#manage_mode'],
      '#attached' => [
        'library' => ['contacts/tabs'],
      ],
    ];

    foreach ($element['#tabs'] as $tab_id => $tab) {
      $element['content']['#tabs'][$tab_id] = [
        'text' => $tab['label'],
        'link' => Url::fromRoute('page_manager.page_view_contacts_dashboard_contact', [
          'user' => $element['#user']->id(),
          'subpage' => $tab['path'],
        ]),
      ];

      // Swap links for AJAX request links.
      if ($element['#ajax']) {
        $element['content']['#tabs'][$tab_id]['link_attributes']['data-ajax-url'] = Url::fromRoute('contacts.ajax_subpage', [
          'user' => $element['#user']->id(),
          'subpage' => $tab['path'],
        ])->toString();
        $element['content']['#tabs'][$tab_id]['link_attributes']['class'][] = 'use-ajax';
        $element['content']['#tabs'][$tab_id]['link_attributes']['data-ajax-progress'] = 'fullscreen';
      }

      // Add tab id to attributes.
      $element['content']['#tabs'][$tab_id]['attributes']['data-contacts-drag-tab-id'] = $tab_id;

      // Add active class to current tab.
      if ($tab['path'] == $element['#subpage']) {
        $element['content']['#tabs'][$tab_id]['attributes']['class'][] = 'is-active';
        $element['content']['#tabs'][$tab_id]['link_attributes']['class'][] = 'is-active';
      }
    }

    return $element;
  }

}

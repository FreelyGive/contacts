<?php

namespace Drupal\contacts\Element;

use Drupal\contacts\ContactsTabManager;
use Drupal\Core\Layout\LayoutPluginManager;
use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a dashboard tab content render element.
 *
 * Properties:
 * - #tab: The tab entity being viewed.
 * - #user: The user entity being viewed.
 * - #subpage: The tab's dashboard subpage id.
 *
 * Usage example:
 * @code
 * $build['examples_tab_content'] = [
 *   '#type' => 'contact_tab_content',
 *   '#tab' => $tab,
 *   '#subpage' => 'example',
 *   '#user' => $user,
 * ];
 * @endcode
 *
 * @RenderElement("contact_tab_content")
 */
class ContactTabContent extends RenderElement {

  /**
   * The tab manager service.
   *
   * @var \Drupal\contacts\ContactsTabManager
   */
  static protected $tabManager;

  /**
   * The layout manager service.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManager
   */
  static protected $layoutManager;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#attributes' => [],
      '#not_found' => $this->t('Page not found.'),
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
    // Check this tab is valid for the contact.
    if ($element['#tab'] && $tab_manager->verifyTab($element['#tab'], $element['#user'])) {
      $layout = $element['#tab']->get('layout') ?: 'contacts_tab_content.stacked';
      $layout_manager = static::getLayoutManager();
      $layoutInstance = $layout_manager->createInstance($layout, []);

      // Get available regions from tab.
      $regions = [];
      foreach (array_keys($layoutInstance->getPluginDefinition()->getRegions()) as $region) {
        $regions[$region] = [];
      }

      $blocks = $tab_manager->getBlocks($element['#tab'], $element['#user']);
      foreach ($blocks as $key => $block) {
        /* @var \Drupal\Core\Block\BlockPluginInterface $block */
        // @todo fix weight.
        $block_content = [
          '#theme' => 'block',
          '#attributes' => [],
          '#configuration' => $block->getConfiguration(),
          '#plugin_id' => $block->getPluginId(),
          '#base_plugin_id' => $block->getBaseId(),
          '#derivative_plugin_id' => $block->getDerivativeId(),
          '#weight' => $block->getConfiguration()['weight'],
          'content' => $block->build(),
        ];

        $block_content['content']['#title'] = $block->label(TRUE);
        $regions[$block->getConfiguration()['region']][] = $block_content;
      }

      $element['content'] = $layoutInstance->build($regions);
      $element['content']['#attributes'] = $element['#attributes'];
    }
    else {
      drupal_set_message($element['#not_found'], 'warning');
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
   * Gets the layout manager service.
   *
   * @return \Drupal\Core\Layout\LayoutPluginManager
   *   The layout manager service.
   */
  protected static function getLayoutManager() {
    if (!isset(self::$layoutManager)) {
      self::$layoutManager = \Drupal::service('plugin.manager.core.layout');
    }
    return self::$layoutManager;
  }

  /**
   * Sets the layout manager service to use.
   *
   * @param \Drupal\Core\Layout\LayoutPluginManager $layout_manager
   *   The layout manager service.
   */
  public static function setLayoutManager(LayoutPluginManager $layout_manager) {
    self::$layoutManager = $layout_manager;
  }

}

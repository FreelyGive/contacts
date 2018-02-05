<?php

namespace Drupal\contacts\Element;

use Drupal\contacts\Plugin\DashboardBlockInterface;
use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a dashboard tab content render element.
 *
 * Properties:
 * - #layout: The layout instance to assign blocks to.
 * - #tab: The tab entity being viewed.
 * - #blocks: Array of block plugins from tab.
 * - #user: The user entity being viewed.
 * - #subpage: The tab's dashboard subpage id.
 *
 * Usage example:
 * @code
 * $build['examples_tab_content'] = [
 *   '#type' => 'contact_tab_content',
 *   '#layout' => $layout,
 *   '#tab' => $tab,
 *   '#blocks' => [],
 *   '#subpage' => 'example',
 *   '#user' => $user,
 *   '#manage_mode' => TRUE,
 * ];
 * @endcode
 *
 * @RenderElement("contact_tab_content")
 */
class ContactTabContent extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#attributes' => [],
      '#region_attributes' => [],
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
    $blocks = $element['#blocks'];
    if (!empty($blocks)) {

      // Get available regions from layout if not already provided.
      if (empty($element['#regions'])) {
        foreach (array_keys($element['#layout']->getPluginDefinition()->getRegions()) as $region) {
          $element['#regions'][$region] = [];
        }
      }

      foreach ($blocks as $key => $block) {
        /* @var \Drupal\Core\Block\BlockPluginInterface $block */
        if ($element['#manage_mode']) {
          $block_content = [
            '#theme' => 'contacts_manage_block',
            '#attributes' => [],
            '#id' => $block->getPluginId(),
            '#tab' => $element['#tab'],
            '#block' => $block,
            '#subpage' => $element['#subpage'],
            '#mode' => 'manage',
          ];
        }
        else {
          $content = $block->build();
          $block_content = [
            '#theme' => 'block',
            '#attributes' => [],
            '#configuration' => $block->getConfiguration(),
            '#plugin_id' => $block->getPluginId(),
            '#base_plugin_id' => $block->getBaseId(),
            '#derivative_plugin_id' => $block->getDerivativeId(),
            '#weight' => $block->getConfiguration()['weight'],
            'content' => $content,
          ];

          // Add edit link to title.
          if ($block instanceof DashboardBlockInterface) {
            $block_content['#dashboard_label_edit_link'] = $block->getEditLink(DashboardBlockInterface::EDIT_LINK_TITLE);
            $block_content['#pre_render'][] = 'contacts_dashboard_block_edit_link_pre_render';
          }

          $block_content['content']['#title'] = $block->label();
        }
        $element['#regions'][$block->getConfiguration()['region']][] = $block_content;
      }

      $element['content'] = $element['#layout']->build($element['#regions']);
      $element['content']['#attributes'] = $element['#attributes'];
    }
    else {
      drupal_set_message($element['#not_found'], 'warning');
    }

    return $element;
  }

}

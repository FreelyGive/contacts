<?php

namespace Drupal\contacts\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a dashboard tab content render element.
 *
 * Properties:
 * - #tab: The tab entity being viewed.
 * - #user: The user entity being viewed.
 * - #subpage: The tab's dashboard subpage id.
 * - #region_attributes: Attributes array for the content regions.
 * - #content: Array of renderable regions.
 *
 * Usage example:
 * @code
 * $build['examples_tab_content'] = [
 *   '#type' => 'contact_tab_content',
 *   '#region_attributes' => [],
 *   '#tab' => $tab,
 *   '#subpage' => 'example',
 *   '#user' => $user,
 *   '#content' => [
 *     'left' => [],
 *     'right' => [],
 *   ],
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
      '#theme' => 'contact_tab_content',
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
    if ($element['#tab']) {
      /* @var \Drupal\contacts\ContactsTabManager $tab_manager */
      $tab_manager = \Drupal::service('contacts.tab_manager');
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

        $block_content['content']['#title'] = $block->label();
        $element['#content'][$block->getConfiguration()['region']][] = $block_content;
      }
    }
    else {
      drupal_set_message($element['#not_found'], 'warning');
    }

    return $element;
  }

}

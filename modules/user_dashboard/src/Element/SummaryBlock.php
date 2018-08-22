<?php

namespace Drupal\contacts_user_dashboard\Element;

use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Url;

/**
 * Provides a render element for any HTML tag, with properties and value.
 *
 * Properties:
 * - #icon: The icon name to render.
 * - #attributes: (array, optional) HTML attributes to apply to the icon. The
 *   attributes are escaped, see \Drupal\Core\Template\Attribute.
 * - #color: (string, optional) A css compatible color specification for the
 *   background color.
 * - #fill: (string, optional) A css compatible color specification for the SVG
 *   fill color.
 * - #size: (string, optional) A number string to be used for height and width.
 *
 * Usage example:
 * @code
 * $build['block'] = [
 *   '#type' => 'user_dashboard_summary',
 *   '#buttons' => [],
 *   '#title' => 'Title',
 *   '#content' => '',
 * ];
 * @endcode
 *
 * @RenderElement("user_dashboard_summary")
 */
class SummaryBlock extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#pre_render' => [
        [$class, 'preRenderSummaryBlock'],
      ],
      '#theme' => 'user_dashboard_summary',
      '#attributes' => [],
      '#buttons' => [],
    ];
  }

  /**
   * Pre-render callback: Renders an icon SVG with attributes.
   *
   * @param array $element
   *   An associative array containing:
   *   - #icon: The name of the open-iconic icon.
   *   - #attributes: (optional) An array of HTML attributes to apply to the
   *     tag. The attributes are escaped, see \Drupal\Core\Template\Attribute.
   *   - #color: (optional) A css compatible color specification for the
   *   background color.
   *   - #fill: (optional) A css compatible color specification for the SVG
   *   fill color.
   *   - #size: (optional) The px size to use for height and width of icon.
   *
   * @return array
   *   A renderable array for a role icon.
   */
  public static function preRenderSummaryBlock(array $element) {
    foreach ($element['#buttons'] as &$button) {
      $button['link'] = Url::fromRoute(
        $button['route_name'],
        $button['route_parameters']
      )->toString();
    }

    return $element;
  }

}

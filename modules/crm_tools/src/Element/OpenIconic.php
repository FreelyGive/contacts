<?php

namespace Drupal\crm_tools\Element;

use Drupal\Component\Utility\Html as HtmlUtility;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Render\Markup;
use Drupal\Core\Template\Attribute;

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
 * $build['role'] = [
 *   '#type' => 'open_iconic',
 *   '#icon' => 'person',
 * ];
 * @endcode
 *
 * @RenderElement("open_iconic")
 */
class OpenIconic extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#pre_render' => [
        [$class, 'preRenderOpenIconic'],
      ],
      '#attributes' => [],
      '#icon' => 'person',
      '#color' => '#000000',
      '#fill' => '#ffffff',
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
  public static function preRenderOpenIconic(array $element) {
    $element['#attributes']['viewBox'] = '0 0 8 8';
    $element['#attributes']['class'][] = 'role-icon';
    $element['#attributes']['style'][] = 'background-color:' . HtmlUtility::escape($element['#color']) . ';';
    $element['#attached']['library'][] = 'crm_tools/open-iconic';

    if (!empty($element['#size'])) {
      if (is_numeric($element['#size'])) {
        $element['#size'] .= 'px';
      }
      $element['#attributes']['style'][] = 'height:' . HtmlUtility::escape($element['#size']) . ';';
      $element['#attributes']['style'][] = 'width:' . HtmlUtility::escape($element['#size']) . ';';
    }

    if (!empty($element['#fill'])) {
      $element['#attributes']['style'][] = 'fill:' . HtmlUtility::escape($element['#fill']) . ';';
    }

    $attributes = new Attribute($element['#attributes']);
    $icon = HtmlUtility::escape($element['#icon']);
    $element['icon'] = [
      '#type' => 'html_tag',
      '#tag' => 'use',
      '#value' => '',
      '#prefix' => '<svg' . $attributes . '>',
      '#suffix' => '</svg>',
      '#attributes' => [
        'xlink:href' => base_path() . drupal_get_path('module', 'crm_tools') . '/includes/open-iconic.svg#' . $icon,
        'class' => ["icon-$icon"],
      ],
    ];

    return $element;
  }

}

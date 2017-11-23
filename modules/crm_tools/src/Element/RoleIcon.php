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
 * - #color: (string, optional) A string for the background hex color.
 * - #size: (string, optional) A number string to be used for height and width.
 *
 * Usage example:
 * @code
 * $build['role'] = [
 *   '#type' => 'role_icon',
 *   '#icon' => 'person',
 * ];
 * @endcode
 *
 * @RenderElement("role_icon")
 */
class RoleIcon extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#pre_render' => [
        [$class, 'preRenderRoleIcon'],
      ],
      '#attributes' => [],
      '#icon' => 'person',
      '#color' => '#000000',
    ];
  }

  /**
   * Pre-render callback: Renders an icon svg with attributes.
   *
   * @param array $element
   *   An associative array containing:
   *   - #icon: The name of the open-iconic icon.
   *   - #attributes: (optional) An array of HTML attributes to apply to the
   *     tag. The attributes are escaped, see \Drupal\Core\Template\Attribute.
   *   - #color: (optional) The hex code color (starting with '#').
   *   - #size: (optional) The px size to use for height and width of icon.
   *
   * @return array
   *   A renderable array for a role icon.
   */
  public static function preRenderRoleIcon(array $element) {
    $element['#attributes']['viewBox'] = '0 0 8 8';
    $element['#attributes']['class'][] = 'role-icon';
    $element['#attributes']['style'][] = 'background-color:' . HtmlUtility::escape($element['#color']) . ';';
    $element['#attached']['library'][] = 'crm_tools/open-iconic';

    if (!empty($element['#size'])) {
      $element['#attributes']['style'][] = 'height:' . HtmlUtility::escape($element['#size']) . 'px;';
      $element['#attributes']['style'][] = 'width:' . HtmlUtility::escape($element['#size']) . 'px;';

    }

    $attributes = new Attribute($element['#attributes']);
    $open_tag = '<svg' . $attributes . '>';
    $prefix = isset($element['#prefix']) ? $element['#prefix'] . $open_tag : $open_tag;
    $close_tag = "</svg>\n";
    $suffix = isset($element['#suffix']) ? $close_tag . $element['#suffix'] : $close_tag;

    $icon = HtmlUtility::escape($element['#icon']);
    $markup = [
      '#type' => 'html_tag',
      '#tag' => 'use',
      '#value' => '',
      '#attributes' => [
        'xlink:href' => base_path() . drupal_get_path('module', 'crm_tools') . '/includes/open-iconic.svg#' . $icon,
        'class' => ["icon-$icon"],
      ],
    ];

    $renderer = \Drupal::service('renderer');
    $element['#markup'] = Markup::create($renderer->render($markup));
    $element['#prefix'] = Markup::create($prefix);
    $element['#suffix'] = Markup::create($suffix);
    return $element;
  }

}

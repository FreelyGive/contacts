<?php

namespace Drupal\crm_tools\Element;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Html as HtmlUtility;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Render\Markup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Template\Attribute;

/**
 * Provides a render element for any HTML tag, with properties and value.
 *
 * Properties:
 * - #tag: The tag name to output.
 * - #attributes: (array, optional) HTML attributes to apply to the tag. The
 *   attributes are escaped, see \Drupal\Core\Template\Attribute.
 * - #value: (string, optional) A string containing the textual contents of
 *   the tag.
 * - #noscript: (bool, optional) When set to TRUE, the markup
 *   (including any prefix or suffix) will be wrapped in a <noscript> element.
 *
 * Usage example:
 * @code
 * $build['hello'] = [
 *   '#type' => 'html_tag',
 *   '#tag' => 'p',
 *   '#value' => $this->t('Hello World'),
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
   * Pre-render callback: Renders a generic HTML tag with attributes.
   *
   * @param array $element
   *   An associative array containing:
   *   - #tag: The tag name to output. Typical tags added to the HTML HEAD:
   *     - meta: To provide meta information, such as a page refresh.
   *     - link: To refer to stylesheets and other contextual information.
   *     - script: To load JavaScript.
   *     The value of #tag is escaped.
   *   - #attributes: (optional) An array of HTML attributes to apply to the
   *     tag. The attributes are escaped, see \Drupal\Core\Template\Attribute.
   *   - #value: (optional) A string containing tag content, such as inline
   *     CSS. The value of #value will be XSS admin filtered if it is not safe.
   *   - #noscript: (optional) If TRUE, the markup (including any prefix or
   *     suffix) will be wrapped in a <noscript> element. (Note that passing
   *     any non-empty value here will add the <noscript> tag.)
   *
   * @return array
   */
  public static function preRenderRoleIcon($element) {
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
      '#value' => '&nbsp;',
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

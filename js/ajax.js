/**
 * @file
 * Contacts' enhancements to core's AJAX system.
 *
 * @todo: Remove if https://www.drupal.org/node/2834834 goes in.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Override core's .use-ajax implementation.
   *
   * Implements an alternative ajax URL and progress types via data attributes.
   *
   * @param {object} element
   *   The context for the behavior.
   */
  Drupal.ajax.bindAjaxLinks = function (element) {
    $(element).find('.use-ajax').once('ajax').each(function (i, ajaxLink) {
      var $linkElement = $(ajaxLink);

      var elementSettings = {
        progress: { type: 'throbber' },
        dialogType: $linkElement.data('dialog-type'),
        dialog: $linkElement.data('dialog-options'),
        dialogRenderer: $linkElement.data('dialog-renderer'),
        base: $linkElement.attr('id'),
        element: ajaxLink
      };

      var href = $linkElement.attr('data-ajax-url') || $linkElement.attr('href');

      console.log(href);

      if (href) {
        elementSettings.url = href;
        elementSettings.event = 'click';
      }
      Drupal.ajax(elementSettings);
    });
  };

})(jQuery, Drupal);

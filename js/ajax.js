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
   * Bind Ajax functionality to links that use the 'use-ajax' class.
   *
   * @param {HTMLElement} element
   *   Element to enable Ajax functionality for.
   */
  Drupal.ajax.bindAjaxLinks = function (element) {
    $(element).find('.use-ajax').once('ajax').each(function (i, ajaxLink) {
      var $linkElement = $(ajaxLink);

      var elementSettings = {
        progress: { type: $(this).attr('data-ajax-progress') || 'throbber' },
        dialogType: $linkElement.data('dialog-type'),
        dialog: $linkElement.data('dialog-options'),
        dialogRenderer: $linkElement.data('dialog-renderer'),
        base: $linkElement.attr('id'),
        element: ajaxLink
      };

      var href = $linkElement.attr('data-ajax-url') || $linkElement.attr('href');

      if (href) {
        elementSettings.url = href;
        elementSettings.event = 'click';
      }
      Drupal.ajax(elementSettings);
    });
  };

})(jQuery, Drupal);

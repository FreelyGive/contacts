/**
 * @file
 * Contacts' enhancements to core's AJAX system.
 *
 * @todo: Remove if https://www.drupal.org/node/2834834 goes in.
 */

(function ($, Drupal) {

  'use strict';

    // Get our original AJAX behavior so we can still call it.
  var originalAjaxAttach = Drupal.behaviors.AJAX.attach;

  /**
   * Override core's .use-ajax implementation.
   *
   * Implements an alternative ajax URL and progress types via data attributes.
   *
   * @param {object} context
   *   The context for the behavior.
   * @param {object} settings
   *   The Drupal settings object.
   */
  Drupal.behaviors.AJAX.attach = function (context, settings) {
    // Bind Ajax behaviors to all items showing the class.
    $('.use-ajax').once('ajax').each(function () {
      var element_settings = {};

      // Clicked links look better with the throbber than the progress bar, but
      element_settings.progress = {
        type: $(this).attr('data-ajax-progress') || 'throbber'
      };

      // For anchor tags, these will go to the target of the anchor rather
      // than the usual location.
      var href = $(this).attr('data-ajax-url') || $(this).attr('href');
      if (href) {
        element_settings.url = href;
        element_settings.event = 'click';
      }
      element_settings.dialogType = $(this).data('dialog-type');
      element_settings.dialog = $(this).data('dialog-options');
      element_settings.base = $(this).attr('id');
      element_settings.element = this;
      Drupal.ajax(element_settings);
    });

    // Call the original attach function.
    originalAjaxAttach(context, settings);
  };

})(jQuery, Drupal);

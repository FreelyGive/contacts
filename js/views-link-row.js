/**
 * @file
 * Make views rows linkable in their entirety.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Make views rows linkable in their entirety.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Set up the click event on views rows.
   */
  Drupal.behaviors.contactsLinkRow = {
    attach: function (context, settings) {
      $('.view-content [data-row-link]', context).on('click', function () {
        // Do not trigger if we have a selection.
        if (!getSelection().toString()) {
          window.location = $(this).attr('data-row-link');
        }
      });
    }
  };

})(jQuery, Drupal);

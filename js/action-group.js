/**
 * @file
 * Contacts' enhancements to actions to allow dropdown grouping.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Control dropdown action groups.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Set our tabs up to use AJAX requests.
   */
  Drupal.behaviors.contactsActionGroups = {
    attach: function (context, settings) {
      $('.action-group-control', context).on('click', function () {
        var $this = $(this);
        $('#' + $this.attr('data-action-group')).toggle();
      });
    }
  };

})(jQuery, Drupal);

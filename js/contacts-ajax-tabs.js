/**
 * @file
 * Contact Dashboard ajax navigation.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Reload contacts dashboard content using ajax.
   *
   * @type {Drupal~Ajax}
   */  Drupal.AjaxCommands.prototype.renderAjaxTabBlock = function(ajax, response, status){
    $('.contacts-ajax-tabs .is-active').removeClass('is-active');

    $('#contacts-tabs-content').html(response.content);
    history.pushState({}, '', response.url);

    $('.contacts-ajax-tabs .' + response.active).addClass('is-active');
    $('.contacts-ajax-tabs .' + response.active + ' a').addClass('is-active');

    // Reload page on back action.
    $(window).on("popstate", function () {
      location.reload();
    });
  };

})(jQuery, Drupal);

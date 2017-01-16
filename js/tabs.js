/**
 * @file
 * Contact Dashboard ajax navigation.
 */

(function ($, Drupal) {

  'use strict';

  var historySupport = !!(window.history && history.pushState);

  /**
   * Fill out the tab with the response contents.
   *
   * @param {Drupal.Ajax} ajax
   *   {@link Drupal.Ajax} object created by {@link Drupal.ajax}.
   * @param {object} response
   *   JSON response from the Ajax request.
   * @param {number} [status]
   *   XMLHttpRequest status.
   */
  Drupal.AjaxCommands.prototype.contactsTab = function (ajax, response, status) {
    if (response.activeTab) {
      $('.contacts-ajax-tabs .is-active').removeClass('is-active');
      $('.contacts-ajax-tabs .' + response.activeTab).find('a').andSelf().addClass('is-active');
    }

    if (response.data) {
      $('#contacts-tabs-content').html(response.data);
    }

    if (response.url && historySupport) {
      if (document.location.pathname != response.url) {
        history.pushState({}, '', response.url);
      }
    }
  };

  // Try and set the browser back/forward buttons up to trigger AJAX requests.
  if (historySupport) {
    $(window).on('popstate', function (e) {
      // Look for an ajax link with this url.
      $('.contacts-ajax-tabs a.use-ajax[href="' + document.location.pathname + '"]').trigger('click');
    });
  }

  // @todo: Remove everything after here if https://www.drupal.org/node/2834834
  // goes in.
  // Get our original AJAX behavior so we can still call it.
  var originalAjaxAttach = Drupal.behaviors.AJAX.attach;

  /**
   * Use AJAX requests for our tabs and update our URL history.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Set our tabs up to use AJAX requests.
   */
  Drupal.behaviors.AJAX.attach = function (context, settings) {
    // Bind Ajax behaviors to all items showing the class.
    $('.use-ajax').once('ajax').each(function () {
      var element_settings = {};
      // Clicked links look better with the throbber than the progress bar.
      element_settings.progress = {type: 'throbber'};

      // For anchor tags, these will go to the target of the anchor rather
      // than the usual location.
      var href = $(this).attr('data-ajax-url') || $(this).attr('href');
      if (href) {
        element_settings.url = href;
        element_settings.event = 'click';
      }
      element_settings.base = $(this).attr('id');
      element_settings.element = this;
      Drupal.ajax(element_settings);
    });

    originalAjaxAttach(context, settings);
  };

})(jQuery, Drupal);

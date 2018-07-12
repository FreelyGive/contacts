/**
 * @file
 * Contact Dashboard ajax navigation.
 */

(function ($, Drupal, settings) {

  'use strict';

  var historySupport = !!(window.history && history.pushState);

  var activeClass = settings.contacts.tabs.activeClass || 'is-active';

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
      $('.contacts-ajax-tabs .' + activeClass).removeClass(activeClass);
      $('.contacts-ajax-tabs').find('[data-contacts-tab-id="'+response.activeTab+'"]').addBack().addClass(activeClass);
    }

    if (response.url && historySupport) {
      var current_url = document.location.pathname + document.location.search;
      if (current_url !== response.url) {
        history.pushState({}, '', response.url);
      }
    }
  };

  // If supported, set the browser back/forward buttons up to trigger AJAX
  // requests that update the tabs.
  if (historySupport) {
    $(window).on('popstate', function (e) {
      // Look for an ajax link with this url.
      $('.contacts-ajax-tabs a.use-ajax[href="' + document.location.pathname + '"]').trigger('click');
    });
  }

})(jQuery, Drupal, drupalSettings);

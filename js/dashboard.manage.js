(function ($, Drupal, drupalSettings, _, JSON, storage) {
  // var cachedPermissionsHash = storage.getItem('Drupal.contactsManage.permissionsHash');
  // var permissionsHash = drupalSettings.user.permissionsHash;
  // if (cachedPermissionsHash !== permissionsHash) {
  //   if (typeof permissionsHash === 'string') {
  //     _.chain(storage).keys().each(function (key) {
  //       if (key.substring(0, 22) === 'Drupal.contactsManage.') {
  //         storage.removeItem(key);
  //       }
  //     });
  //   }
  //   storage.setItem('Drupal.contactsManage.permissionsHash', permissionsHash);
  // }

  function initDashboardManagge($blocks, html) {
    var $region = $blocks.closest('.manage-region');

    $blocks.html(html).addClass('manage-wrapper').prepend(Drupal.theme('manageTrigger'));

    var destination = 'destination=' + Drupal.encodePath(drupalSettings.path.currentPath);
    // @todo swap contextual-links for manage link.
    $blocks.find('.contextual-links a').each(function () {
      var url = this.getAttribute('href');
      var glue = url.indexOf('?') === -1 ? '?' : '&';
      this.setAttribute('href', url + glue + destination);
    });

    $(document).trigger('drupalManageLinkAdded', {
      $el: $blocks,
      $region: $region
    });
  }

  // @todo refactor contactsDashboardManage...
  Drupal.behaviors.contactsDashboardManage = {
    attach: function attach(context) {
      var $context = $(context);

      var $placeholders = $context.find('[data-contextual-id]').once('contextual-render');
      if ($placeholders.length === 0) {
        return;
      }

      var ids = [];
      $placeholders.each(function () {
        ids.push($(this).attr('data-contextual-id'));
      });

      var uncachedIDs = _.filter(ids, function (contextualID) {
        var html = storage.getItem('Drupal.contactsManage.' + contextualID);
        if (html && html.length) {
          window.setTimeout(function () {
            initDashboardManagge($context.find('[data-contextual-id="' + contextualID + '"]'), html);
          });
          return false;
        }
        return true;
      });

      if (uncachedIDs.length > 0) {
        $.ajax({
          url: Drupal.url('contextual/render'),
          type: 'POST',
          data: { 'ids[]': uncachedIDs },
          dataType: 'json',
          success: function success(results) {
            _.each(results, function (html, contextualID) {
              storage.setItem('Drupal.contactsManage.' + contextualID, html);

              if (html.length > 0) {
                $placeholders = $context.find('[data-contextual-id="' + contextualID + '"]');

                for (var i = 0; i < $placeholders.length; i++) {
                  initDashboardManagge($placeholders.eq(i), html);
                }
              }
            });
          }
        });
      }
    }
  };

  Drupal.contactsManage = {};

  Drupal.theme.manageTrigger = function () {
    return '<button class="trigger visually-hidden focusable" type="button"></button>';
  };

  $(document).on('drupalManageLinkAdded', function (event, data) {
    Drupal.ajax.bindAjaxLinks(data.$el[0]);
  });
})(jQuery, Drupal, drupalSettings, _, window.JSON, window.sessionStorage);
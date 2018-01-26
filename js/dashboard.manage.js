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

  function initDashboardManage($block) {
    var destination = 'destination=' + Drupal.encodePath(drupalSettings.path.currentPath);
    var user = $block.attr('data-dnd-contacts-block-user'),
        tab = $block.attr('data-dnd-contacts-block-tab'),
        name = $block.attr('data-dnd-contacts-block-name'),
        url = '/admin/contacts/ajax/manage-off-canvas/'+user+'/'+tab+'/'+name+'?'+destination;
    $block.addClass('manage-wrapper').prepend(Drupal.theme('manageTrigger', url));

    $(document).trigger('drupalManageLinkAdded', {
      $el: $block
    });
  }

  Drupal.behaviors.contactsDashboardManage = {
    attach: function attach(context) {
      var $context = $(context);

      var $placeholders = $context.find('[data-dnd-contacts-block-name]').once('contextual-render');
      if ($placeholders.length === 0) {
        return;
      }

      var ids = [];
      $placeholders.each(function () {
        ids.push($(this).attr('data-dnd-contacts-block-name'));
      });

      _.each(ids, function (id) {
        $placeholders = $context.find('[data-dnd-contacts-block-name="' + id + '"]');

        for (var i = 0; i < $placeholders.length; i++) {
          initDashboardManage($placeholders.eq(i));
        }
      });
    }
  };

  Drupal.behaviors.contactsDashboardManageToolbar = {
    attach: function attach(context) {
      var $context = $(context);

      var $placeholders = $context.find('.toolbar-dashboard-manage').once('toolbar-render');
      if ($placeholders.length === 0) {
        return;
      }

      $placeholders.each(function () {
        $(this).attr('data-ajax-url', '/admin/contacts/ajax/manage-mode/3/summary')

        $(document).trigger('drupalManageTabAdded', {
          $el: $(this)
        });
      });
    }
  };

  Drupal.contactsManage = {};

  Drupal.theme.manageTrigger = function (url) {
    return '<button data-ajax-url="'+url+'" data-dialog-type="dialog" data-dialog-renderer="off_canvas" class="use-ajax trigger" type="button"></button>';
  };

  $(document).on('drupalManageLinkAdded', function (event, data) {
    console.log(data.$el);
    // Drupal.ajax.bindAjaxLinks(data.$el[0]);
  });
  $(document).on('drupalManageTabAdded', function (event, data) {
    console.log(data.$el);
    // Drupal.ajax.bindAjaxLinks(data.$el[0]);
  });
})(jQuery, Drupal, drupalSettings, _, window.JSON, window.sessionStorage);
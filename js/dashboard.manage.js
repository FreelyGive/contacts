(function ($, Drupal) {

  Drupal.behaviors.contactsDashboardManageToolbar = {
    attach: function attach(context) {
      var $context = $(context);

      var $placeholders = $context.find('.toolbar-dashboard-manage').once('toolbar-render');
      if ($placeholders.length === 0) {
        return;
      }

      $placeholders.each(function () {
        $(this).attr('data-ajax-url', '/admin/contacts/ajax/manage-mode');
        $(this).addClass('use-ajax');

        $(document).trigger('drupalManageTabAdded', {
          $el: $(this)
        });
      });
    }
  };

  $(document).on('drupalManageTabAdded', function (event, data) {
    Drupal.ajax.bindAjaxLinks(data.$el[0]);
  });

})(jQuery, Drupal);

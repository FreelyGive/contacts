/**
 * @file
 * CRM Tools js behaviour for open iconic svgs.
 */

(function ($, Drupal) {

    'use strict';

    /**
     * Control icon preview for open ionic icons.
     *
     * @type {Drupal~behavior}
     *
     * @prop {Drupal~behaviorAttach} attach
     *   Set up icon watchers.
     */
    Drupal.behaviors.crmToolsAdminRoles = {
        attach: function (context, settings) {
            console.log('admin form');
            $('.crm_tools_roles input[data-crm-tools-parent]', context).change(function() {
                var parent_id = $(this, context).attr("data-crm-tools-parent");
                if (parent_id && this.checked) {
                    var parent = $('.crm_tools_roles input[value='+ parent_id +']', context);
                    parent.prop("checked", true);
                    parent.trigger("change");
                }
            });
        }
    };

})(jQuery, Drupal);

/**
 * @file
 * CRM Tools js behaviour for admin forms.
 */

(function ($, Drupal) {

    'use strict';

    /**
     * Update hierarchical role parents and children.
     *
     * @type {Drupal~behavior}
     *
     * @prop {Drupal~behaviorAttach} attach
     *   Set up role watchers.
     */
    Drupal.behaviors.crmToolsUserFormsRoles = {
        attach: function (context, settings) {
            // Track role changes.
            $('.crm-tools-roles input', context).change(function() {
                // Add in parents.
                var parent_id = $(this, context).attr("data-crm-tools-parent");
                if (parent_id && this.checked) {
                    var parent = $('.crm-tools-roles input[value='+ parent_id +']', context);
                    parent.prop("checked", true);
                    parent.trigger("change");
                }

                // Remove children.
                var child_key = $(this, context).attr("data-crm-tools-children");
                if (child_key && !this.checked) {
                    var children = child_key.split(":");
                    for (var i = 0; i < children.length; i++) {
                        var child = $('.crm-tools-roles input[value="'+ children[i] +'"]', context);
                        child.prop("checked", false);
                        child.trigger("change");
                    }
                }
            });
        }
    };

})(jQuery, Drupal);

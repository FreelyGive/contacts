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
    Drupal.behaviors.crmToolsOpenIconicPreview = {
        attach: function (context, settings) {
            this.updateIcon = function(text, color) {
                text = text || $('input[name="crm_tools_icon"]', context).val();
                text = text || "person";
                color = color || $('input[name="color"]', context).val();
                color = color || "#000000";
                $('.icon-preview', context).html('<svg viewBox="0 0 8 8" class="icon" style="background-color:'+color+'"><use xlink:href="/contacts_proto/web/modules/contrib/contacts/modules/crm_tools/includes/open-iconic.svg#'+text+'" class="icon-'+text+'"></use></svg>');
            };

            // Initialise icon on page load.
            this.updateIcon();

            // Watch icon change.
            $('input[name="crm_tools_icon"]', context).bind('input propertychange', function() {
                var text = $(this, context).val();
                Drupal.behaviors.crmToolsOpenIconicPreview.updateIcon(text);
            });

            // Watch color change.
            $('input[name="color"]', context).change(function() {
                var color = $(this, context).val();
                Drupal.behaviors.crmToolsOpenIconicPreview.updateIcon(undefined, color);
            });
        }
    };

})(jQuery, Drupal);

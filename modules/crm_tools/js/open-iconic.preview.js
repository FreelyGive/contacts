/**
 * @file
 * CRM Tools js behaviour for previewing open iconic svgs.
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
            // Make sure we have the relevant settings to proceed.
            if (typeof settings.crm_tools !== 'undefined') {
                var spritePath = settings.crm_tools.open_iconic.settings.sprite_path;
                this.updateIcon = function(text, color) {
                    text = text || $('input[name="crm_tools_icon"]', context).val();
                    text = text || "person";
                    color = color || $('input[name="crm_tools_color"]', context).val();
                    color = color || "#000000";
                    var icon = $('.icon-preview svg.role-icon', context);
                    icon.css('background-color', color);
                    icon.children('use').attr('xlink:href', spritePath + '#' + text);
                    icon.children('use').attr('class', 'icon-' + text);
                };

                // Watch icon change.
                $('input[name="crm_tools_icon"]', context).bind('input propertychange', function() {
                    var text = $(this, context).val();
                    Drupal.behaviors.crmToolsOpenIconicPreview.updateIcon(text);
                });

                // Watch color change.
                $('input[name="crm_tools_color"]', context).change(function() {
                    var color = $(this, context).val();
                    Drupal.behaviors.crmToolsOpenIconicPreview.updateIcon(undefined, color);
                });
            }
        }
    };

})(jQuery, Drupal);

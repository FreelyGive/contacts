/**
 * @file
 * CRM Tools js behaviour for open iconic svgs.
 */

(function ($, Drupal) {

    'use strict';

    /**
     * Control dropdown action groups.
     *
     * @type {Drupal~behavior}
     *
     * @prop {Drupal~behaviorAttach} attach
     *   Set our tabs up to use AJAX requests.
     */
    Drupal.behaviors.crmToolsOpenIconic = {
        attach: function (context, settings) {
            var text = $('input[name="crm_tools_icon"]', context).val();
            var color = $('input[name="color"]', context).val();
            $('.icon-preview', context).html('<svg viewBox="0 0 8 8" class="icon" style="background-color:'+color+'"><use xlink:href="/contacts_proto/web/modules/contrib/contacts/modules/crm_tools/includes/open-iconic.svg#'+text+'" class="icon-'+text+'"></use></svg>');

            $('input[name="crm_tools_icon"]', context).bind('input propertychange', function() {
                var text = $(this, context).val();
                var color = $('input[name="color"]', context).val();
                $('.icon-preview', context).html('<svg viewBox="0 0 8 8" class="icon" style="background-color:'+color+'"><use xlink:href="/contacts_proto/web/modules/contrib/contacts/modules/crm_tools/includes/open-iconic.svg#'+text+'" class="icon-'+text+'"></use></svg>');
            });

            $('input[name="color"]', context).change(function() {
                var color = $(this, context).val();
                console.log(color);
                var text = $('input[name="crm_tools_icon"]', context).val();
                $('.icon-preview', context).html('<svg viewBox="0 0 8 8" class="icon" style="background-color:'+color+'"><use xlink:href="/contacts_proto/web/modules/contrib/contacts/modules/crm_tools/includes/open-iconic.svg#'+text+'" class="icon-'+text+'"></use></svg>');
            });
        }
    };

})(jQuery, Drupal);

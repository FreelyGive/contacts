(function ($, Drupal) {

  Drupal.behaviors.openIconicSpriteAjaxLoad = {
    attach: function attach(context) {
      $.get("/modules/contrib/contacts/modules/crm_tools/includes/open-iconic.svg", function(data) {
        var div = document.createElement("div");
        div.innerHTML = new XMLSerializer().serializeToString(data.documentElement);
        document.body.insertBefore(div, document.body.childNodes[0]);
      });
    }
  };

})(jQuery, Drupal);

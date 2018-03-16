(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.openIconicSpriteAjaxLoad = {
    attach: function attach(context) {
      if (typeof drupalSettings.contacts.openIconic.path !== 'undefined') {
        var svg = drupalSettings.contacts.openIconic.path;
        $.get(svg, function(data) {
          var div = document.createElement("div");
          div.innerHTML = new XMLSerializer().serializeToString(data.documentElement);
          document.body.insertBefore(div, document.body.childNodes[0]);
        });
      }
    }
  };

})(jQuery, Drupal, drupalSettings);

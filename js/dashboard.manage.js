(function ($, Drupal, drupalSettings, _) {

  /**
   * Update ajax url for Manage Dashboard links.
   *
   * @param $block
   *   Dashboard block to update the manage link for.
   */
  function initDashboardManage($block) {
    var destination = 'destination=' + Drupal.encodePath(drupalSettings.path.currentPath);
    var tab = $block.data('contacts-manage-block-tab'),
      name = $block.data('contacts-manage-block-name'),
      url = [['/admin/contacts/ajax/manage-off-canvas', tab, name].join('/'), destination].join('?');

    // @todo Build link url as part of rendering but keep it hidden.
    $block.addClass('manage-wrapper');
    var link = $block.find('.manage-trigger');
    if (link.length !== 0) {
      link.find('a').attr('data-ajax-url', url);
    }

    $(document).trigger('drupalManageLinkAdded', {
      $el: $block
    });
  }

  /**
   * Scans a particular region for blocks and builds structured data.
   *
   * @param tab
   *   ID of the current Dashboard tab.
   * @param region
   *   ID of the region to build data for.
   * @param ids
   *   The ordered list of block ids.
   *
   * @returns {{tab: string, region: string, blocks: Array}}
   *   Structured data of blocks in region.
   */
  function buildDashboardRegionData(tab, region, ids) {
    var data = {
      'tab': tab,
      'region': region,
      'blocks': []
    };

    for (var weight = 0; weight < ids.length; weight++) {
      var el = $('[data-contacts-manage-block-name=' + ids[weight] + ']');

      // @todo check that profile type and relationship are available.
      var block_data = {
        name: ids[weight],
        id: el.data('contacts-manage-block-id')
      };
      data.blocks.push(block_data);
    }

    return data;
  }

  /**
   * Update the Dashboard tab with changes made to block contents.
   *
   * @param tab
   *   ID of tab to be updated.
   * @param context
   *   The context of the tab.
   */
  function updateDashboardDrag(tab, context) {
    var $dragAreas = $(context).find('.drag-area');

    if ($dragAreas.length === 0) {
      return;
    }

    var regions = [];
    $dragAreas.each(function () {
      var sortedIDs = $(this).sortable("toArray", {attribute: 'data-contacts-manage-block-name'});
      if (sortedIDs.length !== 0) {
        var region = $(this, context).data('contacts-manage-region-id');
        var data = buildDashboardRegionData(tab, region, sortedIDs);
        regions.push(data);
      }
    });

    var url = $(context).find('[data-contacts-manage-update-url]').data('contacts-manage-update-url');
    var postData = {
      regions: regions,
      tab: tab
    };

    $.ajax({
      type: 'POST',
      url: url,
      data: postData
    }).done(function (data) {
      console.log(data);
    });
  }

  function updateDashboardTabs(context) {
    var $dragAreas = $(context).find('.contacts-ajax-tabs');

    if ($dragAreas.length === 0) {
      return;
    }

    var tabs = $dragAreas.sortable("toArray", {attribute: 'data-contacts-drag-tab-id'});
    var url = '/admin/contacts/ajax/update-tabs',
      postData = {
        tabs: tabs
      };

    $.ajax({
      type: 'POST',
      url: url,
      data: postData
    }).done(function (data) {
      console.log(data);
    });
  }

  /**
   * Update the Dashboard tabs after re-ordering.
   *
   * @param context
   *   The context of the tabs.
   */
  function updateDashboardTabs(context) {
    var $dragArea = $(context).find('.contacts-ajax-tabs');

    if ($dragArea.length === 0) {
      return;
    }

    var tabs = $dragArea.sortable("toArray", {attribute: 'data-contacts-drag-tab-id'});

    console.log(tabs);

    var url = '/admin/contacts/ajax/update-tabs';
    var postData = {
      tabs: tabs
    };

    $.ajax({
      type: 'POST',
      url: url,
      data: postData
    }).done(function (data) {
      console.log(data);
    });
  }

  /**
   * Find all dashboard blocks and set manage ajax links.
   */
  Drupal.behaviors.contactsDashboardManage = {
    attach: function attach(context) {
      var $context = $(context);

      var $placeholders = $context.find('[data-contacts-manage-block-name]').once('contextual-render');
      if ($placeholders.length === 0) {
        return;
      }

      var ids = [];
      $placeholders.each(function () {
        ids.push($(this).data('contacts-manage-block-name'));
      });

      _.each(ids, function (id) {
        $placeholders = $context.find('[data-contacts-manage-block-name="' + id + '"]');

        for (var i = 0; i < $placeholders.length; i++) {
          initDashboardManage($placeholders.eq(i));
        }
      });
    }
  };

  /**
   * Set toolbar manage mode ajax link.
   */
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
        $(this).attr('data-ajax-progress', 'fullscreen');

        $(document).trigger('drupalManageTabAdded', {
          $el: $(this)
        });
      });

//      if (!drupalSettings.contacts.manageMode) {
        var activeTab = $context.find('.nav-link.active');
        var tab = activeTab.closest('[data-contacts-drag-tab-id]').data('contacts-drag-tab-id');
        if (tab) {
          setTimeout(function () {
            // @todo Remove the click and the timout - solve properly.
            $(activeTab).click();
            $.ajax({
              url: Drupal.url('admin/contacts/ajax/update-offcanvas/'+tab)
            }).done(function(data) {console.log(data)});
          }, 100);
        }
//      }
    }
  };

  /**
   * Add sorting of dashboard blocks in manage mode.
   */
  Drupal.behaviors.contactsDashboardManageDragBlocks = {
    attach: function attach(context) {

      var $dragAreas = $(context).find('.drag-area');

      if ($dragAreas.length === 0) {
        return;
      }

      $dragAreas.each(function () {
        $(this).sortable({
          placeholder: "drag-area-placeholder",
          items: '.draggable',
          connectWith: '.drag-area',
          scrollSpeed: 10,
          update: function update(event, ui) {
            var itemRegion = ui.item.closest('.drag-area');
            if (event.target === itemRegion[0]) {

              var tab = ui.item.closest('[data-contacts-manage-block-tab]').data('contacts-manage-block-tab');
              updateDashboardDrag(tab, context);
            }
          }
        });
      });
    }
  };

  /**
   * Add sorting of dashboard tabs in manage mode.
   */
  Drupal.behaviors.contactsDashboardManageDragTabs = {
    attach: function attach(context) {

      var $dragAreas = $(context).find('.contacts-ajax-tabs');

      if ($dragAreas.length === 0) {
        return;
      }

      $dragAreas.each(function () {
        $(this).sortable({

          placeholder: "nav-item nav-link tab-area-placeholder",
          handle: '.drag-handle',
          update: function update(event, ui) {
            var itemRegion = ui.item.closest('.contacts-ajax-tabs');
            if (event.target === itemRegion[0]) {
              updateDashboardTabs(context);
            }
          }
        });
      });
    }
  };

  $(document).on('drupalManageLinkAdded', function (event, data) {
    Drupal.ajax.bindAjaxLinks(data.$el[0]);
  });

  $(document).on('drupalManageTabAdded', function (event, data) {
    Drupal.ajax.bindAjaxLinks(data.$el[0]);
  });

})(jQuery, Drupal, drupalSettings, _);

(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.opignoModuleActivitiesBank = {
    attach: function (context, settings) {
      var view = $('.view-opigno-activities-bank-lp-interface tbody');

      if (typeof $.cookie('activities_bank_checked') !== 'undefined') {
        var arrayLength = $.cookie('activities_bank_checked').length;
        if (arrayLength) {
          var checked_array = $.cookie('activities_bank_checked').split(/,/);
          for (var i = 0; i < arrayLength; i++) {
            var activity_input = view.find('input[value="' + checked_array[i] + '"]');
            activity_input.prop('checked', 'checked');
          }
        }
      }

      view.find('input[type="checkbox"]').click(function () {
        if (typeof $.cookie('activities_bank_checked') !== 'undefined') {
          var checkboxes_ids = $.cookie('activities_bank_checked').split(/,/);
        }
        else {
          checkboxes_ids = [];
        }

        if (typeof $.cookie('activities_bank_activities') !== 'undefined') {
          var activities_ids = $.cookie('activities_bank_activities').split(/,/);
        }
        else {
          activities_ids = [];
        }

        var href = $(this).parents('td').next().find('a').attr('href');
        var hrefArray = href.split('/');
        var activityID = hrefArray[hrefArray.length - 1];

        if ($(this).is(':checked')) {
          checkboxes_ids.push($(this).val());
          activities_ids.push(activityID);
        }
        else {
          checkboxes_ids.splice($.inArray($(this).val(), checkboxes_ids), 1);
          activities_ids.splice($.inArray(activityID, activities_ids), 1);
        }

        if (checkboxes_ids.length && activities_ids.length) {
          $.cookie('activities_bank_checked', checkboxes_ids);
          $.cookie('activities_bank_activities', activities_ids);
        }
      });
    }
  };
}(jQuery, Drupal, drupalSettings));

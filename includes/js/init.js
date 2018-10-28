jQuery.browser = {};
(function () {
  jQuery.browser.msie = false;
  jQuery.browser.version = 0;
  if (navigator.userAgent.match(/MSIE ([0-9]+)\./)) {
    jQuery.browser.msie = true;
    jQuery.browser.version = RegExp.$1;
  }
})();

function init() {
  // Delete multiply
  jQuery('.remove-multiply').on('click', function (e) {
    jQuery('#wpcontent').prepend('<div class="loader"></div>');
    e.preventDefault();
    var link = jQuery(this);
    jQuery.post(ajaxurl, 'action=auto_generator_remove_multiply&multiply=' + jQuery(this).attr('data-id')).done(function (res) {
      res = jQuery.parseJSON(res);
      if (res.message) {
        var mes = jQuery('#message');
        mes.find('p').text(res.message);
        mes.show();
        mes.find('button').off('click');
        mes.find('button').on('click', function () {
          mes.hide()
        });
        link.parents('tr').remove();
      }
      jQuery('.loader').remove();
    })
  });

  // Load multiply params
  jQuery('.load-multiply').on('click', function (e) {
    jQuery('#wpcontent').prepend('<div class="loader"></div>');
    e.preventDefault();
    var id = jQuery(this).attr('data-id');
    jQuery.post(ajaxurl, 'action=auto_generator_load_multiply&multiply=' + id).done(function (res) {
      res = jQuery.parseJSON(res);
      if (res.message) {
        var mes = jQuery('#message');
        mes.find('p').text(res.message);
        mes.show();
        mes.find('button').off('click');
        mes.find('button').on('click', function () {
          mes.hide()
        });
      }
      if (res.settings) {
        var settings = res.settings;
        jQuery('#price-from-1').val(settings.price_from_1);
        jQuery('#price-from-2').val(settings.price_from_2);
        jQuery('#price-to-1').val(settings.price_to_1);
        jQuery('#price-to-2').val(settings.price_to_2);
        jQuery('#price-step-1').val(settings.price_step_1);
        jQuery('#price-step-2').val(settings.price_step_2);
        jQuery('#date-from').val(settings.date_from);
        jQuery('#date-to').val(settings.date_to);

        jQuery('#keywords').val(settings.keywords);
        jQuery('#description').val(settings.description);
        jQuery('#text-before').val(settings.text_before);
        jQuery('#text-after').val(settings.text_after);
        jQuery('#template').val(settings.template);

        jQuery('#relink-text').val(settings.links);

        if (settings.images) {
          var view = jQuery('.auto-images'), form = jQuery('.upload_image'),
              html = '', html2 = '';
          view.html('');
          for (var i in settings.images) {
            html += '<img src="' + settings.images[i] + '" height="128"/>';
            html2 += '<input type="hidden" name="images[]" value="' + settings.images[i] + '">'
                + '<input type="hidden" name="ids[]" value="' + i + '">'
          }
          view.html(html);
          form.html(html2)
        }
      }
      jQuery('#multiply-params').find('input[name=multiply]').val(id);
      jQuery('.loader').remove();
    })
  });

  // Generate by multiply
  jQuery('.generate-multiply').on('click', function (e) {
    jQuery('#wpcontent').prepend('<div class="loader"></div>');
    e.preventDefault();
    var link = jQuery(this);
    jQuery.post(ajaxurl, 'action=auto_generator_generate_multiply&id=' + jQuery(this).attr('data-id')).done(function (res) {
      res = jQuery.parseJSON(res);
      if (res.message) {
        var mes = jQuery('#message');
        mes.find('p').text(res.message);
        mes.show();
        mes.find('button').off('click');
        mes.find('button').on('click', function () {
          mes.hide()
        });
      }
      jQuery('.loader').remove();
    })
  });

  // Generate by multiply only marks
  jQuery('.generate-multiply-marks').on('click', function (e) {
    jQuery('#wpcontent').prepend('<div class="loader"></div>');
    e.preventDefault();
    var link = jQuery(this);
    jQuery.post(ajaxurl, 'action=auto_generator_generate_multiply_marks&id=' + jQuery(this).attr('data-id')).done(function (res) {
      res = jQuery.parseJSON(res);
      if (res.message) {
        var mes = jQuery('#message');
        mes.find('p').text(res.message);
        mes.show();
        mes.find('button').off('click');
        mes.find('button').on('click', function () {
          mes.hide()
        });
      }
      jQuery('.loader').remove();
    })
  });

  // Generate by multiply marks-models
  jQuery('.generate-multiply-marks-models').on('click', function (e) {
    jQuery('#wpcontent').prepend('<div class="loader"></div>');
    e.preventDefault();
    var link = jQuery(this);
    jQuery.post(ajaxurl, 'action=auto_generator_generate_multiply_mark_models&id=' + jQuery(this).attr('data-id')).done(function (res) {
      res = jQuery.parseJSON(res);
      if (res.message) {
        var mes = jQuery('#message');
        mes.find('p').text(res.message);
        mes.show();
        mes.find('button').off('click');
        mes.find('button').on('click', function () {
          mes.hide()
        });
      }
      jQuery('.loader').remove();
    })
  });

  // Check all
  jQuery('input[name=multiply-all]').click(function(){
    jQuery('input[name="multiply-ids[]"]').prop('checked', jQuery(this).prop('checked'));
  });

  // Generate all
  jQuery('.generate-all').on('click', function (e) {
    jQuery('#wpcontent').prepend('<div class="loader"></div>');
    e.preventDefault();
    var id = [];
    jQuery('input[name="multiply-ids[]"]:checked').each(function(){
      id.push(jQuery(this).val());
    });
    jQuery.post(ajaxurl, 'action=auto_generator_generate_multiply&id=' + id).done(function (res) {
      res = jQuery.parseJSON(res);
      if (res.message) {
        var mes = jQuery('#message');
        mes.find('p').text(res.message);
        mes.show();
        mes.find('button').off('click');
        mes.find('button').on('click', function () {
          mes.hide()
        });
      }
      jQuery('.loader').remove();
    })
  });

  // Delete parts by multiply
  jQuery('.clear-multiply').on('click', function (e) {
    jQuery('#wpcontent').prepend('<div class="loader"></div>');
    e.preventDefault();
    jQuery.post(ajaxurl, 'action=auto_generator_clear_multiply&id=' + jQuery(this).attr('data-id')).done(function (res) {
      res = jQuery.parseJSON(res);
      if (res.message) {
        var mes = jQuery('#message');
        mes.find('p').text(res.message);
        mes.show();
        mes.find('button').off('click');
        mes.find('button').on('click', function () {
          mes.hide()
        });
      }
      jQuery('.loader').remove();
    })
  });

  // Generate csv mark & model
  jQuery('.csv-multiply').on('click', function (e) {
    jQuery('#wpcontent').prepend('<div class="loader"></div>');
    e.preventDefault();
    var link = jQuery(this);
    jQuery.post(ajaxurl, 'action=auto_generator_csv_multiply&id=' + jQuery(this).attr('data-id')).done(function (res) {
      res = jQuery.parseJSON(res);
      if (res.message) {
        var mes = jQuery('#message');
        mes.find('p').html(res.message);
        mes.show();
        mes.find('button').off('click');
        mes.find('button').on('click', function () {
          mes.hide()
        });
      }
      jQuery('.loader').remove();
    })
  });
}

jQuery(document).ready(function () {
  var table = jQuery('table');
  if (table.find('td').length) {
    table.tablesorter({
      widthFixed: true,
      sortReset: true,
      sortRestart: true,
      sortInitialOrder: 'desc'
    }).tablesorterPager({container: jQuery(".pager"), size: 100})
    table.show();
  }

  // Add new multiply
  jQuery('#add-multiply').on('submit', function (e) {
    jQuery('#wpcontent').prepend('<div class="loader"></div>');
    // http://tablesorter.com/docs/example-ajax.html
    e.preventDefault();
    jQuery.post(ajaxurl, 'action=auto_generator_add_multiply&' + jQuery(this).serialize()).done(function (res) {
      res = jQuery.parseJSON(res);
      if (res.message) {
        var mes = jQuery('#message');
        mes.find('p').text(res.message);
        mes.show();
        mes.find('button').off('click');
        mes.find('button').on('click', function () {
          mes.hide()
        });
        jQuery('table tbody').append(res.add);
        table.trigger("update");
        init();
      }
      jQuery('.loader').remove();
    })
  });

  // Save multiply params
  jQuery('#multiply-params').on('submit', function (e) {
    jQuery('#wpcontent').prepend('<div class="loader"></div>');
    e.preventDefault();
    jQuery.post(ajaxurl, 'action=auto_generator_save_multiply&' + jQuery(this).serialize()
        + '&' + jQuery('.upload_image').serialize()
        + '&' + jQuery('#add-relink').serialize()).done(function (res) {
      res = jQuery.parseJSON(res);
      if (res.message) {
        var mes = jQuery('#message');
        mes.find('p').text(res.message);
        mes.show();
        mes.find('button').off('click');
        mes.find('button').on('click', function () {
          mes.hide()
        });
      }
      window.scrollTo(0, 0);
      jQuery('.loader').remove();
    })
  });

  // Generate csv all
  jQuery('.csv-all').on('click', function (e) {
    jQuery('#wpcontent').prepend('<div class="loader"></div>');
    e.preventDefault();
    var link = jQuery(this);
    jQuery.post(ajaxurl, 'action=auto_generator_csv_multiply&id=0').done(function (res) {
      res = jQuery.parseJSON(res);
      if (res.message) {
        var mes = jQuery('#message');
        mes.find('p').html(res.message);
        mes.show();
        mes.find('button').off('click');
        mes.find('button').on('click', function () {
          mes.hide()
        });
      }
      jQuery('.loader').remove();
    })
  });

  init();
});

function upload_new_img(obj) {
  var file_frame;

  if (file_frame) {
    file_frame.open();
    return;
  }

  file_frame = wp.media.frames.file_frame = wp.media(
      {
        title: 'Выбрать файл',
        button: {
          text: jQuery(this).data('uploader_button_text')
        },
        multiple: true
      }
  );

  file_frame.on('select', function () {
    jQuery('.auto-images').find('img').remove();
    jQuery('.upload_image').find('input[type=hidden]').remove();
    jQuery('.upload-button').find('div').remove();
    var selection = file_frame.state().get('selection');
    selection.map(function (attachment) {
      attachment.toJSON();
      jQuery('.auto-images').append('<img src="' + attachment.attributes.url + '" height="128"/>');
      jQuery('.upload_image').append('<input type="hidden" name="images[]" value="' + attachment.attributes.url + '"/>');
      jQuery('.upload_image').append('<input type="hidden" name="ids[]" value="' + attachment.attributes.id + '"/>');
    });
    file_frame.close();
  });

  file_frame.open();
}
Drupal.behaviors.publications_page = {
  attach: function(context, settings) {
    jQuery(context).find('.paragraph--type--document .field--name-field-pages').once('processed').each(function() {
      if (jQuery(this).html() == 1) {
        jQuery(this).html(jQuery( this).text() + 'page');
      }else{
        jQuery(this).html(jQuery(this).text() + 'pages');
      }
    });
    jQuery(context).find('.paragraph--type--document').once('processed').each(function() {
      var href = jQuery(this).find('.file-link a').attr('href').replace('https://' + location.hostname, '');
      var ext = href.split('.');
      ext = ext[ext.length - 1].toUpperCase();
      jQuery(this).find('.file-link').replaceWith('<span class="file-ext">' + ext + '</span>,');
      var title = jQuery(this).find('.field--name-field-title').text();
      jQuery(this).find('.field--name-field-title').replaceWith('<div class="field--name-field-title"><a href="' + href +'" rel="nofollow">' + title + '</a></div>');
    });
  }
};
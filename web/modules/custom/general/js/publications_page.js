Drupal.behaviors.publications_page = {
  attach: function(context, settings) {
    jQuery(context).find('.paragraph--type--document .field--name-field-pages').once('processed').each(function() {
      if (jQuery(this).html() == 1) {
        jQuery(this).html('&nbsp;' +jQuery( this).text() + '&nbsp;page');
      }else{
        jQuery(this).html('&nbsp;' +jQuery(this).text() + '&nbsp;pages');
      }
    });
  }
};
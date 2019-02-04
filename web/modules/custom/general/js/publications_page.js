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
      jQuery(this).find('img').wrap('<a href="' + href +'" rel="nofollow" title="' + title + '" />');
      jQuery(this).find('.file-size').text(jQuery(this).find('.file-size').text() + ',');
      if (isIE () == 8) {
        jQuery(this).find('img').removeAttr('width');
        jQuery(this).find('img').removeAttr('height');
      }
    });
  }
};

function isIE () {
  var myNav = navigator.userAgent.toLowerCase();
  return (myNav.indexOf('msie') != -1) ? parseInt(myNav.split('msie')[1]) : false;
}
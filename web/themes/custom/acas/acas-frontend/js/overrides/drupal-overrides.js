Drupal.behaviors.acas = {
  attach: function(context, settings) {
    var ua = window.navigator.userAgent;
    var msie = ua.indexOf("MSIE ");
    if (msie > 0) {
      jQuery('body').addClass('ie' + parseInt(ua.substring(msie + 5, ua.indexOf(".", msie))));
    }
    document.body.style.display="block";
    jQuery('a').each(function() {
      if (jQuery(this).attr('href')) {
        if (jQuery(this).attr('href').indexOf('http') != -1 && !jQuery(this).hasClass('processed')) {
          jQuery(this).addClass('processed');
          jQuery(this).attr('target', '_blank');
          jQuery(this).append('<span class="visually-hidden">this link opens in a new window</span>');
        }
      }
    });
  }
};
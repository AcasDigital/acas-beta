(function ($, Drupal) {
  Drupal.behaviors.icheck = {
    attach: function (context, settings) {
      $('input').iCheck({
        checkboxClass: 'icheckbox_square-aero',
        radioClass: 'iradio_square-aero',
      });
      $('input').on('ifChanged', function (event) { $(event.target).trigger('change'); });
    }
  };
})(jQuery, Drupal);
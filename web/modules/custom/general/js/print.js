var win;

Drupal.behaviors.print = {
  attach: function(context, settings) {
    jQuery(".print-download-email .print").click(function() {
      win = window.open(this.href);
      jQuery(win.document).ready(function() {
        setTimeout(doPrint, 2000);
      });
      return false;
    });
    // Download page
    jQuery(".print-download-email .btn-save--download").click(function() {
      var d = new Date();
      jQuery(this).attr('href', jQuery(this).attr('href') + "?" + d.getTime());
    });
  }
};

function doPrint() {
  win.print();
  win.close();
}
var win;

Drupal.behaviors.print = {
  attach: function(context, settings) {
    jQuery(".print-download-email .print").click(function() {
      // Run in new thread. Attempt to keep main window active
      setTimeout(printClicked, 10);
      return false;
    });
    // Download page
    jQuery(".print-download-email .btn-save--download").click(function() {
      var d = new Date();
      jQuery(this).attr('href', jQuery(this).attr('href') + "?" + d.getTime());
    });
  }
};

function printClicked() {
  win = window.open(this.href);
  jQuery(win.document).ready(function() {
    setTimeout(doPrint, 2000);
  });
}

function doPrint() {
  win.print();
  win.close();
}
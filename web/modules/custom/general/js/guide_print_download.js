var win;

Drupal.behaviors.guide_print_download = {
  attach: function(context, settings) {
    // Print modal
    jQuery(".print-download-email .print-opener").click(function() {
      jQuery(".print-download-email #guide_print_download_overlay").show();
      jQuery(".print-download-email #guide_print_modal").show();
      return false;
    });
    jQuery(".print-download-email .print-page .btn-panel--print-page").click(function() {
      win = window.open(this.href);
      jQuery(win.document).ready(function() {
        setTimeout(doPrint, 2000);
      });
      closeModals();
      return false;
    });
    jQuery(".print-download-email .print-guide .btn-panel--print-guide").click(function() {
      win = window.open(this.href);
      jQuery(win.document).ready(function() {
        setTimeout(doPrint, 2000);
      });
      closeModals();
      return false;
    });
    
    // Download modal
    jQuery(".print-download-email .download-opener").click(function() {
      jQuery(".print-download-email #guide_print_download_overlay").show();
      jQuery(".print-download-email #guide_download_modal").show();
      return false;
    });
    jQuery(".print-download-email .download-page .btn-panel--download-page").click(function() {
      var d = new Date();
      jQuery(this).attr('href', jQuery(this).attr('href') + "?" + d.getTime());
      closeModals();
    });
    jQuery(".print-download-email .download-guide .btn-panel--download-guide").click(function() {
      var d = new Date();
      jQuery(this).attr('href', jQuery(this).attr('href') + "?" + d.getTime());
      closeModals();
    });

    // Misc
    jQuery(".print-download-email .modal .close").click(function() {
      closeModals();
    });
    jQuery("#guide_print_download_overlay").click(function() {
      closeModals();
    });
  }
};

function doPrint() {
  win.print();
  win.close();
}

function closeModals() {
  jQuery(".modal").hide();
  jQuery("#guide_print_download_overlay").hide();
}

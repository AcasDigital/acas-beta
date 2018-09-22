var lastSync = "";

Drupal.behaviors.sync_prod = {
  attach: function(context, settings) {
    
  }
};

function syncProd() {
  jQuery("#edit-submit").attr("disabled", true);
  jQuery("#sync_progress").show();
  //setTimeout(syncCheck, 500);
  return true;
}

function syncCheck() {
  jQuery.ajax({
    url: "/sync-status",
    type: "GET",
    dataType: "html",
    cache: false,
    timeout: 60000,
    error: function(XMLHttpRequest, textStatus, errorThrown){
      
    },
    success: function(data){
      if (data != lastSync) {
        jQuery("#ajax_target").html(jQuery("#ajax_target").html() + "<div class='text'>" + data + "</div>");
        lastSync = data
      }
      if (data == 'finished') {
        location.href = '/sync-prod';
      }else{
        setTimeout(syncCheck, 500);
      }
    }
  });

}
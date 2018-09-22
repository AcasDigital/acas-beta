var i = 0;
var nodes;
var prod;
var d = new Date();

Drupal.behaviors.test_prod = {
  attach: function(context, settings) {
    jQuery.ajax({
      url: "/sync-prod-data?" + d.getTime(),
      type: "GET",
      dataType: "json",
      cache: false,
      timeout: 180000,
      error: function(XMLHttpRequest, textStatus, errorThrown){
        jQuery("#test-target").html('<div class="red">Fetch data error = ' + textStatus + '</div>');
      },
      success: function(data){
        prod = data.prod
        nodes = data.nodes;
        getPage(nodes[i]);
        /*
        if (location.pathname.indexOf('sync-prod') != -1) {
          jQuery("#test-target").html('<div class="target">Running git pull, composer update, clearing caches and rebuilding config on Production. Please wait...</div>');
          jQuery.ajax({
            url: prod + "/sync-cleanup?" + d.getTime(),
            type: "GET",
            dataType: "json",
            cache: false,
            timeout: 180000,
            error: function(XMLHttpRequest, textStatus, errorThrown){
              jQuery("#test-target").html('<div class="red">Clear cache error = ' + textStatus + '</div>');
            },
            success: function(data){
              jQuery("#test-target").html('');
              getPage(nodes[i]);
            }
          });
        }else{
          jQuery("#test-target").html('');
          getPage(nodes[i]);
        }
        */
      }
    });
  }
};

function getPage(node) {
  jQuery.ajax({
    url: prod + node.url + "?" + d.getTime(),
    type: "GET",
    dataType: "html",
    cache: false,
    node: node,
    timeout: 60000,
    error: function(XMLHttpRequest, textStatus, errorThrown){
      jQuery("#test-target").html(jQuery("#test-target").html() + '<div class="result"><span class="title">' + node.title + '</span>&nbsp;<span class="red">BAD</span></div>');
    },
    success: function(data){
      var a1 = data.split('last-changed="');
      var a2 = a1[1].split('">');
      if (a2[0] == node.changed) {
        jQuery("#test-target").html(jQuery("#test-target").html() + '<div class="result"><span class="title">' + node.title + '</span>&nbsp;<span class="green">OK</span></div>');
      }else{
        jQuery("#test-target").html(jQuery("#test-target").html() + '<div class="result"><span class="title">' + node.title + '</span>&nbsp;<span class="red">BAD</span></div>');
      }
      i++
      if (i < nodes.length) {
        getPage(nodes[i]);
      }else{
        jQuery("#test-target").html(jQuery("#test-target").html() + '<div class="result">FINISHED</div>');
      }
    }
  });
}
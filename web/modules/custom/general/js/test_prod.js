var i = 0;
var nodes;
var prod;
var d = new Date();

Drupal.behaviors.test_prod = {
  attach: function(context, settings) {
    jQuery.ajax({
      url: "/sync-prod-data",
      type: "GET",
      dataType: "json",
      cache: false,
      timeout: 60000,
      error: function(XMLHttpRequest, textStatus, errorThrown){
        
      },
      success: function(data){
        prod = data.prod
        nodes = data.nodes;
        getPage(nodes[i]);
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
      if (data.indexOf(node.title + "</span>") != -1) {
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
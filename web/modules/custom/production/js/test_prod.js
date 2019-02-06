var i = 0;
var nodes;
var prod;

Drupal.behaviors.test_prod = {
  attach: function(context, settings) {
    jQuery("#test-target").html('Starting in 5 seconds');
    setTimeout(startTest, 5000);
  }
};

function startTest() {
  jQuery.ajax({
      url: "/sync-prod-data",
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
      }
    });
}

function getPage(node) {
  jQuery.ajax({
    url: prod + node.url,
    type: "GET",
    dataType: "html",
    cache: false,
    node: node,
    timeout: 60000,
    error: function(XMLHttpRequest, textStatus, errorThrown){
      jQuery("#test-target").html(jQuery("#test-target").html() + '<div class="result"><span class="title">' + node.title + '</span>&nbsp;<span class="red">BAD</span><br />Check the <a href="https://beta.acas.org.uk/admin/reports/dblog">Production log</a> for any PHP SQL deadlock errors (messages beginig with "Drupal\Core\Database\DatabaseExceptionWrapper: SQLSTATE"). You will have to run the sync again.</div>');
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
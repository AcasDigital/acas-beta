(function ($, Drupal) {
  Drupal.behaviors.adminBehavior = {
    attach: function (context, settings) {
      $("#node-landing-page-edit-form #edit-field-taxonomy").change(function() {
        var title = $("option:selected", this).text().replace(/-/g, "");
        $("#node-landing-page-edit-form #edit-title-0-value").val(title);
      });
      $("#node-landing-page-form #edit-field-taxonomy").change(function() {
        var title = $("option:selected", this).text().replace(/-/g, "");
        $("#node-landing-page-form #edit-title-0-value").val(title);
      });
      
      $("#node-details-page-edit-form #edit-field-taxonomy").change(function() {
        var title = $("option:selected", this).text().replace(/-/g, "");
        $("#node-details-page-edit-form #edit-title-0-value").val(title);
      });
      $("#node-details-page-form #edit-field-taxonomy").change(function() {
        var title = $("option:selected", this).text().replace(/-/g, "");
        $("#node-details-page-form #edit-title-0-value").val(title);
      });
      // Changes to Taxonomy UI
      $("#toolbar-item-administration-tray .toolbar-menu-administration .toolbar-menu .toolbar-icon-entity-taxonomy-vocabulary-collection").text("Site structure");
      $(".toolbar-icon-entity-taxonomy-vocabulary-edit-form-acas").parent().parent().hide();
      $(".toolbar-icon-entity-taxonomy-vocabulary-edit-form-acas").parent().parent().parent().css("background-image", "none");
      $("#block-seven-primary-local-tasks nav .tabs__tab a:contains('View')").parent().hide();
      $("#edit-relations summary").text("Place in site structure");
      $("#edit-relations .form-select").attr("size", "10");
    }
  };
})(jQuery, Drupal);
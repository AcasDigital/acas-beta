(function ($, Drupal) {
  Drupal.behaviors.anythingWrongBehavior = {
    attach: function (context, settings) {
      if ($('.webform-submission-no-feedback-add-form').length || $('.webform-submission-yes-feedback-add-form').length) {
        $('#feedback-form').append($('.webform-submission-no-feedback-add-form'));
        $('#feedback-form').append($('.webform-submission-yes-feedback-add-form'));
        $("#feedback-form #no").click(function() {
          $("#feedback-form .right-wrapper").show();
          $("#feedback-form .webform-submission-yes-feedback-form").slideUp();
          $("#feedback-form .webform-submission-no-feedback-form").slideDown();
          sendVote(this, false);
          $([document.documentElement, document.body]).animate({
            scrollTop: $("#footer").offset().top
          }, 500);
          return false;
        });
        $("#feedback-form #yes").click(function() {
          $("#feedback-form .right-wrapper").show();
          $("#feedback-form .webform-submission-no-feedback-form").slideUp();
          $("#feedback-form .webform-submission-yes-feedback-form").slideDown();
          setTimeout(function(){ $("#feedback-form .webform-submission-yes-feedback-form textarea").focus(); }, 500);
          sendVote(this, false);
          $([document.documentElement, document.body]).animate({
            scrollTop: $("#footer").offset().top
          }, 500);
          return false;
        });
        $("#feedback-form .right-wrapper").click(function() {
          $("#feedback-form .right-wrapper").hide();
          $("#feedback-form .webform-submission-form").slideUp();
        });
        $("#feedback-form .webform-button--submit").click(function() {
          //$("#feedback-form .webform-submission-no-feedback-add-form").submit();
        });
        $("#feedback-form .webform-submission-no-feedback-form input[type='radio']").on('ifChanged', function (e) {
          setTimeout(function(){ $("#feedback-form .webform-submission-no-feedback-form textarea").focus(); }, 500);
        });
        var options = {
          beforeSubmit:  showAnythingWrongRequest,  // pre-submit callback
        };
        $('#feedback-form .webform-submission-form').ajaxForm(options);
      }else{
        $("#feedback-wrapper #feedback-form a").click(function() {
          sendVote(this, true);
          return false;
        });
      }
      function showAnythingWrongRequest(formData, jqForm, options) {
        $("#feedback-form .left-wrapper").html("<span class='text'>Thank you. Your feedback will help us improve our advice.<br />Unfortunately we cannot respond to individual feedback. If you need help, call our helpline on 0300 123 1190</span>");
        $("#feedback-form .right-wrapper").hide();
        $("#feedback-form .webform-submission-form").slideUp();
      }
      function sendVote(element, simple) {
        jQuery.ajax({
          url: "/feedback/" + jQuery(element).attr('nid') + "/" + jQuery(element).text() ,
          type: "GET",
          dataType: "html",
          cache: false,
          timeout: 60000,
          error: function(XMLHttpRequest, textStatus, errorThrown){

          },
          success: function(data){
            if (simple) {
              $("#feedback-wrapper #feedback-form").html("<span class='text'>Thank you. Your feedback will help us improve our advice.<br />Unfortunately we cannot respond to individual feedback. If you need help, call our helpline on 0300 123 1190</span>");
            }
          }
        });
      }
    }
  };
})(jQuery, Drupal);

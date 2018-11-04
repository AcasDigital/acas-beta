(function ($, Drupal) {
  Drupal.behaviors.anythingWrongBehavior = {
    attach: function (context, settings) {
      if ($('.webform-submission-no-feedback-add-form').length || $('.webform-submission-yes-feedback-add-form').length) {
        $('#feedback-form').append($('.webform-submission-no-feedback-add-form'));
        $('#feedback-form').append($('.webform-submission-yes-feedback-add-form'));
        $("#feedback-form #no").click(function() {
          $("#feedback-form .feedback-close-wrapper").show();
          $("#feedback-form .feedback-question-wrapper a").hide();
          $("#feedback-form .webform-submission-yes-feedback-form").slideUp();
          $("#feedback-form .webform-submission-no-feedback-form").slideDown();
          sendVote(this, false);
          $([document.documentElement, document.body]).animate({
            scrollTop: $("#footer").offset().top
          }, 500);
          return false;
        });
        $("#feedback-form #yes").click(function() {
          $("#feedback-form .feedback-close-wrapper").show();
          $("#feedback-form .webform-submission-no-feedback-form").slideUp();
          $("#feedback-form .webform-submission-yes-feedback-form").slideDown();
          $("#feedback-form .feedback-question-wrapper a").hide();
          setTimeout(function(){ $("#feedback-form .webform-submission-yes-feedback-form textarea").focus(); }, 500);
          sendVote(this, false);
          $([document.documentElement, document.body]).animate({
            scrollTop: $("#footer").offset().top
          }, 500);
          return false;
        });
        if (!$("#feedback-form #edit-radios").val()) {
          $("#feedback-form .no-feedback .form-item-answer").hide();
        }
        $("#feedback-form #edit-radios").change(function() {
          var questions = JSON.parse($("[data-drupal-selector=edit-questions]").val());
          var a = $("#feedback-form #edit-radios input:checked").val();
          $(".form-item-answer .control-label").text(questions[a]);
          $("#feedback-form .form-item-answer").slideDown();
          $("#feedback-form .form-item-answer textarea").focus();
        });
        $("#feedback-form .feedback-close-wrapper").click(function() {
          $("#feedback-form .feedback-close-wrapper").hide();
          $("#feedback-form .webform-submission-form").slideUp();
          $("#feedback-form .feedback-question-wrapper a").show();
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
        $("#feedback-form .feedback-question-wrapper .feedback-question").html("Thank you. Your feedback will help us improve our advice.<br />Unfortunately we cannot respond to individual feedback. If you need help, call our helpline on 0300 123 1190.");
        $("#feedback-form .feedback-close-wrapper").hide();
        $("#feedback-form .webform-submission-form").slideUp();
        $([document.documentElement, document.body]).animate({
          scrollTop: $("#feedback-wrapper").offset().top
        }, 500);
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
              $("#feedback-form .feedback-question-wrapper .text").html("Thank you. Your feedback will help us improve our advice.<br />Unfortunately we cannot respond to individual feedback. If you need help, call our helpline on 0300 123 1190.");
            }
          }
        });
      }
    }
  };
})(jQuery, Drupal);

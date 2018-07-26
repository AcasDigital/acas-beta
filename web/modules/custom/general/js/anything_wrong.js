(function ($, Drupal) {
  Drupal.behaviors.anythingWrongBehavior = {
    attach: function (context, settings) {
      $('#feedback-form').append($('.webform-submission-no-feedback-add-form'));
      $('#feedback-form').append($('.webform-submission-yes-feedback-add-form'));
      $("#feedback-form .form-url").val(location.origin + location.pathname);
      $("#feedback-form #no").click(function() {
        $("#feedback-form .right-wrapper").show();
        $("#feedback-form .webform-submission-yes-feedback-form").slideUp();
        $("#feedback-form .webform-submission-no-feedback-form").slideDown();
        $([document.documentElement, document.body]).animate({
          scrollTop: $("#footer").offset().top
        }, 500);
        return false;
      });
      $("#feedback-form #yes").click(function() {
        $("#feedback-form .right-wrapper").show();
        $("#feedback-form .webform-submission-no-feedback-form").slideUp();
        $("#feedback-form .webform-submission-yes-feedback-form").slideDown();
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
      $("#feedback-form input[type='checkbox']").on('ifChanged', function (e) {
        setTimeout(function(){ $("#feedback-form textarea").focus(); }, 500);
      });
      var options = {
        beforeSubmit:  showAnythingWrongRequest,  // pre-submit callback
        success:       showAnythingWrongResponse  // post-submit callback
      };
      $('#feedback-form .webform-submission-form').ajaxForm(options);
      /*
      $("#feedback-wrapper #feedback-form a").click(function() {
        jQuery.ajax({
          url: "/feedback/" + jQuery(this).attr('nid') + "/" + jQuery(this).text() ,
          type: "GET",
          dataType: "html",
          cache: false,
          timeout: 60000,
          error: function(XMLHttpRequest, textStatus, errorThrown){

          },
          success: function(data){
            $("#feedback-wrapper #feedback-form").html("Thank you for your feedback");
          }
        });
        return false;
      });
      */
      function showAnythingWrongRequest(formData, jqForm, options) {
        $("#feedback-form .left-wrapper").html("<span class='text'>Thank you for your feedback</span>");
        $("#feedback-form .right-wrapper").hide();
        $("#feedback-form .webform-submission-form").slideUp();
      }
      function showAnythingWrongResponse(responseText, statusText, xhr, $form)  {

      }
    }
  };
})(jQuery, Drupal);

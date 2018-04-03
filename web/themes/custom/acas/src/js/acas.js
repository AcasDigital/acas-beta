Drupal.behaviors.acas = {
  attach: function(context, settings) {
    var ua = window.navigator.userAgent;
    var msie = ua.indexOf("MSIE ");
    if (msie > 0) {
      jQuery('body').addClass('ie' + parseInt(ua.substring(msie + 5, ua.indexOf(".", msie))));
    }
    setTimeout(searchFocus, 200);
    document.body.style.display="block";
    function searchFocus() {
      document.getElementById("edit-keys").focus();
    }
  }
};


var dropdownButtons = (function() {
  // dom elements
  var primaryWrappers = document.getElementsByClassName('menu-primary__item');
  var primaryLinks = 'menu-primary__link';
  var secondaryWrappers = document.getElementsByClassName('menu-secondary__item');
  var secondaryLinks = 'menu-secondary__link';

  var activeDropdowns = [];

  addEventListeners(primaryWrappers, primaryLinks);
  addEventListeners(secondaryWrappers, secondaryLinks);

  function addEventListeners(wrappers, linkClass) {
    // Add click listeners to all the dropdown buttons
    for (i = 0; i < wrappers.length; i++) {
      if (wrappers[i].classList.contains('active')) {
        activeDropdowns.push(wrappers[i]);
      }
      for (c = 0; c < wrappers[i].getElementsByClassName(linkClass).length; c++) {
        wrappers[i].getElementsByClassName(linkClass)[c].addEventListener("click", function() {
          toggleDropdown(this, event);
        });
      }
    }
  }

  // Hide all other dropdowns
  function hideDropdowns(activeDropdowns) {
    for (i = 0; i < activeDropdowns.length; i++) {
      activeDropdowns[i].classList.remove('active');
    }
  }

  // Toggle the dropdown
  function toggleDropdown(link, event) {
    event.preventDefault(event);
    link.parentNode.classList.toggle('active');
    for (i = 0; i < activeDropdowns.length; i++) {
      if (activeDropdowns[i] !== this.parentNode) {
        activeDropdowns[i].classList.remove('active');
      }
    }
  }

  return {
    // Returning hideDropdowns in case another module needs to access this
    activeDropdowns: activeDropdowns,
    hideDropdowns: hideDropdowns
  }

})();

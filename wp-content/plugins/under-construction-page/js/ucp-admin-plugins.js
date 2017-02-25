/*
 * UnderConstructionPage 
 * Backend plugins enhancement
 * (c) Web factory Ltd, 2015 - 2016
 */


jQuery(function($) {
  // ask users to confirm plugin deactivation
  $('#the-list tr[data-slug="under-construction-page"] span.deactivate a').on('click', function(e) {
    if (confirm(ucp.deactivate_confirmation)) {
      return true;
    } else {
      e.preventDefault();
      return false;      
    }
  }); // confirm plugin deactivation
}); // onload

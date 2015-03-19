(function($) {
  $(document).ready(function() {
    //Set the correct tab to display
    var hash = location.hash.replace('#','');

    if(hash == '') {
      hash = 'account';
    }
    else {
      hash = hash.replace('chargify-','');
    }

    show_chosen_tab(hash);

    function show_chosen_tab(chosen)
    {
      var hash = '#chargify-' + chosen;

      //Adjust tab's style
      $('a.nav-tab-active').removeClass('nav-tab-active');
      $('a#' + chosen).addClass('nav-tab-active');

      //Adjust pane's style
      $('div.chargify-options-hidden-pane').hide();
      $('div#' + chosen).show();

      //Set action to the proper tab
      $('#chargify_options_form').attr('action', hash);
      window.location.hash = hash;
    }
    
    $('a.nav-tab').click(function() {
      if($(this).hasClass('nav-tab-active'))
        return false;

      var chosen = $(this).attr('id');

      show_chosen_tab(chosen);

      return false;
    });
  });
})(jQuery);

jQuery(function() {
	jQuery(document).scroll(function() {
		var a = jQuery('body').scrollTop();
		if (a > 250) {
			jQuery('.top-nav').addClass('fixed');
		} else {
			jQuery('.top-nav').removeClass('fixed');
		}
	});
	if (jQuery('div.more ul > li').length > 0) {
		//jQuery('div.mobile > ul').append(jQuery('div.more > ul > li').clone());
	}
	jQuery('.panel-heading').next().wrap('<div class="panel-body">');
	jQuery('.mobile_menu').click(function(event) {
		event.preventDefault();
		jQuery('div.menu:not(.more) > ul').slideToggle();
	});
	jQuery('.more_menu, div.more_menu_click_overlay').click(function(event) {
		event.preventDefault();
		jQuery('div.more > ul').slideToggle();
		jQuery('div.more_menu_click_overlay').toggle();
		jQuery('div.primary > ul > li:not(.more_menu_li)').fadeToggle();
		jQuery('.more_menu').toggleClass('expanded');
	});
	jQuery('.form-submit .submit').addClass('btn btn-primary');
	jQuery('.page-links a').wrap('<div class="col-xs-6">').addClass('btn btn-default btn-block');
	setTimeout(function() {
		jQuery('.scroll-arrow').fadeIn();
	}, 2000);
});
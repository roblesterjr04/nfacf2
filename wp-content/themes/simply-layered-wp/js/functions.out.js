/*
This file has been generated with SiteFlow (http://siteflow.witiz.com).
To remove this comment, please support us and upgrade to SiteFlow Pro.
*/
jQuery(function(){jQuery(document).scroll(function(){var e=jQuery("body").scrollTop();if(e>250)jQuery(".top-nav").addClass("fixed");else jQuery(".top-nav").removeClass("fixed")});if(jQuery("div.more ul > li").length>0);jQuery(".panel-heading").next().wrap('<div class="panel-body">');jQuery(".mobile_menu").click(function(e){e.preventDefault();jQuery("div.menu:not(.more) > ul").slideToggle()});jQuery(".more_menu, div.more_menu_click_overlay").click(function(e){e.preventDefault();jQuery("div.more > ul").slideToggle();jQuery("div.more_menu_click_overlay").toggle();jQuery("div.primary > ul > li:not(.more_menu_li)").fadeToggle();jQuery(".more_menu").toggleClass("expanded")});jQuery(".form-submit .submit").addClass("btn btn-primary");jQuery(".page-links a").wrap('<div class="col-xs-6">').addClass("btn btn-default btn-block");setTimeout(function(){jQuery(".scroll-arrow").fadeIn()},2e3)});


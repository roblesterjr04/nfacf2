<?php

/**
 * Plugin Name: North Fullerton Utilities
 * Plugin URI:  https://nfullertonartsfair.com
 * Description: Utilities for the website
 * Author:      Rob Lester
 * Author URI:  https://rmlsoft.com
 * Version:     1.0
 * Text Domain: nf_utilities
 * Domain Path: languages
 */
 
 
class NF_Utilities {
	 
	public function __construct() {
	 
		add_action('wp_footer', array($this, 'footer'));
		add_filter('post_class', array($this, 'post_class'), 10, 3);
	 
	}
	 
	public function post_class($classes, $class, $postid) {
		
		$post = get_post($postid);
		
		$classes[] = $post->post_name;
		
		return $classes;
		
	}
	
	public function footer() {
		
		$theme_options = get_option('theme_mods_twentyseventeen');
		
		$posts = array(
			get_post($theme_options['panel_1'])->post_name,
			get_post($theme_options['panel_2'])->post_name,	
			get_post($theme_options['panel_3'])->post_name,
			get_post($theme_options['panel_4'])->post_name
		);
		
		?>
		<!-- NFACF Utilities -->
		<script>
			
		var site_url = '<?php echo site_url() ?>';
		var post_id = '<?php echo get_the_id() ?>';
		var home_posts = <?php echo json_encode($posts) ?>;
			
		jQuery(function($) {
			
			var hash = window.location.hash.substr(1);
			if (hash) {
				$('html, body').animate({
			        scrollTop: $('.' + hash).find('.panel-content').offset().top - 100
			    }, 1000);
			}
			
			$('.navigation-top .menu-item a').click(function(event) {
				event.preventDefault();
				
				var href = $(this).attr('href');
				var slug = href.replace('#', '');
								
				if ($('.' + slug).length) {
				
				$('html, body').animate({
			        scrollTop: $('.' + slug).find('.panel-content').offset().top - 100
			    }, 1000);
			    
			    } else {
				    
				    if (home_posts.indexOf(slug) >= 0) {
					    href = site_url + '/#' + slug;
					    
				    } else {
				    
				    	href = site_url + '/' + slug;
				    
				    }
				    window.location.href = href;
			    }
				
			});
			
		});
		
		</script>
		<?php	
	}
	 
 }
 new NF_Utilities;
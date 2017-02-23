<?php

// Simply Layered Theme

function custom_theme_setup() {
	add_theme_support('post-thumbnails');
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( "title-tag" );
	
	add_post_type_support('post','excerpt');
	add_post_type_support('page','excerpt');
	add_post_type_support('post','thumbnail');
	
	register_nav_menu( 'primary', 'Primary Menu' );
	
	load_theme_textdomain( 'lb-theme', get_template_directory().'/languages' );
	
	register_nav_menus( array(
		'mobile_menu' => __('Mobile Menu', 'lb-theme'),
	) );
	
	register_nav_menus( array(
		'more_menu' => __('Hidden Menu', 'lb-theme'),
	) );
	
	if ( ! isset( $content_width ) ) $content_width = 900;
}
add_action( 'after_setup_theme', 'custom_theme_setup' );

add_filter( 'wp_title', 'lb_hack_wp_title_for_home' );
function lb_hack_wp_title_for_home( $title )
{
  if( empty( $title ) && ( is_home() || is_front_page() ) ) {
    return __( 'Home', 'lb-theme' ) . ' | ' . get_bloginfo( 'name' );
  }
  	return  $title . ' | ' . get_bloginfo('name');
}

function enqueue_scripts() {
	wp_enqueue_script('jquery');
	wp_enqueue_script('functions', get_template_directory_uri() . '/js/functions.out.js', 'jquery', time(), true);
	wp_enqueue_style('reset', get_template_directory_uri() . '/style/reset.css');
	wp_enqueue_style('dashicons');
	wp_enqueue_style('bootstrap', get_template_directory_uri() . '/bootstrap/css/bootstrap.min.css', array('reset'));
	wp_enqueue_style('site', get_template_directory_uri() . '/style/site.css', array('bootstrap'), time());
	wp_enqueue_style('schemes', get_template_directory_uri() . '/style/schemes.css', array('site'), time());
}
add_action('wp_enqueue_scripts', 'enqueue_scripts');

function lb_no_mobile_nav($args) {
	if ($args['theme_location'] == 'mobile_menu') {
		$args['theme_location'] = 'more_menu';
	} else if ($args['theme_location'] == 'more_menu') { 
		$args['theme_location'] = 'primary';
		$args['fallback_cb'] = 'lb_no_primary_nav';
	} 
	wp_nav_menu($args);
}

function lb_no_primary_nav($args) {
	return false;
}

function lb_more_menu_cb($args) {
	return false;
}

function lb_register_text( $link ) {
	if (is_user_logged_in()) {
		$user = wp_get_current_user();
		$avatar = get_avatar($user->user_email, 19);
		$link = str_replace('Site Admin', $avatar . 'Hi, ' . $user->user_firstname, $link);
	}
	return $link;
}
add_action('register', 'lb_register_text');

function lb_add_mobile_menu_button( $menu, $args ) {
	if ( has_nav_menu( 'more_menu' ) && $args->theme_location == 'primary' ) {
		$menu .= '<li class="more_menu_li"><a class="more_menu" href="#"><span class="dashicons dashicons-menu"></span></a></li>';
	}
	$has_status = preg_match("!<li (.*?)><a (.*?)>#loginstatus</a></li>!", $menu);
	$login_Status = '';
	if ($has_status) {
		$login_Status .= wp_register('<li>', '</li>', false);
		$login_Status .= '<li>'.wp_loginout(false, false).'</li>';
	}
	$menu = preg_replace("!<li (.*?)><a (.*?)>#loginstatus</a></li>!", $login_Status, $menu);
	return $menu;
}
add_filter( 'wp_nav_menu_items', 'lb_add_mobile_menu_button', 10, 2 );

function lb_menus_notice() {
	$screen = get_current_screen();
	if ($screen->base == 'nav-menus') :
	?>
    <div class="updated">
        <p><?php _e( 'To add the login meta links to your menu, create using "Links" with # as the <i>URL</i> and #loginstatus as the <i>Link Text</i>.', 'lb-theme' ); ?></p>
    </div>
    <?php
	 endif;
}
add_action('admin_notices', 'lb_menus_notice');

function lb_menu_overlay() {
	?><div class="more_menu_click_overlay"></div><?php
}
add_action('wp_footer', 'lb_menu_overlay');

function theme_page_header($title = false, $subtitle = false) {
	if ($subtitle) $subtitle = strip_tags($subtitle);
	?>
	<div class="top-nav">
		<a class="site_logo" href="<?php echo site_url(); ?>">
			<img class="large" src="<?php echo get_theme_mod('site_logo') ?>" />
			<img class="small" src="<?php echo get_theme_mod('site_logo_small') ?>" />
		</a>
		<a class="mobile_menu visible-xs" href="#"><span class="dashicons dashicons-menu"></span></a>
		<?php wp_nav_menu(array('theme_location'=>'primary', 'container_class'=>'menu primary')) ?>
		<?php wp_nav_menu(array('theme_location'=>'mobile_menu', 'fallback_cb' => 'lb_no_mobile_nav', 'container_class'=>'menu mobile')) ?>
		<?php wp_nav_menu(array('theme_location'=>'more_menu', 'fallback_cb' => 'lb_more_menu_cb', 'container_class'=>'menu more')) ?>
	</div>
	<div class="top">
		<div class="top-content overlay <?php echo is_front_page() ? 'home' : '' ?>">
			<div class="container">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<div class="headwrap">
							<div class="headline">
								<?php if (!is_single()) : ?>
								<h1 class="title"><?php echo $title ?: get_bloginfo('name') ?></h1>
								<p class="description"><?php echo $subtitle ?></p>
								<div class="row">
									<?php dynamic_sidebar('hero-grid'); ?>
								</div>
								<?php endif; ?>
								<?php if (is_front_page()) : ?>
									<span class="dashicons dashicons-arrow-down-alt2 scroll-arrow"></span>
								<?php endif; ?>
								
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
}

function theme_lb_top_background($element = '.top', $post_id = false) {
	$postid = $post_id;
	if ($element == '.top') {
		global $post;
		$postid = $post->ID;
	}
	$attachment_id = get_post_thumbnail_id($postid);
	$heroimage = get_theme_mod('site_header_image') ?: get_template_directory_uri() . '/images/64H.jpg';
	if (!$post_id) $image = is_front_page() || is_home() || is_archive() || is_search() ? array($heroimage) : (wp_get_attachment_image_src($attachment_id, 'large') ?: array($heroimage));
	else $image = wp_get_attachment_image_src($attachment_id, 'large');
	if (is_404()) $image = array($heroimage);
	echo '<style id="theme_bg" type="text/css">'.$element.' { background-image: url('.$image[0].'); }</style>';
}
add_action('wp_head', 'theme_lb_top_background', 10 , 0);

function theme_lb_widgets_init() {
    register_sidebar( array(
        'name' => __( 'Homepage Grid', 'lb-theme' ),
        'id' => 'homepage-grid',
        'description' => __( 'Widgets in this area will be shown on the home page below the headline area.', 'lb-theme' ),
        'before_title' => '<h4>',
        'after_title' => '</h4>',
        'before_widget' => '<div class="col-sm-4">',
        'after_widget' => '</div>'
    ) );
    register_sidebar( array(
        'name' => __( 'Right Sidebar', 'lb-theme' ),
        'id' => 'right-sidebar',
        'description' => __( 'Widgets in this area will be shown on the right side of pages such as the blog.', 'lb-theme' ),
        'before_title' => '<div class="panel-heading"><h3 class="panel-title">',
        'after_title' => '</h3></div>',
        'before_widget' => '<div class="panel panel-default">',
        'after_widget' => '</div>'
    ) );
    register_sidebar( array(
        'name' => __( 'Footer Left', 'lb-theme' ),
        'id' => 'footer-left',
        'description' => __( 'Widgets in this area will be shown on the left side of the footer.', 'lb-theme' ),
        'before_title' => '<h3>',
        'after_title' => '</h3>',
        'before_widget' => '<div class="widget"><div class="widget-wrap">',
        'after_widget' => '</div></div>'
    ) );
    register_sidebar( array(
        'name' => __( 'Hero Widgets', 'lb-theme' ),
        'id' => 'hero-grid',
        'description' => __( 'Widgets in this area will be shown in the page hero beneath head/subhead.', 'lb-theme' ),
        'before_title' => '<h5>',
        'after_title' => '</h5>',
        'before_widget' => '<div class="col-sm-4">',
        'after_widget' => '</div>'
    ) );
}
add_action( 'widgets_init', 'theme_lb_widgets_init' );

function theme_lb_customizer( $wp_customize ) {
   $wp_customize->add_section(
		'theme_logo',
		array(
			'title' => __('Site Logo', 'lb-theme'),
			'description' => __('Set Site Logos.', 'lb-theme'),
			'priority' => 35,
		)
   );
   $wp_customize->add_section(
		'theme_blog_image',
		array(
			'title' => __('Blog Header Image', 'lb-theme'),
			'description' => __('Set the header image for your blog or blog page.', 'lb-theme'),
			'priority' => 36,
		)
   );
   $wp_customize->add_section(
		'theme_color',
		array(
			'title' => __('Color Scheme', 'lb-theme'),
			'description' => __('Set the color scheme.', 'lb-theme'),
			'priority' => 36,
		)
   );

   $wp_customize->add_setting(
	   'site_logo',
	   array(
		   'sanitize_callback' => 'esc_url_raw'
	   )
	);
	$wp_customize->add_setting(
	   'site_header_image',
	   array(
		   'sanitize_callback' => 'esc_url_raw'
	   )
	);
	$wp_customize->add_setting(
	   'site_logo_small',
	   array(
		   'sanitize_callback' => 'esc_url_raw'
	   )
	);
	$wp_customize->add_setting(
	   'site_color',
	   array(
		   'sanitize_callback' => 'sanitize_text_field'
	   )
	);
	$wp_customize->add_setting(
	   'site_allow_analytics',
	   array(
		   'sanitize_callback' => 'sanitize_text_field'
	   )
	);
	$wp_customize->add_control(
		new WP_Customize_Image_Control(
			$wp_customize,
			'logo',
			array(
				'label'      => __( 'Upload a large logo', 'lb-theme' ),
				'section'    => 'theme_logo',
				'settings'   => 'site_logo' 
			)
		)
   );
   $wp_customize->add_control(
		new WP_Customize_Image_Control(
			$wp_customize,
			'lb_header_image',
			array(
				'label'      => __( 'Select a header image for the blog', 'lb-theme' ),
				'section'    => 'theme_blog_image',
				'settings'   => 'site_header_image' 
			)
		)
   );
   $wp_customize->add_control(
		new WP_Customize_Image_Control(
			$wp_customize,
			'logo_small',
			array(
				'label'      => __( 'Upload a smaller logo', 'lb-theme' ),
				'section'    => 'theme_logo',
				'settings'   => 'site_logo_small' 
			)
		)
   );
   $wp_customize->add_control(
		new WP_Customize_Control(
			$wp_customize,
			'theme_color',
			array(
				'label'      => __( 'Select color scheme', 'lb-theme' ),
				'section'    => 'theme_color',
				'settings'   => 'site_color',
				'type'		 => 'select',
				'choices'	 => array(
					'default' => __( 'Dark', 'lb-theme' ),
					'light' => __( 'Light', 'lb-theme' ),
					'blue' => __( 'Blue', 'lb-theme' ),
					'green' => __( 'Green', 'lb-theme' ),
					'orange' => __( 'Orange', 'lb-theme' ),
					'plum' => __( 'Plum', 'lb-theme' )
				)
			)
		)
   );
   $wp_customize->add_control(
	   new WP_Customize_Control(
		   $wp_customize,
		   'theme_analytics',
		   array(
			   'label'		=> __( 'Help us improve our theme by turning on usage tracking. This will enable tracking pixels in the theme to allow RML Soft to receive usage statistics of the theme. This data is entirely enonymous.', 'lb-theme'),
			   'section'	=> 'title_tagline',
			   'settings'	=> 'site_allow_analytics',
			   'type'		=> 'checkbox'
		   )
	   )
   );
}
add_action( 'customize_register', 'theme_lb_customizer' );

function lb_theme_read_more( $more ) {
	return ' <a href="'.get_the_permalink(get_the_ID()).'">[...]</a>';
}
add_filter('excerpt_more', 'lb_theme_read_more');

function lb_theme_comment_args() {
	return array(
		'comment_field' =>  '<div class="comment-form-comment"><h4>' . _x( 'Comment', 'noun', 'lb-theme' ) .
    '</h4><textarea id="comment" name="comment" aria-required="true">' .
    '</textarea></div>'
	);
}

// LOGIN SCREEN CHANGES

function lb_login_logo() { 
	$logo = get_theme_mod('site_logo', 'not_set');
	$logosize = array();
	if ($logo && $logo != 'not_set') {
		$logosize = getimagesize($logo);
		$logoheight = (320 * $logosize[1]) / $logosize[0];
	}
	?><link rel="stylesheet" href="<?php echo get_template_directory_uri() ?>/style/schemes.css" />
	   <style type="text/css">
		   #login {
			   padding: 3% 0 0;
		   }
		   <?php if ($logoheight) : ?>
		   #login h1 {
			   height: <?php echo $logoheight ?>px;
		   }
		   <?php endif; ?>
	        <?php if ($logo != 'not_set') : ?>
	        body.login div#login h1 a {
	            background-image: url(<?php echo $logo; ?>);
	            padding-bottom: 30px;
	            -webkit-background-size: contain !important;
					background-size: contain !important;
					width: 100%;
					height: 70%;
	        }
	        <?php endif; ?>
	        body {
		        background-image: url(<?php echo get_theme_mod('site_header_image') ?: get_template_directory_uri() . '/images/64H.jpg'; ?>);
		        background-size: cover;
		        background-position: 50%;
	        }
	        .login #nav, .login #backtoblog {
		      	margin-top: 20px;
					margin-left: 0;
					padding: 15px 24px;
					font-weight: 400;
					overflow: hidden;
					background: #fff;
					-webkit-box-shadow: 0 1px 3px rgba(0,0,0,.13);
					box-shadow: 0 1px 3px rgba(0,0,0,.13);
	        }
	   </style>
		<?php 
}
add_action( 'login_enqueue_scripts', 'lb_login_logo' );

function lb_login_classes( $classes ) {
	$scheme = get_theme_mod('site_color', 'default');
	$classes[] = $scheme;
	return $classes;
}
add_filter( 'login_body_class', 'lb_login_classes' );

function lb_login_logo_url() {
    return home_url();
}
add_filter( 'login_headerurl', 'lb_login_logo_url' );

function tracking_pixels() {
	?><img class="tracking-pixel" src="http://www.google-analytics.com/collect?v=1&tid=UA-44501346-1&cid=Themes&el=<?php echo esc_url( home_url() ) ?>&t=event&ec=Usage&ea=Load&cs=Wordpress&cm=theme&cn=ThemeUsage" height="1" width="1" alt="tracking" /><?php
	if ( has_nav_menu( 'mobile_menu' )) { ?>
		<img class="tracking-pixel" src="http://www.google-analytics.com/collect?v=1&tid=UA-44501346-1&cid=Themes&el=<?php echo esc_url( home_url() ) ?>&t=event&ec=Usage&ea=HasMobileMenu&cs=Wordpress&cm=theme&cn=ThemeUsage" height="1" width="1" alt="tracking" />
	<?php }
	if ( has_nav_menu( 'more_menu' )) { ?>
		<img class="tracking-pixel" src="http://www.google-analytics.com/collect?v=1&tid=UA-44501346-1&cid=Themes&el=<?php echo esc_url( home_url() ) ?>&t=event&ec=Usage&ea=HasMoreMenu&cs=Wordpress&cm=theme&cn=ThemeUsage" height="1" width="1" alt="tracking" />
	<?php }
	if ( 'posts' == get_option( 'show_on_front' ) ) { ?>
		<img class="tracking-pixel" src="http://www.google-analytics.com/collect?v=1&tid=UA-44501346-1&cid=Themes&el=<?php echo esc_url( home_url() ) ?>&t=event&ec=Usage&ea=UsedAsBlog&cs=Wordpress&cm=theme&cn=ThemeUsage" height="1" width="1" alt="tracking" />
	<?php }
	else { ?>
		<img class="tracking-pixel" src="http://www.google-analytics.com/collect?v=1&tid=UA-44501346-1&cid=Themes&el=<?php echo esc_url( home_url() ) ?>&t=event&ec=Usage&ea=UsedAsWebsite&cs=Wordpress&cm=theme&cn=ThemeUsage" height="1" width="1" alt="tracking" />
	<?php }
}



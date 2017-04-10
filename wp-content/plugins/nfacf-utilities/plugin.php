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
require_once('MailChimp.php');
require_once('permits.php');
require_once( __DIR__ . '/stripe-php/init.php');
require_once( __DIR__ . '/pdf/tcpdf.php');
use \DrewM\MailChimp\MailChimp;
 
class NF_Utilities {
	
	private $MailChimp;
	private $vendor_amount = 115;
	private $food_amount = 150;
	private $non_profit_amount = 65;
	 
	public function __construct() {
	 
		add_action('wp_footer', array($this, 'footer'));
		add_filter('post_class', array($this, 'post_class'), 10, 3);
		
		add_action('user_register', array($this, 'user_register'), 10, 1);
		
		add_shortcode('nf_payment_button', array($this, 'payment_button'));
		add_shortcode( 'nf_price', array($this, 'vendor_price'));
		add_shortcode( 'nf_food', array($this, 'food_price'));
		
		add_filter('wp_nav_menu_objects', array($this, 'menu_login'), 10, 2);
		
		add_action('wp_ajax_nopriv_arts_fair_charge', array($this, 'update_subscriber'));
		add_action('wp_ajax_nopriv_arts_fair_refund', array($this, 'update_refund'));
		
		$this->MailChimp = new MailChimp('98f888ec715f151b8a63d086f441c8bf-us13');
	 
	}
	
	public function menu_login($items, $args) {
		
		$logged = is_user_logged_in();
		
		$output = array();
		
		foreach ($items as $item) {
			
			if ($item->post_name == 'login' && $logged) continue;
			if ($item->post_name == 'my-profile' && !$logged) continue;
			//if ($item->post_name == 'register' && $logged) continue;
			
			$output[] = $item;
			
		}
		
		return $output;
	}
	
	public function payment_button( $args = [], $content = '') {
		
		$amount = $this->vendor_amount;
		
		$user = get_current_user_id();
		$vendor_type = get_user_meta($user, 'vendor_type', true);
		$existing_permit = get_user_meta($user, 'existing_permit', true);
		$permit_approved = get_user_meta($user, 'existing_permit_approved', true);
		
		if ($vendor_type == 'Food Vendor') $amount = $this->food_amount;
		if ($vendor_type == 'Non Profit') $amount = $this->non_profit_amount;
 				
		$logo = isset($args['logo']) ? $args['logo'] : plugin_dir_url( __FILE__ ) . "logo.jpg";
		
		$user_obj = get_user_by('id', $user);
		$name = $user_obj->display_name;
		$email = $user_obj->email;
		
		if ($existing_permit && $permit_approved) $amount -= 25;
		
		$userid = get_current_user_id();
		$paid = get_user_meta($userid, 'attending', true);
		
		if ($paid == date('Y')) {
			return '<h4>You have paid $'.money_format('%i', $amount).' for the '.date('Y').' event.</h4>';
		}
		
		$code = '<h4>Your price will be $'.money_format('%i', $amount).'</h4>[accept_stripe_payment email="'.$email.'" name="Fair Registration" price="'.$amount.'" item_logo="'.$logo.'"]';
		
		return do_shortcode($code);
		
	}
	
	function vendor_price($args, $content = '') {
		return '$' . money_format('%i', $this->vendor_amount);
	}
	
	function food_price($args, $content = '') {
		return '$' . money_format('%i', $this->food_amount);
	}
		
	public function user_register( $user_id ) {
		
		if (isset($_POST['user_email'])) {
			$this->subscribe($_POST['user_email'], $_POST['first_name'], $_POST['last_name']);
		}
		
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
			        scrollTop: $('.' + hash).find('.panel-content').offset().top - 50
			    }, 1000);
			}
			
			$('.navigation-top .menu-item a').click(function(event) {
				event.preventDefault();
				
				var href = $(this).attr('href');
				var slug = href.replace('#', '');
								
				if ($('.' + slug).length) {
				
				$('html, body').animate({
			        scrollTop: $('.' + slug).find('.panel-content').offset().top - 50
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
			//if (is_user_logged_in()) echo '<style>.register { display: none; }</style>';
	}
	
	private function subscribe($email, $first, $last) {
				
		$list = '88a3aedb5f';
		
		$result = $this->MailChimp->post("lists/$list/members", [
            'email_address' => $email,
            'status'        => 'subscribed',
            'merge_fields'	=> [
	            'FNAME'		=> $first,
	            'LNAME'		=> $last,
	            'PSTATUS'	=> 'Unpaid',
	            'ATTEND'	=> 'Not'
            ]
        ]);
        
        if (isset($result['errors'])) return false;
        return true;
        
	}
	
	public function update_refund() {
		
		$body = @file_get_contents('php://input');
		
		$event = json_decode($body);
		
		$email = $event->data->object->receipt_email;
		
		$user = get_user_by_email($email);
		update_user_meta($user->ID, 'attending', date('Y'));
		
		if ($email === null) exit;
		
		$hash = md5(strtolower($email));
		
		$list = '88a3aedb5f';
		
		$result = $this->MailChimp->patch("lists/$list/members/$hash", [
            'merge_fields'	=> [
	            'PSTATUS'	=> 'Unpaid',
	            'ATTEND'	=> 'Not'
            ]
        ]);
        
        var_dump($result);
        
        exit;
		
	}
	
	public function update_subscriber() {
		
		$body = @file_get_contents('php://input');
		
		$event = json_decode($body);
		
		$email = $event->data->object->receipt_email;
		
		$user = get_user_by_email($email);
		update_user_meta($user->ID, 'attending', date('Y'));
		
		if ($email === null) exit;
		
		$hash = md5(strtolower($email));
		
		$list = '88a3aedb5f';
		
		$result = $this->MailChimp->patch("lists/$list/members/$hash", [
            'merge_fields'	=> [
	            'PSTATUS'	=> 'Paid',
	            'ATTEND'	=> date('Y')
            ]
        ]);
        
        var_dump($result);
        
        exit;
	}
	 
 }
 new NF_Utilities;
<?php
	
/**
 * Plugin Name: North Fullerton Form Expansion
 * Plugin URI:  https://nfullertonartsfair.com
 * Description: WPForms expansion for the fair registration.
 * Author:      Rob Lester
 * Author URI:  https://rmlsoft.com
 * Version:     1.0
 * Text Domain: nf_forms
 * Domain Path: languages
 */
 
 
require_once('MailChimp.php'); 
require_once('stripe-php/init.php');
require_once('nf-admin.php');

use \DrewM\MailChimp\MailChimp;
use \Stripe\Stripe;
use \Stripe\Charge;
use \Stripe\Customer;
 
class NF_Forms {
	
	private static $field_map = array(
		'name'				=>	'Name',
		'bus_name'			=>	'Business Name',
		'bus_phone'			=>	'Business Phone #',
		'phone'				=>	'Contact Phone #',
		'bus_email'			=>	'Business Email Address',
		'email'				=>	'Contact Email Address',
		'work_types'		=>	'Type of Work',
		'power'				=>	'power',
		'cc_number'			=>	'Credit Card #',
		'cc_exp'			=>	'Expires',
		'cc_cvc'			=>	'CVC',
		'vendor_type'		=>  'vendor_type',
		'work_types'		=>  'Types of Work',
		'image_name'		=>  'file',
		'permit'			=>  'permit',
		'permit_number'		=>  'Mobile Vendor Permit #'
	);
	
	private static $address_map = array(
		'line_1',
		'line_2',
		'city',
		'zip',
		'state'	
	);
	
	private $stripe_key;
	private $mailchimp_key;
 
	private $MailChimp;
	
	private $listid;
	
	public $mode;
	
	private $chardeid;
	 
	public function __construct() {
				
		add_action( 'wpforms_process', array($this, 'process_fields') );
		add_action( 'init', array($this, 'init_form') );
		
		add_action( 'wp_footer', array($this, 'footer'));
		
		add_shortcode( 'nf_price', array($this, 'fair_price'));
		add_shortcode( 'nf_food', array($this, 'food_price'));
		
		add_action( 'wpforms_process_validate_credit_card', array($this, 'validate_fields'), 10, 3);
		add_action( 'wpforms_process_validate_text', array($this, 'validate_vendor_id'), 10, 3);
		add_filter( 'wpforms_process_before_form_data', array($this, 'pre_process'), 10, 2);
		
		$this->mode = get_option('run_mode');
		$this->charge_amount = get_option('fair_price');
		$this->food_amount = get_option('food_price');
		
		if ($this->mode == 'test') {
			add_filter( 'bloginfo', array($this, 'test_title'), 10, 2 );
			add_action( 'admin_notices', array($this, 'admin_test_notice') );
			$this->stripe_key = get_option('stripe_test_key');
		} else {
			$this->stripe_key = get_option('stripe_live_key');
		}
		
		Stripe::setApiKey($this->stripe_key);
		$this->MailChimp = new MailChimp(get_option('mailchimp_key'));
		$this->listid = get_option('mailchimp_list_id');
		
		$this->charge_amount = get_option('fair_price');
				
	}
	
	function admin_notice() {
		?>
	    <div class="notice notice-error is-dismissible">
	        <p>WP Forms is not active. Reinstall or activate WPForms for NF Extension to work.</p>
	    </div>
	    <?php
	}
	
	function fair_price($args, $content = '') {
		return '$' . money_format('%i', $this->charge_amount);
	}
	
	function food_price($args, $content = '') {
		return '$' . money_format('%i', $this->food_amount);
	}
	
	function admin_test_notice() {
	    ?>
	    <div class="notice notice-warning is-dismissible">
	        <p>You are currently in TEST mode on the site. All transactions will be sent to TEST Stripe Account.</p>
	    </div>
	    <?php
	}
	
	public function init_form() {
		if ( !class_exists(WPForms) ) {
			add_action( 'admin_notices', array($this, 'admin_notice') );
			return;
		}
		require_once('field.php');
	}
	
	public function test_title($data, $key) {
		if ($key == 'name') return 'TEST - ' . $data;
		return $data;
	}
	
	public function add_fields($fields) {
		$fields[] = 'nf-forms-cc';
		return $fields;		
	}
	
	public static function map_fields($fields) {
		$values = array();
		
		$types = array();
		
		foreach ( self::$field_map as $key => $field ) {
			
			foreach($fields as $field_data) {
				if ($field_data['name'] == $field) {
					$values[$key] = $field_data['value'];
				}
				
				if ($field_data['name'] == 'Billing Address') {
					foreach (self::$address_map as $address_field) {
						if (isset($field_data[$address_field])) $values[$address_field] = $field_data[$address_field];
					}
				}
			}
			
		}
		
		if (isset($values['cc_exp'])) {
			$exp_arr = explode('/', $values['cc_exp']);
			$values['cc_month'] = $exp_arr[0];
			$values['cc_year'] = $exp_arr[1];
			unset($values['cc_exp']);
		}
				
		return $values;
	}
	
	public function validate_fields($field_id, $field_submit, $form_data) {
		
		if (isset($form_data['failed_to_charge'])) {
			
			$form_id = $form_data['id'];
			wpforms()->process->errors[$form_id][$field_id] = 'Unable to verify card.';
			
		} else {
			$this->chargeid = $form_data['charge_id'];
		}
		
	}
	
	public function validate_vendor_id($field_id, $field_submit, $form_data) {
		
		if (isset($form_data['missing_permit'])) {
			$form_id = $form_data['id'];
			wpforms()->process->errors[$form_id][$field_id] = 'Please enter your permit number.';
		} 
		
	}
	
	public function pre_process($form_data, $entry) {
		
		$values = array();
		
		for ($i = 0; $i < 100; $i++) {
		
			if ($form_data['fields'][$i]['label'] == NULL) continue;
			$values[] = array(
				'name'=>$form_data['fields'][$i]['label'],
				'value'=>$entry['fields'][$i]
			);
		}
		
		$values = self::map_fields($values);
		
		$amount = self::get_amount($values['vendor_type'] == NULL);
		$charge = self::authorize($amount, $values);
		
		if (is_array($charge)) {
			$form_data['failed_to_charge'] = true;
		} else {
			$form_data['charge_id'] = $charge;
		}
		
		//if (!empty($values['permit']) && empty($values['permit_number']))
		//	$form_data['missing_permit'] = true;
		
			
		return $form_data;
	}
	
	public function process_fields($fields) {		
		
		global $wpdb;
		
		$values = self::map_fields($fields);
				
		$values['subscribed'] = $this->subscribe($values['email']);
		
		$values['stripe_id'] = $this->charge($this->chargeid);
		
		$values['phone'] = preg_replace('/[^0-9]/', '', $values['phone']);
		$values['bus_phone'] = preg_replace('/[^0-9]/', '', $values['bus_phone']);
		
		if (isset($values['power'])) $values['power'] = 1;
		
		$wp_fields = $wpdb->get_col('DESC vendors', 0);
		foreach ($values as $key => $value) {
			if (!in_array($key, $wp_fields)) unset($values[$key]);
		}
		$wpdb->insert('vendors', $values);
		
		return $fields;
		
	}
	
	private static function card_array($data) {
		return [
			"object" => 'card',
			"name" => $data['name'],
			"number" => $data['cc_number'],
			"exp_month" => $data['cc_month'],
			"exp_year" => $data['cc_year'],
			"cvc" => $data['cc_cvc']
		];
	}
	
	public static function get_amount($type = false) {
		
		if ($type) $amount = str_replace('.', '', money_format('%i', get_option('fair_price')));
		else $amount = str_replace('.', '', money_format('%i', get_option('food_price')));
		
		return $amount;
	}
	
	private function charge($charge_id) {
		
		$charge = Charge::retrieve($charge_id);
		$charge->capture();
		
		return $charge_id;
		
	}
	
	private static function authorize($amount, Array $data) {
		
		try {
			$charge = Charge::create(array(
				"source" => self::card_array($data),
				"amount" => $amount,
				"currency" => "usd",
				"capture" => false,
				"receipt_email" => $data['email'],
				"description" => "FAIR DONATION"
			));
			return $charge->id;
		} catch (\Stripe\Error\InvalidRequest $e) {
			return array('error'=>'invalid-request');
		} catch (\Stripe\Error\Card $e) {
			return array('error'=>'invalid-card');
		}
		
		
	}
	
	private function subscribe($email) {
				
		$list = $this->listid;
		
		$result = $this->MailChimp->post("lists/$list/members", [
            'email_address' => $email,
            'status'        => 'subscribed',
        ]);
        
        if (isset($result['errors'])) return false;
        return true;
        
	}
 
	public function footer() {
		?>
		<script>
			
		jQuery(function($) {
			
			$('.navigation-top .menu-item a').click(function(event) {
				event.preventDefault();
				
				var href = $(this).attr('href');
				
				var offset = 500;
				
				if (href == '#page') offset = 0;
				
				$('html, body').animate({
			        scrollTop: $(href).offset().top + offset
			    }, 1000);
				
			});
			
		});
		
		</script>
		<?php	
	}
 
}
new NF_Forms;
<?php
	
class NF_Admin {
	
	public function __construct() {
		
		add_action( 'admin_init', array($this, 'register_settings') );
		add_action( 'admin_menu', array($this, 'add_options_page') );
		
	}
	
	public function register_settings() {
		
		$g = 'nf-options';
		register_setting( $g, 'fair_price', 'floatval' );
		register_setting( $g, 'food_price', 'floatval' );
		register_setting( $g, 'run_mode', 'strval' );
		register_setting( $g, 'stripe_test_key', 'strval' );
		register_setting( $g, 'stripe_live_key', 'strval' );
		register_setting( $g, 'mailchimp_key', 'strval' );
		register_setting( $g, 'mailchimp_list_id', 'strval' );
				
	}
	
	public function add_options_page() {
		
		add_options_page( 'Art Fair Settings', 'Art Fair', 'manage_options', 'art-fair', array($this, 'settings'));
		
	}
	
	public function settings() {
		?>
			<div class="wrap">
			<h1>Art Fair Settings</h1>
			<form method="post" action="options.php"> 
				<?php settings_fields( 'nf-options' ); ?>
				<?php do_settings_sections( 'nf-options' ); ?>
				<table class="form-table">
					<tr valign="top">
			        <th scope="row">Fair Cost</th>
			        <td>$<input type="text" name="fair_price" value="<?php echo esc_attr( get_option('fair_price') ); ?>" /></td>
			        </tr>
			        
			        <tr valign="top">
			        <th scope="row">Food Vendor Cost</th>
			        <td>$<input type="text" name="food_price" value="<?php echo esc_attr( get_option('food_price') ); ?>" /></td>
			        </tr>
			        
			        <tr valign="top">
			        <th scope="row">Run Mode (test/live)</th>
			        <? $mode = esc_attr( get_option('run_mode') ); ?>
			        <td><select name="run_mode">
				        <option value="test" <?php echo $mode == 'test' ? 'selected' : ''; ?>>Test</option>
				        <option value="live" <?php echo $mode == 'live' ? 'selected' : ''; ?>>Live</option>
			        </select></td>
			        </tr>
			        
			        <tr valign="top">
			        <th scope="row">Stripe Test Key</th>
			        <td><input class="widefat" type="text" name="stripe_test_key" value="<?php echo esc_attr( get_option('stripe_test_key') ); ?>" /></td>
			        </tr>
			        
			        <tr valign="top">
			        <th scope="row">Stripe Live Key</th>
			        <td><input class="widefat" type="text" name="stripe_live_key" value="<?php echo esc_attr( get_option('stripe_live_key') ); ?>" /></td>
			        </tr>
			        
			        <tr valign="top">
			        <th scope="row">Mailchimp API Key</th>
			        <td><input class="widefat" type="text" name="mailchimp_key" value="<?php echo esc_attr( get_option('mailchimp_key') ); ?>" /></td>
			        </tr>
			        
			        <tr valign="top">
			        <th scope="row">Mailchimp List ID</th>
			        <td><input type="text" name="mailchimp_list_id" value="<?php echo esc_attr( get_option('mailchimp_list_id') ); ?>" /></td>
			        </tr>
			        
				</table>
				
				<?php submit_button(); ?>
			</form>
			</div>
		<?php
	}
	
}

new NF_Admin;
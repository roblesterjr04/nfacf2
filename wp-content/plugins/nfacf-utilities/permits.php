<?php
	
class NF_Permits {
	
	private $date = '6 May 2017';
	
	public function __construct() {
		
		add_action('wp_ajax_assemble_permits', array($this, 'gather_permits'));
		add_filter('user_row_actions', array($this, 'permit_link'), 10, 2);
		
	}
	
	public function permit_link($actions, $user_object) {
		
		$actions['print_permit'] = '<a href="'.admin_url('admin-ajax.php?action=assemble_permits&user=').$user_object->ID.'" target="_blank">Permit</a>';
		
		return $actions;
		
	}
	
	public function gather_permits() {
		
		$user = get_user_by( 'ID', $_GET['user'] );
		
		$this->permit($user);
							
		exit;
				
	}
	
	private function permit($user) {
		
		$permit_image = plugin_dir_path( __FILE__ ) . 'Permit.pages.png';
		
		$image = imagecreatefrompng($permit_image);
		$black = imagecolorallocate($image, 0, 0, 0);
		$gray = imagecolorallocate($image, 80, 80, 80);
		$font = plugin_dir_path( __FILE__ ) . 'Times New Roman.ttf';
		$signature = plugin_dir_path( __FILE__ ) . 'mayqueen.ttf';
		$signature2 = plugin_dir_path( __FILE__ ) . 'PWSignaturetwo.ttf';
		$signature3 = plugin_dir_path( __FILE__ ) . 'MrsSaintDelafield-Regular.ttf';
		$numbers = plugin_dir_path( __FILE__ ) . 'Carybe.otf';
		$size = 20;
		
		$address = explode("\n", get_user_meta( $user->ID, 'mailing_address', true ));
		$address = implode(" ", $address);
		$address = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $address);
		
		imagettftext($image, $size, 0, 420, 160, $black, $font, 'North Fullerton Arts & Crafts Fair 2017');
		imagettftext($image, $size, 0, 940, 320, $black, $font, $this->date);
		imagettftext($image, $size, 0, 300, 590, $black, $font, $user->display_name);
		imagettftext($image, $size, 0, 300, 645, $black, $font, get_user_meta( $user->ID, 'phone_number', true ));
		imagettftext($image, $size, 0, 820, 645, $black, $font, '2');
		imagettftext($image, $size, 0, 300, 690, $black, $font, $address);
		imagettftext($image, $size, 0, 580, 740, $black, $font, get_user_meta( $user->ID, 'type_of_goods', true ));
		
		imagettftext($image, 45, 0, 170, 1220, $gray, $signature2, preg_replace("/[^A-Za-z0-9 ]/", ' ', $user->display_name));
		imagettftext($image, 45, 1, 170, 1330, $gray, $signature, 'Dr Leslie Ann Lester');
		imagettftext($image, 25, 0, 840, 1320, $gray, $font, date('j M Y'));
		
		header('Content-type: image/png');
		
		imagepng($image);
		imagedestroy($image);
				
	}
		
}
new NF_Permits;
<?php
	
class NF_Permits {
	
	private $date = '6 May 2017';
	private $event = 'N. Fullerton Arts & Crafts Fair 2017';
	
	public function __construct() {
		
		add_action('wp_ajax_assemble_permits', array($this, 'gather_permits'));
		add_filter('user_row_actions', array($this, 'permit_link'), 10, 2);
		add_shortcode( 'permit_link', array($this, 'permit_link_sc' ));
		
	}
	
	public function permit_link($actions, $user_object) {
		
		$actions['print_permit'] = '<a href="'.admin_url('admin-ajax.php?action=assemble_permits&user=').$user_object->ID.'" target="_blank">Permit</a>';
		
		return $actions;
		
	}
	
	public function permit_link_sc($attr, $content = '') {
		
		return '<a href="'.admin_url('admin-ajax.php?action=assemble_permits&user=').get_current_user_id().'" target="_blank">Permit</a>';
		
	}
	
	public function gather_permits() {
		
		if (isset($_GET['user'])) {
		
			$user = get_user_by( 'ID', $_GET['user'] );
			$permit = $this->permit($user);
			
			$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		
			$pdf->AddPage();
	
			// set JPEG quality
			$pdf->setJPEGQuality(75);
			
			$pdf->Image('@'.$permit);
			
			$pdf->Output('Permit.pdf', 'I');
		
		} else {
			
			$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
			$pdf->setJPEGQuality(75);
			$users = get_users( array( 'role'=>'Subscriber' ) );
			foreach ($users as $user) {
				
				$permit = $this->permit($user);
				$pdf->AddPage();
				$pdf->Image('@'.$permit);
				
			}
			
			$pdf->Output('Permit.pdf', 'I');
			
		}
							
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
		
		$references = get_user_meta( $user->ID, 'personal_references', true );
		
		$type = get_user_meta( $user->ID, 'vendor_type', true );
		
		$name = ucwords(strtolower($user->display_name));
		$crime = get_user_meta( $user->ID, 'committed_crime', true ) ?: 'No';
		$self = get_user_meta( $user->ID, 'self_employed', true ) ?: 'Yes';
		$thecrime = get_user_meta( $user->ID, 'the_crime', true );
		if ($crime == 'Yes') imagettftext($image, $size, 0, 700, 1030, $black, $font, 'X');
		else imagettftext($image, $size, 0, 830, 1030, $black, $font, 'X');
		if ($self == 'Yes') imagettftext($image, $size, 0, 250, 730, $black, $font, 'X');
		else imagettftext($image, $size, 0, 335, 730, $black, $font, 'X');
		
		if ($thecrime) imagettftext($image, $size, 0, 460, 1055, $black, $font, $thecrime);
		
		imagettftext($image, $size, 0, 320, 100, $black, $font, $this->event);
		imagettftext($image, $size, 0, 800, 250, $black, $font, $this->date);
		imagettftext($image, $size, 0, 160, 530, $black, $font, $name);
		imagettftext($image, $size, 0, 160, 580, $black, $font, get_user_meta( $user->ID, 'phone_number', true ));
		imagettftext($image, $size, 0, 720, 580, $black, $font, '2');
		imagettftext($image, $size, 0, 160, 625, $black, $font, $address);
		imagettftext($image, $size, 0, 120, 805, $black, $font, $references);
		imagettftext($image, $size, 0, 120, 930, $black, $font, $address);
		imagettftext($image, $size, 0, 440, 675, $black, $font, get_user_meta( $user->ID, 'type_of_goods', true ));
		
		imagettftext($image, $size, 0, 830, 730, $black, $font, get_user_meta( $user->ID, 'number_of_trucks', true ) ?: 0);
		
		if (!isset($_GET['nosig'])) imagettftext($image, 47, 0, 50, 1155, $gray, $signature2, preg_replace("/[^A-Za-z0-9 ]/", ' ', $name));
		imagettftext($image, 45, 1, 50, 1265, $gray, $signature, 'Dr Leslie Ann Lester');
		imagettftext($image, 25, 0, 690, 1255, $gray, $font, date('j M Y'));
				
		ob_start();
		imagejpeg($image);
		imagedestroy($image);
		$imagedata = ob_get_contents();
		ob_end_clean();
				
		return $imagedata;
				
	}
		
}
new NF_Permits;
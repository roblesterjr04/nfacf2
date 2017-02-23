<?php

class NF_Forms_Stripe_CC extends WPForms_Field {

	public function init() {
	
		// Define field type information
		$this->name  = __( 'Stripe CC', 'wpforms' );
		$this->type  = 'credit_card';
		$this->icon  = 'fa-credit-card';
		$this->order = 30;
		
		// Set field to default to required
		//add_filter( 'wpforms_field_new_required', array( $this, 'default_required' ), 10, 2 );
		
		// Set confirmation status to option wrapper class
		//add_filter( 'wpforms_builder_field_option_class', array( $this, 'field_option_class' ), 10, 2 );
	}
	
	public function field_preview( $field ) {

		$placeholder = !empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';

		$this->field_preview_option( 'label', $field );

		printf( '<input type="text" placeholder="%s" class="primary-input" disabled>', $placeholder );

		$this->field_preview_option( 'description', $field );
	}
	
	public function field_display( $field, $field_atts, $form_data ) {

		// Setup and sanitize the necessary data
		$field             = apply_filters( 'wpforms_stripe_cc_field_display', $field, $field_atts, $form_data );
		$field_placeholder = !empty( $field['placeholder']) ? esc_attr( $field['placeholder'] ) : '';
		$field_required    = !empty( $field['required'] ) ? ' required' : '';
		$field_class       = implode( ' ', array_map( 'sanitize_html_class', $field_atts['input_class'] ) );
		$field_id          = implode( ' ', array_map( 'sanitize_html_class', $field_atts['input_id'] ) );
		$field_value       = !empty( $field['default_value'] ) ? esc_attr( apply_filters( 'wpforms_process_smart_tags', $field['default_value'], $form_data ) ) : '';
		$field_data        = '';

		if ( !empty( $field_atts['input_data'] ) ) {
			foreach ( $field_atts['input_data'] as $key => $val ) {
			  $field_data .= ' data-' . $key . '="' . $val . '"';
			}
		}

		// Primary text field
		printf( 
			'<input type="text" name="wpforms[fields][%d]" id="%s" class="%s" value="%s" placeholder="%s" %s %s>',
			$field['id'],
			$field_id,
			$field_class,
			$field_value,
			$field_placeholder,
			$field_required,
			$field_data
		);
		
	}
	
	public function field_options( $field ) {

		//--------------------------------------------------------------------//
		// Basic field options
		//--------------------------------------------------------------------//
		
		//$this->field_option( 'meta',        $field );
		$this->field_option( 'basic-options', $field, array( 'markup' => 'open' ) );
		$this->field_option( 'label',         $field );
		$this->field_option( 'description',   $field );
		$this->field_option( 'required',      $field );
		$this->field_option( 'basic-options', $field, array( 'markup' => 'close' ) );
	
		//--------------------------------------------------------------------//
		// Advanced field options
		//--------------------------------------------------------------------//
	
		$this->field_option( 'advanced-options', $field, array( 'markup' => 'open' ) );
		$this->field_option( 'size',             $field );
		$this->field_option( 'placeholder',      $field );
		$this->field_option( 'label_hide',       $field );
		$this->field_option( 'default_value',    $field );
		$this->field_option( 'css',              $field );
		$this->field_option( 'advanced-options', $field, array( 'markup' => 'close' ) );
	}
	
	
}
new NF_Forms_Stripe_CC;

class NF_Forms_Address_Field extends WPForms_Field {
	
	public function init() {

		// Define field type information
		$this->name  = __( 'Billing Address', 'wpforms' );
		$this->type  = 'address';
		$this->icon  = 'fa-location-arrow';
		$this->order = 15;

		// Set field to default to required
		//add_filter( 'wpforms_field_new_required', array( $this, 'default_required' ), 10, 2 );
	}
	
	public function field_display( $field, $field_atts, $form_data) {
		
		$field = apply_filters( 'wpforms_stripe_cc_field_display', $field, $field_atts, $form_data );
		$field_required    = !empty( $field['required'] ) ? ' required' : '';
		$field_class       = implode( ' ', array_map( 'sanitize_html_class', $field_atts['input_class'] ) );
		$field_id          = implode( ' ', array_map( 'sanitize_html_class', $field_atts['input_id'] ) );
		$field_value       = !empty( $field['default_value'] ) ? esc_attr( apply_filters( 'wpforms_process_smart_tags', $field['default_value'], $form_data ) ) : '';
		$field_data        = '';
		$field_sublabel       = !empty( $field['sublabel_hide'] ) ? 'wpforms-sublabel-hide' : '';
		
		$line_1_placeholder   = !empty( $field['line_1_placeholder'] ) ? esc_attr( $field['line_1_placeholder'] ) : '';
		$line_1_default       = !empty( $field['line_1_default'] ) ? esc_attr( $field['line_1_default'] ) : '';
		$line_2_placeholder    = !empty( $field['line_2_placeholder'] ) ? esc_attr( $field['line_2_placeholder'] ) : '';
		$line_2_default        = !empty( $field['line_2_default'] ) ? esc_attr( $field['line_2_default'] ) : '';
		$city_placeholder   = !empty( $field['city_placeholder'] ) ? esc_attr( $field['city_placeholder'] ) : '';
		$city_default       = !empty( $field['city_default'] ) ? esc_attr( $field['city_default'] ) : '';
		$zip_placeholder     = !empty( $field['zip_placeholder'] ) ? esc_attr( $field['zip_placeholder'] ) : '';
		$zip_default         = !empty( $field['zip_default'] ) ? esc_attr( $field['zip_default'] ) : '';
		
		$form_id              = $form_data['id'];

		if ( !empty( $field_atts['input_data'] ) ) {
			foreach ( $field_atts['input_data'] as $key => $val ) {
			  $field_data .= ' data-' . $key . '="' . $val . '"';
			}
		}

		// Primary text field
		printf( 
			'<input type="text" name="wpforms[fields][%d][line_1]" id="%s" class="%s" value="%s" %s %s>',
			$field['id'],
			$field_id,
			$field_class,
			$line_1_default,
			$field_required,
			$field_data
		);
		
		printf( '<label for="wpforms-%d-field_%d" class="wpforms-field-sublabel %s">%s</label>', $form_id, $field['id'], $field_sublabel, __( 'Address Line 1', 'wpforms' ) );
		
		printf( 
			'<input type="text" name="wpforms[fields][%d][line_2]" id="%s" class="%s" value="%s" %s>',
			$field['id'],
			$field_id,
			$field_class,
			$line_2_default,
			$field_data
		);
		
		printf( '<label for="wpforms-%d-field_%d" class="wpforms-field-sublabel %s">%s</label>', $form_id, $field['id'], $field_sublabel, __( 'Address Line 2', 'wpforms' ) );
		
		printf( '<div class="wpforms-field-row %s">', $field_class );
		
			echo '<div class="wpforms-field-row-block wpforms-three-fifths wpforms-first">';
			
				printf( 
						'<input type="text" name="wpforms[fields][%d][city]" id="%s" class="%s" value="%s" %s>',
						$field['id'],
						"wpforms-{$form_id}-field_{$field['id']}",
						$field_class,
						$city_default,
						$field_required
					);
					
				printf( '<label for="wpforms-%d-field_%d" class="wpforms-field-sublabel %s">%s</label>', $form_id, $field['id'], $field_sublabel, __( 'City', 'wpforms' ) );
			
			echo '</div>';
			
			echo '<div class="wpforms-field-row-block wpforms-one-fifth wpforms-last">';
			
				printf( 
						'<input type="text" name="wpforms[fields][%d][zip]" id="%s" class="%s" value="%s" %s limit="5">',
						$field['id'],
						"wpforms-{$form_id}-field_{$field['id']}",
						$field_class,
						$zip_default,
						$field_required
					);
				
				printf( '<label for="wpforms-%d-field_%d" class="wpforms-field-sublabel %s">%s</label>', $form_id, $field['id'], $field_sublabel, __( 'Zip', 'wpforms' ) );
			
			echo '</div>';
			
			echo '<div class="wpforms-field-row-block wpforms-one-fifth wpforms-last">';
			
				printf( 
						'<input type="text" name="wpforms[fields][%d][state]" id="%s" class="%s" value="%s" %s limit="2">',
						$field['id'],
						"wpforms-{$form_id}-field_{$field['id']}",
						$field_class,
						$zip_default,
						$field_required
					);
				
				printf( '<label for="wpforms-%d-field_%d" class="wpforms-field-sublabel %s">%s</label>', $form_id, $field['id'], $field_sublabel, __( 'State', 'wpforms' ) );
				
			echo '</div>';
			
		
		echo '</div>';

		
	}
	
	public function field_preview( $field ) {
		
		$line_1_placeholder = !empty( $field['line_1_placeholder'] ) ? esc_attr( $field['line_1_placeholder'] ) : '';
		$line_2_placeholder = !empty( $field['line_2_placeholder'] ) ? esc_attr( $field['line_2_placeholder'] ) : '';
		$city_placeholder = !empty( $field['city_placeholder'] ) ? esc_attr( $field['city_placeholder'] ) : '';
		$zip_placeholder = !empty( $field['zip_placeholder'] ) ? esc_attr( $field['zip_placeholder'] ) : '';

		$this->field_preview_option( 'label', $field );

		echo '<div class="wpforms-address-scheme">';
			echo '<div class="wpforms-line-1">';
				printf( '<input type="text" placeholder="%s" class="primary-input" disabled>', $line_1_placeholder );
				printf( '<label class="wpforms-sub-label">%s</label>', __( 'Line 1' , 'wpforms') );
			echo '</div>';
			echo '<div class="wpforms-line-2">';
				printf( '<input type="text" placeholder="%s" class="primary-input" disabled>', $line_2_placeholder );
				printf( '<label class="wpforms-sub-label">%s</label>', __( 'Line 2' , 'wpforms') );
			echo '</div>';
			echo '<div class="wpforms-city">';
				printf( '<input type="text" placeholder="%s" class="primary-input" disabled>', $middle_placeholder );
				printf( '<label class="wpforms-sub-label">%s</label>', __( 'City', 'wpforms' ) );
			echo '</div>';
			echo '<div class="wpforms-zip">';
				printf( '<input type="text" placeholder="%s" class="primary-input" disabled>', $last_placeholder );
				printf( '<label class="wpforms-sub-label">%s</label>', __( 'zip', 'wpforms' ) );				
			echo '</div>';
		echo '</div>';

		$this->field_preview_option( 'description', $field );
	}
	
	public function field_options( $field ) {

		//--------------------------------------------------------------------//
		// Basic field options
		//--------------------------------------------------------------------//
		
		//$this->field_option( 'meta',        $field );
		$this->field_option( 'basic-options', $field, array( 'markup' => 'open' ) );
		$this->field_option( 'label',         $field );
		$this->field_option( 'description',   $field );
		$this->field_option( 'required',      $field );
		$this->field_option( 'basic-options', $field, array( 'markup' => 'close' ) );
	
		//--------------------------------------------------------------------//
		// Advanced field options
		//--------------------------------------------------------------------//
	
		$this->field_option( 'advanced-options', $field, array( 'markup' => 'open' ) );
		$this->field_option( 'size',             $field );
		$this->field_option( 'placeholder',      $field );
		$this->field_option( 'label_hide',       $field );
		$this->field_option( 'default_value',    $field );
		$this->field_option( 'css',              $field );
		$this->field_option( 'advanced-options', $field, array( 'markup' => 'close' ) );
	}
	
	public function format( $field_id, $field_submit, $form_data ) {

		$name   = !empty( $form_data['fields'][$field_id]['label'] ) ? $form_data['fields'][$field_id]['label'] : '';
		$line_1  = !empty( $field_submit['line_1'] ) ? $field_submit['line_1'] : '';
		$line_2 = !empty( $field_submit['line_2'] ) ? $field_submit['line_2'] : '';
		$city   = !empty( $field_submit['city'] ) ? $field_submit['city'] : '';
		$zip   = !empty( $field_submit['zip'] ) ? $field_submit['zip'] : '';
		$state = !empty( $field_submit['state']) ? $field_submit['state'] : '';
	
		if ( is_array( $field_submit ) ) {
			$value = array( $line_1, $line_2, $city, $zip );
			$value = array_filter( $value );
			$value = implode( ' ', $value );
		} else {
			$value = $field_submit;
		}

		wpforms()->process->fields[$field_id] = array(
			'name'     => sanitize_text_field( $name ),
			'value'    => sanitize_text_field( $value ),
			'id'       => absint( $field_id ),
			'type'     => $this->type,
			'line_1'    => sanitize_text_field( $line_1 ),
			'line_2'   => sanitize_text_field( $line_2 ),
			'city'     => sanitize_text_field( $city ),
			'zip'     => sanitize_text_field( $zip ),
			'state'	=> sanitize_text_field( $state )
		);
	}
	
}
new NF_Forms_Address_Field;

class NF_Forms_File_Uploader extends WPForms_Field {
	
	public function init() {
	
		// Define field type information
		$this->name  = __( 'File Uploader', 'wpforms' );
		$this->type  = 'file_upload';
		$this->icon  = 'fa-file';
		$this->order = 30;
		
		// Set field to default to required
		//add_filter( 'wpforms_field_new_required', array( $this, 'default_required' ), 10, 2 );
		
		// Set confirmation status to option wrapper class
		//add_filter( 'wpforms_builder_field_option_class', array( $this, 'field_option_class' ), 10, 2 );
	}
	
	public function field_preview( $field ) {
		
		$this->field_preview_option( 'label', $field );
		
		echo '<input disabled type="file" class="primary-input">';
		
		$this->field_preview_option( 'description', $field );
		
	}
	
	public function field_display( $field, $field_atts, $form_data ) {

		// Setup and sanitize the necessary data
		$field             = apply_filters( 'wpforms_file_upload_field_display', $field, $field_atts, $form_data );
		$field_required    = !empty( $field['required'] ) ? ' required' : '';
		$field_class       = implode( ' ', array_map( 'sanitize_html_class', $field_atts['input_class'] ) );
		$field_id          = implode( ' ', array_map( 'sanitize_html_class', $field_atts['input_id'] ) );
		$field_value       = !empty( $field['default_value'] ) ? esc_attr( apply_filters( 'wpforms_process_smart_tags', $field['default_value'], $form_data ) ) : '';
		$field_data        = '';

		if ( !empty( $field_atts['input_data'] ) ) {
			foreach ( $field_atts['input_data'] as $key => $val ) {
			  $field_data .= ' data-' . $key . '="' . $val . '"';
			}
		}

		// Primary text field
		printf( 
			'<input type="file" name="wpforms[fields][%d]" id="%s" class="%s" %s %s>',
			$field['id'],
			$field_id,
			$field_class,
			$field_required,
			$field_data
		);
		
	}
	
	public function field_options( $field ) {

		//--------------------------------------------------------------------//
		// Basic field options
		//--------------------------------------------------------------------//
		
		//$this->field_option( 'meta',        $field );
		$this->field_option( 'basic-options', $field, array( 'markup' => 'open' ) );
		$this->field_option( 'label',         $field );
		$this->field_option( 'description',   $field );
		$this->field_option( 'required',      $field );
		$this->field_option( 'basic-options', $field, array( 'markup' => 'close' ) );
	
		//--------------------------------------------------------------------//
		// Advanced field options
		//--------------------------------------------------------------------//
	
		$this->field_option( 'advanced-options', $field, array( 'markup' => 'open' ) );
		$this->field_option( 'label_hide',       $field );
		$this->field_option( 'css',              $field );
		$this->field_option( 'advanced-options', $field, array( 'markup' => 'close' ) );
	}
	
	public function format( $field_id, $field_submit, $form_data ) {
		
		$target_dir = wp_upload_dir();
		$filename = $_FILES['wpforms']['name']['fields'][$field_id];
		$tmpname = $_FILES['wpforms']['tmp_name']['fields'][$field_id];
		$target_file = $target_dir['basedir'] . $target_dir['subdir'] . '/' . basename($filename);
		
		move_uploaded_file($tmpname, $target_file);
		
		$name   = !empty( $form_data['fields'][$field_id]['label'] ) ? $form_data['fields'][$field_id]['label'] : '';
		
		wpforms()->process->fields[$field_id] = array(
			'value' => $filename,
			'name' => 'file'
		);
				
	}
	
}
new NF_Forms_File_Uploader;
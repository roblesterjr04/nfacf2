<?php

if ( ! defined( 'ABSPATH' ) && ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$options = get_option( 'ttf_delete_helper' );

foreach ( $options as $option ) {
	delete_option( $option );
}

delete_option( 'ttf_delete_helper' );

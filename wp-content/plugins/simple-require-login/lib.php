<?php

class SRL {

	/**
	*Variables
	*/
	const nspace = 'srl';

	protected $_plugin_file;
	protected $_plugin_dir;
	protected $_plugin_path;
	protected $_plugin_url;

	/**
	*Constructor
	*
	*@return void
	*@since 0.1
	*/
	function __construct() {}

	/**
	*Init function
	*
	*@return void
	*@since 0.1
	*/
	function init() {

        // add metabox to page/post/custom post types

        add_action( 'add_meta_boxes', array( &$this, 'add_meta_boxes' ) );

        // show/hide for yes/no

        add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ) );

		// save post

		add_action( 'save_post', array( &$this, 'save_post' ), 10, 2 );

		// require login

		add_action( 'template_redirect', array( &$this, 'template_redirect' ) );

	}

    /**
    *Add meta boxes
    *
    *@return void
    *@since 0.1
    */
    function add_meta_boxes() {
        $post_types = array_merge( get_post_types(), array( 'post', 'page' ) );
        foreach ( $post_types as $post_type ) {
			if ( ! strstr( $post_type, 'nav_menu_item' ) && ! strstr( $post_type, 'acf' ) && ! strstr( $post_type, 'revision' ) ) {
				add_meta_box( self::nspace . '-' . $post_type . '-meta-box', __( 'Require Login?', self::nspace ), array( &$this, 'meta_box_callback' ), $post_type, 'side', 'high' );
			}
        }
    }

    /**
    *Meta box callback
    *
    *@return void
    *@since 0.1
    */
    function meta_box_callback( $post ) {
        global $wp_roles;
        wp_nonce_field( self::nspace . '_save_meta_box_data', self::nspace . '_meta_box_nonce' );

        $value = get_post_meta( $post->ID, '_' . self::nspace . '-yesno', true );
        if ( ! $value ) $value = 'No';
        echo '<p><strong>' . __( 'Require login to view this content?', self::nspace ) . '</strong></p>';
        echo '<select id="' . self::nspace . '-yesno" name="' . self::nspace . '-yesno">';
        foreach ( array( 'Yes', 'No' ) as $val ) {
            echo '<option';
            if ( $val == $value ) echo ' selected="selected"';
            echo '>' . $val . '</option>';
        }
        echo '</select>';

        $value = get_post_meta( $post->ID, '_' . self::nspace . '-role', true );
        echo '<p class="' . self::nspace . '-role-set"><strong>' . __( 'Login type', self::nspace ) . '</strong></p>';
        echo '<select class="' . self::nspace . '-role-set" id="' . self::nspace . '-role" name="' . self::nspace . '-role">';
        echo '<option value="any"';
        if ( 'any' === $value ) echo ' selected="selected"';
        echo '>Any</option>';
        foreach ( $wp_roles->roles as $key => $role ) {
            echo '<option value="' . $key . '"';
            if ( $key === $value ) echo ' selected="selected"';
            echo '>' . $role['name'] . '</option>';
        }
        echo '</select>';
        echo '<p>' . __( 'Note: Administrators will always be able to view "Simple Require Login" content.', self::nspace ) . '</p>';
		$value = get_post_meta( $post->ID, '_' . self::nspace . '-ssl-yesno', true );
        if ( ! $value ) $value = 'No';
        echo '<p><strong>' . __( 'Redirect to SSL?', self::nspace ) . '</strong></p>';
        echo '<select id="' . self::nspace . '-ssl-yesno" name="' . self::nspace . '-ssl-yesno">';
        foreach ( array( 'Yes', 'No' ) as $val ) {
            echo '<option';
            if ( $val == $value ) echo ' selected="selected"';
            echo '>' . $val . '</option>';
        }
        echo '</select>';
    }

    /**
    *Admin Enqueue
    *
    *@return void
    *@since 0.1
    */
    function admin_enqueue_scripts() {
        wp_enqueue_script( self::nspace, $this->get_plugin_url() . '/' . self::nspace . '.js', array( 'jquery' ), '', true );
    }

    /**
    *Save post
    *
    *@return void
    *@since 0.1
    */
    function save_post( $post_id, $post ) {
        $nonce = $_POST[self::nspace . '_meta_box_nonce'];
        if ( ! isset( $nonce ) || ! wp_verify_nonce( $nonce, self::nspace . '_save_meta_box_data' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        $require_login = sanitize_text_field( $_POST[self::nspace . '-yesno'] );
        $role = sanitize_text_field( $_POST[self::nspace . '-role'] );
		$require_ssl = sanitize_text_field( $_POST[self::nspace . '-ssl-yesno'] );
        update_post_meta( $post_id, '_' . self::nspace . '-yesno', $require_login );
        update_post_meta( $post_id, '_' . self::nspace . '-role', $role );
		update_post_meta( $post_id, '_' . self::nspace . '-ssl-yesno', $require_ssl );
    }

    /**
    *Template redirect
    *
    *@return void
    *@since 0.1
    */
    function template_redirect() {
        global $post;
        $require_login = get_post_meta( $post->ID, '_' . self::nspace . '-yesno', true );
        $role = get_post_meta( $post->ID, '_' . self::nspace . '-role', true );
		$require_ssl = get_post_meta( $post->ID, '_' . self::nspace . '-ssl-yesno', true );
        if ( $require_login === 'Yes' ) {
            if ( $role === 'any' && is_user_logged_in() ) return; // allow any logged in user
            $user = wp_get_current_user();
            if ( in_array( 'administrator', array_keys( $user->caps ) ) ) return; // allow admins no matter what
            if ( in_array( $role, array_keys( $user->caps ) ) ) return; // allow specified role
            auth_redirect(); // otherwise, redirect to login
        }
		if ( $require_ssl === 'Yes' && ! is_ssl() ) {
			wp_redirect( 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
			exit;
		}
    }

	/**
	*Set plugin file
	*
	*@return void
	*@since 0.1
	*/
	function set_plugin_file( $plugin_file ) {
		$this->_plugin_file = $plugin_file;
	}

	/**
	*Get plugin file
	*
	*@return string
	*@since 0.1
	*/
	function get_plugin_file() {
		return $this->_plugin_file;
	}

	/**
	*Set plugin directory
	*
	*@return void
	*@since 0.1
	*/
	function set_plugin_dir( $plugin_dir ) {
		$this->_plugin_dir = $plugin_dir;
	}

	/**
	*Get plugin directory
	*
	*@return string
	*@since 0.1
	*/
	function get_plugin_dir() {
		return $this->_plugin_dir;
	}

	/**
	*Set plugin file path
	*
	*@return void
	*@since 0.1
	*/
	function set_plugin_path( $plugin_path ) {
		$this->_plugin_path = $plugin_path;
	}

	/**
	*Get plugin file path
	*
	*@return string
	*@since 0.1
	*/
	function get_plugin_path() {
		return $this->_plugin_path;
	}

	/**
	*Set plugin URL
	*
	*@return void
	*@since 0.1
	*/
	function set_plugin_url( $plugin_url ) {
		$this->_plugin_url = $plugin_url;
	}

	/**
	*Get plugin URL
	*
	*@return string
	*@since 0.1
	*/
	function get_plugin_url() {
		return $this->_plugin_url;
	}

}

?>

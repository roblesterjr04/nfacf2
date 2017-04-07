<?php

class Timeline_Twitter_Feed_Frontend {
	private $advanced_options = array();
	private $other_options    = array();
	
	function __construct() {
		$this->advanced_options = get_option( Timeline_Twitter_Feed_Options::ADVANCED_OPTIONS );
		$this->other_options    = get_option( Timeline_Twitter_Feed_Options::OTHER_OPTIONS );
		
		add_action( 'wp_head', array( $this, 'print_to_head' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_n_scripts' ), 999 );

		add_action( 'wp_ajax_get_tweet_updates', array( $this, 'ajax_tweets_rerenderer' ) );
		// Enable tweet updater for non-logged-in users
		add_action( 'wp_ajax_nopriv_get_tweet_updates', array( $this, 'ajax_tweets_rerenderer' ) );
	}
	
	public function print_to_head() {
		$this->print_twitter_js();
		$this->print_update_interval_js();
		$this->print_custom_css();
	}

	public function print_twitter_js() {
		if ( isset( $this->advanced_options[Timeline_Twitter_Feed_Options::TWITTER_JS] ) && ( 'on' === $this->advanced_options[Timeline_Twitter_Feed_Options::TWITTER_JS] ) ) {
			echo '<script type="text/javascript">!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>';
		}
	}

	public function print_update_interval_js() {
		if ( isset( $this->other_options[Timeline_Twitter_Feed_Options::DO_AJAX_UPDATES] ) && ( 'on' === $this->other_options[Timeline_Twitter_Feed_Options::DO_AJAX_UPDATES] ) && $this->other_options[Timeline_Twitter_Feed_Options::CACHE_EXPIRE] > 59 ) { // should be larger than 59 seconds (failsave)
			printf(
				'<script type="text/javascript">var ajaxurl = "%s"; var feedLoadingText = "%s"; var feedInterval = %s;</script>',
				esc_url( admin_url( 'admin-ajax.php' ) ),
				__( '...reloading...', Timeline_Twitter_Feed::TEXTDOMAIN ),
				intval( $this->other_options[Timeline_Twitter_Feed_Options::CACHE_EXPIRE] * 1000 )
			);
		}
	}

	public function print_custom_css() {
		if ( isset( $this->other_options[Timeline_Twitter_Feed_Options::CUSTOM_CSS] ) && ( $this->other_options[Timeline_Twitter_Feed_Options::CUSTOM_CSS] ) ) {
			$css = wp_kses( $this->other_options[Timeline_Twitter_Feed_Options::CUSTOM_CSS], 'none' );
			$css = str_replace( '&gt;', '>', $css );

			echo '<style type="text/css">' . $css . '</style>';
		}
	}

	public function enqueue_styles_n_scripts() {
		if ( isset( $this->advanced_options[Timeline_Twitter_Feed_Options::USE_CSS] ) && ( 'on' === $this->advanced_options[Timeline_Twitter_Feed_Options::USE_CSS] ) ) {
			wp_enqueue_style(
				'timeline-twitter-feed-frontend',
				esc_url( plugins_url( 'res/css/timeline-twitter-feed-frontend.css', dirname( __FILE__ ) ) ),
				array(),
				Timeline_Twitter_Feed::PLUGIN_VERSION
			);
		}
		
		if ( isset( $this->other_options[Timeline_Twitter_Feed_Options::DO_AJAX_UPDATES] ) && ( 'on' === $this->other_options[Timeline_Twitter_Feed_Options::DO_AJAX_UPDATES] ) ) {
			wp_enqueue_script(
				'timeline-twitter-feed-js',
				esc_url( plugins_url( 'res/js/timeline-twitter-feed.js', dirname( __FILE__ ) ) ),
				array( 'jquery' ),
				Timeline_Twitter_Feed::PLUGIN_VERSION,
				true // place in footer
			);
		}
	}
	
	public function ajax_tweets_rerenderer() {
		$shortcode = stripslashes( $_POST['shortcode'] );
		$shortcode = str_replace( array( '{', '}' ), array( '[', ']' ), $shortcode );
		$id        = sanitize_text_field( $_POST['id'] );
		
		$response['id']	    = $id;
		$response['output'] = do_shortcode( $shortcode );
		
		echo json_encode( $response );
		
		die(); // required to return a proper result
	}
}

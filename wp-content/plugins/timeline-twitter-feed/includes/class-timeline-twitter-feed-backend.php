<?php

if ( ! class_exists( 'WeDevs_Settings_API' ) ) {
	require dirname( dirname( __FILE__ ) ) . '/lib/class-webdevs-settings-api.php';
}

class Timeline_Twitter_Feed_Backend {
	private $settings_api;
	
	public function __construct() {
		$this->settings_api = new WeDevs_Settings_API();
		
		add_action( 'admin_init', array( $this, 'init_plugin_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_plugin_options_page' ) );
		add_action( 'admin_notices', array( $this, 'print_missing_api_authentication_notice' ) );
		add_action( 'admin_head-options-general.php?page=' . Timeline_Twitter_Feed::TEXTDOMAIN, array(
			 $this,
			'print_wp_override_css' 
		) );
		add_action( 'admin_menu', array( $this, 'delete_cached_feeds' ) );
	}
	
	public function init_plugin_settings() {
		$this->settings_api->set_sections( $this->get_settings_sections() );
		$this->settings_api->set_fields( $this->get_settings_fields() );
		$this->settings_api->admin_init();
	}
	
	public function get_settings_sections() {
		$desc = sprintf(
			__( '<p class="description">This area lets you configure your Twitter App and (if you want) reset your consumer key and secret. You can also <a href="%s" target="_blank">create a new application</a>. Fill out the application name, description and use %s as the website.</p>', Timeline_Twitter_Feed::TEXTDOMAIN ),
			'https://dev.twitter.com/apps/new',
			esc_url( home_url( '/' ) )
		);
		
		$desc .= sprintf(
			__( '<p class="description">If you do not know how to find all this information, <a href="%s" target="_blank">this video</a> will help you. NOTE: you can use one app for multiple sites/domains.</p>', Timeline_Twitter_Feed::TEXTDOMAIN ),
			'http://www.youtube.com/watch?v=CVz1MjqTXMg'
		);
		
		$sections = array(
			 array(
			 	'id'    => Timeline_Twitter_Feed_Options::BASIC_OPTIONS,
				'title' => __( 'Basic Settings', Timeline_Twitter_Feed::TEXTDOMAIN ),
				'desc'  => $desc 
			),
			array(
				'id'    => Timeline_Twitter_Feed_Options::ADVANCED_OPTIONS,
				'title' => __( 'Advanced Settings', Timeline_Twitter_Feed::TEXTDOMAIN ) 
			),
			array(
				'id'    => Timeline_Twitter_Feed_Options::OTHER_OPTIONS,
				'title' => __( 'Other Settings', Timeline_Twitter_Feed::TEXTDOMAIN ) 
			),
			array(
				'id'    => Timeline_Twitter_Feed_Options::HELP,
				'title' => __( 'Help', Timeline_Twitter_Feed::TEXTDOMAIN ),
				'desc'  => $this->get_help_section() 
			) 
		);
		
		return $sections;
	}
	
	public function get_help_section() {
        $output = '<h4>' . __( 'Using the Shortcode', Timeline_Twitter_Feed::TEXTDOMAIN ) . '</h4>'
                . '<p>' . __( 'You can insert a Twitter Feed manually in any page, post, template or widget. Here\'s an example of using the shortcode', Timeline_Twitter_Feed::TEXTDOMAIN ) . ':<br />'
                . '<code>[timeline-twitter-feed]</code></p>'
                . '<p>' . __( 'You can also insert the shortcode directly into your theme with PHP', Timeline_Twitter_Feed::TEXTDOMAIN ) . ':<br />'
                . '<code>&lt;?php echo do_shortcode( \'[timeline-twitter-feed]\' ); ?&gt;</code></p>'
                . '<p>' . __( 'There\'s an extra option you can choose in the shortcode. You can either use the standard setting above or add tweets to the feed with certain hashtags in it.', Timeline_Twitter_Feed::TEXTDOMAIN ) . '<br />'
                . __( 'Here\'s an example of using the hashtag option', Timeline_Twitter_Feed::TEXTDOMAIN ) . ':<br />'
                . '<code>[timeline-twitter-feed terms="#WP OR #WordPress"]</code></p>';

        return $output;
    }
	
	public function get_settings_fields() {
		$settings_fields = array(
			 Timeline_Twitter_Feed_Options::BASIC_OPTIONS => array(
				 array(
				 	'name'  => Timeline_Twitter_Feed_Options::CONSUMER_KEY,
					'label' => __( 'Consumer Key', Timeline_Twitter_Feed::TEXTDOMAIN ) . ' <span class="red">*</span>',
					'desc'  => '',
					'type'  => 'text' 
					//'sanitize_callback' => 'intval',
				),
				array(
					'name'  => Timeline_Twitter_Feed_Options::CONSUMER_SECRET,
					'label' => __( 'Consumer Secret', Timeline_Twitter_Feed::TEXTDOMAIN ) . ' <span class="red">*</span>',
					'desc'  => '',
					'type'  => 'text' 
				),
				array(
					'name'  => Timeline_Twitter_Feed_Options::ACCESS_TOKEN,
					'label' => __( 'Access Token', Timeline_Twitter_Feed::TEXTDOMAIN ) . ' <span class="red">*</span>',
					'desc'  => '',
					'type'  => 'text' 
				),
				array(
					'name'  => Timeline_Twitter_Feed_Options::ACCESS_SECRET,
					'label' => __( 'Access Token Secret', Timeline_Twitter_Feed::TEXTDOMAIN ) . ' <span class="red">*</span>',
					'desc'  => '',
					'type'  => 'text' 
				),
				array(
					'name'  => Timeline_Twitter_Feed_Options::USERNAME,
					'label' => __( 'Twitter Username', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'desc'  => __( 'fill in your Twitter username without the @', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'type'  => 'text' 
				),
				array(
					'name'    => Timeline_Twitter_Feed_Options::NUM_TWEETS,
					'label'   => __( 'Number of Tweets to show', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'desc'    => '',
					'type'    => 'select',
					'options' => array(
						'1'  => '1',
						'2'  => '2',
						'3'  => '3',
						'4'  => '4',
						'5'  => '5',
						'6'  => '6',
						'7'  => '7',
						'8'  => '8',
						'9'  => '9',
						'10' => '10' 
					) 
				) 
			),
			Timeline_Twitter_Feed_Options::ADVANCED_OPTIONS => array(
				 array(
				 	'name'    => Timeline_Twitter_Feed_Options::NUM_HASHTAG_TWEETS,
					'label'   => __( 'Number of Hashtag Tweets to get in advance', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'desc'    => __( 'Tweak this option to get more ore less `hashtag tweets` in advance. No need for this if you don\'t show tweets based on hashtags.', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'type'    => 'select',
					'options' => array(
						'1'  => '1',
						'2'  => '2',
						'3'  => '3',
						'4'  => '4',
						'5'  => '5',
						'6'  => '6',
						'7'  => '7',
						'8'  => '8',
						'9'  => '9',
						'10' => '10',
						'11' => '11',
						'12' => '12',
						'13' => '13',
						'14' => '14',
						'15' => '15',
						'16' => '16',
						'17' => '17',
						'18' => '18',
						'19' => '19',
						'20' => '20' 
					) 
				),
				array(
					'name'  => Timeline_Twitter_Feed_Options::ONLY_HASHTAGS,
					'label' => __( 'Only show tweets from hashtags', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'desc'  => __( 'enable this to disable showing tweets from (your) username(s)', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'type'  => 'checkbox' 
				),
				array(
					'name'  => Timeline_Twitter_Feed_Options::TWITTER_JS,
					'label' => __( 'Enable Twitter JS', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'desc'  => __( "disable this if Twitter's JS call is already enabled elsewhere or if the follow button is not activated", Timeline_Twitter_Feed::TEXTDOMAIN ),
					'type'  => 'checkbox' 
				),
				array(
					'name'  => Timeline_Twitter_Feed_Options::PROFILE_IMG,
					'label' => __( 'Show profile pictures', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'desc'  => '',
					'type'  => 'checkbox' 
				),
				array(
					'name'  => Timeline_Twitter_Feed_Options::HTTPS_IMG,
					'label' => __( 'Use HTTPS for pictures', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'desc'  => '',
					'type'  => 'checkbox' 
				),
				array(
					'name'  => Timeline_Twitter_Feed_Options::FOLLOW_BUTTON,
					'label' => __( 'Append Twitter Button', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'desc'  => __( 'inserts a Twitter follow button beneath the Twitter feed', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'type'  => 'checkbox' 
				),
				array(
					'name'    => Timeline_Twitter_Feed_Options::LANGUAGE,
					'label'   => __( 'Button Language', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'desc'    => '',
					'type'    => 'select',
					'options' => array(
						 'en' => __( 'English', Timeline_Twitter_Feed::TEXTDOMAIN ),
						 'nl' => __( 'Dutch', Timeline_Twitter_Feed::TEXTDOMAIN ) 
					) 
				),
				array(
					'name'  => Timeline_Twitter_Feed_Options::LARGE_BUTTON,
					'label' => __( 'Large Twitter Button', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'desc'  => '',
					'type'  => 'checkbox' 
				),
				array(
					'name'  => Timeline_Twitter_Feed_Options::FOLLOWER_COUNT,
					'label' => __( 'Show Follower Count', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'desc'  => __( 'shows the number of followers by your @username for the follow button', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'type'  => 'checkbox' 
				),
				array(
					'name'  => Timeline_Twitter_Feed_Options::USER_LINKS,
					'label' => __( 'Link to @usernames', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'desc'  => __( 'inserts a link to any @username who is mentioned in a tweet', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'type'  => 'checkbox' 
				),
				array(
					'name'  => Timeline_Twitter_Feed_Options::HASH_LINKS,
					'label' => __( 'Link to #hashtags', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'desc'  => __( 'inserts a link to any #hashtag that is mentioned in a tweet', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'type'  => 'checkbox' 
				),
				array(
					'name'  => Timeline_Twitter_Feed_Options::TIMESTAMP,
					'label' => __( 'Show Timestamp', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'desc'  => __( 'whether you want the tweet to append xx minutes/hours/days ago from the tweet', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'type'  => 'checkbox' 
				),
				array(
					'name'  => Timeline_Twitter_Feed_Options::LINK_TO_TWEET,
					'label' => __( 'Link to Tweet', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'desc'  => __( 'this will turn the timestamp into a a link to the tweet', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'type'  => 'checkbox' 
				),
				array(
					'name'  => Timeline_Twitter_Feed_Options::USE_CSS,
					'label' => __( 'Use CSS file', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'desc'  => __( 'disable this to use your own CSS file', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'type'  => 'checkbox' 
				),
				array(
					'name'  => Timeline_Twitter_Feed_Options::AUTH,
					'label' => __( 'Show Username before Tweet', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'desc'  => __( 'inserts @username: before each tweet, which links to that username', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'type'  => 'checkbox' 
				),
				array(
					'name'  => Timeline_Twitter_Feed_Options::ERROR_MESSAGE,
					'label' => __( 'Error Message', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'desc'  => __( 'error message to show if the twitter feed can\'t show tweets', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'type'  => 'text' 
				) 
			),
			Timeline_Twitter_Feed_Options::OTHER_OPTIONS => array(
				 array(
				 	'name'    => Timeline_Twitter_Feed_Options::CACHE_EXPIRE,
					'label'   => __( 'Fragment cache time', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'desc'    => __( 'caching time in seconds', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'type'    => 'text',
					'default' => '300' 
				),
				array(
					'name'  => Timeline_Twitter_Feed_Options::DO_AJAX_UPDATES,
					'label' => __( 'Do AJAX updates?', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'desc'  => __( 'Especially useful when using a static page caching plugin like W3 Total Cache', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'type'  => 'checkbox' 
				),
				array(
					'name'  => Timeline_Twitter_Feed_Options::KEYWORD_FILTER,
					'label' => __( 'Discard Tweets', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'desc'  => __( 'discard tweets with the above comma-seperated words (lowercase)', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'type'  => 'textarea' 
				),
				array(
					'name'  => Timeline_Twitter_Feed_Options::APPROVAL_FIRST,
					'label' => __( 'Approve Tweets first', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'desc'  => __( 'use this option if you are showing tweets from hashtags and want to moderate them first', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'type'  => 'checkbox' 
				),
				array(
					'name'  => Timeline_Twitter_Feed_Options::CUSTOM_CSS,
					'label' => __( 'Extra CSS', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'desc'  => __( 'use the above field to add extra CSS rules (without the &lt;style&gt; tags)', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'type'  => 'textarea' 
				),
				array(
					'name'  => Timeline_Twitter_Feed_Options::PREFIX,
					'label' => __( 'Prefix for the timestamp', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'desc'  => '',
					'type'  => 'text' 
				),
				array(
					'name'  => Timeline_Twitter_Feed_Options::SECONDS,
					'label' => __( 'Seconds', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'desc'  => '',
					'type'  => 'text' 
				),
				array(
					'name'  => Timeline_Twitter_Feed_Options::MINUTES,
					'label' => __( 'Minutes', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'desc'  => '',
					'type'  => 'text' 
				),
				array(
					'name'  => Timeline_Twitter_Feed_Options::HOUR,
					'label' => __( 'Hour', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'desc'  => '',
					'type'  => 'text' 
				),
				array(
					'name'  => Timeline_Twitter_Feed_Options::HOURS,
					'label' => __( 'Hours', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'desc'  => '',
					'type'  => 'text' 
				),
				array(
					'name'  => Timeline_Twitter_Feed_Options::DAY,
					'label' => __( 'Day', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'desc'  => '',
					'type'  => 'text' 
				),
				array(
					'name'  => Timeline_Twitter_Feed_Options::DAYS,
					'label' => __( 'Days', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'desc'  => '',
					'type'  => 'text' 
				),
				array(
					'name'  => Timeline_Twitter_Feed_Options::SUFFIX,
					'label' => __( 'Suffix for the timestamp', Timeline_Twitter_Feed::TEXTDOMAIN ),
					'desc'  => '',
					'type'  => 'text' 
				) 
			) 
		);
		
		return $settings_fields;
	}
	
	public function add_plugin_options_page() {
		add_options_page(
			Timeline_Twitter_Feed::PLUGIN_NAME,
			Timeline_Twitter_Feed::PLUGIN_NAME,
			'manage_options',
			Timeline_Twitter_Feed::TEXTDOMAIN, array( $this, 'print_plugin_settings_page' )
		);
	}
	
	public function print_plugin_settings_page() {
		echo '<div class="wrap">';
		echo '<h2>' . Timeline_Twitter_Feed::PLUGIN_NAME . '</h2>';
		
		$this->settings_api->show_navigation();
		$this->settings_api->show_forms();
		
		echo '</div>';
	}
	
	public function print_missing_api_authentication_notice() {
		if ( $this->is_missing_api_authentication_keys() ) {
			printf(
				'<div class="error"><p>%s <a href="options-general.php?page=%s#%s"><input type="submit" value="%s" class="button-secondary" style="vertical-align: baseline;" /></a></p></div>',
				__( 'You need to enter your Twitter API settings for the Timeline Twitter Feed plugin to work.', Timeline_Twitter_Feed::TEXTDOMAIN ),
				Timeline_Twitter_Feed::TEXTDOMAIN, Timeline_Twitter_Feed_Options::BASIC_OPTIONS,
				__( 'Configure', Timeline_Twitter_Feed::TEXTDOMAIN )
			);
		}
	}
	
	public function print_wp_override_css() {
		echo '<style>label{font-size:13px;font-style:italic;color:#666}.form-table td{padding:15px 10px}input.regular-text{width:30em}.red{color:red}#ttf_help p.submit{display:none}</style>';
	}
	
	public function delete_cached_feeds() {
		if ( ! isset( $_GET[ 'page' ] ) || Timeline_Twitter_Feed::TEXTDOMAIN !== $_GET[ 'page' ] ) {
			return null;
		}
		
		if ( isset( $_GET[ 'settings-updated' ] ) && 'true' === $_GET[ 'settings-updated' ] ) {
			foreach ( get_option( Timeline_Twitter_Feed_Options::HASH_KEYS ) as $hash_key ) {
				delete_transient( $hash_key );
			}
		}
	}
	
	public function is_missing_api_authentication_keys() {
		$basic_options = get_option( Timeline_Twitter_Feed_Options::BASIC_OPTIONS );
		return ( false !== array_search( '', array(
			 $basic_options[ Timeline_Twitter_Feed_Options::CONSUMER_KEY ],
			$basic_options[ Timeline_Twitter_Feed_Options::CONSUMER_SECRET ],
			$basic_options[ Timeline_Twitter_Feed_Options::ACCESS_TOKEN ],
			$basic_options[ Timeline_Twitter_Feed_Options::ACCESS_SECRET ] 
		) ) );
	}
	
}

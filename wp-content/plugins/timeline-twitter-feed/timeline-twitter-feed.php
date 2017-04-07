<?php
/**
 * Plugin Name: Timeline Twitter Feed
 * Plugin URI:  http://wordpress.org/plugins/timeline-twitter-feed/
 * Description: Timeline Twitter Feed let's you output your timeline feed and multiple hashtags into your WordPress site as flat HTML.
 * Version:     1.3
 * Author:      Ezra Verheijen
 * Author URI:  http://profiles.wordpress.org/ezraverheijen/
 * License:     GPL v3
 * Text Domain: timeline-twitter-feed
 * 
 * Copyright (c) 2014, Ezra Verheijen
 * 
 * Forked from jTwits by Floris P. Lof which was forked from Twitter Feed Pro by Alex Moss.
 * 
 * @uses weDevs Settings API from Tareq Hasan <https://github.com/tareq1988/wordpress-settings-api-class>
 * @uses TwitterWP from Justin Sternberg <https://github.com/jtsternberg/TwitterWP>
 * 
 * Timeline Twitter Feed is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 * 
 * Timeline Twitter Feed is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have recieved a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses>.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // exit if accessed directly
}

if ( ! class_exists( 'Timeline_Twitter_Feed' ) ) {
	foreach ( glob( dirname( __FILE__ ) . '/includes/*.php' ) as $file ) {
		require_once( $file );
	}
	
	class Timeline_Twitter_Feed {
		const PLUGIN_NAME    = 'Timeline Twitter Feed';
		const PLUGIN_VERSION = '1.3';
		const TEXTDOMAIN     = 'timeline-twitter-feed';

		private $other_options = array();

		function __construct() {
			$this->other_options = get_option( Timeline_Twitter_Feed_Options::OTHER_OPTIONS );
			
			add_action( 'widgets_init', array( $this, 'register_twitter_feed_widget' ) );
			add_action( 'plugins_loaded', array( $this, 'load_plugin_translation' ) );
			
			new Timeline_Twitter_Feed_Backend();
			new Timeline_Twitter_Feed_Frontend();
			new Timeline_Twitter_Feed_Shortcode();

			if ( 'on' === $this->other_options[Timeline_Twitter_Feed_Options::APPROVAL_FIRST] ) {
				new Timeline_Twitter_Feed_Dashboard_Widget();
			}

			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_plugin_settings_link' ) );

			register_activation_hook( __FILE__, array( $this, 'add_default_options_to_database' ) );
			register_deactivation_hook( __FILE__, array( $this, 'add_plugin_delete_helper' ) );
		}

		public function register_twitter_feed_widget() {
			register_widget( 'Timeline_Twitter_Feed_Widget' );
		}
		
		public function load_plugin_translation() {
			load_plugin_textdomain(
				self::TEXTDOMAIN,
				false,
				dirname( plugin_basename( __FILE__ ) ) . '/languages/'
			);
		}

		public function add_default_options_to_database() {
			add_option( Timeline_Twitter_Feed_Options::BASIC_OPTIONS, array(
				Timeline_Twitter_Feed_Options::CONSUMER_KEY    => '',
				Timeline_Twitter_Feed_Options::CONSUMER_SECRET => '',
				Timeline_Twitter_Feed_Options::ACCESS_TOKEN    => '',
				Timeline_Twitter_Feed_Options::ACCESS_SECRET   => '',
				Timeline_Twitter_Feed_Options::USERNAME        => '',
				Timeline_Twitter_Feed_Options::NUM_TWEETS      => '3',
			) );

			add_option( Timeline_Twitter_Feed_Options::ADVANCED_OPTIONS, array(
				Timeline_Twitter_Feed_Options::NUM_HASHTAG_TWEETS => '3',
				Timeline_Twitter_Feed_Options::ONLY_HASHTAGS      => 'off',
				Timeline_Twitter_Feed_Options::TWITTER_JS         => 'on',
				Timeline_Twitter_Feed_Options::PROFILE_IMG        => 'off',
				Timeline_Twitter_Feed_Options::HTTPS_IMG          => 'off',
				Timeline_Twitter_Feed_Options::FOLLOW_BUTTON      => 'on',
				Timeline_Twitter_Feed_Options::LANGUAGE           => 'en',
				Timeline_Twitter_Feed_Options::LARGE_BUTTON       => 'off',
				Timeline_Twitter_Feed_Options::FOLLOWER_COUNT     => 'on',
				Timeline_Twitter_Feed_Options::USER_LINKS         => 'on',
				Timeline_Twitter_Feed_Options::HASH_LINKS         => 'on',
				Timeline_Twitter_Feed_Options::TIMESTAMP          => 'on',
				Timeline_Twitter_Feed_Options::LINK_TO_TWEET      => 'on',
				Timeline_Twitter_Feed_Options::USE_CSS            => 'on',
				Timeline_Twitter_Feed_Options::AUTH               => 'on',
				Timeline_Twitter_Feed_Options::ERROR_MESSAGE      => 'Unable to show tweets right now...',
			) );

			add_option( Timeline_Twitter_Feed_Options::OTHER_OPTIONS, array(
				Timeline_Twitter_Feed_Options::CACHE_EXPIRE    => '300',
				Timeline_Twitter_Feed_Options::DO_AJAX_UPDATES => 'off',
				Timeline_Twitter_Feed_Options::KEYWORD_FILTER  => '',
				Timeline_Twitter_Feed_Options::APPROVAL_FIRST  => 'off',
				Timeline_Twitter_Feed_Options::CUSTOM_CSS      => '',
				Timeline_Twitter_Feed_Options::PREFIX          => '(about',
				Timeline_Twitter_Feed_Options::SECONDS         => 'seconds',
				Timeline_Twitter_Feed_Options::MINUTES         => 'minutes',
				Timeline_Twitter_Feed_Options::HOUR            => 'hour',
				Timeline_Twitter_Feed_Options::HOURS           => 'hours',
				Timeline_Twitter_Feed_Options::DAY             => 'day',
				Timeline_Twitter_Feed_Options::DAYS            => 'days',
				Timeline_Twitter_Feed_Options::SUFFIX          => 'ago)',
			) );

			add_option( Timeline_Twitter_Feed_Options::APPROVED, array() );
			add_option( Timeline_Twitter_Feed_Options::HASH_KEYS, array() );
		}

		public function add_plugin_settings_link( $links ) {
			array_unshift( $links, sprintf(
				'<a href="options-general.php?page=%s">%s</a>',
				self::TEXTDOMAIN,
				__( 'Settings' ) // translated by WordPress
			) );
			return $links;
		}

		/**
		 * Add an entry to the database which holds the current database entries of this plugin.
		 * 
		 * @see uninstall.php
		 */
		public function add_plugin_delete_helper() {
			add_option( 'ttf_delete_helper', array(
				Timeline_Twitter_Feed_Options::BASIC_OPTIONS,
				Timeline_Twitter_Feed_Options::ADVANCED_OPTIONS,
				Timeline_Twitter_Feed_Options::OTHER_OPTIONS,
				Timeline_Twitter_Feed_Options::HELP,
				Timeline_Twitter_Feed_Options::APPROVED,
				Timeline_Twitter_Feed_Options::OUTPUT,
				Timeline_Twitter_Feed_Options::HASH_KEYS,
			) );
		}
	}

	new Timeline_Twitter_Feed();
}

<?php

class Timeline_Twitter_Feed_Dashboard_Widget {
	private $advanced_options = array();

	function __construct() {
		$this->advanced_options = get_option( Timeline_Twitter_Feed_Options::ADVANCED_OPTIONS );
		
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_widget_css' ) );
		add_action( 'wp_ajax_approve_tweet', array( $this, 'approve_tweet' ) );
	}

	public function add_dashboard_widget() {
		wp_add_dashboard_widget( 'twitter_feed_approval', __( 'Approve #hashtag Tweets', Timeline_Twitter_Feed::TEXTDOMAIN ), array( $this, 'print_dashboard_widget' ) );
	}

	public function enqueue_widget_css() {
		global $pagenow;

        if ( 'index.php' === $pagenow ) {
            wp_enqueue_style(
				'timeline-twitter-feed-widget',
				esc_url( plugins_url( 'res/css/timeline-twitter-feed-widget.css', dirname( __FILE__ ) ) ),
				array(),
				Timeline_Twitter_Feed::PLUGIN_VERSION
			);
        }
    }

	public function approve_tweet() {
		header( 'Content-Type: application/json' );

		if ( isset( $_POST['tweetId'] ) ) {
			$approved = get_option( Timeline_Twitter_Feed_Options::APPROVED );

			$count_approved = count( $approved );
			for ( $i = 0; $i < $count_approved; $i++ ) {
				if ( $approved[$i]->id == $_POST['tweetId'] ) {
					$approved[$i]->approved = 1;
					break;
				}
			}

			update_option( Timeline_Twitter_Feed_Options::APPROVED, $approved );
		}

		echo json_encode( $this->get_unapproved_tweets() );

		exit;
	}

	public function print_dashboard_widget() {
		?>
		<script type="text/javascript">
			jQuery.post(
				ajaxurl,
				{
					action: 'approve_tweet'
				}
			).success(showTweets)
			.error(function(data) {
				jQuery('#ttf_unapproved_tweets').html(data.responseText);
			});

			function showTweets(tweets) {
				jQuery('#ttf_unapproved_tweets').html("");
				jQuery('#ttf_unapproved_tweets').append(tweets);
				for(var i = 0; i < tweets.length; i++) {
					jQuery('#ttf_unapproved_tweets').append(tweets[i].display);
				}
			}

			function doApproveTweet(tweetId) {
				jQuery.post(
					ajaxurl,
					{
						action: 'approve_tweet',
						tweetId: tweetId
					}
				).success(showTweets)
				.error(function(data) {
					jQuery('#ttf_unapproved_tweets').html(data.responseText);	
				});
			}
		</script>
		<div id="ttf_unapproved_tweets">
			<!-- Unapproved tweets are shown here -->
		</div>
		<?php
	}

	public function get_unapproved_tweets() {
		$tweets = get_option( Timeline_Twitter_Feed_Options::OUTPUT );
		if ( ! $tweets ) {
			return $this->get_all_approved_text();
		}

		$approved = get_option( Timeline_Twitter_Feed_Options::APPROVED );

		$result = array();
		$count_approved = count( $approved );
		foreach ( $tweets as $tweet ) {
			for ( $n = 0; $n < $count_approved; $n++ ) {
				if ( $approved[$n]->id == $tweet->id_str && 0 == $approved[$n]->approved ) {
					$unapproved_tweet = new stdClass;
					$unapproved_tweet->display = $this->format_tweet( $tweet );
					$result[] = $unapproved_tweet;
				}
			}
		}

		if ( 0 == count( $result ) ) { 
			$obj = new stdClass;
			$obj->display = $this->get_all_approved_text();
			$result[] = $obj;
		}

		return $result;
	}

	public function get_all_approved_text() {
		return '<div>' . __( 'All tweets are approved', Timeline_Twitter_Feed::TEXTDOMAIN ) . '! :)</div>';
	}

	public function format_tweet( $tweet ) {
		$text = utf8_decode( $tweet->text ); // put the smilies back in :)

		$output = '<div class="ttf-tweet">';

		if ( 'on' === $this->advanced_options[Timeline_Twitter_Feed_Options::AUTH] ) {
			$output .= sprintf(
				'<div class="ttf-tweet-row"><span class="ttf-tweet-user-name"><a class="ttf-tweet-screen-name" href="https://twitter.com/intent/user?screen_name=%s" target="_blank" rel="nofollow">%s</a></span> <span class="ttf-tweet-full-name">@%s</span></div>',
				$tweet->user->screen_name,
				esc_html( $tweet->user->name ),
				esc_html( $tweet->user->screen_name )
			);
		}

		$output .= '<div class="ttf-tweet-row"><div class="ttf-tweet-text">';

		$text = preg_replace( '/(http:\/\/)(.*?)\/([\w\.\/\&\=\?\-\,\:\;\#\_\~\%\+]*)/', '<a href="\\0" target="_blank" rel="nofollow">\\0</a>', $text );

		if ( 'on' === $this->advanced_options[Timeline_Twitter_Feed_Options::USER_LINKS] ) {
			$text = preg_replace( '(@([a-zA-Z0-9\_]+))', '<a href="https://twitter.com/intent/user?screen_name=\\1" target="_blank" rel="nofollow">\\0</a>', $text );
		}
		
		if ( 'on' === $this->advanced_options[Timeline_Twitter_Feed_Options::HASH_LINKS] ) {
			$text = preg_replace( '(#([a-zA-Z0-9\_]+))', '<a href="http://twitter.com/search?q=%23\\1" target="_blank" rel="nofollow">\\0</a>', $text );
		}

		$output .= $text . '</div></div>';
		
		$output .= '<input class="button button-primary button-approve" type="submit" onClick="doApproveTweet(\'' . $tweet->id_str . '\')" value="' . __( 'Approve', Timeline_Twitter_Feed::TEXTDOMAIN ) . '" />';

		if ( 'on' === $this->advanced_options[Timeline_Twitter_Feed_Options::PROFILE_IMG] ) {
			$output .= '</div>';
		}

		$output .= '</div>';

		return $output;
	}
}

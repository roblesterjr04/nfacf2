<?php

if ( ! class_exists( 'TwitterWP' ) ) {
	// Include Twitter API class
	require dirname( dirname( __FILE__ ) ) . '/lib/class-twitterwp.php';
}

class Timeline_Twitter_Feed_Shortcode {
	private $basic_options    = array();
	private $advanced_options = array();
	private $other_options    = array();

	private $approved = array();
	
	public function __construct() {
		$this->basic_options    = get_option( Timeline_Twitter_Feed_Options::BASIC_OPTIONS );
		$this->advanced_options = get_option( Timeline_Twitter_Feed_Options::ADVANCED_OPTIONS );
		$this->other_options    = get_option( Timeline_Twitter_Feed_Options::OTHER_OPTIONS );
		
		$approved = get_option( Timeline_Twitter_Feed_Options::APPROVED );
		if ( ! is_null( $approved ) ) {
			$this->approved = $approved;
		}
		
		add_shortcode( 'timeline-twitter-feed', array( $this, 'generate_tweets' ) );
        
        // add shortcode support for widgets
		add_filter( 'widget_text', 'do_shortcode' );
	}

	public function generate_tweets( $atts, $content, $tag ) {
		if ( $this->has_missing_keys_or_secrets() ) {
			return $this->print_error_message();
		}

		if ( ! $atts ) {
			$atts = array();
		}

		$hash_keys = get_option( Timeline_Twitter_Feed_Options::HASH_KEYS, array() );

		// Make a unique hashkey for this query
		$hash_key = md5( 'twitterfeed-' . implode( '-', $atts ) );

		if ( ! in_array( $hash_key, $hash_keys ) ) {
			$hash_keys[] = $hash_key;
		}
		
		/**
		 * Update database with all hash keys for easy cache flushing.
		 *
		 * @see Timeline_Twitter_Feed_Backend::delete_cached_feeds()
		 */
		update_option( Timeline_Twitter_Feed_Options::HASH_KEYS, $hash_keys );

		// Delete hash key (for dev and debug only!)
		// delete_transient( $hash_key );
		
		// Create new feed if cached version doesn't exist
		if ( false === ( $output = get_transient( $hash_key ) ) ) {

			$twitter_app = $this->initiate_twitter_app();
			if ( is_null( $twitter_app ) || ! is_object( $twitter_app ) ) {
				return null;
			}
			
			$shortcode = '{' . $tag;
			
			if ( isset( $atts['terms'] ) ) {
				$shortcode .= " terms='" . esc_attr( $atts['terms'] ) . "'";
			}
			
			$shortcode .= '}';
			
			$output = sprintf( '<div class="timeline-twitter-feed" id="%s" data-shortcode="%s">', esc_attr( $hash_key ), $shortcode );

			$num_tweets = (int) $this->basic_options[Timeline_Twitter_Feed_Options::NUM_TWEETS];

			$tweets = array();
			if ( isset( $atts['username'] ) ) {
				// @props apkoponen -> https://github.com/ezraverheijen/timeline-twitter-feed/pull/3
                $tweets = $twitter_app->get_tweets( $atts['username'], $num_tweets );
				$num_hashtag_tweets = $num_tweets;
            } elseif ( 'on' !== $this->advanced_options[Timeline_Twitter_Feed_Options::ONLY_HASHTAGS] ) {
				$tweets = $twitter_app->get_tweets( $this->basic_options[Timeline_Twitter_Feed_Options::USERNAME], $num_tweets );
				$num_hashtag_tweets = $num_tweets;
			} else {
				$num_hashtag_tweets = (int) $this->advanced_options[Timeline_Twitter_Feed_Options::NUM_HASHTAG_TWEETS];
			}

			if ( isset( $atts['terms'] ) ) {
				$terms         = str_replace( '#', '%23', $atts['terms'] );
				$search_tweets = $twitter_app->get_search_results( $terms, $num_hashtag_tweets );
				$statuses      = $search_tweets->statuses;
				
				if ( $statuses ) {
					$statuses = $this->filter_unwanted_tweets( $statuses );

					foreach ( $statuses as $status ) {
						$status->text = utf8_encode( $status->text ); // smilies etc. will break the dashboard widget
					}
					
					update_option( Timeline_Twitter_Feed_Options::OUTPUT, array_merge( $tweets, $statuses ) );

					foreach ( $statuses as $status ) {
						$status->text = utf8_decode( $status->text );
					}

					if ( 'on' === $this->other_options[Timeline_Twitter_Feed_Options::APPROVAL_FIRST] ) {
						$approved_tweets = $this->get_approved_tweets( $statuses );
					} else {
						$approved_tweets = $statuses;
					}

					$tweets = array_merge( $tweets, $approved_tweets );

					shuffle( $tweets );
				}

			}

			foreach ( array_slice( $tweets, 0, $num_tweets ) as $tweet ) {
				$output .= $this->generate_tweet( $tweet );
			}

			$output .= '</div>';
			
			if ( 'on' === $this->advanced_options[Timeline_Twitter_Feed_Options::FOLLOW_BUTTON] ) {
				$output .= $this->get_follow_button();
			}
			
			// Cache results
			$cache_expire = ! empty( $this->other_options[Timeline_Twitter_Feed_Options::CACHE_EXPIRE] ) ? (int) $this->other_options[Timeline_Twitter_Feed_Options::CACHE_EXPIRE] : 300;
			set_transient( $hash_key, $output, $cache_expire );

		}

		return $output;
	}
	
	public function generate_tweet( $tweet ) {
		$text = esc_html( $tweet->text ); // prepare tweet for use in HTML

		$output = '<div class="ttf-tweet">';

		if ( 'on' === $this->advanced_options[Timeline_Twitter_Feed_Options::PROFILE_IMG] ) {
			$output .= $this->get_profile_image_url( $tweet );
			$output .= '<div class="ttf-tweet-content">';
		}

		if ( 'on' === $this->advanced_options[Timeline_Twitter_Feed_Options::AUTH] ) {
			$output .= sprintf(
				'<div class="ttf-tweet-row"><span class="ttf-tweet-user-name"><a class="ttf-tweet-screen-name" href="https://twitter.com/intent/user?screen_name=%s" target="_blank" rel="nofollow">%s</a></span> <span class="ttf-tweet-full-name">@%s</span></div>',
				$tweet->user->screen_name,
				esc_html( $tweet->user->name ),
				esc_html( $tweet->user->screen_name )
			);
		}

		$output .= '<div class="ttf-tweet-row"><div class="ttf-tweet-text">';

		$text = preg_replace( '/(http|https):\/\/([a-z0-9_\.\-\+\&\!\#\~\/\,]+)/i', '<a href="$1://$2" target="_blank" rel="nofollow">$1://$2</a>', $text );

		if ( 'on' === $this->advanced_options[Timeline_Twitter_Feed_Options::USER_LINKS] ) {
			$text = preg_replace( '/@([A-Za-z0-9_]+)/is', '<a href="https://twitter.com/$1" target="_blank">@$1</a>', $text );
		}
		
		if ( 'on' === $this->advanced_options[Timeline_Twitter_Feed_Options::HASH_LINKS] ) {
			// @props aaronrossanocomau -> https://wordpress.org/support/topic/encoding-7
			$text = preg_replace( '/(?<!&)#([A-Aa-z0-9_-]+)/is', '<a href="https://twitter.com/hashtag/$1?src=hash" target="_blank">#$1</a>', $text );
		}

		$output .= $text . '</div></div>';

		if ( 'on' === $this->advanced_options[Timeline_Twitter_Feed_Options::TIMESTAMP] ) {
			$output .= '<div class="ttf-tweet-row">' . $this->get_timestamp( $tweet ) . '</div>';
		}

		if ( 'on' === $this->advanced_options[Timeline_Twitter_Feed_Options::PROFILE_IMG] ) {
			$output .= '</div>';
		}

		$output .= '</div>';

		return $output;
	}
	
	public function get_profile_image_url( $tweet ) {
		if ( 'on' === $this->advanced_options[Timeline_Twitter_Feed_Options::HTTPS_IMG] ) {
			$img = $tweet->user->profile_image_url_https;
		} else {
			$img = $tweet->user->profile_image_url;
		}
		
		$img = apply_filters( 'ttf-profile-image', $img );
		
		return sprintf(
			'<div class="ttf-tweet-image"><a href="https://twitter.com/intent/user?screen_name=%s" rel="nofollow"><img alt="%s" src="%s" height="48" width="48" title="%s"></a></div>',
			$tweet->user->screen_name,
			esc_attr( $tweet->user->screen_name ),
			esc_url( $img ),
			esc_attr( $tweet->user->screen_name )
		);	
	}

	public function get_timestamp( $tweet ) {
		$now     = time();
		$seconds = ( $now - ( strtotime( $tweet->created_at ) ) );

		if ( $seconds < 60 ) {
			$time  = $seconds;
			$stamp = $this->other_options[Timeline_Twitter_Feed_Options::SECONDS];

		} elseif ( $seconds < 3600 ) {
			$time  = ( floor( $seconds / 60 ) );
			$stamp =  $this->other_options[Timeline_Twitter_Feed_Options::MINUTES];

		} elseif ( $seconds < 7200 ) {
			$time  = '1';
			$stamp = $this->other_options[Timeline_Twitter_Feed_Options::HOUR];

		} elseif ( $seconds < 86400 ) {
			$time  = ( floor( $seconds / 3600 ) );
			$stamp = $this->other_options[Timeline_Twitter_Feed_Options::HOURS];

		} elseif ( $seconds < 172800 ) {
			$time  = '1';
			$stamp = $this->other_options[Timeline_Twitter_Feed_Options::DAY];

		} else {
			$time  = ( floor( $seconds / 86400 ) );
			$stamp = $this->other_options[Timeline_Twitter_Feed_Options::DAYS];
		}

		return $this->get_timestamp_string( $time, $stamp, $tweet );
	}

	public function get_timestamp_string( $time, $stamp, $tweet ) {
		$timestamp = sprintf(
			'<span class="ttf-timestamp">%s %s %s %s </span>',
			esc_html( $this->other_options[Timeline_Twitter_Feed_Options::PREFIX] ),
			esc_html( $time ),
			esc_html( $stamp ),
			esc_html( $this->other_options[Timeline_Twitter_Feed_Options::SUFFIX] )
		);

		if ( 'on' === $this->advanced_options[Timeline_Twitter_Feed_Options::LINK_TO_TWEET] ) {
			$timestamp = sprintf(
				'<a class="ttf-tweet-timestamp" href="http://twitter.com/%s/statuses/%s" target="_blank" rel="nofollow">%s</a>',
				$tweet->user->screen_name,
				$tweet->id_str,
				$timestamp
			);
		}

		return $timestamp;
	}
	
	public function get_follow_button() {
		$follower_count = ('on' === $this->advanced_options[Timeline_Twitter_Feed_Options::FOLLOWER_COUNT]) ? 'true' : 'false';
		$button_size    = ('on' === $this->advanced_options[Timeline_Twitter_Feed_Options::LARGE_BUTTON]) ? 'large' : 'medium';

		$username = $this->basic_options[Timeline_Twitter_Feed_Options::USERNAME];

		return sprintf(
			'<a href="%s" class="twitter-follow-button" data-show-count="%s" data-lang="%s" data-size="%s">Follow @%s</a>',
			esc_url( 'https://twitter.com/' . $username ),
			$follower_count,
			$this->advanced_options[Timeline_Twitter_Feed_Options::LANGUAGE],
			$button_size,
			esc_html( $username )
		);
	}
	
	public function has_missing_keys_or_secrets() {
        return ( false !== array_search( '', array(
            $this->basic_options[Timeline_Twitter_Feed_Options::CONSUMER_KEY],
            $this->basic_options[Timeline_Twitter_Feed_Options::CONSUMER_SECRET],
            $this->basic_options[Timeline_Twitter_Feed_Options::ACCESS_TOKEN],
            $this->basic_options[Timeline_Twitter_Feed_Options::ACCESS_SECRET]
        ) ) );
	}
	
	public function print_error_message() {
		if ( current_user_can( 'manage_options' ) ) {
			$error = sprintf(
				'<p class="ttf-red">%s <a href="%soptions-general.php?page=%s"><span style="text-decoration: underline;">%s</span></a> %s</p>',
				__( "Twitter is unresponsive, user doesn't exist or Feed API and/or username settings are missing.", Timeline_Twitter_Feed::TEXTDOMAIN ),
				esc_url( get_admin_url() ),
				Timeline_Twitter_Feed::TEXTDOMAIN,
				__( 'Click here', Timeline_Twitter_Feed::TEXTDOMAIN ),
				__( 'to configure and read the instructions.', Timeline_Twitter_Feed::TEXTDOMAIN )
			);
		} else {
			$error = $this->advanced_options[Timeline_Twitter_Feed_Options::ERROR_MESSAGE] ? 
			'<p>' . $this->advanced_options[Timeline_Twitter_Feed_Options::ERROR_MESSAGE] . '</p>' : 
			'<p> ' . __( 'Unable to show tweets right now...', Timeline_Twitter_Feed::TEXTDOMAIN ) . '</p>';
		}
		
		echo $error;
	}

	public function initiate_twitter_app() {
		$twitter_app = TwitterWP::start( array(
			'consumer_key'        => $this->basic_options[Timeline_Twitter_Feed_Options::CONSUMER_KEY],
			'consumer_secret'     => $this->basic_options[Timeline_Twitter_Feed_Options::CONSUMER_SECRET],
			'access_token'        => $this->basic_options[Timeline_Twitter_Feed_Options::ACCESS_TOKEN],
			'access_token_secret' => $this->basic_options[Timeline_Twitter_Feed_Options::ACCESS_SECRET],
		) );		
		
		if ( ! $twitter_app->user_exists( $this->basic_options[Timeline_Twitter_Feed_Options::USERNAME] ) ) {
			return null; // user doesn't exist
		}

		return $twitter_app;
	}
	
	public function has_blocked_words( $tweet ) {
		$tweet = $tweet->user->screen_name . ' ' . $tweet->text;
		
		$keywords = Timeline_Twitter_Feed_Functions::str_split( $this->other_options[Timeline_Twitter_Feed_Options::KEYWORD_FILTER] );
		
		$result = count( array_intersect( $keywords, explode( ' ', $tweet ) ) );
		
		if ( $result > 0 ) {
			return true; // skip this tweet, it has bad words
		}

		return false;
	}

	public function is_retweet( $tweet ) {
		return ( isset( $tweet->retweeted_status ) || false !== strpos( $tweet->text, 'RT @' ) );
	}

	public function is_username_tweet_with_hashtag( $tweet ) {
		return ( $this->basic_options[Timeline_Twitter_Feed_Options::USERNAME] == $tweet->user->screen_name );
	}

	public function filter_unwanted_tweets( $tweets ) {
		$count_tweets = count( $tweets );

		$i = 0;
		while ( $i < $count_tweets ) {
			if ( $this->has_blocked_words( $tweets[$i] ) ) {
				array_splice( $tweets, $i , 1 );
				$count_tweets--;
			} elseif ( $this->is_retweet( $tweets[$i] ) ) {
				array_splice( $tweets, $i , 1 );
				$count_tweets--;
			} elseif ( $this->is_username_tweet_with_hashtag( $tweets[$i] ) ) {
				array_splice( $tweets, $i , 1 );
				$count_tweets--;
			} else {
				$i++;
			}
		}

		return $tweets;
	}

	public function get_approved_tweets( $tweets ) {
		$approved_entries = $this->approved;
		$approved_tweets  = array();

		foreach ( $tweets as $tweet ) {
			$available = false;
			foreach ( $approved_entries as $approved_entry ) {
				if ( $approved_entry->id == $tweet->id_str ) {
					$available = true;

					if ( 1 == $approved_entry->approved ) {
						$approved_tweets[] = $tweet;
					}

					break;
				}
			}

			if ( ! $available ) {
				if ( $this->advanced_options[Timeline_Twitter_Feed_Options::NUM_HASHTAG_TWEETS] == count( $approved_entries ) ) {
					array_shift( $approved_entries );
				}

				$new_tweet = new stdClass;
				$new_tweet->approved = 0;
				$new_tweet->id = $tweet->id_str;
				$approved_entries[] = $new_tweet;
			}
		}

		update_option( Timeline_Twitter_Feed_Options::APPROVED, $approved_entries );

		return $approved_tweets;
	}
}

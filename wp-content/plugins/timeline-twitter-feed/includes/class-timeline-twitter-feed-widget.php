<?php

class Timeline_Twitter_Feed_Widget extends WP_Widget {

	function __construct() {
		parent::__construct(
			'twitter_feed_widget',
			__( 'Timeline Twitter Feed', Timeline_Twitter_Feed::TEXTDOMAIN ),
			array( 'description' => __( 'Widget for the Timeline Twitter Feed', Timeline_Twitter_Feed::TEXTDOMAIN ), )
		);
	}

	public function widget( $args, $instance ) {
		echo $args['before_widget'];
		if ( $instance['title'] ) {
			echo $args['before_title'] . $instance['title'] . $args['after_title'];
		}
		echo '<div class="twitter-feed-widget">';
		$shortcode = '[timeline-twitter-feed';
		if ( $instance['terms'] ) {
			$shortcode .= ' terms="';
			// backwards compatibility for user input from plugin version 0.9
			if ( false !== strpos( $instance['terms'], 'OR' ) ) {
				$shortcode .= esc_attr( trim( $instance['terms'] ) );
			} else {
				$hashtags = Timeline_Twitter_Feed_Functions::str_split( $instance['terms'] );
				foreach ( $hashtags as $hashtag ) {
					$hashtag = esc_attr( $hashtag );
					if ( false === strpos( $hashtag, '#' ) ) {
						$hashtag = '#' . $hashtag;
					}
					$shortcode .= $hashtag  . ' OR ';
				}
				$shortcode = rtrim( $shortcode, ' OR ' );
			}
			$shortcode .= '"';
		}
		$shortcode .= ']';
		echo do_shortcode( $shortcode );
		echo '</div>';
		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : '';
		$terms = isset( $instance['terms'] ) ? $instance['terms'] : '';
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); // will be translated by WordPress ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'terms' ); ?>"><?php _e( 'Hashtags:', Timeline_Twitter_Feed::TEXTDOMAIN ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'terms' ); ?>" name="<?php echo $this->get_field_name( 'terms' ); ?>" type="text" value="<?php echo esc_attr( $terms ); ?>">
		</p>
		<p class="description">
			<em>
				<?php _e( 'Seperate multiple hashtags with commas', Timeline_TWitter_Feed::TEXTDOMAIN ) ?>.<br />
				<?php _e( 'For example', Timeline_TWitter_Feed::TEXTDOMAIN ); ?>: #WP, #WordPress, #CMS, #blog
			</em>
		</p>
		<?php 
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ! empty( $new_instance['title'] ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['terms'] = ! empty( $new_instance['terms'] ) ? strip_tags( $new_instance['terms'] ) : '';
		return $instance;
	}

}

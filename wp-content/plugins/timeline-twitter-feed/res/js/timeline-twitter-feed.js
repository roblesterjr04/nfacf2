/**
 * Updates all the twitterfeed timelines with the same shortcode.
 */
function feedUpdate() {
	jQuery(".timeline-twitter-feed").each(function() {
		$this = jQuery(this);
		
		var data = {
			action:	"get_tweet_updates",
			id:	$this.attr("id"),
			shortcode: $this.data("shortcode")
		};
		
		$this.html("<div class='ttf-tweet'>" + feedLoadingText + "</div>"); // feedLoadingText is set in the head
		
		jQuery.post(ajaxurl, data, function(response) {
			var result = jQuery(response.output);
			var feed = result.html();
			jQuery("#" + response.id).html(feed);
		}, 'json');
	});
}

/**
 * Load all timelines and set an interval to update the timelines after document.ready.
 */
jQuery(document).ready(function() {
	setTimeout(feedUpdate, 3000); // 
	setInterval(feedUpdate, feedInterval); // feedInterval is set in the head and is the same as the transient caching time (no use to call earlier)
});

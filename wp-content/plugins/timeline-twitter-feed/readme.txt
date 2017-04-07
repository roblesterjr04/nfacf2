=== Timeline Twitter Feed ===
Contributors: ezraverheijen
Donate link: http://bit.ly/1eC8iDE
Tags: twitter, feed, tweet, tweets, twitter feed, twitter widget, twitter sidebar, social, social media
Requires at least: 3.5
Tested up to: 4.3
Stable tag: 1.3
License: GPL v3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Output timeline feeds and multiple hashtags into your WordPress site as flat HTML.

== Description ==

Timeline Twitter Feed let's you output your timeline feed and multiple hashtags into your WordPress site as flat HTML.
The output is customizable on nearly every aspect. With or without profile pictures, tweet date, usernames before tweets, hashtags and usernames as links etc. etc.
CSS styling can be added/overwrited via your theme's stylesheet or in the Timeline Twitter Feed settings screen.
There is also a widget to easily add a Twitter feed to your header, sidebar or footer, if your theme supports it.

If you have any issues using Timeline Twitter Feed, find a bug or have an idea to make the plugin even better then please [help to improve Timeline Twitter Feed](https://github.com/ezraverheijen/timeline-twitter-feed).
If you don’t report it, I can’t fix it!

== Installation ==

1. Upload the `timeline-twitter-feed` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Configure Timeline Twitter Feed by going to the corresponding menu item under 'Settings'

== Frequently Asked Questions ==

= Why is the feed not showing tweets when "hashtags only" option is selected? =

If you show tweets from hashtags, unwanted tweets will be filtered out.
Unwanted teets are tweets that contain words from the "discard tweets" option, tweets that are retweets or tweets with hashtags from your own account.
Try to set the "number of tweets to get in advance" to a higher number.

== Screenshots ==

1. Timeline Twitter Feed Settings Screen
1. Standard feed using #hashtags
1. Customized feed with profile pictures
1. Customized feed with background images
1. Approve hashtags tweets first before showing them on your site

== Changelog ==

= 1.3 =
* Enhancements:
	* The profile image URL can now be filtered with `add_filter( 'ttf-profile-image', 'your_url_filter_function_here' );`
	* Added support for username attribute (for now only via shortcode, not via the widget)

= 1.2 =
* Bugfixes:
	* Fixed a nasty bug with some emoticons/smilies breaking the feed
* Enhancements:
	* Better handling of links, hashtags and usernames in tweets
	* Better checking if tweet is a retweet

= 1.1 =
* Bugfixes:
	* Fixed bug with follow button not showing up
	* Plugin will now try to show enough items in the feed if the option to show only hashtags has been selected

= 1.0 =
* Final version

* Bugfixes:
	* Fixed AJAX updates to work with non-logged-in users
	* Fixed a bug where characters like & #039; would be treated as hashtags

* Enhancements:
	* Updated widget to have easier settings
	* Some small code improvements
	* Added possibility to have a feed only based on hashtags

= 0.9 =
* Beta release

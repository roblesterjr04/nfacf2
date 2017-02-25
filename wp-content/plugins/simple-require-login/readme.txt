=== Simple Require Login ===
Contributors: timmcdaniels
Tags: admin, login, authentication, password, roles
Requires at least: 3.5
Tested up to: 4.3.1
Stable tag: 0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Require login for content on a per page/post/custom post type basis. You can also select a specific role required to view the content.

== Description ==

WordPress plugin that adds a metabox to posts, pages, and custom post types where you can select if the content requires a login and what role is allowed to view the content. The native auth_redirect function is used to redirect users to the login page.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload `simple-require-login` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

None yet!

== Screenshots ==
1. This is a screenshot of the Simple Require Login metabox when editing a page.

== Changelog ==

= 0.2 =
* Added condition so that metabox doesn't show up on ACF and menu pages. Added an option to redirect to SSL.

= 0.1 =
* Initial Release

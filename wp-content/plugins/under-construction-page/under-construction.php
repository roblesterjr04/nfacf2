<?php
/*
  Plugin Name: Under Construction
  Plugin URI: https://underconstructionpage.com/
  Description: Hide your site behind a great looking under construction page while you do maintenance work.
  Author: Web factory Ltd
  Version: 1.70
  Author URI: http://www.webfactoryltd.com/
  Text Domain: under-construction-page
  Domain Path: lang

  Copyright 2015 - 2016  Web factory Ltd  (email: ucp@webfactoryltd.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


// this is an include only WP file
if (!defined('ABSPATH')) {
  die;
}


define('UCP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('UCP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('UCP_OPTIONS_KEY', 'ucp_options');
define('UCP_META_KEY', 'ucp_meta');
define('UCP_POINTERS_KEY', 'ucp_pointers');
define('UCP_NOTICES_KEY', 'ucp_notices');
define('UCP_SURVEYS_KEY', 'ucp_surveys');

// main plugin class
class UCP {
  static $version = 0;
  static $licensing_servers = array('https://license1.underconstructionpage.com/', 'https://license2.underconstructionpage.com/');

  
  // get plugin version from header
  static function get_plugin_version() {
    $plugin_data = get_file_data(__FILE__, array('version' => 'Version'), 'plugin');
    self::$version = $plugin_data['version'];
     
    return $plugin_data['version'];
  } // get_plugin_version
  
  
  // hook things up  
  static function init() {
    // check if minimal required WP version is present
    if (false === self::check_wp_version(4.0)) {
      return false;
    }
      
    if (is_admin()) {
      // if the plugin was updated from ver < 1.20 upgrade settings array
      self::maybe_upgrade();
      
      // add UCP menu to admin tools menu group
      add_action('admin_menu', array(__CLASS__, 'admin_menu'));

      // settings registration
      add_action('admin_init', array(__CLASS__, 'register_settings'));

      // aditional links in plugin description
      add_filter('plugin_action_links_' . plugin_basename(__FILE__),
                            array(__CLASS__, 'plugin_action_links'));
      add_filter('plugin_row_meta', array(__CLASS__, 'plugin_meta_links'), 10, 2);

      // manages admin header notifications
      add_action('admin_notices', array(__CLASS__, 'admin_notices'));
      add_action('admin_action_ucp_dismiss_notice', array(__CLASS__, 'dismiss_notice'));
      
      // enqueue admin scripts
      add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_enqueue_scripts'));
      
      // AJAX endpoints
      add_action('wp_ajax_ucp_dismiss_pointer', array(__CLASS__, 'dismiss_pointer_ajax'));
      add_action('wp_ajax_ucp_dismiss_survey', array(__CLASS__, 'dismiss_survey_ajax'));
      add_action('wp_ajax_ucp_submit_survey', array(__CLASS__, 'submit_survey_ajax'));
    } else {
      // main plugin logic
      add_action('wp', array(__CLASS__, 'display_construction_page'), 0, 1);
      
      // show under construction notice on login form
      add_filter('login_message', array(__CLASS__, 'login_message'));

      // disable feeds
      add_action('do_feed_rdf', array(__CLASS__, 'disable_feed'), 1, 1);
      add_action('do_feed_rss', array(__CLASS__, 'disable_feed'), 1, 1);
      add_action('do_feed_rss2', array(__CLASS__, 'disable_feed'), 1, 1);
      add_action('do_feed_atom', array(__CLASS__, 'disable_feed'), 1, 1);
    } // if not admin

    // admin bar notice for frontend & backend
    add_action('wp_before_admin_bar_render', array(__CLASS__, 'admin_bar_notice'));
  } // init

  
  // check if user has the minimal WP version required by UCP
  static function check_wp_version($min_version) {
    if (!version_compare(get_bloginfo('version'), $min_version,  '>=')) {
        add_action('admin_notices', array(__CLASS__, 'notice_min_wp_version'));
      return false;
    } else {
      return true;  
    }
  } // check_wp_version
  
  
  // display error message if WP version is too low
  static function notice_min_wp_version() {
    echo '<div class="error"><p>' . sprintf('UnderConstruction plugin <b>requires WordPress version 4.0</b> or higher to function properly. You are using WordPress version %s. Please <a href="%s">update it</a>.', get_bloginfo('version'), admin_url('update-core.php')) . '</p></div>';
  } // notice_min_wp_version_error
  
  
  // some things have to be loaded earlier
  static function plugins_loaded() {
    self::get_plugin_version();
    
    load_plugin_textdomain('under-construction-page');
  } // plugins_loaded
  
  
  // activate doesn't get fired on upgrades so we have to compensate
  public static function maybe_upgrade() {
    $meta = self::get_meta();
    $options = self::get_options();
    
    // added in v1.70 to rename roles to whitelisted_roles
    if (isset($options['roles'])) {
      $options['whitelisted_roles'] = $options['roles'];
      unset($options['roles']);
      update_option(UCP_OPTIONS_KEY, $options);
    }
    
    // check if we need to convert options from the old format to new, or maybe it is already done
    if (isset($meta['options_ver']) && $meta['options_ver'] == self::$version) {
      return;
    }    
    
    if (get_option('set_size') || get_option('set_tweet') || get_option('set_fb') || get_option('set_font') || get_option('set_msg') || get_option('set_opt') || get_option('set_admin')) {
      // convert old options to new
      $options['status'] = (get_option('set_opt') === 'Yes')? '1': '0';
      $options['content'] = trim(get_option('set_msg'));
      $options['whitelisted_roles'] = (get_option('set_admin') === 'No')? array('administrator'): array();
      $options['social_facebook'] = trim(get_option('set_fb'));
      $options['social_twitter'] = trim(get_option('set_tweet'));
      update_option(UCP_OPTIONS_KEY, $options);
      
      delete_option('set_size');
      delete_option('set_tweet');
      delete_option('set_fb');
      delete_option('set_font');
      delete_option('set_msg');
      delete_option('set_opt');
      delete_option('set_admin');
      
      self::reset_pointers();
    }

    // we update only once    
    $meta['options_ver'] = self::$version;
    update_option(UCP_META_KEY, $meta);
  } // maybe_upgrade
  
  
  // get plugin's options
  static function get_options() {
    $options = get_option(UCP_OPTIONS_KEY, array());

    if (!is_array($options)) {
      $options = array();
    }
    $options = array_merge(self::default_options(), $options);

    return $options;
  } // get_options
  
  
  // get plugin's meta data
  static function get_meta() {
    $meta = get_option(UCP_META_KEY, array());

    if (!is_array($meta) || empty($meta)) {
      $meta['first_version'] = self::get_plugin_version();
      $meta['first_install'] = current_time('timestamp');
      update_option(UCP_META_KEY, $meta);
    }

    return $meta;
  } // get_meta
  

  // fetch and display the construction page if it's enabled or preview requested
  static function display_construction_page() {
    $options = self::get_options();
    
    // some URLs have to be accessible at all times
    if ($_SERVER['REQUEST_URI'] == '/wp-admin' ||
        $_SERVER['REQUEST_URI'] == '/wp-admin/' ||
        $_SERVER['REQUEST_URI'] == '/wp-login.php') {
      return true;
    }
    
    if (true == self::is_construction_mode_enabled(false) 
        || (is_user_logged_in() && isset($_GET['ucp_preview']))) {
      header(self::wp_get_server_protocol() . ' 503 Service Unavailable');
      echo self::get_template($options['theme']);
      exit;
    }
  } // display_construction_page

  
  // keeping compatibility with WP < v4.4
  static function wp_get_server_protocol() {
    $protocol = $_SERVER['SERVER_PROTOCOL'];
    if (!in_array($protocol, array('HTTP/1.1', 'HTTP/2', 'HTTP/2.0'))) {
        $protocol = 'HTTP/1.0';
    }
    
    return $protocol;
  } // wp_get_server_protocol
  
  
  // disables feed if necessary
  static function disable_feed() {
    if (true == self::is_construction_mode_enabled(false)) {
      echo '<?xml version="1.0" encoding="UTF-8" ?><status>Service unavailable.</status>';
      exit;
    }
  } // disable_feed

  
  // enqueue CSS and JS scripts in admin
  static function admin_enqueue_scripts($hook) {
    $surveys = get_option(UCP_SURVEYS_KEY);
    $meta = self::get_meta();
    
    // features survey is shown min 5min after install
    if (empty($surveys['features']) && current_time('timestamp') - $meta['first_install'] > 300) {
      $open_features_survey = true;
    } else {
      $open_features_survey = false;
    }
    
    $js_localize = array('undocumented_error' => __('An undocumented error has occured. Please refresh the page and try again.', 'under-construction-page'),
                         'plugin_name' => __('UnderConstruction', 'under-construction-page'),
                         'settings_url' => admin_url('options-general.php?page=ucp'),
                         'whitelisted_users_placeholder' => 'Select whitelisted user(s)',
                         'open_features_survey' => $open_features_survey,
                         'nonce_dismiss_survey' => wp_create_nonce('ucp_dismiss_survey'),
                         'nonce_submit_survey' => wp_create_nonce('ucp_submit_survey'),
                         'deactivate_confirmation' => __('Are you sure you want to deactivate UnderConstruction plugin?' . "\n" . 'If you are removing it because of a problem please contact our support. They will be more than happy to help.', 'under-construction-page'));
                         
    if ('settings_page_ucp' == $hook) {
      wp_enqueue_style('wp-jquery-ui-dialog');
      wp_enqueue_style('ucp-select2', UCP_PLUGIN_URL . 'css/select2.min.css', array(), self::$version);
      wp_enqueue_style('ucp-admin', UCP_PLUGIN_URL . 'css/ucp-admin.css', array(), self::$version);
      
      wp_enqueue_script('jquery-ui-tabs');
      wp_enqueue_script('jquery-ui-dialog');
      wp_enqueue_script('ucp-jquery-plugins', UCP_PLUGIN_URL . 'js/ucp-jquery-plugins.js', array('jquery'), self::$version, true);
      wp_enqueue_script('ucp-select2', UCP_PLUGIN_URL . 'js/select2.min.js', array(), self::$version, true);
      wp_enqueue_script('ucp-admin', UCP_PLUGIN_URL . 'js/ucp-admin.js', array('jquery'), self::$version, true);
      wp_localize_script('ucp-admin', 'ucp', $js_localize);
    }
    
    if ('plugins.php' == $hook) {
      wp_enqueue_script('ucp-admin-plugins', UCP_PLUGIN_URL . 'js/ucp-admin-plugins.js', array('jquery'), self::$version, true);
      wp_localize_script('ucp-admin-plugins', 'ucp', $js_localize);
    }
    
    $pointers = get_option(UCP_POINTERS_KEY);
    if ($pointers && 'settings_page_ucp' != $hook) {
      $pointers['_nonce_dismiss_pointer'] = wp_create_nonce('ucp_dismiss_pointer');
      wp_enqueue_script('wp-pointer');
      wp_enqueue_script('ucp-pointers', plugins_url('js/ucp-admin-pointers.js', __FILE__), array('jquery'), self::$version, true);
      wp_enqueue_style('wp-pointer');
      wp_localize_script('wp-pointer', 'ucp_pointers', $pointers);
    }
  } // admin_enqueue_scripts
  
  
  // permanently dismiss a pointer
  static function dismiss_pointer_ajax() {
    check_ajax_referer('ucp_dismiss_pointer');
    
    $pointers = get_option(UCP_POINTERS_KEY);
    $pointer = trim($_POST['pointer']);

    if (empty($pointers) || empty($pointers[$pointer])) {
      wp_send_json_error();
    }

    unset($pointers[$pointer]);
    update_option(UCP_POINTERS_KEY, $pointers);
    
    wp_send_json_success();
  } // dismiss_pointer_ajax
  
  
  // permanently dismiss a survey
  static function dismiss_survey_ajax() {
    check_ajax_referer('ucp_dismiss_survey');
    
    $surveys = get_option(UCP_SURVEYS_KEY, array());
    $survey = trim($_POST['survey']);

    $surveys[$survey] = -1;
    update_option(UCP_SURVEYS_KEY, $surveys);
    
    wp_send_json_success();
  } // dismiss_survey_ajax
  
  
  // submit survey
  static function submit_survey_ajax() {
    check_ajax_referer('ucp_submit_survey');
    
    $meta = self::get_meta();
    $surveys = get_option(UCP_SURVEYS_KEY);
    
    $vars = wp_parse_args($_POST, array('survey' => '', 'answers' => '', 'custom_answer' => '', 'emailme' => ''));
    $vars['answers'] = trim($vars['answers'], ',');
    $vars['custom_answer'] = trim(strip_tags($vars['custom_answer']));
    
    if (empty($vars['survey']) || empty($vars['answers'])) {
      wp_send_json_error();
    }
    
    $request_params = array('sslverify' => false, 'timeout' => 15, 'redirection' => 2);
    $request_args = array('action' => 'submit_survey',
                          'survey' => $vars['survey'],
                          'email' => $vars['emailme'],
                          'answers' => $vars['answers'],
                          'custom_answer' => $vars['custom_answer'],
                          'first_version' => $meta['first_version'],
                          'version' => UCP::$version,
                          'codebase' => 'free',
                          'site' => get_home_url());

    $url = add_query_arg($request_args, self::$licensing_servers[0]);
    $response = wp_remote_get(esc_url_raw($url), $request_params);
    
    if (is_wp_error($response) || !wp_remote_retrieve_body($response)) {
      $url = add_query_arg($request_args, self::$licensing_servers[1]);
      $response = wp_remote_get(esc_url_raw($url), $request_params);
    }
    
    $surveys[$vars['survey']] = current_time('timestamp');
    update_option(UCP_SURVEYS_KEY, $surveys);
    
    wp_send_json_success();
  } // submit_survey_ajax
 
  
  static function encode_email($email) {
    $len = strlen($email);
    $out = '';
    
    for ($i = 0; $i < $len; $i++) {
      $out .= '&#'. ord($email[$i]) . ';';
    }
  
    return $out;
  } // encode_email
  
  
  // parse shortcode alike variables
  static function parse_vars($string) {
    $vars = array('site-title' => get_bloginfo('name'), 
                  'site-tagline' => get_bloginfo('description'), 
                  'site-description' => get_bloginfo('description'), 
                  'site-url' => trailingslashit(get_home_url()), 
                  'wp-url' => trailingslashit(get_site_url()),
                  'site-login-url' => get_site_url() . '/wp-login.php');
    
    foreach ($vars as $var_name => $var_value) {
      $var_name = '[' . $var_name . ']';
      $string = str_ireplace($var_name, $var_value, $string);
    }
    
    return $string;
  } // parse_vars
  
  
  // generate HTML from social icons
  static function generate_social_icons($options, $template_id) {
    $out = '';
    
    if (!empty($options['social_facebook'])) {
      $out .= '<a title="Facebook" href="' . $options['social_facebook'] . '" target="_blank"><i class="fa fa-facebook-square fa-3x"></i></a>';
    }
    if (!empty($options['social_twitter'])) {
      $out .= '<a title="Twitter" href="' . $options['social_twitter'] . '" target="_blank"><i class="fa fa-twitter-square fa-3x"></i></a>';
    }
    if (!empty($options['social_google'])) {
      $out .= '<a title="Google+" href="' . $options['social_google'] . '" target="_blank"><i class="fa fa-google-plus-square fa-3x"></i></a>';
    }
    if (!empty($options['social_linkedin'])) {
      $out .= '<a title="LinkedIn" href="' . $options['social_linkedin'] . '" target="_blank"><i class="fa fa-linkedin-square fa-3x"></i></a>';
    }
    if (!empty($options['social_youtube'])) {
      $out .= '<a title="YouTube" href="' . $options['social_youtube'] . '" target="_blank"><i class="fa fa-youtube-square fa-3x"></i></a>';
    }
    if (!empty($options['social_pinterest'])) {
      $out .= '<a title="Pinterest" href="' . $options['social_pinterest'] . '" target="_blank"><i class="fa fa-pinterest-square fa-3x"></i></a>';
    }
    if (!empty($options['social_dribbble'])) {
      $out .= '<a title="Dribbble" href="' . $options['social_dribbble'] . '" target="_blank"><i class="fa fa-dribbble fa-3x"></i></a>';
    }
    if (!empty($options['social_behance'])) {
      $out .= '<a title="Behance" href="' . $options['social_behance'] . '" target="_blank"><i class="fa fa-behance-square fa-3x"></i></a>';
    }
    if (!empty($options['social_instagram'])) {
      $out .= '<a title="Instagram" href="' . $options['social_instagram'] . '" target="_blank"><i class="fa fa-instagram fa-3x"></i></a>';
    }
    if (!empty($options['social_skype'])) {
      $out .= '<a title="Skype" href="skype:' . $options['social_skype'] . '?chat"><i class="fa fa-skype fa-3x"></i></a>';
    }
    if (!empty($options['social_whatsapp'])) {
      $out .= '<a title="WhatsApp" href="whatsapp:' . $options['social_whatsapp'] . '"><i class="fa fa-whatsapp fa-3x"></i></a>';
    }
    if (!empty($options['social_email'])) {
      $out .= '<a title="Email" href="mailto:' . self::encode_email($options['social_email']) . '"><i class="fa fa-envelope fa-3x"></i></a>';
    }
    if (!empty($options['social_phone'])) {
      $out .= '<a title="Phone" href="callto:' . $options['social_phone'] . '"><i class="fa fa-phone-square fa-3x"></i></a>';
    }
    
    return $out;
  } // generate_social_icons
  
  
  // shortcode for inserting things in header
  static function generate_head($options, $template_id) {
    $out = '';
    
    $out .= '<link rel="stylesheet" href="' . trailingslashit(UCP_PLUGIN_URL . 'themes') . 'css/common.css?v=' . self::$version . '" type="text/css">';
    
    if (!empty($options['ga_tracking_id'])) {
      $out .= "
      <script>
        (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
        (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
        m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
        })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
        ga('create', '{$options['ga_tracking_id']}', 'auto');
        ga('send', 'pageview');
      </script>";
    }
    
    if (!empty($options['custom_css'])) {
      $out .= "\n" . '<style type="text/css">' . $options['custom_css'] . '</style>';
    }
    
    return trim($out);
  } // generate_head
  
  
  // shortcode for inserting things in footer
  static function generate_footer($options, $template_id) {
    $out = '';
    
    if ($options['linkback'] == '1') {
      $tmp = md5(get_site_url());
      if ($tmp[0] < 9) {
        $out .= '<p id="linkback">Create stunning <a href="https://underconstructionpage.com/" target="_blank">under construction pages for WordPress</a> for free.</p>';
      } else {
        $out .= '<p id="linkback">Create free <a href="https://underconstructionpage.com/" target="_blank">landing pages for WordPress</a>.</p>';
      }
    }
    
    if ($options['login_button'] == '1') {
      if (is_user_logged_in()) {
        $out .= '<div id="login-button" class="loggedin">';
        $out .= '<a title="Open WordPress admin" href="' . get_site_url() . '/wp-admin/"><i class="fa fa-wordpress fa-2x" aria-hidden="true"></i></a>';
      } else {
        $out .= '<div id="login-button" class="loggedout">';
        $out .= '<a title="Log in to WordPress admin" href="' . get_site_url() . '/wp-login.php"><i class="fa fa-wordpress fa-2x" aria-hidden="true"></i></a>';
      }
      $out .= '</div>';
    }
    
    return $out;
  } // generate_footer
   
   
  // returnes parsed template
  static function get_template($template_id) {
    $vars = array();
    $options = self::get_options();

    $vars['version'] = self::$version;
    $vars['site-url'] = trailingslashit(get_home_url());
    $vars['wp-url'] = trailingslashit(get_site_url());
    $vars['theme-url'] = trailingslashit(UCP_PLUGIN_URL . 'themes/' . $template_id);
    $vars['theme-url-common'] = trailingslashit(UCP_PLUGIN_URL . 'themes');
    $vars['title'] = self::parse_vars($options['title']);
    $vars['generator'] = 'Free UnderConstructionPage plugin for WordPress';
    $vars['heading1'] = self::parse_vars($options['heading1']);
    $vars['content'] = nl2br(self::parse_vars($options['content']));
    $vars['description'] = self::parse_vars($options['description']);
    $vars['social-icons'] = self::generate_social_icons($options, $template_id);
    $vars['head'] = self::generate_head($options, $template_id);
    $vars['footer'] = self::generate_footer($options, $template_id);
    
    ob_start();
    require UCP_PLUGIN_DIR . 'themes/' . $template_id . '/index.php';
    $template = ob_get_clean();
    
    foreach ($vars as $var_name => $var_value) {
      $var_name = '[' . $var_name . ']';
      $template = str_ireplace($var_name, $var_value, $template);
    }
    
    return $template;
  } // get_template

  
  // checks if construction mode is enabled for the current visitor
  static function is_construction_mode_enabled($settings_only = false) {
    $options = self::get_options();
    $current_user = wp_get_current_user();
    
    // just check if it's generally enabled
    if ($settings_only) {
      if ($options['status']) {
        return true;
      } else {
        return false;
      }
    } else {
      // check if enabled for current user
      if (!$options['status']) {
        return false;
      } elseif (defined('WP_CLI') && WP_CLI) {
        return false;
      } elseif (self::user_has_role($options['whitelisted_roles'])) {
        return false;
      } elseif (in_array($current_user->ID, $options['whitelisted_users'])) {
        return false;
      } elseif (strlen($options['end_date']) === 16 && $options['end_date'] !== '0000-00-00 00:00' && $options['end_date'] < current_time('Y-m-d H:i')) {
        return false;
      } else {
        return true;
      }
    }
  } // is_construction_mode_enabled

  
  // check if user has the specified role
  static function user_has_role($roles) {
    $current_user = wp_get_current_user();

    if ($current_user->roles) {
      $user_role = $current_user->roles[0];
    } else {
      $user_role = 'guest';
    }

    return in_array($user_role, $roles);
  } // user_has_role

  
  // displays various notices in admin header
  static function admin_notices() {
    $notices = get_option(UCP_NOTICES_KEY);
    $meta = self::get_meta();
    
    if (empty($notices['dismiss_rate']) &&
        (current_time('timestamp') - $meta['first_install']) > (DAY_IN_SECONDS * 3)) {
      $rate_url = 'https://wordpress.org/support/plugin/under-construction-page/reviews/?rate=5#new-post';
      $dismiss_url = add_query_arg(array('action' => 'ucp_dismiss_notice', 'notice' => 'rate', 'redirect' => urlencode($_SERVER['REQUEST_URI'])), admin_url('admin.php'));

      echo '<div id="ucp_rate_notice" class="notice-info notice is-dismissible"><p>Hi! We saw you\'ve been using <b class="ucp-logo" style="font-weight: bold;">UnderConstructionPage</b> plugin for a few days and wanted to ask for your help to <b>make the plugin better</b>.<br>We just need a minute of your time to rate the plugin. Thank you!';

      echo '<br><a target="_blank" href="' . esc_url($rate_url) . '" style="vertical-align: baseline; margin-top: 15px;" class="button-primary">Help us out &amp; rate the plugin</a>';
      echo '&nbsp;&nbsp;<a href="' . esc_url($dismiss_url) . '">I\'ve already rated the plugin</a>';
      echo '</p></div>';  
    }
  } // notices
  
  
  // handle dismiss button for notices
  static function dismiss_notice() {
    if (empty($_GET['notice'])) {
      wp_redirect(admin_url());
      exit;
    }
    
    $notices = get_option(UCP_NOTICES_KEY, array());
    
    if ($_GET['notice'] == 'rate') {
      $notices['dismiss_rate'] = true;
    }
    
    update_option(UCP_NOTICES_KEY, $notices);

    if (!empty($_GET['redirect'])) {
      wp_redirect($_GET['redirect']);
    } else {
      wp_redirect(admin_url());
    }

    exit;
  } // dismiss_notice

  
  // add admin bar notice when construction is enabled
  static function admin_bar_notice() {
    global $wp_admin_bar;

    if (self::is_construction_mode_enabled(true)) {
      $title = '<img style="height: 17px; margin-bottom: -4px; padding-right: 3px;" src="' . UCP_PLUGIN_URL . '/images/ucp_icon_enabled.png" alt="Under construction mode is enabled" title="Under construction mode is enabled"> <span class="ab-label">Under construction mode is <strong style="font-weight: bold;">enabled</strong></span>';
      $class = 'ucp-enabled';
    } else {
      $title = '<img style="height: 17px; margin-bottom: -4px; padding-right: 3px;" src="' . UCP_PLUGIN_URL . '/images/ucp_icon_disabled.png" alt="Under construction mode is disabled" title="Under construction mode is disabled"> <span class="ab-label">Under construction mode is <strong style="font-weight: bold;">disabled</strong></span>';
      $class = 'ucp-disabled';
    }
    
    $wp_admin_bar->add_menu(array(
        'parent' => '',
        'id' => 'construction-mode',
        'title' => $title,
        'href' => admin_url('options-general.php?page=ucp'),
        'meta'  => array('class' => $class)
    ));
  } // admin_bar_notice
  
  
  // show under construction notice on WP login form
  static function login_message($message) {
    $options = get_option('wf_mm');

    if (self::is_construction_mode_enabled(true)) {
      $message .= '<div class="message">Under construction mode is <b>enabled</b>.</div>';
    }
    
    return $message;
  } // login_notice

  
  // add settings link to plugins page
  static function plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=ucp') . '" title="UnderConstruction Settings">Settings</a>';
    array_unshift($links, $settings_link);

    return $links;
  } // plugin_action_links
  
  
  // add links to plugin's description in plugins table
  static function plugin_meta_links($links, $file) {
    $support_link = '<a target="_blank" href="https://wordpress.org/support/plugin/under-construction-page" title="Get help">Support</a>';

    if ($file == plugin_basename(__FILE__)) {
      $links[] = $support_link;
    }

    return $links;
  } // plugin_meta_links
  

  // create the admin menu item
  static function admin_menu() {
    add_options_page('UnderConstruction', 'UnderConstruction', 'manage_options', 'ucp', array(__CLASS__, 'main_page'));
  } // admin_menu

  
  // all settings are saved in one option
  static function register_settings() {
    register_setting(UCP_OPTIONS_KEY, UCP_OPTIONS_KEY, array(__CLASS__, 'sanitize_settings'));
  } // register_settings

  
  // set default settings
  static function default_options() {
    $defaults = array('status' => '0',
                      'end_date' => '',
                      'ga_tracking_id' => '',
                      'theme' => 'mad_designer',
                      'custom_css' => '',
                      'title' => '[site-title] is under construction',
                      'description' => '[site-tagline]',
                      'heading1' => 'Sorry, we\'re doing some work on the site',
                      'content' => 'Thank you for being patient. We are doing some work on the site and will be back shortly.',
                      'social_facebook' => '',
                      'social_twitter' => '',
                      'social_google' => '',
                      'social_linkedin' => '',
                      'social_youtube' => '',
                      'social_pinterest' => '',
                      'social_dribbble' => '',
                      'social_behance' => '',
                      'social_instagram' => '',
                      'social_email' => '',
                      'social_phone' => '',
                      'social_skype' => '',
                      'social_whatsapp' => '',
                      'login_button' => '1',
                      'linkback' => '0',
                      'whitelisted_roles' => array('administrator'),
                      'whitelisted_users' => array()
                      );

    return $defaults;
  } // default_options
  

  // sanitize settings on save
  static function sanitize_settings($options) {
    $old_options = self::get_options();

    foreach ($options as $key => $value) {
      switch ($key) {
        case 'title':
        case 'description':
        case 'heading1':
        case 'content':
        case 'custom_css':
        case 'social_facebook':
        case 'social_twitter':
        case 'social_google':
        case 'social_linkedin':
        case 'social_youtube':
        case 'social_pinterest':
        case 'social_dribbble':
        case 'social_behance':
        case 'social_instagram':
        case 'social_email':
        case 'social_phone':
        case 'social_skype':
        case 'social_whatsapp':
          $options[$key] = trim($value);
        break;
        case 'ga_tracking_id':
          $options[$key] = substr(strtoupper(trim($value)), 0, 15);
        break;
        case 'end_date':
          $options[$key] = substr(trim($value), 0, 16);
        break;
      } // switch
    } // foreach
    
    $options['whitelisted_roles'] = empty($options['whitelisted_roles'])? array(): $options['whitelisted_roles'];
    $options['whitelisted_users'] = empty($options['whitelisted_users'])? array(): $options['whitelisted_users'];
    $options = self::check_var_isset($options, array('status' => 0, 'linkback' => 0, 'login_button' => 0));
    
    if (!empty($options['ga_tracking_id']) && preg_match('/^UA-\d{3,}-\d{1,3}$/', $options['ga_tracking_id']) === 0) {
      add_settings_error('ucp', 'ga_tracking_id', 'Please enter a valid Google Analytics Tracking ID, or leave empty to disable tracking.');
    }

    // empty cache in 3rd party plugins
    if ($options != $old_options) {
      if (function_exists('w3tc_pgcache_flush')) {
        w3tc_pgcache_flush(); 
      } 
      if (function_exists('wp_cache_clean_cache')) {
        global $file_prefix;
        wp_cache_clean_cache($file_prefix); 
      }
      if (function_exists('wp_cache_clear_cache')) {
        wp_cache_clear_cache();
      }
      if (class_exists('Endurance_Page_Cache')) {
        $epc = new Endurance_Page_Cache;
        $epc->purge_all();  
      }
    }
    
    return array_merge($old_options, $options);
  } // sanitize_settings
  
  
  // checkbox helper function
  static function checked($value, $current, $echo = false) {
    $out = '';

    if (!is_array($current)) {
      $current = (array) $current;
    }

    if (in_array($value, $current)) {
      $out = ' checked="checked" ';
    }

    if ($echo) {
      echo $out;
    } else {
      return $out;
    }
  } // checked
  
  
  // helper function for saving options, mostly checkboxes
  static function check_var_isset($values, $variables) {
    foreach ($variables as $key => $value) {
      if (!isset($values[$key])) {
        $values[$key] = $value;
      }
    }
    
    return $values;
  } // check_var_isset
  
  
  // helper function for creating dropdowns
  static function create_select_options($options, $selected = null, $output = true) {
    $out = "\n";

    if(!is_array($selected)) {
      $selected = array($selected);
    }

    foreach ($options as $tmp) {
      $data = '';
      if (isset($tmp['disabled'])) {
        $data .= ' disabled="disabled" ';
      }
      if (in_array($tmp['val'], $selected)) {
        $out .= "<option selected=\"selected\" value=\"{$tmp['val']}\"{$data}>{$tmp['label']}&nbsp;</option>\n";
      } else {
        $out .= "<option value=\"{$tmp['val']}\"{$data}>{$tmp['label']}&nbsp;</option>\n";
      }
    } // foreach

    if ($output) {
      echo $out;
    } else {
      return $out;
    }
  } // create_select_options
  
  
  static function tab_main() {
    $options = self::get_options();
    $default_options = self::default_options();
    
    echo '<table class="form-table">';

    echo '<tr valign="top">
    <th scope="row"><label for="status">Status</label></th>
    <td><div class="onoffswitch">
    <input ' . self::checked(1, $options['status']) . ' type="checkbox" value="1" name="' . UCP_OPTIONS_KEY . '[status]" class="onoffswitch-checkbox" id="status">
    <label class="onoffswitch-label" for="status">
        <span class="onoffswitch-inner"></span>
        <span class="onoffswitch-switch"></span>
    </label>
    </div>';
    
    echo '<p class="description">By enabling construction mode all users (<a class="change_tab" data-tab="3" href="#whitelisted-roles">except selected ones</a>) will not be able to access the site\'s content. They will only see the under construction page.</p>';
    echo '</td></tr>';
    
    echo '<tr valign="top">
    <th scope="row"><label for="end_date">End Date &amp; Time</label></th>
    <td><input id="end_date" type="text" class="datepicker" name="' . UCP_OPTIONS_KEY . '[end_date]" value="' . esc_attr($options['end_date']) . '" placeholder="yyyy-mm-dd hh:mm"><span title="Open date & time picker" alt="Open date & time picker" class="show-datepicker dashicons dashicons-calendar-alt"></span> <span title="Clear date & time" alt="Clear date & time" class="clear-datepicker dashicons dashicons-no"></span>';
    echo '<p class="description">If enabled, construction mode will automatically stop showing on the selected date.<br>
    This option will not "auto-enable" construction mode. Status has to be set to "On".</p>';
    echo '</td></tr>';
    
    echo '<tr valign="top">
    <th scope="row"><label for="ga_tracking_id">Google Analytics Tracking ID</label></th>
    <td><input id="ga_tracking_id" type="text" class="code" name="' . UCP_OPTIONS_KEY . '[ga_tracking_id]" value="' . esc_attr($options['ga_tracking_id']) . '" placeholder="UA-xxxxxx-xx">';
    echo '<p class="description">Enter the unique tracking ID found in your GA tracking profile settings to track visits to the page.<br>Leave blank to disable tracking.</p>';
    echo '</td></tr>';
    
    echo '</table>';
  } // tab_main
  
  
  static function tab_content() {
    $options = self::get_options();
    $default_options = self::default_options();
    
    echo '<table class="form-table">';
    
    echo '<tr valign="top">
    <th scope="row"><label for="title">Title</label></th>
    <td><input type="text" id="title" class="regular-text" name="' . UCP_OPTIONS_KEY . '[title]" value="' . esc_attr($options['title']) . '" />';
    echo '<p class="description">Page title. Default: ' . $default_options['title'] . '</p>';
    echo '<p><b>Available shortcodes:</b> (only active in UC themes, not on the rest of the site)</p>
    <ul class="ucp-list">
    <li><code>[site-title]</code> - blog title, as set in <a href="options-general.php">Options - General</a></li>
    <li><code>[site-tagline]</code> - blog tagline, as set in <a href="options-general.php">Options - General</a></li>
    <li><code>[site-url]</code> - site address (URL), as set in <a href="options-general.php">Options - General</a></li>
    <li><code>[wp-url]</code> - WordPress address (URL), as set in <a href="options-general.php">Options - General</a></li>
    <li><code>[site-login-url]</code> - URL to site login page</li>
    </ul>';
    echo '</td></tr>';

    echo '<tr valign="top">
    <th scope="row"><label for="description">Description</label></th>
    <td><input id="description" type="text" class="large-text" name="' . UCP_OPTIONS_KEY . '[description]" value="' . esc_attr($options['description']) . '" />';
    echo '<p class="description">Description meta tag (see above for available <a href="#title">shortcodes</a>). Default: ' . $default_options['description'] . '</p>';
    echo '</td></tr>';
    
    echo '<tr valign="top">
    <th scope="row"><label for="heading1">Headline</label></th>
    <td><input id="heading1" type="text" class="large-text" name="' . UCP_OPTIONS_KEY . '[heading1]" value="' . esc_attr($options['heading1']) . '" />';
    echo '<p class="description">Main heading/title (see above for available <a href="#title">shortcodes</a>). Default: ' . $default_options['heading1'] . '</p>';
    echo '</td></tr>';

    echo '<tr valign="top" id="content_wrap">
    <th scope="row"><label for="content">Content</label></th>
    <td>';
    wp_editor($options['content'], 'content', array('tabfocus_elements' => 'insert-media-button,save-post', 'editor_height' => 250, 'resize' => 1, 'textarea_name' => UCP_OPTIONS_KEY . '[content]', 'drag_drop_upload' => 1));
    echo '<p class="description">All HTML elements are allowed. Shortcodes are not parsed except <a href="#title">UC template ones</a>. Default: ' . $default_options['content'] . '</p>';
    echo '</td></tr>';
    
    echo '<tr valign="top" id="login_button_wrap">
    <th scope="row"><label for="login_button">Login Button</label></th>
    <td><div class="onoffswitch">
    <input ' . self::checked(1, $options['login_button']) . ' type="checkbox" value="1" name="' . UCP_OPTIONS_KEY . '[login_button]" class="onoffswitch-checkbox" id="login_button">
    <label class="onoffswitch-label" for="login_button">
        <span class="onoffswitch-inner"></span>
        <span class="onoffswitch-switch"></span>
    </label>
    </div>';
    echo '<p class="description">Show a descrete link to the login form, or WP admin if you\'re logged in, in the lower right corner of the page.</p>';
    echo '</td></tr>';
    
    echo '<tr valign="top">
    <th scope="row"><label for="linkback">Show Some Love</label></th>
    <td><div class="onoffswitch">
    <input ' . self::checked(1, $options['linkback']) . ' type="checkbox" value="1" name="' . UCP_OPTIONS_KEY . '[linkback]" class="onoffswitch-checkbox" id="linkback">
    <label class="onoffswitch-label" for="linkback">
        <span class="onoffswitch-inner"></span>
        <span class="onoffswitch-switch"></span>
    </label>
    </div>';
    echo '<p class="description">Please help others learn about this free plugin by placing a small link in the footer. Thank you very much!</p>';
    echo '</td></tr>';
    
    
    echo '</table>';
    
    echo '<h2 class="title">Social &amp; Contact Icons</h2>';
    
    echo '<table class="form-table">';
    echo '<tr valign="top">
    <th scope="row"><label for="social_facebook">Facebook Page</label></th>
    <td><input id="social_facebook" type="url" class="regular-text code" name="' . UCP_OPTIONS_KEY . '[social_facebook]" value="' . esc_attr($options['social_facebook']) . '" placeholder="Facebook business or personal page URL">';
    echo '<p class="description">Complete URL, with http prefix, to Facebook page.</p>';
    echo '</td></tr>';
    
    echo '<tr valign="top">
    <th scope="row"><label for="social_twitter">Twitter Profile</label></th>
    <td><input id="social_twitter" type="url" class="regular-text code" name="' . UCP_OPTIONS_KEY . '[social_twitter]" value="' . esc_attr($options['social_twitter']) . '" placeholder="Twitter profile URL">';
    echo '<p class="description">Complete URL, with http prefix, to Twitter profile page.</p>';
    echo '</td></tr>';
    
    echo '<tr valign="top">
    <th scope="row"><label for="social_google">Google Page</label></th>
    <td><input id="social_google" type="url" class="regular-text code" name="' . UCP_OPTIONS_KEY . '[social_google]" value="' . esc_attr($options['social_google']) . '" placeholder="Google+ page URL">';
    echo '<p class="description">Complete URL, with http prefix, to Google+ page.</p>';
    echo '</td></tr>';
    
    echo '<tr valign="top">
    <th scope="row"><label for="social_linkedin">LinkedIn Profile</label></th>
    <td><input id="social_linkedin" type="url" class="regular-text code" name="' . UCP_OPTIONS_KEY . '[social_linkedin]" value="' . esc_attr($options['social_linkedin']) . '" placeholder="LinkedIn profile page URL">';
    echo '<p class="description">Complete URL, with http prefix, to LinkedIn profile page.</p>';
    echo '</td></tr>';
    
    echo '<tr valign="top">
    <th scope="row"><label for="social_youtube">YouTube Profile Page or Video</label></th>
    <td><input id="social_youtube" type="url" class="regular-text code" name="' . UCP_OPTIONS_KEY . '[social_youtube]" value="' . esc_attr($options['social_youtube']) . '" placeholder="YouTube page or video URL">';
    echo '<p class="description">Complete URL, with http prefix, to YouTube page or video.</p>';
    echo '</td></tr>';
    
    echo '<tr valign="top">
    <th scope="row"><label for="social_pinterest">Pinterest Profile</label></th>
    <td><input id="social_pinterest" type="url" class="regular-text code" name="' . UCP_OPTIONS_KEY . '[social_pinterest]" value="' . esc_attr($options['social_pinterest']) . '" placeholder="Pinterest profile URL">';
    echo '<p class="description">Complete URL, with http prefix, to Pinterest profile.</p>';
    echo '</td></tr>';
    
    echo '<tr valign="top">
    <th scope="row"><label for="social_dribbble">Dribbble Profile</label></th>
    <td><input id="social_dribbble" type="url" class="regular-text code" name="' . UCP_OPTIONS_KEY . '[social_dribbble]" value="' . esc_attr($options['social_dribbble']) . '" placeholder="Dribbble profile URL">';
    echo '<p class="description">Complete URL, with http prefix, to Dribbble profile.</p>';
    echo '</td></tr>';
    
    echo '<tr valign="top">
    <th scope="row"><label for="social_behance">Behance Profile</label></th>
    <td><input id="social_behance" type="url" class="regular-text code" name="' . UCP_OPTIONS_KEY . '[social_behance]" value="' . esc_attr($options['social_behance']) . '" placeholder="Behance profile URL">';
    echo '<p class="description">Complete URL, with http prefix, to Behance profile.</p>';
    echo '</td></tr>';
    
    echo '<tr valign="top">
    <th scope="row"><label for="social_instagram">Instagram Profile</label></th>
    <td><input id="social_instagram" type="url" class="regular-text code" name="' . UCP_OPTIONS_KEY . '[social_instagram]" value="' . esc_attr($options['social_instagram']) . '" placeholder="Instagram profile URL">';
    echo '<p class="description">Complete URL, with http prefix, to Instagram profile.</p>';
    echo '</td></tr>';
    
    echo '<tr valign="top">
    <th scope="row"><label for="social_skype">Skype Username</label></th>
    <td><input id="social_skype" type="text" class="regular-text code" name="' . UCP_OPTIONS_KEY . '[social_skype]" value="' . esc_attr($options['social_skype']) . '" placeholder="Skype username / account name">';
    echo '<p class="description">Skype username / account name.</p>';
    echo '</td></tr>';
    
    echo '<tr valign="top">
    <th scope="row"><label for="social_whatsapp">WhatsApp Phone Number</label></th>
    <td><input id="social_whatsapp" type="text" class="regular-text code" name="' . UCP_OPTIONS_KEY . '[social_whatsapp]" value="' . esc_attr($options['social_whatsapp']) . '" placeholder="+1-123-456-789">';
    echo '<p class="description">WhatsApp phone number in full international format.</p>';
    echo '</td></tr>';
    
    echo '<tr valign="top">
    <th scope="row"><label for="social_email">Email Address</label></th>
    <td><input id="social_email" type="email" class="regular-text code" name="' . UCP_OPTIONS_KEY . '[social_email]" value="' . esc_attr($options['social_email']) . '" placeholder="name@domain.com">';
    echo '<p class="description">Email will be encoded on the page to protect it from email address harvesters.</p>';
    echo '</td></tr>';
    
    echo '<tr valign="top">
    <th scope="row"><label for="social_phone">Phone Number</label></th>
    <td><input id="social_phone" type="tel" class="regular-text" name="' . UCP_OPTIONS_KEY . '[social_phone]" value="' . esc_attr($options['social_phone']) . '" placeholder="+1-123-456-789">';
    echo '<p class="description">Complete phone number in international format.</p>';
    echo '</td></tr>';
    
    echo '</table>';
  } // tab_content
  
  
  static function tab_design() {
    $options = self::get_options();
    $default_options = self::default_options();
    
    $img_path = UCP_PLUGIN_URL . 'images/';
    
    $themes = array('mad_designer' => 'Mad Designer', 'plain_text' => 'Plain Text', 'under_construction' => 'Under Construction', 'dark' => 'Things Went Dark', 'forklift' => 'Forklift at Work', 'under_construction_text' => 'Under Construction Text', 'cyber_chick' => 'Cyber Chick', 'rocket' => 'Rocket Launch', 'loader' => 'Loader at Work', 'cyber_chick_dark' => 'Cyber Chick Dark', 'safe' => 'Safe', 'people' => 'People at Work', 'windmill' => 'Windmill');
    
    echo '<table class="form-table">';
    echo '<tr valign="top">
    <th scope="row">Theme</th>
    <td>';
    echo '<input type="hidden" id="theme_id" name="' . UCP_OPTIONS_KEY . '[theme]" value="' . $options['theme'] . '">';
    
    foreach ($themes as $theme_id => $theme_name) {
      if ($theme_id === $options['theme']) {
        $class = ' active';
      } else {
        $class = '';
      }
      echo '<div class="ucp-thumb' . $class . '" data-theme-id="' . $theme_id . '"><img src="' . $img_path . $theme_id . '.png" alt="' . $theme_name . '" title="' . $theme_name . '" /><span>' . $theme_name . '</span></div>';
    }
    
    echo '<div class="ucp-thumb-special"><a href="https://twitter.com/intent/tweet?text=' . urlencode('I need more themes for the free Under Construction #wordpress plugin. When are they coming out? @webfactoryltd') . '&url=https://wordpress.org/plugins/under-construction-page/" target="_blank"><img src="' . $img_path . 'more_coming_soon.png" alt="Need more themes?" title="Need more themes?" /></a><br />Click for More Themes</div>';
    
    echo '</td></tr>';
    
    echo '<tr valign="top">
    <th scope="row"><label for="custom_css">Custom CSS</label></th>
    <td>';
    echo '<textarea data-autoresize="1" rows="3" id="custom_css" class="code large-text" name="' . UCP_OPTIONS_KEY . '[custom_css]" placeholder=".selector { property-name: property-value; }">' . esc_textarea($options['custom_css']) . '</textarea>';
    echo '<p class="description">&lt;style&gt; tags will be added automatically. Do not include them in your code.<br>
    For RTL languages support add: <code>body { direction: rtl; }</code></p>';
    echo '</td></tr>';
    
    echo '</table>';
  } // tab_design
  
  
  // markup & logic for access tab
  static function tab_access() {
    $options = self::get_options();
    $default_options = self::default_options();
    $roles = $users = array();
    
    $tmp_roles = get_editable_roles();
    foreach ($tmp_roles as $tmp_role => $details) {
      $name = translate_user_role($details['name']);
      $roles[] = array('val' => $tmp_role,  'label' => $name);
    }
    
    $tmp_users = get_users(array('fields' => array('id', 'display_name')));
    foreach ($tmp_users as $user) {
      $users[] = array('val' => $user->id, 'label' => $user->display_name);
    }
    
    echo '<table class="form-table">';
    
    echo '<tr valign="top" id="whitelisted-roles">
    <th scope="row">Whitelisted User Roles</th>
    <td>';
    
    foreach ($roles as $tmp_role) {
      echo  '<input name="' . UCP_OPTIONS_KEY . '[whitelisted_roles][]" id="roles-' . $tmp_role['val'] . '" ' . self::checked($tmp_role['val'], $options['whitelisted_roles'], false) . ' value="' . $tmp_role['val'] . '" type="checkbox" /> <label for="roles-' . $tmp_role['val'] . '">' . $tmp_role['label'] . '</label><br />';
    }
    echo '<p class="description">Selected user roles will <b>not</b> be affected by the under construction mode and will always see the "normal" site. Default: administrator.</p>';
    echo '</td></tr>';
    
    echo '<tr valign="top">
    <th scope="row"><label for="whitelisted_users">Whitelisted Users</label></th>
    <td><select id="whitelisted_users" class="select2" style="width: 50%; max-width: 300px;" name="' . UCP_OPTIONS_KEY . '[whitelisted_users][]" multiple>';
    self::create_select_options($users, $options['whitelisted_users'], true);
    
    echo '</select><p class="description">Selected users (when logged in) will <b>not</b> be affected by the under construction mode and will always see the "normal" site.</p>';
    echo '</td></tr>';
    
    echo '</table>';
  } // tab_access
  

  // support tab - FAQ and links
  static function tab_support() {
    echo '<h2>FAQ</h2>';
    
    echo '<p><b>How can I work on my site while construction mode is enabled?</b><br>Make sure your user role (probably admin) is selected under <a class="change_tab" data-tab="3" href="#whitelisted-roles">Access - Whitelisted User Roles</a> and open the site while logged in.</p>';
    
    echo '<p><b>How can I log in / access WordPress admin after construction mode has been enabled?</b><br>Enable the <a class="change_tab" data-tab="2" href="#login_button_wrap">Login Button</a> option under Content, and a login link will be shown in the lower right corner of the under construction page.</p>';
    
    echo '<p><b>How do I add my logo to the page?</b><br>Head over to <a class="change_tab" data-tab="2" href="#content_wrap">Content</a> and click "Add Media". Upload/select the logo, position it as you see fit and add other content.</p>';
    
    echo '<p><b>I\'ve made changes to UCP, but they are not visible. What do I do?</b><br>Click "Save Changes" one more time. Open your site and force refresh browser cache (Ctrl or Shift + F5). If that doesn\'t help it means you have a caching plugin installed. Purge/delete cache in that plugin or disable it.</p>';
    
    echo '<p><b>How can I get more designs? Where do I download them?</b><br>We update the plugin every 7-10 days and each update comes with at least one new theme/design. There is no other way of getting more designs nor a place to download them.</p>';
    
    echo '<p><b>How can I edit designs?</b><br>There is an option to add <a class="change_tab" data-tab="1" href="#custom_css">custom CSS</a>. If you want more than that you will have to edit the source files located in <code>/under-construction-page/themes/</code>.</p>';
    
    echo '<p><b>I have disabled UCP but the under construction page is still visible. How do I remove it?</b><br>Open your site and force refresh browser cache (Ctrl or Shift + F5). If that doesn\'t help it means you have a caching plugin installed. Purge/delete cache in that plugin or disable it.<br>If that fails too contact your hosting provider and ask to empty the site cache for you.</p>';
    
    echo '<h2><br>How to get support?</h2>';
    
    echo '<p>We do our very best to keep <span class="ucp-logo">UnderConstructionPage</span> bug free and compatible with all plugins and themes. If you run into a problem head over to the <a target="_blank" href="http://wordpress.org/support/plugin/under-construction-page">official support forum</a>, open a new thread, and we\'ll help you ASAP.</p>';
  } // tab_support
  
  
  // output the whole options page
  static function main_page() {
    if (!current_user_can('manage_options'))  {
      wp_die('You do not have sufficient permissions to access this page.');
    }

    $options = self::get_options();
    $default_options = self::default_options();

    echo '<div class="wrap">
          <h1 class="ucp-logo"><img src="' . UCP_PLUGIN_URL . '/images/ucp_logo.png" alt="UnderConstructionPage" title="UnderConstructionPage">UnderConstructionPage</h1>';

    echo '<form action="options.php" method="post" id="ucp_form">';
    settings_fields(UCP_OPTIONS_KEY);

    $tabs = array();
    $tabs[] = array('id' => 'ucp_main', 'icon' => 'dashicons-admin-settings', 'class' => '', 'label' => 'Main', 'callback' => array(__CLASS__, 'tab_main'));
    $tabs[] = array('id' => 'ucp_design', 'icon' => 'dashicons-admin-customizer', 'class' => '', 'label' => 'Design', 'callback' => array(__CLASS__, 'tab_design'));
    $tabs[] = array('id' => 'ucp_content', 'icon' => 'dashicons-format-aside', 'class' => '', 'label' => 'Content', 'callback' => array(__CLASS__, 'tab_content'));
    $tabs[] = array('id' => 'ucp_access', 'icon' => 'dashicons-shield', 'class' => '', 'label' => 'Access', 'callback' => array(__CLASS__, 'tab_access'));
    $tabs[] = array('id' => 'ucp_support', 'icon' => 'dashicons-sos', 'class' => '', 'label' => 'Support', 'callback' => array(__CLASS__, 'tab_support'));
    $tabs = apply_filters('ucp_tabs', $tabs);

    echo '<div id="ucp_tabs" class="ui-tabs" style="display: none;">';
    echo '<ul>';
    foreach ($tabs as $tab) {
      if(!empty($tab['label'])){  
          echo '<li><a href="#' . $tab['id'] . '" class="' . $tab['class'] . '"><span class="icon"><span class="dashicons ' . $tab['icon'] . '"></span></span><span class="label">' . $tab['label'] . '</span></a></li>';
      }
    }
    echo '</ul>';
    
    foreach ($tabs as $tab) {
      if(is_callable($tab['callback'])) {
        echo '<div style="display: none;" id="' . $tab['id'] . '">';
        call_user_func($tab['callback']);
        echo '</div>';
      }      
    } // foreach

    echo '</div>'; // ucp_tabs
    
    echo '<p class="submit">';
    echo get_submit_button('Save Changes', 'primary large', 'submit', false);
    echo ' &nbsp; &nbsp; <a id="ucp_preview" href="' . get_home_url() . '/?ucp_preview" class="button button-large button-secondary" target="_blank">Preview</a>';
    echo '</p>';
    
    echo '</form>'; // ucp_tabs
    echo '</div>'; // wrap
    
    echo '<div id="features-survey-dialog" style="display: none;" title="Please help us make UCP better"><span class="ui-helper-hidden-accessible"><input type="text"/></span>';
    echo '<p>We strive to add at least one new feature to <span class="ucp-logo">UnderConstructionPage</span> every week and want to make it as usefull as possible.<br><b>What new features do you need the most?</b> Please choose two:</p>';
    
    $questions = array();
    $questions[] = '<div class="question-wrapper" data-value="designs">' .
                   '<div class="question"><b>More designs like the ones we already have</b><br>' .
                   '<i>Fun, unique &amp; made just for the UCP plugin</i></div>' .
                   '<span class="dashicons dashicons-yes"></span>' .
                   '</div>';
    
    $questions[] = '<div class="question-wrapper" data-value="drag-drop">' .
                   '<div class="question"><b>Drag &amp; drop page designer</b><br>' .
                   '<i>Countdown timers, progress bars, text, images - the complete package</i></div>' .
                   '<span class="dashicons dashicons-yes"></span>' .
                   '</div>';
    
    $questions[] = '<div class="question-wrapper" data-value="analytics">' .
                   '<div class="question"><b>Built-in analytics</b><br>' .
                   '<i>No 3rd party service needed; simple &amp; fast statistics in UCP admin</i></div>' .
                   '<span class="dashicons dashicons-yes"></span>' .
                   '</div>';
    
    $questions[] = '<div class="question-wrapper" data-value="leads">' .
                   '<div class="question"><b>Optin form on construction page</b><br>' .
                   '<i>Collect visitors\' emails &amp; add them to an autoresponder</i></div>' .
                   '<span class="dashicons dashicons-yes"></span>' .
                   '</div>';
    
    $questions[] = '<div class="question-wrapper" data-value="custom">' .
                   '<div class="question"><b>Something we missed? Enter the feature you need below:</b><br>' .
                   '<input type="text" class="custom-input"></div>' .
                   '<span class="dashicons dashicons-yes"></span>' .
                   '</div>';
    
    shuffle($questions);
    echo implode(' ', $questions);
    
    $current_user = wp_get_current_user();
    echo '<div class="footer">';
    echo '<input id="emailme" type="checkbox" value="' . $current_user->user_email . '"> <label for="emailme">Email me on ' . $current_user->user_email . ' when new features are added</label><br>';
    echo '<a data-survey="features-survey" class="submit-survey button-primary button button-large" href="#">Add those features ASAP!</a>';
    echo '<a href="#" class="dismiss-survey" data-survey="features-survey"><small><i>Close the survey and never show it again</i></small></a>';
    echo '</div>';
    
    echo '</div>';
  } // options_page
  
  
  // reset all pointers to default state - visible
  static function reset_pointers() {
    $pointers = array();
    $pointers['welcome'] = array('target' => '#menu-settings', 'edge' => 'left', 'align' => 'right', 'content' => 'Thank you for installing the <b style="font-weight: 800; font-variant: small-caps;">UnderConstructionPage</b> plugin! Please open <a href="' . admin_url('options-general.php?page=ucp'). '">Settings - UnderConstruction</a> to create a beautiful under construction page.');
    
    update_option(UCP_POINTERS_KEY, $pointers);
  } // reset_pointers
  
  
  // reset pointers on activation
  static function activate() {
    self::reset_pointers();
  } // activate
  
  // clean up on deactivation
  static function deactivate() {
    delete_option(UCP_POINTERS_KEY);
    delete_option(UCP_NOTICES_KEY);
  } // deactivate
  
  
  // clean up on uninstall
  static function uninstall() {
    delete_option(UCP_OPTIONS_KEY);
    delete_option(UCP_META_KEY);
    delete_option(UCP_POINTERS_KEY);
    delete_option(UCP_NOTICES_KEY);
  } // uninstall
} // class UCP


// hook everything up
register_activation_hook(__FILE__, array('UCP', 'activate'));
register_deactivation_hook(__FILE__, array('UCP', 'deactivate'));
register_uninstall_hook(__FILE__, array('UCP', 'uninstall'));
add_action('init', array('UCP', 'init'));
add_action('plugins_loaded', array('UCP', 'plugins_loaded'));

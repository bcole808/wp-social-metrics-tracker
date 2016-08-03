<?php
/*
Plugin Name: Social Metrics Tracker
Plugin URI: https://github.com/bcole808/wp-social-metrics-tracker
Description: Collect and display social network shares, likes, tweets, and view counts of posts.
Version: 1.6.7
Author: Ben Cole
Author URI: http://www.bencole.net
License: GPLv2+
*/

/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 */


// Class Dependancies
require_once('MetricsUpdater.class.php');
require_once('data-sources/google_analytics.php');
include_once('SocialMetricsSettings.class.php');
include_once('SocialMetricsTrackerWidget.class.php');
include_once('SocialMetricsDebugger.class.php');

// Handlebars Autoloader
require_once('lib/Handlebars/Autoloader.php');
Handlebars_Autoloader::register();
// use Handlebars\Handlebars;


class SocialMetricsTracker {

	public $version = '1.6.7'; // for db upgrade comparison
	public $updater;
	public $options;
	protected $network_activated;
	protected $use_network_settings;


	public function __construct() {

		// Plugin activation hooks
		register_activation_hook( __FILE__, array($this, 'activate') );
		register_deactivation_hook( __FILE__, array($this, 'deactivate') );

		if (is_admin()) {
			add_action('admin_menu', array($this,'adminMenuSetup'));
			add_action('admin_enqueue_scripts', array($this, 'adminHeaderScripts'));
			add_action('plugins_loaded', array($this, 'version_check'));
			add_action('wp_dashboard_setup', array($this, 'dashboard_setup'));

			new socialMetricsSettings($this);
		}

		add_action('init', array($this, 'init'));

	} // end constructor

	public function init() {

		// Set up options
		$this->initOptions();

		// Ensure setup occurs for each blog when network activated
		if (empty($this->options)) $this->activate();

		// Development server notice
		if ($this->is_development_server()) {
			add_action('admin_notices', array($this, 'developmentServerNotice'));
		}

		$this->updater  = new MetricsUpdater($this);
		$this->debugger = new SocialMetricsDebugger($this);

		// Data export tool
		if (is_admin() && isset($_GET['smt_download_export_file']) && $_GET['smt_download_export_file'] && $_GET['page'] == 'social-metrics-tracker-export') {
			require('smt-export.php');
			smt_download_export_file($this);
		}
	}

	private function initOptions() {
		if (is_array($this->options)) return;

		if ( $this->use_network_settings() ) {
			$this->options = get_site_option('smt_settings', array());
		} else {
			$this->options = get_option('smt_settings', array());
		}

	}

	/**
	 * Returns true if this blog is multisite enabled and this plugin has been activated network wide
	 *
	 * @return bool
	 */
	public function is_active_for_network() {

		// Return cached value?
		if ( null !== $this->network_activated ) {
			return $this->network_activated;
		}

		// Single site
		if ( ! is_multisite() ) {
			return $this->network_activated = false;
		}

		// Multisite
		if ( !function_exists( 'is_plugin_active_for_network' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		return $this->network_activated = is_plugin_active_for_network( 'social-metrics-tracker/social-metrics-tracker.php' );
	}

	/**
	 * Returns true if we should always read/write to network settings instead of current blog
	 * Also updates the saved value if provided
	 *
	 * @param bool - Update the option
	 * @return bool
	 */
	public function use_network_settings($new_value=null) {

		// If unable to use_network_settings
		if ( !$this->is_active_for_network() ) {
			return $this->use_network_settings = false;
		}

		// Update saved value if provided
		if ( null !== $new_value  && strlen($new_value) > 0 ) {

			// Update option
			$this->use_network_settings = $new_value;
			update_site_option( 'smt_use_network_settings_everywhere', $this->use_network_settings );

			// Re-load options from DB (IMPORTANT!)
			$this->options = null;
			$this->initOptions();
			$this->add_missing_settings();
		}

		// Return cached value?
		if ( null !== $this->use_network_settings ) {
			return $this->use_network_settings;
		}

		// Get saved value, or default
		return $this->use_network_settings = get_site_option('smt_use_network_settings_everywhere', false);
	}

	/***************************************************
	* Renders a template using the Handlebars Engine
	***************************************************/
	public function renderTemplate($tpl, $data) {

		if (!isset($this->template_engine)) {

			$this->template_engine = new Handlebars_Engine(array(
			    'loader' => new Handlebars_Loader_FilesystemLoader(dirname(__FILE__).'/templates/'),
			    'partials_loader' => new Handlebars_Loader_FilesystemLoader(
			        dirname(__FILE__).'/templates/',
			        array(
			            'prefix' => '_'
			        )
			    )
			));

		}

		return $this->template_engine->render($tpl, $data);
	}

	// Determines if we are on a development or staging environment
	public function is_development_server() {
		return (defined('WP_ENV') && strtolower(WP_ENV) != 'production');
	}

	public function developmentServerNotice() {
		if (!current_user_can('manage_options')) return false;

		$screen = get_current_screen();

		if (!in_array($screen->base, array('social-metrics_page_social-metrics-tracker-settings', 'toplevel_page_social-metrics-tracker', 'social-metrics-tracker_page_social-metrics-tracker-debug'))) {
			return false;
		}

		$message = '<h3 style="margin-top:0;">Social Metrics data syncing is disabled</h3> You are on a development server; Social Network share data cannot be retrieved for private development URLs. ';

		$message .= "<ul><li>The PHP constant <b>WP_ENV</b> must be set to <b>production</b> or be undefined. WP_ENV is currently set to: <b>".WP_ENV."</b>. </li></ul>";

		printf( '<div class="error"> <p> %s </p> </div>', $message);

	}

	public function adminHeaderScripts() {

		wp_register_style( 'smt-css', plugins_url( 'css/social-metrics-tracker.min.css' , __FILE__ ), false, $this->version );
		wp_enqueue_style( 'smt-css' );

		wp_register_script( 'smt-js', plugins_url( 'js/social-metrics-tracker.min.js' , __FILE__ ), array('jquery', 'jquery-ui-datepicker'), $this->version );
		wp_enqueue_script( 'smt-js' );

		wp_enqueue_style('jquery-style', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');

	} // end adminHeaderScripts()

	public function adminMenuSetup() {

		// Add Social Metrics Tracker menu
		$visibility = ($this->options['smt_options_report_visibility']) ? $this->options['smt_options_report_visibility'] : 'manage_options';
		add_menu_page( 'Social Metrics Tracker', 'Social Metrics', $visibility, 'social-metrics-tracker', array($this, 'render_view_Dashboard'), 'dashicons-chart-area', '30.597831' );

		// Export page
		add_submenu_page('social-metrics-tracker', 'Data Export Tool', 'Export Data', $visibility, 'social-metrics-tracker-export',  array($this, 'render_view_export'));

	} // end adminMenuSetup()

	public function dashboard_setup() {
		if ($this->get_smt_option('display_widget')) new SocialMetricsTrackerWidget($this);
	}

	public function render_view_Dashboard() {
		require('smt-dashboard.php');
		smt_render_dashboard_view($this);
	} // end render_view_Dashboard()

	public function render_view_AdvancedDashboard() {
		require('smt-dashboard-debug.php');
		smt_render_dashboard_debug_view($this->options);
	} // end render_view_AdvancedDashboard()

	public function render_view_export() {
		require('smt-export.php');
		smt_render_export_view($this);
	}

	public function render_view_Settings() {
		require('smc-settings-view.php');
		smc_render_settings_view();
	} // end render_view_Settings()

	public static function timeago($time) {
		$periods = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
		$lengths = array("60","60","24","7","4.35","12","10");

		$now = current_time( 'timestamp' );

			$difference    = $now - $time;
			$tense         = "ago";

		for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
			$difference /= $lengths[$j];
		}

		$difference = round($difference);

		if($difference != 1) {
			$periods[$j].= "s";
		}

		return "$difference $periods[$j] ago";
	}

	/***************************************************
	* Return an array of the post types we are currently tracking
	***************************************************/
	public function tracked_post_types() {
		$types_to_track = array();

		$smt_post_types = get_post_types( array( 'public' => true ), 'names' );

		foreach ($smt_post_types as $type) {
			if (isset($this->options['smt_options_post_types_'.$type]) && $this->options['smt_options_post_types_'.$type] == $type) $types_to_track[] = $type;
		}

		$smt_post_types = apply_filters( 'smt_post_types', $smt_post_types );

		// If none selected, default post types
		return ($types_to_track) ? $types_to_track : array_values($smt_post_types);
	}

	/***************************************************
	* Check the version of the plugin and perform upgrade tasks if necessary
	***************************************************/
	public function version_check() {
		$this->initOptions();
		$installed_version = get_option( "smt_version" );

		if( $installed_version != $this->version ) {

			// **********************
			// Perform upgrade tasks:

			// 1: Update version number
			update_option( "smt_version", $this->version );

			// 2: If migrating from version below 1.3 (not a clean install)
			if ($installed_version !== false && version_compare($installed_version, '1.3', '<')) {

				// Do not require an initial full data sync for migrating users. 
				update_option( 'smt_last_full_sync', 1 );
			}

			// 3: If migrating from version below 1.4.0 (not a clean install)
			if ($installed_version !== false && version_compare($installed_version, '1.4.0', '<')) {

				// Prior to 1.4.0, the system defaulted to http:// URLs
				$this->set_smt_option('url_protocol', 'http');

				// The debug option is no longer in use
				$this->delete_smt_option('debug_mode');
			}

			// 4: If migrating from version below 1.4.1 (not a clean install)
			if ($installed_version !== false && version_compare($installed_version, '1.4.1', '<')) {
				$this->delete_smt_option('debug_report_visibility');
			}

			// 4: If migrating from version below 1.6.0 (not a clean install)
			if ($installed_version !== false && version_compare($installed_version, '1.6.0', '<')) {

				// users prior to this version had the following set of APIs enabled, so we should maintain that even if the defaults change. 
				$this->set_smt_option('api_enabled', array(
					'facebook'    => true,
					'twitter'     => true,
					'linkedin'    => true,
					'googleplus'  => true,
					'pinterest'   => true,
					'stumbleupon' => true
				));

				// Multisite installations should explicitly retain their behavior
				if ( is_multisite() ) {
					$this->use_network_settings( false );
				}
			}

			// 4: Add any new settings
			$this->add_missing_settings();

		}
	}

	/***************************************************
	* Runs at plugin activation;
	* Also runs at initialization in event that option defaults have not been set for some reason
	***************************************************/
	public function activate() {

		$this->add_missing_settings(); // Also saves the items above

		$this->version_check();
	}

	/***************************************************
	* Checks all of the settings and if any are undefined, adds them from the defaults
	***************************************************/
	public function add_missing_settings() {
		$this->initOptions();

		$updater = new MetricsUpdater($this);

		// Configure default options here;
		// They will be set only if a value does not already exist in the DB. 
		$defaults = array(
			'connection_type_facebook' => 'public',
			'post_types_post'          => 'post',
			'post_types_page'          => 'page',
		);

		// Allow overriding settings by default
		if ( $this->is_active_for_network() ) {
			if ( 'does-not-exist' === get_site_option('smt_use_network_settings_everywhere', 'does-not-exist') ) update_site_option( 'smt_use_network_settings_everywhere', 0 );
		}

		foreach ($defaults as $key => $value) {
			if ($this->get_smt_option($key) === false) {
				$this->set_smt_option($key, $value, false);
			}
		}

		// Merge the api_enabled array to ensure all APIs have a value
		$api_enabled_defaults = array();
		$api_enabled_current = $this->get_smt_option('api_enabled') ? $this->get_smt_option('api_enabled') : array();

		foreach ($updater->allSources() as $HTTPResourceUpdater) {
			$api_enabled_defaults[$HTTPResourceUpdater->slug] = $HTTPResourceUpdater->enabled_by_default;
		}

		$this->set_smt_option('api_enabled', array_merge($api_enabled_defaults, $api_enabled_current), false);

		// Load defaults from smt-general.php
		require('settings/smt-general.php');
		global $wpsf_settings;

		foreach ($wpsf_settings['smt']['fields'] as $default) {
			$key = $default['id'];

			if ($this->get_smt_option($key) === false) {
				$this->set_smt_option($key, $default['std'], false);
			}
		}

		$this->save_smt_options();
	}

	public function get_smt_options() {
		$this->initOptions();
		return $this->options;
	}

	/***************************************************
	* Get plugin option with the specified key
	***************************************************/
	public function get_smt_option($key) {
		$this->initOptions();
		return (array_key_exists('smt_options_'.$key, $this->options)) ? $this->options['smt_options_'.$key] : false;
	}

	/***************************************************
	* Update and optionally save plugin option with the specified key/value
	* (We might not want to save if we are bulk updating)
	***************************************************/
	public function set_smt_option($key, $val, $save = true) {
		$this->initOptions();
		$this->options['smt_options_'.$key] = $val;
		return ($save) ? $this->save_smt_options() : null;
	}

	/***************************************************
	* Remove specified option
	***************************************************/
	public function delete_smt_option($key) {
		$this->initOptions();
		unset($this->options['smt_options_'.$key]);
		return $this->save_smt_options();
	}

	/***************************************************
	* Merge another array of options into the current, use input to overwrite local settings
	***************************************************/
	public function merge_smt_options($options) {
		if (!is_array($options)) return false;
		$this->options = array_merge($this->options, $options);
		return $this->save_smt_options();
	}

	/***************************************************
	* Saves the settings to the DB
	***************************************************/
	private function save_smt_options() {
		if ( $this->use_network_settings() ) {
			return update_site_option('smt_settings', $this->options);
		} else {
			return update_option('smt_settings', $this->options);
		}
	}

	public function deactivate() {

		// Remove Queued Updates
		MetricsUpdater::removeAllQueuedUpdates();

	}

} // END SocialMetricsTracker

// Run plugin
$SocialMetricsTracker = new SocialMetricsTracker();

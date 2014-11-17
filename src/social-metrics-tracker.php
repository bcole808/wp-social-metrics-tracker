<?php
/*
Plugin Name: Social Metrics Tracker
Plugin URI: https://github.com/ChapmanU/wp-social-metrics-tracker
Description: Collect and display social network shares, likes, tweets, and view counts of posts.
Version: 1.3.2
Author: Ben Cole, Chapman University
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

class SocialMetricsTracker {

	public $version = '1.3.2'; // for db upgrade comparison
	public $updater;
	public $options;

	public function __construct() {

		// Plugin activation hooks
		register_activation_hook( __FILE__, array($this, 'activate') );
		register_deactivation_hook( __FILE__, array($this, 'deactivate') );

		if (is_admin()) {
			add_action('admin_menu', array($this,'adminMenuSetup'));
			add_action('admin_enqueue_scripts', array($this, 'adminHeaderScripts'));
			add_action('plugins_loaded', array($this, 'version_check'));
			add_action('wp_dashboard_setup', array($this, 'dashboard_setup'));
		}

		add_action('init', array($this, 'init'));

	} // end constructor

	public function init() {

		// Set up options
		$this->options = get_option('smt_settings');

		// Ensure setup occurs when network activated
		if ($this->options === false) $this->activate();

		// Check if we can enable data syncing
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

	// Determines if we are on a development or staging environment
	public function is_development_server() {
		return ((defined('WP_ENV') && strtolower(WP_ENV) != 'production') || (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] == '127.0.0.1'));
	}

	public function developmentServerNotice() {
		if (!current_user_can('manage_options')) return false;

		$screen = get_current_screen();

		if (!in_array($screen->base, array('social-metrics_page_social-metrics-tracker-settings', 'toplevel_page_social-metrics-tracker', 'social-metrics-tracker_page_social-metrics-tracker-debug'))) {
			return false;
		}

		$message = '<h3 style="margin-top:0;">Social Metrics data syncing is disabled</h3> You are on a development server; Social Network share data cannot be retrieved for private development URLs. <ul>';

		if ($_SERVER['REMOTE_ADDR'] == '127.0.0.1') {
			$message .= "<li>The server IP address appears to be set to 127.0.0.1 which is a local address. </li>";
		}

		if (defined('WP_ENV') && strtolower(WP_ENV) != 'production') {
			$message .= "<li>The PHP constant <b>WP_ENV</b> must be set to <b>production</b> or be undefined. WP_ENV is currently set to: <b>".WP_ENV."</b>. </li>";
		}

		$message .= '</ul>';

		printf( '<div class="error"> <p> %s </p> </div>', $message);

	}

	public function adminHeaderScripts() {

		wp_register_style( 'smt-css', plugins_url( 'css/social_metrics.css' , __FILE__ ), false, $this->version );
		wp_enqueue_style( 'smt-css' );

		wp_register_script( 'smt-js', plugins_url( 'js/social-metrics-tracker.js' , __FILE__ ), 'jquery', $this->version );
		wp_enqueue_script( 'smt-js' );

	} // end adminHeaderScripts()

	public function adminMenuSetup() {

		// Add Social Metrics Tracker menu
		$visibility = ($this->options['smt_options_report_visibility']) ? $this->options['smt_options_report_visibility'] : 'manage_options';
		add_menu_page( 'Social Metrics Tracker', 'Social Metrics', $visibility, 'social-metrics-tracker', array($this, 'render_view_Dashboard'), 'dashicons-chart-area', '30.597831' );

		// Add advanced stats menu
		if ($this->options['smt_options_debug_mode']) {
			$debug_visibility = ($this->options['smt_options_debug_report_visibility']) ? $this->options['smt_options_debug_report_visibility'] : 'manage_options';
			add_submenu_page('social-metrics-tracker', 'Relevancy Rank', 'Debug Info', $debug_visibility, 'social-metrics-tracker-debug',  array($this, 'render_view_AdvancedDashboard'));
		}

		// Export page
		add_submenu_page('social-metrics-tracker', 'Data Export Tool', 'Export Data', $visibility, 'social-metrics-tracker-export',  array($this, 'render_view_export'));

		new socialMetricsSettings($this);

	} // end adminMenuSetup()

	public function dashboard_setup() {
		new SocialMetricsTrackerWidget($this);
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

		$now = time();

			$difference     = $now - $time;
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
		unset($smt_post_types['attachment']);

		foreach ($smt_post_types as $type) {
			if (isset($this->options['smt_options_post_types_'.$type]) && $this->options['smt_options_post_types_'.$type] == $type) $types_to_track[] = $type;
		}

		// If none selected, default post types
		return ($types_to_track) ? $types_to_track : array_values($smt_post_types);
	}

	/***************************************************
	* Check the version of the plugin and perform upgrade tasks if necessary
	***************************************************/
	public function version_check() {
		$installed_version = get_option( "smt_version" );

		if( $installed_version != $this->version ) {
			update_option( "smt_version", $this->version );

			// IF migrating from version below 1.3
			if ($installed_version !== false && version_compare($installed_version, '1.3', '<')) {
				update_option( 'smt_last_full_sync', 1 );
			}

		}
	}

	public function activate() {

		// Add default settings
		if (get_option('smt_settings') === false) {

			require('settings/smt-general.php');

			global $wpsf_settings;

			foreach ($wpsf_settings[0]['fields'] as $setting) {
				$defaults['smt_options_'.$setting['id']] = $setting['std'];
			}

			// Track these post types by default
			$defaults['smt_options_post_types_post'] = 'post';
			$defaults['smt_options_post_types_page'] = 'page';

			add_option('smt_settings', $defaults);
		}

		$this->version_check();

	}

	public function deactivate() {

		// Remove Queued Updates
		MetricsUpdater::removeAllQueuedUpdates();

	}

} // END SocialMetricsTracker

// Run plugin
$SocialMetricsTracker = new SocialMetricsTracker();

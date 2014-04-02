<?php
/*
Plugin Name: Social Insight Dashboard
Plugin URI: https://github.com/ChapmanU/WP-Social-Insight-Dashboard
Description: Collect and display social network shares, likes, tweets, and view counts of posts.  
Version: 0.9 (Beta)
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
include('SocialInsightUpdater.class.php');

class SocialInsightDashboard {

	private $updater;
	private $options;

	public function __construct() {

		// Set up options
		$this->options = get_option('socialinsight_settings');

		// Plugin activation hooks
		register_activation_hook( __FILE__, array($this, 'activate') );
		register_deactivation_hook( __FILE__, array($this, 'deactivate') );
		register_uninstall_hook( __FILE__, array($this, 'uninstall') );

		if (is_admin()) {
			add_action('admin_menu', array($this,'adminMenuSetup'));
			add_action('admin_enqueue_scripts', array($this, 'adminHeaderScripts'));
		}

		// Check if we can enable data syncing
		if (defined('WP_ENV') && strtolower(WP_ENV) != 'production' || $_SERVER['REMOTE_ADDR'] == '127.0.0.1') {
			add_action('admin_notices', array($this, 'developmentServerNotice'));

		} else if (!is_array($this->options)) {
			add_action('admin_notices', array($this, 'optionsNotice'));
			
		} else {
			$this->updater = new SocialInsightUpdater($this->options);
		}

	} // end constructor

	public function developmentServerNotice() {
		if (!current_user_can('manage_options')) return false;

		$screen = get_current_screen();

		if (!in_array($screen->base, array('settings_page_social-insight-settings', 'toplevel_page_social-insight', 'social-insight_page_social-insight-advanced'))) {
			return false;
		}

		$message = '<h3 style="margin-top:0;">Social Insight Dashboard data syncing is disabled</h3> You are on a development server; Social Network share data cannot be retrieved for private development URLs. <ul>';

		if ($_SERVER['REMOTE_ADDR'] == '127.0.0.1') {
			$message .= "<li>The server IP address appears to be set to 127.0.0.1 which is a local address. </li>";
		}

		if (defined('WP_ENV') && strtolower(WP_ENV) != 'production') {
			$message .= "<li>The PHP constant <b>WP_ENV</b> must be set to <b>production</b> or be undefined. WP_ENV is currently set to: <b>".WP_ENV."</b>. </li>";
		}

		$message .= '</ul>';

		printf( '<div class="error"> <p> %s </p> </div>', $message);
		
	}

	public function optionsNotice() {
		if (!current_user_can('manage_options')) return false;

		printf( '<div class="error"> <p> %s </p> </div>', "Social Insight Dashboard data syncing is disabled. An administrator must <a class='login' href='options-general.php?page=social-insight-settings'>update the Social Insight Dashboard settings</a>." );
	}

	public function adminHeaderScripts() {
		wp_register_style( 'smc_social_metrics_css', plugins_url( 'css/social_insight.css' , __FILE__ ), false, '11-15-13' );
		wp_enqueue_style( 'smc_social_metrics_css' );
	} // end adminHeaderScripts()

	public function adminMenuSetup() {

		// Do not run unless options have been added!

		$icon = get_option('siteurl') . '/wp-content/plugins/' . basename(dirname(__FILE__)) . '/img/smc-social-metrics-icon.png';

		// Add Social Insight Dashboard menu
		add_menu_page( 'Social Insight Dashboard', 'Social Insight', $this->options['socialinsight_options_report_visibility'] ?: 'manage_options', 'social-insight', array($this, 'render_view_Dashboard'), $icon, 30 );

		// Add advanced stats menu
		if ($this->options['socialinsight_options_debug_mode']) {
			add_submenu_page('social-insight', 'Relevancy Rank', 'Debug Info', $this->options['socialinsight_options_advanced_report_visibility'] ?: 'manage_options', 'social-insight-advanced',  array($this, 'render_view_AdvancedDashboard'));
		}

		include_once('settings-setup.php');
		include_once('dashboard-widget.php');
		
	} // end adminMenuSetup()

	public function render_view_Dashboard() {
		require('smc-dashboard-view.php');
		smc_render_dashboard_view($this->options);
	} // end render_view_Dashboard()

	public function render_view_AdvancedDashboard() {
		require('smc-dashboard-view-2.php');
		smc_render_dashboard_2_view($this->options);
	} // end render_view_AdvancedDashboard()

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

	public function activate() {
		// Add default settings

		if (get_option('socialinsight_settings') === false) {

			require('settings.php');

			global $wpsf_settings;

			// $defaults = array("hello" => "test");

			foreach ($wpsf_settings[0]['fields'] as $setting) {
				$defaults['socialinsight_options_'.$setting['id']] = $setting['std'];
			}

			add_option('socialinsight_settings', $defaults);
		}

		// Sync all data
		$this->updater->scheduleFullDataSync();
		
	}

	public function deactivate() {

		// Remove Queued Updates
		$this->updater->removeAllQueuedUpdates();

	}

	public function uninstall() {

		// Delete options
		delete_option('socialinsight_settings');

		// Google Auth Tokens
		delete_site_option('smc_ga_token');
		delete_option('smc_ga_token');

	}

} // END SocialInsightDashboard

// Run plugin
$SocialInsightDashboard = new SocialInsightDashboard();

?>
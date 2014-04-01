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
		$this->updater = new SocialInsightUpdater($this->options);

		// Plugin activation hooks
		register_activation_hook( __FILE__, array($this, 'activate') );
		register_deactivation_hook( __FILE__, array($this, 'deactivate') );
		register_uninstall_hook( __FILE__, array($this, 'uninstall') );


		if (is_admin()) {
			add_action('admin_menu', array($this,'adminMenuSetup'));
			add_action('admin_enqueue_scripts', array($this, 'adminHeaderScripts'));
			add_action('admin_notices', array($this, 'adminNotices'));
		}

	} // end constructor

	public function adminNotices() {
		// WP_ENV is defined and we are not on production
		if (defined('WP_ENV') && strtolower(WP_ENV) != 'production') {

			add_action( 'admin_notices', 'social_insight_sync_disabled_message'); // Shows a notice after a post is moved. 

			function social_insight_sync_disabled_message() {
				if (current_user_can('manage_options')) {
			        printf( '<div class="error"> <p> %s </p> </div>', "Social Insight Dashboard data syncing is disabled because you are on a development server. The PHP constant <b>WP_ENV</b> must be set to <b>production</b>. Your WP_ENV is currently set to: ".WP_ENV );
				}
			}
		} else {
			// OKAY TO RUN UPDATER
			if(!is_array($this->options)) {

				$screen = get_current_screen();

				if ($screen->base != 'toplevel_page_social-insight' && $screen->base != 'social-insight_page_social-insight-advanced')
			    	printf( '<div class="error"> <p> %s </p> </div>', "Social Insight Dashboard data syncing is disabled. An administrator must <a class='login' href='options-general.php?page=social-insight-settings'>update the Social Insight Dashboard settings</a>." );
			}
		}
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
		add_submenu_page('social-insight', 'Relevancy Rank', 'Advanced Stats', $this->options['socialinsight_options_advanced_report_visibility'] ?: 'manage_options', 'social-insight-advanced',  array($this, 'render_view_AdvancedDashboard'));

		include_once('settings-setup.php');
		include_once('dashboard-widget.php');
		
	} // end adminMenuSetup()



	public function render_view_Dashboard() {
		require('smc-dashboard-view.php');
		smc_render_dashboard_view();
	} // end render_view_Dashboard()

	public function render_view_AdvancedDashboard() {
		require('smc-dashboard-view-2.php');
		smc_render_dashboard_2_view();
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

	}

	public function deactivate() {
		$this->updater->removeAllQueuedUpdates();
	}

	public function uninstall() {

		$this->updater->removeAllQueuedUpdates();

		delete_site_option('smc_ga_token');
		delete_option('smc_ga_token');
		delete_option('socialinsight_settings');

	}

} // END CLASS

// Run plugin
$SocialInsightDashboard = new SocialInsightDashboard();


global $smc_options;
$smc_options = get_option('socialinsight_settings');




?>
<?php
/*
Plugin Name: SMC Social Metrics
Plugin URI: 
Description: Retrieve and display social metrics including shares, likes, views, etc. 
Version: 0.1
Author: Ben Cole
Author URI: http://www.bencole.net
*/

global $smc_options;
$smc_options = get_option('socialinsight_settings');

// Retrieve the number of views for a post
function smc_get_views($post_id = 0) {
	$current_views = get_post_meta($post_id, "ga_pageviews", true);
	if(!isset($current_views) OR empty($current_views) OR !is_numeric($current_views) ) {
		$current_views = 0;
	}
	return $current_views;
}

// Connect to 3rd party services and sync stats
function smc_do_update($post_id) {

	if ($post_id <= 0) {
		return false;
	}

	global $smc_options; 
	$smc_options = get_option('socialinsight_settings');

	$permalink = get_permalink($post_id);

	// If social is being tracked, pull update
	if ($smc_options['socialinsight_options_enable_social']) {

		// Get JSON data from api.sharedcount.com
		$json = file_get_contents("http://api.sharedcount.com/?url=" . rawurlencode($permalink));
		$counts = json_decode($json, true);


		// Facebook
		if ($counts['Facebook']['total_count'] > 0) 
			update_post_meta($post_id, "socialcount_facebook", $counts['Facebook']['total_count']);
		// Twitter
		if ($counts['Twitter'] > 0) 
			update_post_meta($post_id, "socialcount_twitter", $counts['Twitter']);
		// Google+
		if ($counts['GooglePlusOne'] > 0) 
			update_post_meta($post_id, "socialcount_googleplus", $counts['GooglePlusOne']);
		// LinkedIn
		if ($counts['LinkedIn'] > 0) 
			update_post_meta($post_id, "socialcount_linkedin", $counts['LinkedIn']);
		// Pinterest
		if ($counts['Pinterest'] > 0) 
			update_post_meta($post_id, "socialcount_pinterest", $counts['Pinterest']);
		// Diggs
		if ($counts['Diggs'] > 0) 
			update_post_meta($post_id, "socialcount_diggs", $counts['Diggs']);
		// Delicious
		if ($counts['Delicious'] > 0) 
			update_post_meta($post_id, "socialcount_delicious", $counts['Delicious']);
		// Reddit
		if ($counts['Reddit'] > 0) 
			update_post_meta($post_id, "socialcount_reddit", $counts['Reddit']);
		// StumbleUpon
		if ($counts['StumbleUpon'] > 0) 
			update_post_meta($post_id, "socialcount_stumbleupon", $counts['StumbleUpon']);

		$total_count = $counts['Facebook']['total_count'] + $counts['Twitter'] +$counts['LinkedIn'] + $counts['GooglePlusOne'] + $counts['Pinterest'] + $counts['Diggs'] + $counts['Delicious'] + $counts['Reddit'] + $counts['StumbleUpon'];

		update_post_meta($post_id, "socialcount_TOTAL", $total_count);
	}

	// If analytics are being tracked, pull update
	if ($smc_options['socialinsight_options_enable_analytics']) {
		$smc_ga_token = unserialize(get_site_option('smc_ga_token'));

		if (strlen($smc_ga_token) > 1) {
			require_once ('smc-ga-query.php');
			$ga_pageviews = smc_ga_getPageviewsByURL($permalink, $smc_ga_token);
			if ($ga_pageviews > 0) {
				update_post_meta($post_id, "ga_pageviews", $ga_pageviews);
			}
		}
	}

	update_post_meta($post_id, "socialcount_LAST_UPDATED", time());

	/*
	// Method B (directly from each social network)

	$json = file_get_contents("http://api.ak.facebook.com/restserver.php?v=1.0&method=links.getStats&urls=".rawurlencode($permalink)."&format=json");
	$counts = json_decode($json, true);
	echo "the Facebook count was: ".$counts[0]['total_count'];

	$json = file_get_contents("http://urls.api.twitter.com/1/urls/count.json?url=".rawurlencode($permalink));
	$counts = json_decode($json, true);
	echo "Twitter returned: ".$counts['count'];
	*/

	return $total_count;
}

add_action( 'smc_update_single_post', 'smc_do_update', 10, 1 );

// Schedule an update on each individual page load
add_action("wp_head", "smc_schedule_update");
function smc_schedule_update($post_id) {
	$ttl = $smc_options['socialinsight_options_ttl_hours'] * 3600;

	if ($post_id <= 0) {
		global $post;
		$post_id = $post->ID;
	}

	if ($post_id <= 0) {
		return false;
	}

	$last_updated = get_post_meta($post_id, "socialcount_LAST_UPDATED", true);
	if ($last_updated < time() - $ttl) {

		// Schedule an update
		wp_schedule_single_event( time(), 'smc_update_single_post', array( $post_id ) );
	} 
}

// Return the social count total
function smc_get_socialcount($post_id = 0, $update = true) {
	// TTL of our local cache
	
	$total_count = 0;

	if (strlen($post_id) <= 0) {
		$post_id = get_the_id();
	}
	
	// Check if we need to schedule an update
	if ($update) {
		smc_schedule_update($post_id);
	}

	$total_count = get_post_meta($post_id, "socialcount_TOTAL", true);

	return $total_count;
}

// Admin menus
if ( is_admin() ){
	
	function smc_setup_menus () {
		global $smc_options; 
		$icon = get_option('siteurl') . '/wp-content/plugins/' . basename(dirname(__FILE__)) . '/img/smc-social-metrics-icon.png';
		add_menu_page( 'Social Insight Dashboard', 'Social Insight', $smc_options['socialinsight_options_report_visibility'], 'smc-social-insight', 'smc_social_insight_dashboard', '',100 );
	}
	
	add_action('admin_menu', 'smc_setup_menus');
	

	include_once('smc-settings-setup.php');

	add_action('admin_head', 'admin_register_head');
	function admin_register_head() {
	    $siteurl = get_option('siteurl');
	    $url = $siteurl . '/wp-content/plugins/' . basename(dirname(__FILE__)) . '/smc.css';
	    echo "<link rel='stylesheet' type='text/css' href='$url' />\n";
	}
	
	function smc_social_insight_dashboard() {
		
	 	require('smc-dashboard-view.php');
	 	smc_render_dashboard_view();
	}

	function smc_social_insight_settings() {
		require('smc-settings-view.php');
		smc_render_settings_view();
	}

	/* settings link in plugin management screen */
	function smc_social_insight_settings_link($actions, $file) {
	if(false !== strpos($file, 'smc-social-metrics'))
	 $actions['settings'] = '<a href="options-general.php?page=smc_settings">Settings</a>';
	return $actions; 
	}
	add_filter('plugin_action_links', 'smc_social_insight_settings_link', 2, 2);
	

	// register_activation_hook( __FILE__, 'smc_schedule_full_update' );
	function smc_schedule_full_update() {
		wp_schedule_single_event( time(), 'smc_schedule_full_update_cron' );
	}

	add_action( 'smc_schedule_full_update_cron', 'smc_do_full_update', 10 );
	function smc_do_full_update() {
		$querydata = query_posts(array(
		    'order'=>'desc',
		    'orderby'=>'post_date',
		    'posts_per_page'=>-1,
		    'post_status'   => 'publish'
		    // 'meta_query' => array(
		    //    'relation' => 'OR',
		    //     array(
		    //      'key' => 'ga_pageviews',
		    //      'compare' => 'NOT EXISTS', // works!
		    //      'value' => '' // This is ignored, but is necessary...
		    //     ),
		    //     array(
		    //      'key' => 'ga_pageviews',
		    //      'compare' => '<=',
		    //      'value' => '0'
		    //     )
		    // )
		)); 
		$nextTime = time();
		foreach ($querydata as $querydatum ) {
			wp_schedule_single_event( $nextTime, 'smc_update_single_post', array( $querydatum->ID ) );
			$nextTime = $nextTime + 10;
		}
	}

	register_deactivation_hook( __FILE__, 'smc_uninstall' );

	function smc_uninstall() {
		delete_site_option('smc_ga_token');
		delete_option('smc_ga_token');
		delete_option('socialinsight_settings');


	    $crons = _get_cron_array();
	    if ( !empty( $crons ) ) {
		    foreach( $crons as $timestamp => $cron ) {
		        if ( ! empty( $cron['smc_update_single_post'] ) )  {
		            unset( $crons[$timestamp]['smc_update_single_post'] );
		        }
		    }
		    _set_cron_array( $crons );
		}
	}

} // end admin

?>
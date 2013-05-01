<?php
/*
Plugin Name: SMC Social Metrics
Plugin URI: 
Description: Retrieve and display social metrics including shares, likes, views, etc. 
Version: 0.1
Author: Ben Cole
Author URI: http://www.bencole.net
*/

/**
 * Retrieve the number of views for a post
 */
function smc_get_views($post_id = 0) {

	$current_views = get_post_meta($post_id, "ga_pageviews", true);

	// Validate data
	if(!isset($current_views) OR empty($current_views) OR !is_numeric($current_views) ) {
		$current_views = 0;
	}

	return $current_views;
}

// Download the latest numbers from the web

function smc_do_update($post_id) {

	// debug
	// update_post_meta($post_id, "socialcount_TOTAL", 2);
	// update_post_meta($post_id, "ga_pageviews", 2);
	// $permalink = 'http://blogs.chapman.edu/students/2013/04/24/summer-job-web-design-and-development-assistant/';
	// return true;

	$permalink = get_permalink($post_id);

	// Method A (3rd party service)
	$json = file_get_contents("http://api.sharedcount.com/?url=" . rawurlencode($permalink));
	$counts = json_decode($json, true);

	$total_count = $counts['Facebook']['total_count'] + $counts['Twitter'] +$counts['LinkedIn'] + $counts['LinkedIn'];

	update_post_meta($post_id, "socialcount_facebook", $counts['Facebook']['total_count']);
	update_post_meta($post_id, "socialcount_twitter", $counts['Twitter']);
	update_post_meta($post_id, "socialcount_googleplus", $counts['GooglePlusOne']);
	update_post_meta($post_id, "socialcount_linkedin", $counts['LinkedIn']);
	update_post_meta($post_id, "socialcount_TOTAL", $total_count);
	

	// Get Google Analytics views
	require ('smc-ga-query.php');
	$ga_pageviews = smc_ga_getPageviewsByURL($permalink);
	if ($ga_pageviews > 0) {
		update_post_meta($post_id, "ga_pageviews", $ga_pageviews);
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

// Schedule an update
add_action("wp_head", "smc_schedule_update");
function smc_schedule_update($post_id) {
	$ttl = 3600;

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
		//wp_unschedule_event('1367431841','smc_update_single_post',array($post_id));

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
		add_menu_page( 'Commmunity Impact Dashboard', 'Social Insight', 'activate_plugins', 'smc_social_impact_browse', 'smc_social_impact_browse_output', '',100 );
	}

	add_action('admin_menu', 'smc_setup_menus');

	function admin_register_head() {
	    $siteurl = get_option('siteurl');
	    $url = $siteurl . '/wp-content/plugins/' . basename(dirname(__FILE__)) . '/smc.css';
	    echo "<link rel='stylesheet' type='text/css' href='$url' />\n";
	}
	add_action('admin_head', 'admin_register_head');

	function smc_social_impact_browse_output() {

	 	require('smc-dashboard-view.php');

	 	smc_render_dashboard_view();

	}

	register_activation_hook( __FILE__, 'smc_schedule_full_update' );
	function smc_schedule_full_update() {
		$querydata = query_posts(array(
		    'order'=>'desc',
		    'orderby'=>'post_date',
		    'posts_per_page'=>-1,
		    'post_status'   => 'publish'
		)); 
		$nextTime = time();
		foreach ($querydata as $querydatum ) {
			wp_schedule_single_event( $nextTime, 'smc_update_single_post', array( $querydatum->ID ) );
			$nextTime = $nextTime + 10;
		}
	}

} // end admin

?>
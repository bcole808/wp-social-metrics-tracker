<?php

/***************************************************
* This class attaches an updater function which runs when
* the Shared Count plugin updates an individual post.
***************************************************/

class SharedCountUpdater {
	public function __construct() {
		// hook into post updater
		add_action('social_metrics_data_sync', array($this, 'syncSharedCountData'), 10, 2);
	}

	public function syncSharedCountData($post_id, $post_url) {

		// reject if missing arguments
		if (!isset($post_id) || !isset($post_url))  return;

		// get social data from api.sharedcount.com
		$curl_handle = curl_init();

		curl_setopt($curl_handle, CURLOPT_URL, 'http://api.sharedcount.com/?url='.rawurlencode($post_url));
		curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);

		$json = curl_exec($curl_handle);

		curl_close($curl_handle);

		// reject if no response
		if (!strlen($json)) return;

		// decode social data from JSON
		$shared_count_service_data = json_decode($json, true);

		// prepare stats array
		$stats = array();

		// Stats we want to include in total
		$stats['facebook']    		= $shared_count_service_data['Facebook']['total_count'];
		$stats['twitter']     		= $shared_count_service_data['Twitter'];
		$stats['googleplus']  		= $shared_count_service_data['GooglePlusOne'];
		$stats['linkedin']    		= $shared_count_service_data['LinkedIn'];
		$stats['pinterest']   		= $shared_count_service_data['Pinterest'];
		$stats['diggs']       		= $shared_count_service_data['Diggs'];
		$stats['delicious']   		= $shared_count_service_data['Delicious'];
		$stats['reddit']      		= $shared_count_service_data['Reddit'];
		$stats['stumbleupon'] 		= $shared_count_service_data['StumbleUpon'];

		// Calculate total
		$stats['TOTAL'] = array_sum($stats);

		// Additional stats
		$stats['facebook_shares']   = $shared_count_service_data['Facebook']['share_count'];
		$stats['facebook_comments'] = $shared_count_service_data['Facebook']['comment_count'];
		$stats['facebook_likes']    = $shared_count_service_data['Facebook']['like_count'];

		// Calculate change since last update
		$delta = array();
		$old_meta = get_post_custom($post_id);
		foreach ($stats as $key => $value) if (is_int($value) && intval($old_meta['socialcount_'.$key][0])) $delta[$key] = $value - intval($old_meta['socialcount_'.$key][0]);

		// update post with populated stats
		foreach ($stats as $key => $value) if ($value) update_post_meta($post_id, 'socialcount_'.$key, $value);

		$this->saveToDB($post_id, $delta);

	}

	// Save only the change value to the DB
	private function saveToDB($post_id, $delta) {
		global $wpdb;

		$reset = date_default_timezone_get();
		date_default_timezone_set(get_option('timezone_string'));

		$args = array(
			'post_id' 	=> $post_id,
			'day_retrieved' => date("Y-m-d H:i:s", strtotime('today'))
		);

		date_default_timezone_set($reset);

		$existing = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix . "social_metrics_log WHERE post_id = ".$args['post_id']." AND day_retrieved = '".$args['day_retrieved']."'", ARRAY_A);

		if ($existing === null) {

			// Create new entry
			$wpdb->insert( $wpdb->prefix . "social_metrics_log", array_merge($args, $delta) );

		} else {

			// Add the existing values to the delta array
			foreach ($delta as $key => $val) if ($existing[$key] > 0) $delta[$key] = $existing[$key] + $val;

			// Update existing entry
			$wpdb->update($wpdb->prefix . "social_metrics_log", $delta, $args);
			
		}
	}
}

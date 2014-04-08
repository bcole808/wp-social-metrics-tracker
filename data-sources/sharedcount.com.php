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
		if (!$json) return;

		// decode social data from JSON
		$shared_count_service_data = json_decode($json, true);

		// prepare stats array
		$stats = array();

		// set social data to stats
		$stats['socialcount_facebook']    = $shared_count_service_data['Facebook']['total_count'];
		$stats['socialcount_twitter']     = $shared_count_service_data['Twitter'];
		$stats['socialcount_googleplus']  = $shared_count_service_data['GooglePlusOne'];
		$stats['socialcount_linkedin']    = $shared_count_service_data['LinkedIn'];
		$stats['socialcount_pinterest']   = $shared_count_service_data['Pinterest'];
		$stats['socialcount_diggs']       = $shared_count_service_data['Diggs'];
		$stats['socialcount_delicious']   = $shared_count_service_data['Delicious'];
		$stats['socialcount_reddit']      = $shared_count_service_data['Reddit'];
		$stats['socialcount_stumbleupon'] = $shared_count_service_data['StumbleUpon'];

		// set combined stat total
		$stats['socialcount_TOTAL'] = array_sum($stats);

		// update post with populated stats
		foreach ($stats as $key => $value) if ($value) update_post_meta($post_id, $key, $value);
	}
}

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
		$shared_count_service_data = $this->getData($post_url);

		if ($shared_count_service_data === false) return false;

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

		// update post with populated stats
		foreach ($stats as $key => $value) if ($value && $value > 0) update_post_meta($post_id, 'socialcount_'.$key, $value);

	}

	// Retrieve and return the data from the remote resource
	public function getData($url) {

		$curl_handle = curl_init();
		curl_setopt($curl_handle, CURLOPT_URL, 'http://api.sharedcount.com/?url='.rawurlencode($url));
		curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
		$data = curl_exec($curl_handle);
		curl_close($curl_handle);

		// reject if no response
		if (!strlen($data)) return false;

		// decode social data from JSON
		$shared_count_service_data = json_decode($data, true);

		return $shared_count_service_data;
	}

}

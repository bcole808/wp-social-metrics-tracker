<?php
/***************************************************
* This class attaches an updater function which runs when
* the Shared Count plugin updates an individual post. 
***************************************************/
class SharedCountUpdater {

	public function __construct() {

		// Hook this in to the post updater
		add_action( 'social_metrics_data_sync', array($this, 'syncSharedCountData'), 10, 2);

	}

	public function syncSharedCountData($post_id, $post_url) {

		if (!isset($post_id)) 	return false;
		if (!isset($post_url)) 	return false;

		// Get JSON data from api.sharedcount.com
		$curl_handle=curl_init();
		curl_setopt($curl_handle, CURLOPT_URL,"http://api.sharedcount.com/?url=" . rawurlencode($post_url));
		curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
		$json = curl_exec($curl_handle);
		curl_close($curl_handle);

		// Verify response
		if ($json !== false) {
			$shared_count_service_data = json_decode($json, true);

			// Load data into stats array
			$stats = array();
			$stats['socialcount_facebook'] 		= $shared_count_service_data['Facebook']['total_count'];
			$stats['socialcount_twitter'] 		= $shared_count_service_data['Twitter'];
			$stats['socialcount_googleplus'] 	= $shared_count_service_data['GooglePlusOne'];
			$stats['socialcount_linkedin'] 		= $shared_count_service_data['LinkedIn'];
			$stats['socialcount_pinterest'] 	= $shared_count_service_data['Pinterest'];
			$stats['socialcount_diggs'] 		= $shared_count_service_data['Diggs'];
			$stats['socialcount_delicious'] 	= $shared_count_service_data['Delicious'];
			$stats['socialcount_reddit']		= $shared_count_service_data['Reddit'];
			$stats['socialcount_stumbleupon'] 	= $shared_count_service_data['StumbleUpon'];

			// There is nothing else in the $stats array YET but we will add more later. We can use the sum for now. 
			$stats['socialcount_TOTAL'] = array_sum($stats);
			update_post_meta($post_id, "socialcount_TOTAL", $stats['socialcount_TOTAL']);

			// Facebook
			if ($stats['socialcount_facebook'] > 0) 
				update_post_meta($post_id, "socialcount_facebook", $stats['socialcount_facebook']);
			// Twitter
			if ($stats['socialcount_twitter'] > 0) 
				update_post_meta($post_id, "socialcount_twitter", $stats['socialcount_twitter']);
			// Google+
			if ($stats['socialcount_googleplus'] > 0) 
				update_post_meta($post_id, "socialcount_googleplus", $stats['socialcount_googleplus']);
			// LinkedIn
			if ($stats['socialcount_linkedin'] > 0) 
				update_post_meta($post_id, "socialcount_linkedin", $stats['socialcount_linkedin']);
			// Pinterest
			if ($stats['socialcount_pinterest'] > 0) 
				update_post_meta($post_id, "socialcount_pinterest", $stats['socialcount_pinterest']);
			// Diggs
			if ($stats['socialcount_diggs'] > 0) 
				update_post_meta($post_id, "socialcount_diggs", $stats['socialcount_diggs']);
			// Delicious
			if ($stats['socialcount_delicious'] > 0) 
				update_post_meta($post_id, "socialcount_delicious", $stats['socialcount_delicious']);
			// Reddit
			if ($stats['socialcount_reddit'] > 0) 
				update_post_meta($post_id, "socialcount_reddit", $stats['socialcount_reddit']);
			// StumbleUpon
			if ($stats['socialcount_stumbleupon'] > 0) 
				update_post_meta($post_id, "socialcount_stumbleupon", $stats['socialcount_stumbleupon']);

		} // end if $json !== false

		return;
	}

}


?>
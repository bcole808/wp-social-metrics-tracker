<?php
/***************************************************
* THIS CODE IS NOT CURRENTLY WORKING
*
* The Google Analytics data source is not ready for use yet; the code is still under development and does not work!
***************************************************/
require_once ('analytics_lib/my_google_analytics.php');

class GoogleAnalyticsUpdater {

	public function __construct() {

		$smc_ga_token = unserialize(get_site_option('smc_ga_token'));

		if (strlen($smc_ga_token) > 1) {
			
			// Execute GA API query
			$stats['ga_pageviews'] = smc_ga_getPageviewsByURL($permalink, $smc_ga_token);
			if ($stats['ga_pageviews'] > 0) {
				update_post_meta($post_id, "ga_pageviews", $stats['ga_pageviews']);
			}

		}

	} // end constructor

} // END CLASS

?>
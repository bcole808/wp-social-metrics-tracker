<?php
require ('lib/gapi.class.php');

function smc_ga_getPageviewsByURL($url) {

	$ga_access_token = 'DQAAALwAAAAMmGwmkyr1yx42ps9O3XMc-9RPoslFG3jeiabgjUjNCCr-UPBConbb-CRUm1S7vpSFF1JQB31k3r2U3QHRnxEC_vxVw-haikm9ryxz97WbVc8eYXH13HN8D4tI06FmnDnCZ6tqWWa23vXMn_aLEHZ_EKnOOc9yU1WbjJRcDMLXkheiMmdHgoFM6SfGTsrJWp6ujFJmCT4TZyP8lQUuPbzUey22k5lEMGrPkn4dTZtT4Y00zlWsqKOgD4eUhNdMyqc';
	$ga_profile_id = '58596075';

	// Validate URL
	if (filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
	    return false;
	}

	try {
		$url_parts = parse_url($url);

		$ga = new gapi('','', $ga_access_token);

		$ga->requestReportData(
			$ga_profile_id, //report_id
			array('pagePath'), // dimensions
			array('pageviews'), // metrics
			array('-pageviews'), //sort_metric
			'pagePath == '.$url_parts['path'], //filter string
			'2005-01-01', //start date = all time
			date('Y-m-d'), // end date = today
			1, // start_index
			1 // max_results
		);

		return $ga->getPageviews();

	} catch (Exception $e) {
		echo '<h1>ERROR:</h1> ', $e->getMessage();
	}

}
?>
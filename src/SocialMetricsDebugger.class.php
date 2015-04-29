<?php
/***************************************************
* This class does helpful debugging for the WP Social Metrics Tracker
***************************************************/

class SocialMetricsDebugger {

	private $pingback_url = 'http://api.socialmetricstracker.com/callback/stats';

	public function __construct($smt) {
		$this->smt = $smt;

		// Run a debug cron after the data finishes syncing
		if ($this->smt->get_smt_option('allow_debug_pingback')) {
			add_action('social_metrics_data_sync_complete', array($this, 'cronReportDebugStats'), 999);
		}

	}

	/**
	 * Returns an array of CircuitBreakers that are offline;
	 * Runs a single test if needed at the time.
	 *
	 * @return array
	 */
	public function getOfflineHTTPResourceUpdaters() {
		$offline = array();

		foreach ($this->smt->updater->getSources() as $name => $HTTPResourceUpdater) {
			$status = $HTTPResourceUpdater->wpcb->getStatusDetail();
			if ( $status['last_query_at'] === false) $this->executeResourceTest($HTTPResourceUpdater);
			if ( $status['working'] === false ) $offline[$name] = $HTTPResourceUpdater;
		}

		return $offline;
	}

	/**
	 * Immediately test connections to all social networks.
	 * !!! Runs tests regardless of circuit breaker status !!!
	 *
	 * @return boolean true,  - if all resources tested successfully
	 * @return array          - An array of the failed services
	 */
	public function testHTTPResourceUpdaters() {

		// Perform a test immediately, regardless of circuit breaker status
		foreach ($this->smt->updater->getSources() as $name => $HTTPResourceUpdater) {
			$this->executeResourceTest($HTTPResourceUpdater);
		}

		$offline = $this->getOfflineHTTPResourceUpdaters();

		return (count($offline) == 0) ? true : $offline;
	}


	/**
	 * Fires reportDebugStates once every 24 hours
	 *
	 * @return boolean (success or failure of the ping)
	 */
	public function cronReportDebugStats() {

		$last = intval($this->smt->get_smt_option('last_debug_pingback'));

		if (current_time( 'timestamp' ) - $last > MINUTE_IN_SECONDS) {
			return $this->reportDebugStats();
		}
	}

	/**
	 * Phone home and report helpful debug information to the plugin developer! :)
	 *
	 * @return boolean (success or failure of the ping)
	 */
	public function reportDebugStats() {

		// Do not allow reporting unless the user has explicitly authorized it
		if (!$this->smt->get_smt_option('allow_debug_pingback')) return false;

		$this->smt->set_smt_option('last_debug_pingback', current_time( 'timestamp' ));

		$args = array(
			'timeout'     => 3,
			'blocking'    => true,
			'headers'     => array('Content-type' => 'application/json'),
			'sslverify'   => true,
			'body'        => json_encode($this->buildDebugReport)
		);

		wp_remote_post($this->pingback_url, $args);

	}

	/**
	 * Builds a debug report
	 *
	 * @return array
	 */
	public function buildDebugReport() {
		global $wp_version;

		$options = $this->smt->options;

		if (array_key_exists('facebook_access_token', $options)) {
			$options['facebook_access_token'] = 'SECRET_KEY_HIDDEN__STRLEN_'.strlen($options['facebook_access_token']);
		}

		return array(
			'site_id' => get_home_url(),
			'is_multisite' => is_multisite(),
			'wordpress_version' => $wp_version,
			'plugin_version' => $this->smt->version,
			'plugin_settings' => $this->smt->options,
			'api_connection_status' => $this->buildConnectionStatusReport()
		);
	}

	/**
	 * Create an array of debug info with the current status of circuit breakers
	 *
	 * @return array
	 */
	private function buildConnectionStatusReport() {
		$items = array();
		foreach ($this->smt->updater->getSources() as $name => $HTTPResourceUpdater) {
			$items[] = array(
				'name' => $HTTPResourceUpdater->name,
				'slug' => $HTTPResourceUpdater->slug,
				'status' => $HTTPResourceUpdater->wpcb->getStatusDetail()
			);
		}
		return $items;
	}


	/***************************************************
	* Executes a test fetch for a given HTTPResourceUpdater and does nothing with the retrieved data.
	***************************************************/
	private function executeResourceTest($HTTPResourceUpdater) {

		$HTTPResourceUpdater->setParams(0, 'http://www.wordpress.org');
		$result = $HTTPResourceUpdater->fetch(true);

		$http_error_detail = array(
			'request_time'     => current_time('mysql'),
			'request_method'   => $HTTPResourceUpdater->resource_request_method,
			'request_uri'      => $HTTPResourceUpdater->resource_uri,
			'request_response' => $result
		);

		// If the connection did not fail, check to make sure we received data!
		if ($result !== false && $HTTPResourceUpdater->get_total() <= 0) {
			$HTTPResourceUpdater->wpcb->reportFailure('The connection was successful, but we did not receive the expected data from the social network.', $http_error_detail);
		}

	}

}

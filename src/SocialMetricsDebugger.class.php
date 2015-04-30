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

		// Ask the user to enable debug one time
		if ($this->smt->get_smt_option('allow_debug_pingback_last_prompt') === false) {
			add_action('smt_dashboard_before_table', array($this, 'displayDebugPrompt'));
			$this->maybeToggleDebugMode();
		}

	}

	/**
	 * Performs any tasks requested by URL params
	 *
	 * @return nothing
	 */
	public function maybeToggleDebugMode() {

		// Enable debug and hide message
		if (isset($_GET['smt_enable_debug_report'])) {
			$this->smt->set_smt_option('allow_debug_pingback', true);
			$this->smt->set_smt_option('allow_debug_pingback_last_prompt', current_time( 'timestamp' ));

			header("Location:".remove_query_arg('smt_enable_debug_report'));
		}

		// Disable debug and hide message
		if (isset($_GET['smt_disable_debug_report'])) {
			$this->smt->set_smt_option('allow_debug_pingback', false);
			$this->smt->set_smt_option('allow_debug_pingback_last_prompt', current_time( 'timestamp' ));

			header("Location:".remove_query_arg('smt_disable_debug_report'));
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
	 * Displays a nice prompt asking the user if they are willing to report debug stats
	 *
	 * @return prints to screen!
	 */
	public function displayDebugPrompt() {
		$args = array(
			'debug_report' => print_r($this->buildDebugReport(), true),
			'url_to_enable_reports'  => add_query_arg('smt_enable_debug_report', '1'),
			'url_to_disable_reports' => add_query_arg('smt_disable_debug_report', '1'),
		);
		print($this->smt->renderTemplate('debug-prompt-box', $args));
	}

	/**
	 * Fires reportDebugStates once every 24 hours
	 *
	 * @return boolean (success or failure of the ping)
	 */
	public function cronReportDebugStats() {

		$last = intval($this->smt->get_smt_option('last_debug_pingback'));

		if (current_time( 'timestamp' ) - $last > DAY_IN_SECONDS) {
			return $this->reportDebugStats();
		}
	}

	/**
	 * Phone home and report helpful debug information to the plugin developer! :)
	 *
	 * @return nothing
	 */
	public function reportDebugStats() {

		// Do not allow reporting unless the user has explicitly authorized it
		if (!$this->smt->get_smt_option('allow_debug_pingback')) return false;

		$this->smt->set_smt_option('last_debug_pingback', current_time( 'timestamp' ));

		$args = array(
			'timeout'     => 2,
			'blocking'    => true,
			'headers'     => array('Content-type' => 'application/json', 'Accept' => 'application/json'),
			'sslverify'   => true,
			'body'        => json_encode($this->buildDebugReport())
		);

		wp_remote_post($this->pingback_url, $args);

	}

	/**
	 * Builds a debug report
	 *
	 * @return (array) The debug report which will be sent
	 */
	public function buildDebugReport() {
		global $wp_version;

		$options = $this->smt->options;

		if (array_key_exists('smt_options_facebook_access_token', $options)) {
			$options['smt_options_facebook_access_token'] = 'SECRET_KEY_REMOVED__STRLEN_'.strlen($options['smt_options_facebook_access_token']);
		}

		return array(
			'site_id' => get_home_url(),
			'is_multisite' => (is_multisite()) ? 'true' : 'false',
			'wordpress_version' => $wp_version,
			'plugin_version' => $this->smt->version,
			'plugin_settings' => $options,
			'api_connection_status' => $this->buildConnectionStatusReport(),
		);
	}

	/**
	 * Create an array of debug info with the current status of circuit breakers
	 *
	 * @return (array) Just the API connection status portion of the debug report
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

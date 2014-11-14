<?php
/***************************************************
* This class does helpful debugging for the WP Social Metrics Tracker
***************************************************/

class SocialMetricsDebugger {

	public function __construct($smt) {
		$this->smt = $smt;
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

	/***************************************************
	* Executes a test fetch for a given HTTPResourceUpdater and does nothing with the retrieved data.
	***************************************************/
	private function executeResourceTest($HTTPResourceUpdater) {

		$HTTPResourceUpdater->setParams(0, 'http://www.wordpress.org');
		$result = $HTTPResourceUpdater->fetch(true);

		// If the connection did not fail, check to make sure we received data!
		if ($result !== false && $HTTPResourceUpdater->get_total() <= 0) {
			$HTTPResourceUpdater->wpcb->reportFailure('The connection was successful, but we did not receive the expected data from the social network.');
		}

	}

}

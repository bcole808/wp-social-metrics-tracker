<?php

/***************************************************
* A circuit breaker helper which helps an application gracefully fall back
* when a remote service becomes unavailable. State persists between WordPress
* sessions by using the Transients API.
***************************************************/

class WordPressCircuitBreaker {

	public $identifier = '';
	private $prefix = 'smt_wpcb_';

	//
	// OPTIONS
	public $persist_ttl = WEEK_IN_SECONDS; // Reset state if no activity for this period of time.
	// public $permanant_offline_failures = 100; // Resource is considered gone until manual intervention

	public function __construct($identifier, $options = null) {

		$this->identifier = $identifier;

		$this->load();

		// Accept new options
		if ($options['max_failures']) $this->set('max_failures', $options['max_failures']);
		if ($options['time_to_wait']) $this->set('time_to_wait', $options['time_to_wait']);

		// Defaults
		if (!$this->get('max_failures')) $this->set('max_failures', 3); // Failures to trigger offline status
		if (!$this->get('time_to_wait')) $this->set('time_to_wait', 3 * HOUR_IN_SECONDS); // How long between checks of an offline service

	}

	/***************************************************
	* Allow connections through when the service is online,
	* as well as once in a while to check when a service is down.
	***************************************************/
	public function readyToConnect() {

		// Not offline yet!
		if ($this->get('fail_count') < $this->get('max_failures')) return true;

		// Offline, check if we should allow one attempt
		return ($this->getTime() > ($this->get('last_query_time') + $this->get('time_to_wait')));

	}

	/***************************************************
	* Get some useful information about the service status
	***************************************************/
	public function getStatusDetail() {
		return array(
			'working'       => $this->get('fail_count') == 0,
			'fail_count'    => $this->get('fail_count'),
			'error_message' => $this->get('error_message'),
			'last_query_at' => $this->get('last_query_time'),
			'next_query_at' => ($this->readyToConnect()) ? $this->getTime() : $this->get('last_query_time') + $this->get('time_to_wait'),
		);
	}

	/***************************************************
	* Application should report each success
	***************************************************/
	public function reportSuccess() {
		$this->set('fail_count', 0);
		$this->set('last_query_time', $this->getTime());

		$this->save();
	}

	/***************************************************
	* Application should report each failure
	***************************************************/
	public function reportFailure($message = 'An error occured, but no error message was reported.') {
		$this->set('fail_count', $this->get('fail_count') + 1);
		$this->set('last_query_time', $this->getTime());
		$this->set('error_message', $message);

		$this->save();
	}

	/***************************************************
	* Get a value
	***************************************************/
	public function get($key) {
		return isset($this->data[$key]) ? $this->data[$key] : false;
	}

	/***************************************************
	* Set a value
	***************************************************/
	private function set($key, $val) {
		$this->data[$key] = $val;
	}

	/***************************************************
	* Load saved state
	***************************************************/
	private function load() {
		if (is_multisite()) {
			$this->data = get_site_transient($this->prefix . $this->identifier);
		} else {
			$this->data = get_transient($this->prefix . $this->identifier);
		}
	}

	/***************************************************
	* Write current state
	***************************************************/
	private function save() {
		if (is_multisite()) {
			set_site_transient( $this->prefix . $this->identifier, $this->data, $this->persist_ttl );
		} else {
			set_transient( $this->prefix . $this->identifier, $this->data, $this->persist_ttl );
		}
	}

	/***************************************************
	* Get the current time
	***************************************************/
	public function getTime() {
		return current_time( 'timestamp' );
	}
}

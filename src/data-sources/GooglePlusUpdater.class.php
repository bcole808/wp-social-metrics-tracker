<?php

/***************************************************
* This class attaches an updater function which runs when
* the Shared Count plugin updates an individual post.
***************************************************/

class GooglePlusUpdater extends HTTPResourceUpdater {

	public $slug  = 'googleplus';
	public $name  = 'Google Plus';

	public $enabled_by_default = false;

	private $uri = 'https://clients6.google.com/rpc';

	public function __construct() {
		$this->updater = parent::__construct($this->slug, $this->name, $this->uri);
	}

	public function setParams($post_id, $post_url = false) {
		parent::setparams($post_id, $post_url);

		$this->updater->resource_request_method = 'post';

		$this->updater->resource_params = array(
			'method' => 'pos.plusones.get',
			'id' => 'p',
			'params' => array(
				'nolog' => true,
				'id' => $this->updater->post_url,
				'source' => 'widget',
				'userId' => '@viewer',
				'groupId' => '@self'
			),
			'jsonrpc' => '2.0',
			'key' => 'p',
			'apiVersion' => 'v1'
		);
	}

	public function parse() {
		$updater = $this->updater;
		if (!is_array($updater->data)) return false;

		$updater->meta = array();

		if ( $this->updater->data !== null && isset($this->updater->data['result']) ) {
			$updater->meta[$this->updater->meta_prefix.$this->updater->slug] = $this->get_total();
		}

	}

	public function get_total() {

		if ( ($this->updater->data === null) ) return 0;
		if ( !isset($this->updater->data['result']) ) return 0;

		return intval($this->updater->data['result']['metadata']['globalCounts']['count']);

	}

	/**
	 * Checks the response body and reports status to circuit breaker
	 * @param $response
	 */
	public function confirmResponse($response) {
		if ( !$response ) {
			$this->wpcb->reportFailure($this->http_error, $this->http_error_detail);
		} else if ( $this->updater->data === null || !isset($this->updater->data['result']) ) {
			$this->wpcb->reportFailure('The connection was successful, but we did not receive the expected data from Google.', $this->updater->data);
		} else {
			$this->wpcb->reportSuccess();
		}
	}

}

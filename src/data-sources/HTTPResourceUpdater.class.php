<?php

/***************************************************
* This class is a framework for social media APIs which
* allow us to call them via a HTTP GET request.
***************************************************/

require_once('WordPressCircuitBreaker.class.php');

abstract class HTTPResourceUpdater {

	public $resource_uri;
	public $resource_params;
	public $resource_request_method;

	public $data;
	public $meta;

	public $slug;
	public $name;

	public $meta_prefix = 'socialcount_';

	public $http_error = '';
	public $complete;

	public function __construct($slug, $name, $resource_uri) {

		$this->slug = $slug;
		$this->name = $name;
		$this->resource_uri = $resource_uri;

		$this->wpcb = new WordPressCircuitBreaker($slug);

		return $this;
	}

	/***************************************************
	* Update all the data for a given post
	*
	* Note: $post_url is required to be set explicilty because it might be filtered by the MetricsUpdater class.
	***************************************************/
	public function sync($post_id, $post_url) {

		// Validation
		if (!isset($post_id)  || !is_int($post_id))     return false;
		if (!isset($post_url) || !is_string($post_url)) return false;

		// Set args
		$this->setParams($post_id, $post_url);

		// Perform sync
		$this->fetch();
		$this->parse();
		$this->save();

		return $this->complete;
	}

	/***************************************************
	* Prepare our params
	***************************************************/
	public function setParams($post_id, $post_url = false) {
		if ($post_url === false) $post_url = get_permalink($post_id);

		$this->post_url = $post_url;
		$this->post_id  = $post_id;

		$this->complete = false;
		$this->data     = null;
		$this->meta     = array();

	}

	/***************************************************
	* Retrieve data from our remote resource
	***************************************************/
	public function fetch($force = false) {

		// Validation
		if (!is_array($this->resource_params)) return false;

		// Circuit breaker
		if (!$this->wpcb->readyToConnect() && !$force) return false;

		// Get the data
		$result = $this->getURL($this->resource_uri, $this->resource_params, $this->resource_request_method);

		// Report to circuit breaker
		if (!$result) {
			$this->wpcb->reportFailure($this->http_error);
		} else {
			$this->wpcb->reportSuccess();
		}

		return $this->data = (strlen($result) > 0) ? $this->jsonp_decode($result, true) : false;
	}

	/***************************************************
	* Maps retrieved data to corresponding meta fields
	***************************************************/
	abstract function parse();

	/***************************************************
	* Return the total of social points
	***************************************************/
	abstract function get_total();

	/***************************************************
	* Retrieve the contents of a remote URL
	***************************************************/
	public function getURL($url, $post_params = null, $method = 'get') {

		$method = ($method) ? $method : 'get';

		$args = array(
			'timeout'     => 3,
			'blocking'    => true,
			'headers'     => array('Content-type' => 'application/json'),
			'sslverify'   => true
		);

		switch (strtolower($method)) {
			case 'post':
				$args['body'] = json_encode($post_params);
				$response = wp_remote_post($url, $args);
				break;

			case 'get' :
				$response = wp_remote_get($url . '?' . http_build_query($this->resource_params, '', '&'), $args);
				break;
		}

		if (is_wp_error($response)) {
			$this->http_error = $response->get_error_message();
			return false;
		} else if ($response['response']['code'] != 200) {
			$this->http_error = "Received HTTP response code: <b>".$response['response']['code']." ".$response['response']['message']."</b>";
		}

		return wp_remote_retrieve_body($response);
	}

	/***************************************************
	* Writes post meta fields to database
	***************************************************/
	public function save() {
		if (!isset($this->meta) || count($this->meta) == 0) return false;

		// Update each custom field
		foreach ($this->meta as $key => $value) {
			if (!$value) continue;
			if (is_numeric($value) && intval($value) <= 0) continue;

			if (update_post_meta($this->post_id, $key, $value)) {
				$this->complete = true;
			}
		}

		return $this->complete;
	}

	/***************************************************
	* jsonp_decode handles either json or jsonp strings
	***************************************************/
	private function jsonp_decode($input, $assoc = false) {
		if ($input[0] !== '[' && $input[0] !== '{') {
			$input = substr($input, strpos($input, '('));
			$input = trim($input,'();');
		}
		return json_decode($input, $assoc);
	}

}

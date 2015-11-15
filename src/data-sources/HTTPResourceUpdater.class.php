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

	public $enabled_by_default = false;

	public $http_error = '';
	public $http_error_detail = '';
	public $complete;

	public function __construct($slug, $name, $resource_uri) {

		$this->slug = $slug;
		$this->name = $name;
		$this->resource_uri = $resource_uri;

		$this->wpcb = new WordPressCircuitBreaker($slug);

		return $this;
	}

	/**
	***************************************************
	* Update all the data for a given post
	*
	* @param  integer   $post_id - The ID of the post to update
	* @param  string    $post_url - The permalink to query social APIs with
	*
	* @return (boolean | array) this will be the array of meta data OR false if the sync failed.
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

		return $this->getMetaFields();
	}

	/***************************************************
	* Prepare our params
	***************************************************/
	public function setParams($post_id, $post_url = false) {
		if ($post_url === false) $post_url = get_permalink($post_id);

		$this->post_url = $post_url;
		$this->post_id  = $post_id;

		$this->complete   = false;
		$this->http_error = null;
		$this->data       = null;
		$this->meta       = array();

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


		// Return either a json_decoded array, or a string, or false (in that order)
		if (strlen($result) > 0) {
			$decoded_result = $this->jsonp_decode($result, true);
			$this->data = ($decoded_result) ? $decoded_result : $result;
		} else {
			$this->data = false;
		}

		// Report to circuit breaker
		$this->confirmResponse($result);

		return $this->data;
	}

	/**
	 * Checks the response body and reports status to circuit breaker
	 * @param $response
     */
	public function confirmResponse($response) {
		if ( !$response ) {
			$this->wpcb->reportFailure($this->http_error, $this->http_error_detail);
		} else {
			$this->wpcb->reportSuccess();
		}
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
				$request_uri = $url;
				$response = wp_remote_post($request_uri, $args);
				break;

			case 'get' :
				$request_uri = $url . '?' . http_build_query($this->resource_params, '', '&');
				$response = wp_remote_get($request_uri, $args);
				break;
		}

		if (is_wp_error($response)) {
			$this->http_error = $response->get_error_message();
			$this->http_error_detail = array(
				'request_time'     => current_time('mysql'),
				'request_method'   => $method,
				'request_uri'      => $request_uri,
				'request_response' => $response
			);
			return false;
		} else if ($response['response']['code'] != 200) {
			$this->http_error = '';

			// Attempt to build a helpful error message (provided by Facebook)
			$body = $this->jsonp_decode($response['body'], true);

			if ($body && strlen($body['error']['message']) > 1) {
				$this->http_error .= $body['error']['message'];
			}

			if ($body && strlen($body['error']['type']) > 1) {
				$this->http_error .= ' ('.$body['error']['type'].' '.$body['error']['code'].'). ';
			}

			// Generic error message
			$this->http_error .= "Received HTTP response code: ".$response['response']['code']." ".$response['response']['message'];

			$this->http_error_detail = array(
				'request_time'     => current_time('mysql'),
				'request_method'   => $method,
				'request_uri'      => $request_uri,
				'request_response' => $response
			);
			return false;
		}

		return wp_remote_retrieve_body($response);
	}

	/***************************************************
	* Return an array of post meta fields that need to be saved; filters invalid values.
	***************************************************/
	public function getMetaFields() {
		if (!isset($this->meta) || count($this->meta) == 0) return false;

		$fields = array();

		foreach ($this->meta as $key => $value) {
			if (!is_numeric($value)) continue;

			$fields[$key] = $value;
		}

		return (count($fields) > 0) ? $fields : false;
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

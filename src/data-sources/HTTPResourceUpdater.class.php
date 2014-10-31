<?php

/***************************************************
* This class is a framework for social media APIs which
* allow us to call them via a HTTP GET request.
***************************************************/

abstract class HTTPResourceUpdater {

	public $resource_uri;
	public $resource_params;
	public $resource_request_method;

	public $data;
	public $meta;

	public $meta_prefix = 'socialcount_';

	public function __construct($shortname, $resource_uri) {

		$this->shortname = $shortname;
		$this->resource_uri = $resource_uri;

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
	public function fetch() {

		if (!is_array($this->resource_params)) return false;

		if (strtolower($this->resource_request_method) == 'post') {
			$data = $this->getURL($this->resource_uri, $this->resource_params);
		} else {
			$get_query = $this->resource_uri . '?' . http_build_query($this->resource_params);
			$data = $this->getURL($get_query);
		}

		return $this->data = (strlen($data) > 0) ? $this->jsonp_decode($data, true) : false;
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
	public function getURL($url, $post_params = null) {

		$curl_handle = curl_init();
		curl_setopt($curl_handle, CURLOPT_URL, $url);
		curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Content-type: application/json'));

		if (is_array($post_params)) {
			curl_setopt($curl_handle, CURLOPT_POSTFIELDS, json_encode($post_params));
		}

		$response = curl_exec($curl_handle);
		curl_close($curl_handle);

		return $response;
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

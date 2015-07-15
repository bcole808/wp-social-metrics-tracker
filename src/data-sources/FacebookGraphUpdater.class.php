<?php

/***************************************************
* This class attaches an updater function which runs when
* the Shared Count plugin updates an individual post.
***************************************************/

class FacebookGraphUpdater extends HTTPResourceUpdater {

	public $slug  = 'facebook';
	public $name  = 'Facebook';

	public $enabled_by_default = true;

	private $uri = 'https://graph.facebook.com/v2.3';

	public function __construct($access_token=false) {
		$this->updater = parent::__construct($this->slug, $this->name, $this->uri);

		if ($access_token) $this->setAccessToken($access_token);
	}

	public function setAccessToken($access_token) {
		$this->access_token = $access_token;

		// If params were already set...
		if (is_array($this->updater->resource_params)) {
			$this->updater->resource_params['access_token'] = $this->access_token;
		}
	}

	// Connect to Facebook and validate an App ID and App Secret; return an App Access Token
	public function requestAccessToken($app_id, $app_secret) {
		if (strlen($app_id) == 0 || strlen($app_secret) == 0) return false;

		$oauth_uri = "https://graph.facebook.com/v2.3/oauth/access_token?client_id=$app_id&client_secret=$app_secret&grant_type=client_credentials";

		$response = wp_remote_get($oauth_uri);
		if (is_wp_error($response)) return false;

		$data = json_decode(wp_remote_retrieve_body($response), true);

		if ($response['response']['code'] != 200) {

			$this->error_message = $data['error']['message'] . '('.$data['error']['type'].' '.$data['error']['code'].')';
			return false;
		}

		return strlen($data['access_token']) ? $data['access_token'] : false;
	}

	public function setParams($post_id, $post_url = false) {
		parent::setparams($post_id, $post_url);

		$url = $this->updater->post_url;

		$this->updater->resource_params = array(
			'id' => $url,
			'fields' => 'og_object{engagement}'
		);

		// Append the access token, if set
		if (isset($this->access_token)) {
			$this->setAccessToken($this->access_token);
		}

		// Note: The final encoded URL should look a bit like this:
		// https://graph.facebook.com/v2.3/?id=http://www.wordpress.org&fields=og_object{engagement}&access_token=TOKEN_HERE
	}

	public function parse() {
		$updater = $this->updater;
		if (!is_array($updater->data)) return false;

		$updater->meta = array();
		$updater->meta[$this->updater->meta_prefix.$this->updater->slug] = $this->get_total();

	}

	// Must return an integer
	public function get_total() {

		// Validation
		if (!is_array($this->updater->data)) return 0;
		if (!isset($this->updater->data['og_object'])) return 0;
		if (!isset($this->updater->data['og_object']['engagement'])) return 0;
		if (!isset($this->updater->data['og_object']['engagement']['count'])) return 0;

		// Return count
		return intval($this->updater->data['og_object']['engagement']['count']);
	}

}

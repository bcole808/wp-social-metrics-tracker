<?php

/***************************************************
* This class attaches an updater function which runs when
* the Shared Count plugin updates an individual post.
***************************************************/

class FacebookGraphUpdater extends HTTPResourceUpdater {

	public $slug  = 'facebook';
	public $name  = 'Facebook';

	private $uri = 'https://graph.facebook.com/v2.0/fql';

	public function __construct() {
		$this->updater = parent::__construct($this->slug, $this->name, $this->uri);
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
			// This FQL query will be URL encoded by http_build_query()
			'q' => "SELECT url, share_count, like_count, comment_count, total_count, click_count FROM link_stat where url='$url'"
		);

		// Append the access token, if set
		if (isset($this->access_token)) {
			$this->setAccessToken($this->access_token);
		}

		// Note: The final encoded URL should look a bit like this:
		// https://graph.facebook.com/v2.0/fql?q=SELECT%20url,%20share_count,%20like_count,%20comment_count,%20total_count,%20click_count%20FROM%20link_stat%20where%20url=%27http://www.wordpress.org%27
	}

	public function parse() {
		$updater = $this->updater;
		if (!is_array($updater->data) || !isset($updater->data['data'])) return false;

		$updater->meta = array();
		$updater->meta[$this->updater->meta_prefix.$this->updater->slug] = $this->get_total();

		// Do not process further if there is no data to parse
		if (count($updater->data['data']) == 0) return;

		$updater->meta['facebook_comments']    = $updater->data['data'][0]['comment_count'];
		$updater->meta['facebook_shares']      = $updater->data['data'][0]['share_count'];
		$updater->meta['facebook_likes']       = $updater->data['data'][0]['like_count'];
	}

	public function get_total() {
		return ($this->updater->data === null || count($this->updater->data['data']) == 0) ? 0 : $this->updater->data['data'][0]['total_count'];
	}

}

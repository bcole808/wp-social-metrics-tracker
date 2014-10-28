<?php

/***************************************************
* This class attaches an updater function which runs when
* the Shared Count plugin updates an individual post.
***************************************************/

class TwitterUpdater extends HTTPResourceUpdater {

	public function __construct() {
		$this->updater = parent::__construct();
		$this->updater->resource_uri = 'http://urls.api.twitter.com/1/urls/count.json';
	}

	public function setParams($post_id, $post_url = false) {
		parent::setparams($post_id, $post_url);

		$this->updater->resource_params = array(
			'url' => $this->updater->post_url
		);
	}

	public function parse() {
		$updater = $this->updater;
		if (!is_array($updater->data)) return false;

		$updater->meta = array();
		$updater->meta['socialcount_twitter'] = $this->get_total();
	}

	public function get_total() {
		return ($this->updater->data === null) ? 0 : $this->updater->data['count'];
	}

}

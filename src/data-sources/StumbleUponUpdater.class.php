<?php

/***************************************************
* This class attaches an updater function which runs when
* the Shared Count plugin updates an individual post.
***************************************************/

class StumbleUponUpdater extends HTTPResourceUpdater {

	public function __construct() {
		$this->updater = parent::__construct();
		$this->updater->resource_uri = 'http://www.stumbleupon.com/services/1.01/badge.getinfo';
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
		$updater->meta['socialcount_stumbleupon'] = $this->get_total();
	}

	public function get_total() {
		return ($this->updater->data === null || $this->updater->data['result']['in_index'] == false) ? 0 : $this->updater->data['result']['views'];
	}

}

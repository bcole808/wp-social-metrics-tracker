<?php

/***************************************************
* This class attaches an updater function which runs when
* the Shared Count plugin updates an individual post.
***************************************************/

class GooglePlusUpdater extends HTTPResourceUpdater {

	public $slug  = 'googleplus';
	public $name  = 'Google Plus';

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
		$updater->meta[$this->updater->meta_prefix.$this->updater->slug] = $this->get_total();
	}

	public function get_total() {
		return ($this->updater->data === null) ? 0 : $this->updater->data['result']['metadata']['globalCounts']['count'];
	}

}

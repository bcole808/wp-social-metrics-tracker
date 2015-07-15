<?php

/***************************************************
* This class attaches an updater function which runs when
* the Shared Count plugin updates an individual post.
***************************************************/

class XingUpdater extends HTTPResourceUpdater {

	public $slug  = 'xing';
	public $name  = 'XING';

	public $enabled_by_default = false;

	private $uri = 'https://www.xing-share.com/spi/shares/statistics';

	public function __construct() {
		$this->updater = parent::__construct($this->slug, $this->name, $this->uri);
	}

	public function setParams($post_id, $post_url = false) {
		parent::setparams($post_id, $post_url);

		$this->updater->resource_request_method = 'post';

		$this->updater->resource_params = array(
			'url' => $this->updater->post_url
		);
	}

	public function parse() {
		$updater = $this->updater;
		if (!is_array($updater->data)) return false;

		$updater->meta = array();
		$updater->meta[$this->updater->meta_prefix.$this->updater->slug] = $this->get_total();
	}

	// Must return an integer
	public function get_total() {
		return ($this->updater->data === null) ? 0 : intval($this->updater->data['share_counter']);
	}

}

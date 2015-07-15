<?php

/***************************************************
* This class attaches an updater function which runs when
* the Shared Count plugin updates an individual post.
***************************************************/

class FlattrUpdater extends HTTPResourceUpdater {

	public $slug  = 'flattr';
	public $name  = 'Flattr';

	public $enabled_by_default = false;

	private $uri = 'https://api.flattr.com/rest/v2/things/lookup';

	public function __construct() {
		$this->updater = parent::__construct($this->slug, $this->name, $this->uri);
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
		$updater->meta[$this->updater->meta_prefix.$this->updater->slug] = $this->get_total();
	}

	// Must return an integer
	public function get_total() {

		// If the Flattr service did not find our URL
		if (!isset($this->updater->data['flattrs'])) return 0;
		if (isset($this->updater->data['message']) && $this->updater->data['message'] == 'not_found') return 0;

		return ($this->updater->data === null) ? 0 : intval($this->updater->data['flattrs']);
	}

}

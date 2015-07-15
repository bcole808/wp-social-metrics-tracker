<?php

/***************************************************
* This class attaches an updater function which runs when
* the Shared Count plugin updates an individual post.
***************************************************/

class RedditUpdater extends HTTPResourceUpdater {

	public $slug  = 'reddit';
	public $name  = 'Reddit';

	public $enabled_by_default = true;

	private $uri = 'https://www.reddit.com/api/info.json';

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
		$count = 0;
		foreach ($this->updater->data['data']['children'] as $child) {
			$count += $child['data']['score'];
		}
		return ($this->updater->data === null) ? 0 : intval($count);
	}

}

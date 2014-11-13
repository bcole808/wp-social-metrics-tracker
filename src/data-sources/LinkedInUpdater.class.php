<?php

/***************************************************
* This class attaches an updater function which runs when
* the Shared Count plugin updates an individual post.
***************************************************/

class LinkedInUpdater extends HTTPResourceUpdater {

	public $slug = 'linkedin';
	public $name = 'LinkedIn';

	private $uri = 'http://www.linkedin.com/countserv/count/share';

	public function __construct() {
		$this->updater = parent::__construct($this->slug, $this->name, $this->uri);
	}

	public function setParams($post_id, $post_url = false) {
		parent::setparams($post_id, $post_url);

		$this->updater->resource_params = array(
			'format' => 'json',
			'url' => $this->updater->post_url
		);
	}

	public function parse() {
		$updater = $this->updater;
		if (!is_array($updater->data)) return false;

		$updater->meta = array();
		$updater->meta[$this->updater->meta_prefix.$this->updater->slug] = $this->get_total();
	}

	public function get_total() {
		return ($this->updater->data === null) ? 0 : $this->updater->data['count'];
	}

}

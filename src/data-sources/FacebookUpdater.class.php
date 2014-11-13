<?php

/***************************************************
* This class attaches an updater function which runs when
* the Shared Count plugin updates an individual post.
***************************************************/

class FacebookUpdater extends HTTPResourceUpdater {

	public $slug  = 'facebook';
	public $name  = 'Facebook';

	private $uri = 'https://api.facebook.com/method/links.getStats';

	public function __construct() {
		$this->updater = parent::__construct($this->slug, $this->name, $this->uri);
	}

	public function setParams($post_id, $post_url = false) {
		parent::setparams($post_id, $post_url);

		$this->updater->resource_params = array(
			'format' => 'json',
			'urls' => $this->updater->post_url
		);
	}

	public function parse() {
		$updater = $this->updater;
		if (!is_array($updater->data)) return false;

		$updater->meta = array();
		$updater->meta[$this->updater->meta_prefix.$this->updater->slug] = $this->get_total();
		$updater->meta['facebook_comments']    = $updater->data[0]['comment_count'];
		$updater->meta['facebook_shares']      = $updater->data[0]['share_count'];
		$updater->meta['facebook_likes']       = $updater->data[0]['like_count'];
	}

	public function get_total() {
		return ($this->updater->data === null) ? 0 : $this->updater->data[0]['total_count'];
	}

}

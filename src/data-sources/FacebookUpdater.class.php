<?php

/***************************************************
* This class attaches an updater function which runs when
* the Shared Count plugin updates an individual post.
***************************************************/

class FacebookUpdater extends HTTPResourceUpdater {

	public $slug  = 'facebook';
	public $name  = 'Facebook';

	private $uri = 'https://graph.facebook.com/v1.0/fql';

	public function __construct() {
		$this->updater = parent::__construct($this->slug, $this->name, $this->uri);
	}

	public function setParams($post_id, $post_url = false) {
		parent::setparams($post_id, $post_url);

		$url = $this->updater->post_url;

		$this->updater->resource_params = array(
			// This FQL query will be URL encoded by http_build_query()
			'q' => "SELECT url, share_count, like_count, comment_count, total_count, click_count FROM link_stat where url='$url'"
		);

		// Note: The final encoded URL should look a bit like this:
		// https://graph.facebook.com/v1.0/fql?q=SELECT%20url,%20share_count,%20like_count,%20comment_count,%20total_count,%20click_count%20FROM%20link_stat%20where%20url=%27http://www.wordpress.org%27
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

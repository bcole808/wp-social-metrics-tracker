<?php

/***************************************************
* This class attaches an updater function which runs when
* the Shared Count plugin updates an individual post.
***************************************************/

class FacebookPublicUpdater extends HTTPResourceUpdater {

	public $slug  = 'facebook';
	public $name  = 'Facebook';

	public $enabled_by_default = true;

	private $uri = 'https://www.facebook.com/v2.3/plugins/like.php';

	public function __construct() {
		$this->updater = parent::__construct($this->slug, $this->name, $this->uri);
	}

	public function setParams($post_id, $post_url = false) {
		parent::setparams($post_id, $post_url);

		$this->updater->resource_params = array(
			'href' => $this->updater->post_url,
			'locale' => 'en_US' // Result will be parsed in English
		);

	}

	public function parse() {
		if (is_array($this->updater->data) || strlen($this->updater->data) <= 0) return false;

		$this->updater->meta = array();
		$this->updater->meta[$this->updater->meta_prefix.$this->updater->slug] = $this->get_total();

	}

	// Must return an integer
	public function get_total() {

		// Safety check 
		if (!is_string($this->updater->data)) return 0;

		// If there is only 1 share
		if (strpos($this->updater->data, '<span id="u_0_2"><span>One person likes this.</span>') !== false) return 1;

		// Strings to search for in the result if there is more than one share
		$wrapper_start = '<span id="u_0_2"><span>';
		$wrapper_end   = ' people like this.</span>';

		$start = strpos($this->updater->data, $wrapper_start);
		$end   = strpos($this->updater->data, $wrapper_end, $start);

		// If the data was not found in the result
		if ($start === false || $end === false) return 0;

		// Perform substring matching
		$match = substr($this->updater->data, $start+strlen($wrapper_start), $end - $start - strlen($wrapper_start));

		// If the substring task failed
		if ($match === false) return 0;

		// Convert the string with commas to an integer
		return intval(str_replace(',', '', $match));
	}

}

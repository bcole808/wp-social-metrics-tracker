<?php
/***************************************************
* This class manages the updates of data from social networks and Google Analytics.
*
* Updates are done using the Wordpress Cron so that page views are not slowed down while waiting for data to be returned from outside servers.
*
* A TTL is set so that new data is only fetched when the numbers are stale.
*
* Updates are triggered by page views, so if no one views a page then no new data is fetched (but that is okay, because if no one views the page then that means the data has not changed).
***************************************************/

require_once('data-sources/HTTPResourceUpdater.class.php');
require_once('data-sources/FacebookGraphUpdater.class.php');
require_once('data-sources/FacebookPublicUpdater.class.php');
require_once('data-sources/LinkedInUpdater.class.php');
require_once('data-sources/GooglePlusUpdater.class.php');
require_once('data-sources/PinterestUpdater.class.php');
require_once('data-sources/StumbleUponUpdater.class.php');
require_once('data-sources/XingUpdater.class.php');
require_once('data-sources/FlattrUpdater.class.php');
require_once('data-sources/RedditUpdater.class.php');

class MetricsUpdater {

	public $GoogleAnalyticsUpdater; // needs to be accessed from Settings page
	public $sources; // Object containing HTTPResourceUpdater instances

	public function __construct($smt) {
		$this->smt = ($smt) ? $smt : new SocialMetricsTracker();
		$this->sources = new stdClass();

		// Check post on each page load
		add_action( 'wp_head', array($this, 'checkThisPost'));

		// Set up event hooks
		add_action( 'social_metrics_full_update', array( $this, 'scheduleFullDataSync' ) );
		add_action( 'social_metrics_update_single_post', array( $this, 'updatePostStats' ), 10, 2 );

		// Manual data update for a post
		if (is_admin() && isset($_REQUEST['smt_sync_now']) && $_REQUEST['smt_sync_now']) {
			add_action ( 'wp_loaded', array($this, 'manualUpdate') );
		} else if (is_admin() && isset($_REQUEST['smt_sync_done']) && $_REQUEST['smt_sync_done']) {
			add_action ( 'admin_notices', array($this, 'manualUpdateSuccess') );
		}

	} // end constructor

	public function setupDataSources() {
		if (isset($this->dataSourcesReady) && $this->dataSourcesReady) return;

		if (class_exists('GoogleAnalyticsUpdater') && !isset($this->GoogleAnalyticsUpdater)) {
			$this->GoogleAnalyticsUpdater = new GoogleAnalyticsUpdater();
		}

		$this->sources = $this->activeSources();

		return $this->dataSourcesReady = true;
	}

	// Gets active updater objects only
	public function getSources() {
		$this->setupDataSources();
		return $this->sources;
	}

	// Return all possible updater objects, regardless of status
	public function allSources() {
		$sources = new stdClass();

		// Any special settings?
		$fb_graph_mode   = ( 'graph' == $this->smt->get_smt_option('connection_type_facebook') );
		$fb_access_token = $this->smt->get_smt_option('facebook_access_token');

		// Initialize sources
		$sources->FacebookUpdater    = $fb_graph_mode ? new FacebookGraphUpdater($fb_access_token) : new FacebookPublicUpdater();
		$sources->LinkedInUpdater    = new LinkedInUpdater();
		$sources->RedditUpdater      = new RedditUpdater();
		$sources->StumbleUponUpdater = new StumbleUponUpdater();
		$sources->GooglePlusUpdater  = new GooglePlusUpdater();
		$sources->PinterestUpdater   = new PinterestUpdater();
		$sources->FlattrUpdater      = new FlattrUpdater();
		$sources->XingUpdater        = new XingUpdater();

		return $sources;
	}

	// Return only the currently active updater objects, based on user configuration
	private function activeSources() {

		// Get all sources
		$sources = $this->allSources();

		// Disable inactive sources
		$api_enabled = $this->smt->get_smt_option('api_enabled');

		foreach ($sources as $key => $HTTPResourceUpdater) {

			// If there is no value, default behavior is to leave enabled
			if ( ! isset($api_enabled[$HTTPResourceUpdater->slug]) ) continue;

			// If a truthy value is present, leave enabled
			if ( $api_enabled[$HTTPResourceUpdater->slug] ) continue;
			
			// Disable this source from being used
			unset($sources->$key);
		}

		return $sources;
	}

	// Get the current time
	public function getLocalTime() {
		return current_time( 'timestamp' );
	}

	// Get the TTL in seconds
	public function getTTL() {
		return $this->smt->options['smt_options_ttl_hours'] * HOUR_IN_SECONDS;
	}

	// Check if a timestamp has passed the TTL
	public function hasPassedTTL($last_timestamp, $secondary=false) {
		$ttl = $this->getTTL();

		if ($secondary) {
			$multiplier = max(1, intval($this->smt->get_smt_option('alt_url_ttl_multiplier')));
			$ttl = $ttl * $multiplier;
		}

		return $last_timestamp + $ttl < $this->getLocalTime();
	}

	// Check if a post ID is ready to be scheduled for update
	public function isPostReadyForNextUpdate($post_id) {
		$last_updated = intval(get_post_meta($post_id, "socialcount_LAST_UPDATED", true));
		return $this->hasPassedTTL($last_updated);
	}

	// Manual data update for a post
	public function manualUpdate() {

		$post_id = intval( $_REQUEST['smt_sync_now'] );
		if (!$post_id) return false;

		if (get_post_meta($post_id, 'socialcount_LAST_UPDATED', true) > $this->getLocalTime()-300) {
			add_action ( 'admin_notices', array($this, 'manualUpdateMustWait') );
		} else {
			$this->updatePostStats($_REQUEST['smt_sync_now'], true);
			header("Location: ".add_query_arg(array('smt_sync_done' => $post_id), remove_query_arg('smt_sync_now')));
		}

	}

	// Display a notice that we did not update a post
	public function manualUpdateMustWait() {
		$message = "You must wait at least 5 minutes before performing another update on this post. ";
		printf( '<div class="error"> <p> %s </p> </div>', $message);
	}

	// Display a notice that we updated a post
	public function manualUpdateSuccess() {
		$post_id = intval( $_REQUEST['smt_sync_done'] );
		if (!$post_id) return false;

		$title = get_the_title($post_id);
		$message = "<b>$title</b> was updated successfully! &nbsp;<a href=\"".remove_query_arg('smt_sync_done')."\">Dismiss</a> ";
		printf( '<div class="updated"> <p> %s </p> </div>', $message);
	}

	/**
	* Check to see if this post requires an update, and if so schedule it.
	*
	* @param int $post_id the post id to check. Defaults to current ID.
	* @return
	*/
	public function checkThisPost($post_id = 0) {

		global $post;

		// If no post ID specified, use current page
		if ($post_id <= 0 && $post) $post_id = $post->ID;

		// Get post types to track
		$types = $this->smt->tracked_post_types();

		// Validation
		if (is_admin())                                  return false;
		if (is_int($post_id) && $post_id <= 0)           return false;
		if (!$post || !in_array($post->post_status, array('publish', 'inherit')))   return false; // Allow only published posts
		if ((count($types) > 0) && !is_singular($types)) return false; // Allow singular view of enabled post types

		// Block if TTL has not elapsed
		if ( !$this->isPostReadyForNextUpdate($post_id) ) {
			return false;
		}

		// Block if restricted by date range
		if ( !$this->isPostWithinAllowedRange($post) ) {
			return false;
		}

		// Schedule the update!
		switch($this->smt->get_smt_option('update_mode')) {

			case 'pageload' :
					add_action('wp_footer', array($this, 'updateCurrentPostNow'), 100);
				break;

			case 'cron' :
				// use default for case 'cron'
			default :

				// Schedule an update for cron
				wp_schedule_single_event( $this->getLocalTime(), 'social_metrics_update_single_post', array( $post_id ) );

				break;
		}

		return true;
	} // end checkThisPost()

	public function updateCurrentPostNow() {
		global $post;
		wp_reset_postdata();
		$this->updatePostStats($post->ID);
	}


	/**
	 * Checks if the post was published with the date range for auto-updates
	 *
	 * @param $post
	 * @return bool
     */
	private function isPostWithinAllowedRange($post) {

		// See what date range we want to allow auto-updates for
		$range = $this->smt->get_smt_option('update_range');

		if ($range == 'none') {
			return false;
		}

		if ($range == 'all') {
			return true;
		}

		$cutoff = current_time('timestamp') - ( $range * DAY_IN_SECONDS );

		if (strtotime($post->post_date) > $cutoff) {
			return true;
		} else {
			return false;
		}

	}


	/**
	* Ensure that all URLs match the protocol in configuration
	*
	* @param  string    $url       The URL to clean
	* @param  string    $protocol  The protocol to conver to
	* @return
	*/
	public function adjustProtocol($url, $protocol=false) {
		$protocol = ($protocol) ? $protocol : $this->smt->get_smt_option('url_protocol');

		if ($protocol == 'both') $protocol = $this->primary_protocol();

		switch ($protocol) {
			case 'http':
				return preg_replace("/^https:/i", "http:", $url);
				break;

			case 'https':
				return preg_replace("/^http:/i", "https:", $url);
				break;
			
			default:
				return $url;
				break;
		}
	}

	private function getProtocol($url) {
		return parse_url($url, PHP_URL_SCHEME);
	}

	// Returns the protocol in use by the home_url()
	private function primary_protocol() {
		$protocol = $this->getProtocol(get_option('home'));
		return ($protocol) ? $protocol : 'http';
	}

	// Returns the opposite of the protocol in use by the home_url();
	private function secondary_protocol() {
		return ($this->primary_protocol() == 'http') ? 'https' : 'http';
	}


	/**
	* Fetch new stats from remote services and update post social score.
	*
	* @param  int    $post_id    The ID of the post to update
	* @param  bool   $ignore_ttl If we should execute the update immediately, ignoring all TTL rules
	* @param  string $permalink  The primary permalink for the post (WARNING: only for use with phpunit automated tests)
	* @return
	*/
	public function updatePostStats($post_id, $ignore_ttl=false, $permalink=false) {

		if ($this->smt->is_development_server()) return false;

		// Data validation
		$post_id = intval($post_id);
		if ($post_id <= 0) return false;

		// Get post object
		$post = get_post($post_id);

		// Validate that post was found
		if (!$post instanceof WP_Post) return false;

		$permalink = ($permalink) ? $permalink : get_permalink($post_id);

		if ($permalink === false) return false;

		// Stop if TTL not elapsed
		if ($ignore_ttl === false && !$this->isPostReadyForNextUpdate($post_id)) return false;

		// Remove secure protocol from URL
		$permalink = $this->adjustProtocol($permalink);

		// Setup data sources (must be done before action hook)
		$this->setupDataSources();

		// Retrieve 3rd party data updates (Used for Google Analytics)
		do_action('social_metrics_data_sync', $post_id, $permalink);

		// Will we re-check the alt_data?
		$last_alt_check = intval(get_post_meta($post_id, 'socialcount_alt_data_LAST_UPDATED', true));
		$incl_alt_data  = ($ignore_ttl || $this->hasPassedTTL($last_alt_check, true));

		// Gather updated data from remote sources
		$data             = $this->fetchPostStats($post_id, $incl_alt_data, $permalink, $post);
		$post_meta        = $data['post_meta'];
		$alt_data_cache   = $data['alt_data_cache'];
		$alt_data_updated = $data['alt_data_updated'];

		// Calculate aggregate score.
		$social_aggregate_score_detail = $this->calculateScoreAggregate(
		                                    intval($post_meta['socialcount_TOTAL']),
		                                    intval(get_post_meta($post_id, 'ga_pageviews', true)),
		                                    intval($post->comment_count)
		                                 );

		// Calculate decayed score.
		$social_aggregate_score_decayed = $this->calculateScoreDecay($social_aggregate_score_detail['total'], $post->post_date);

		$post_meta['social_aggregate_score']                      = $social_aggregate_score_detail['total'];
		$post_meta['social_aggregate_score_detail']               = $social_aggregate_score_detail;
		$post_meta['social_aggregate_score_decayed']              = $social_aggregate_score_decayed;
		$post_meta['social_aggregate_score_decayed_last_updated'] = $this->getLocalTime();

		// Last updated time
		$post_meta['socialcount_LAST_UPDATED'] = $this->getLocalTime();
		if ($incl_alt_data && count($alt_data_updated) > 0) {
			$post_meta['socialcount_alt_data_LAST_UPDATED'] = $this->getLocalTime();
		}

		// Save all of the meta fields
		foreach ($post_meta as $key => $value) {
			update_post_meta($post_id, $key, $value);
		}

		// Save socialcount_url_data fields
		foreach ($alt_data_updated as $key => $value) {
			if (array_key_exists($key, $alt_data_cache)) {
				update_post_meta($post_id, 'socialcount_url_data', $alt_data_updated[$key], $alt_data_cache[$key]);
			} else {
				add_post_meta($post_id, 'socialcount_url_data', $value);
			}
		}

		$smt_stats['social_aggregate_score'] = $social_aggregate_score_detail['total'];
		$smt_stats['social_aggregate_score_decayed'] = $social_aggregate_score_decayed;

		// Custom action hook allows us to extend this function.
		do_action('social_metrics_data_sync_complete', $post_id, $smt_stats);

		return;
	} // end updatePostStats()


	/**
	* Retrieve new data about a post and a URL, or return cached values if remote service unavailable
	*
	* @param  int    $post_id
	* @param  bool   $refresh_alt_data If we should execute the update immediately, ignoring all TTL rules
	* @param  string $permalink the primary permalink associated with a post
	* @return array  The social data collected, or cached data
	*/
	public function fetchPostStats($post_id, $refresh_alt_data=false, $permalink=false, $post=false) {

		// Data validation
		$post_id = intval($post_id);
		if ($post_id <= 0) return false;

		$permalink = ($permalink) ? $permalink : get_permalink($post_id);
		if ($permalink === false) return false;

		// Setup
		$network_failure = false;
		$errors = array();

		// Init meta fields to update
		$post_meta = array('socialcount_TOTAL' => 0);

		// Init alt_url fields to check
		$alt_data_cache   = $this->filterAltMeta($post_id, $permalink);
		$alt_data_updated = $this->prepAltMeta($alt_data_cache, $permalink, $post);
		
		foreach ($this->getSources() as $HTTPResourceUpdater) {
			// EACH - API Resource
			$primary_result = $HTTPResourceUpdater->sync($post_id, $permalink);

			if ($primary_result === false) {
				$network_failure = true;
				if ($HTTPResourceUpdater->http_error) $errors[] = $HTTPResourceUpdater->http_error;

				// Use last saved value in total
				$post_meta['socialcount_TOTAL'] += intval(get_post_meta($post_id, $HTTPResourceUpdater->meta_prefix.$HTTPResourceUpdater->slug, true));
				
			} else {

				if ($refresh_alt_data) {
					foreach ($alt_data_updated as $key => $val) {
						// EACH - Alternate URL

						$result = $HTTPResourceUpdater->sync($post_id, $val['permalink']);

						if ($result !== false) {
							$alt_data_updated[$key] = array_merge($val, $result);
						} else {
							$network_failure = true;
							if ($HTTPResourceUpdater->http_error) $errors[] = $HTTPResourceUpdater->http_error;
						}

					}
				}

				// Merge all the data we collected
				$final_result = $this->mergeResults($primary_result, $alt_data_updated);

				// Compute the total count total
				$post_meta['socialcount_TOTAL'] += $final_result[$HTTPResourceUpdater->meta_prefix.$HTTPResourceUpdater->slug];

				// Merge fields
				$post_meta = array_merge($post_meta, $final_result);
			}
		}

		return array(
			// Required
			'post_meta'        => $post_meta,
			'alt_data_cache'   => $alt_data_cache,
			'alt_data_updated' => $alt_data_updated,

			// Extras
			'primary_url'      => $permalink,
			'primary_result'   => $primary_result,
			'network_failure'  => $network_failure,
			'errors'           => $errors
		);
	}


	/**
	* Add integers, excluding duplicate values
	*
	* @param  array  $nums  An array of integers
	* @return int    The sum of all the unique integers
	*/
	private function sumUnique($nums) {
		return (is_array($nums)) ? array_sum(array_unique($nums)) : false;
	}


	/**
	* Merge all the alt URL results into the primary result
	*
	* This function iterates on the keys of $primary_result and merges any matching values from the alt data set. 
	*
	* @param  array   $primary_result    The primary meta keys we are going to store
	* @param  array   $alt_data_updated  An array containing many sets of secondary keys we need to merge
	* @return boolean        If the string is a valid URL
	*/
	private function mergeResults($primary_result, $alt_data_updated) {
		foreach ($primary_result as $key => $val) {

			$nums = array($val);

			foreach ($alt_data_updated as $item) {
				if (array_key_exists($key, $item)) $nums[] = $item[$key];
			}

			$primary_result[$key] = $this->sumUnique($nums);
		}

		return $primary_result;
	}


	/**
	* Clean and get post meta entries for 'socialcount_url_data'
	*     - Removes anything that is an invalid or duplicate URL. 
	*
	* Valid values for 'socialcount_url_data' include: 
	*     A) A string representing an alternate post URL for the current post
	*     B) An array of social metrics data, with the key 'permalink' containing the alternate URL 
	*
	* @param  int    $post_id  The Post ID to fetch
	* @return array  The matching set of entries for 'socialcount_url_data'
	*/
	private function filterAltMeta($post_id, $permalink) {
		$alt_data = get_post_meta($post_id, 'socialcount_url_data');
		$protocol = $this->smt->get_smt_option('url_protocol');

		$delete_if_found = array();

		// If filtering by protocol
		if ($protocol == 'http' || $protocol == 'https') {
			$delete_if_found[] = $this->adjustProtocol($permalink, 'https');
			$delete_if_found[] = $this->adjustProtocol($permalink, 'http');
		}

		if ($protocol == 'both') {
			$delete_if_found[] = $this->adjustProtocol($permalink, $this->primary_protocol());
		}

		foreach ($alt_data as $key => $val) {

			$url = '';

			// Check data type
			if (is_string($val)) {
				$url = $val;
			} else if (is_array($val) && array_key_exists('permalink', $val)) {
				$url = $val['permalink'];
			} else {
				// No matching data type
				delete_post_meta($post_id, 'socialcount_url_data', $val);
				unset($alt_data[$key]);
			}

			// Delete invalid URL strings
			if (!$this->isValidURL($url)) {
				delete_post_meta($post_id, 'socialcount_url_data', $val);
				unset($alt_data[$key]);
			}

			// Delete duplicate entries or unwanted items
			if (in_array($url, $delete_if_found)) {
				delete_post_meta($post_id, 'socialcount_url_data', $val);
				unset($alt_data[$key]);
			}

			$delete_if_found[] = $url;
		}

		return $alt_data;
	}


	/**
	* Prepare socialcount_url_data for updates by converting string entries into arrays
	*
	* @param  array    $alt_data  An array of entries for postmeta 'socialcount_url_data'
	* @return array    $alt_data_updated An array of tweaked entries for postmeta
	*/
	private function prepAltMeta($alt_data, $permalink, $post) {
		$alt_data_updated = $alt_data;
		for ($i = 0; $i < count($alt_data); ++$i) {
			$url = (is_string($alt_data[$i])) ? $alt_data[$i] : $alt_data[$i]['permalink'];

			if (!is_array($alt_data_updated[$i])) {
				$alt_data_updated[$i] = array();
			}
			$alt_data_updated[$i]['permalink'] = $url;
		}
		return $this->addMissingAltURLs($alt_data_updated, $permalink, $post);
	}


	/**
	* Adds any missing URLs into alt_data
	*
	* @param  array    $alt_data  An array of entries for postmeta 'socialcount_url_data'
	* @return array    $alt_data_updated An array of tweaked entries for postmeta
	*/
	private function addMissingAltURLs($alt_data, $permalink, $post) {

		$need_to_add = array();
		$need_to_remove = array();

		// Protocol
		$protocol = $this->smt->get_smt_option('url_protocol');

		if ($protocol == 'both') {
			$need_to_add[] = $this->adjustProtocol($permalink, $this->secondary_protocol());
		}

		// Domain migration
		$url_rewrites = $this->smt->get_smt_option('url_rewrites');

		if ($url_rewrites) {
			foreach ($url_rewrites as $rewrite) {

				// Date comparison
				$timestamp = strtotime($post->post_date);
				$before    = strtotime($rewrite['rewrite_before_date']);

				// Skip if published later than 'before' date
				if ($timestamp !== false && $before !== false && $timestamp > $before)  continue;

				// Do the replacement
				$find    = $rewrite['rewrite_match_from'];
				$replace = $rewrite['rewrite_change_to'];

				$url = preg_replace("/^".preg_quote($find, '/')."/i", $replace, $permalink, 1, $count);

				if ($this->isValidURL($url) && $count > 0) $need_to_add[] = $url;
			}
		}
	
		// Add to meta object
		foreach ($need_to_add as $url) {
			if (!$this->hasURL($alt_data, $url))
				$alt_data[] = array('permalink' => $url);
		}

		return $alt_data;
	}

	private function hasURL($alt_data, $url) {
		foreach ($alt_data as $key => $val) {
			if ($val['permalink'] == $url) return true;
		}
		return false;
	}


	/**
	* Check if a string is a valid URL
	*
	* @param  string   $url  A string representing a URL
	* @return boolean        If the string is a valid URL
	*/
	public function isValidURL($url) {
		return filter_var($url, FILTER_VALIDATE_URL);
	}


	/**
	* Combine Social, Views, and Comments into one aggregate value
	*
	* @param The input values for social, views, and comments
	* @return An array representing the weighted score of all three input values
	*/
	public function calculateScoreAggregate($social_num = 0, $views_num = 0, $comment_num = 0) {

		// Validate input
		if (!is_int($social_num) || !is_int($views_num) || !is_int($comment_num)) return false;

		// Configuration
		$social_weight 	= 1;
		$view_weight	= 0.1;
		$comment_weight	= 20;

		// Calculate weighted points
		$social_points 	= $social_num	* $social_weight;
		$view_points 	= $views_num 	* $view_weight;
		$comment_points = $comment_num 	* $comment_weight;

		$data = array(
			'total' 			=> $social_points + $view_points + $comment_points,
			'social_points'		=> $social_points,
			'view_points'		=> $view_points,
			'comment_points'	=> $comment_points
		);

		return $data;
	} // end calculateScoreAggregate()


	/**
	* Reduces a number over time based on how much time has elapsed since inception.
	*
	* Purpose: To lower the score of posts over time so that older posts do not display on top.
	*
	* @param  int  		$score  The original number
	* @param  string  	$datePublished The date string of when the content was published; parsed with strto$this->getLocalTime();
	* @return float The decayed score
	*/
	public function calculateScoreDecay($score, $datePublished) {

		// Config
		$GRACE_PERIOD = 10.5;
		$SECONDS_PER_DAY = 60*60*24;
		$BOOST_PERIOD = 5;

		// Data validation
		if (!$score) return false;
		if (!$datePublished) return false;
		if (($timestamp = strtotime($datePublished)) === false) return false;
		if (!$timestamp) return false;
		if ($score < 0 || $timestamp <= 0) return false;

		$daysActive = ($this->getLocalTime() - $timestamp) / $SECONDS_PER_DAY;

		// If newer than 5 days, boost.
		if ($daysActive < 5) {

			$k = $score / ($BOOST_PERIOD*$BOOST_PERIOD);
			$new_score = $k*($daysActive - $BOOST_PERIOD)*($daysActive - $BOOST_PERIOD) + $score;

		// If older than 5 days, decay.
		} else {
			$new_score = $score / (1.0 + pow(M_E,($daysActive - $GRACE_PERIOD)));
		}

		return  $new_score;
	} // end calculateScoreDecay()


	/**
	* Recalculate the score aggregate and decay values.
	*
	* Purpose: This only needs to be run when the parameters are changed for how to calculate scores. No new data is fetched and it is only used to recalculate based on the data in the DB.
	*
	* @param bool $print_output - If true, progress will be echoed while this function runs.
	* @return int the number of posts updated.
	*/
	public function recalculateAllScores($print_output = false) {

		// Get all posts which have social data
		$querydata = query_posts(array(
			'order'=>'DESC',
			'orderby'=>'post_date',
			'posts_per_page'=>-1,
			'post_status'   => 'publish',
			'meta_query' => array(
				array(
				 'key' => 'socialcount_LAST_UPDATED',
				 'compare' => '>=', // works!
				 'value' => '0' // This is ignored, but is necessary...
				)
			)
		));

		$total = array(
			'count' 		=> 0,
			'socialscore'	=> 0,
			'views'			=> 0,
			'comments'		=> 0
		);

		foreach ($querydata as $post ) {

			$socialcount_TOTAL = get_post_meta( $post->ID, 'socialcount_TOTAL', true );
			$ga_pageviews = get_post_meta( $post->ID, 'ga_pageviews', true );

			// Update aggregate score.
			$social_aggregate_score_detail = $this->calculateScoreAggregate($socialcount_TOTAL, $ga_pageviews, $post->comment_count);
			update_post_meta($post->ID, "social_aggregate_score", $social_aggregate_score_detail['total']);
			update_post_meta($post->ID, "social_aggregate_score_detail", $social_aggregate_score_detail);

			// Update decayed score.
			$social_aggregate_score_decayed = $this->calculateScoreDecay($social_aggregate_score_detail['total'], $post->post_date);
			update_post_meta($post->ID, "social_aggregate_score_decayed", $social_aggregate_score_decayed);
			update_post_meta($post->ID, "social_aggregate_score_decayed_last_updated", $this->getLocalTime());

			if ($print_output) {
				echo "Updated ".$post->post_title.", total: <b>".$social_aggregate_score_detail['total'] . "</b> decayed: ".$social_aggregate_score_decayed."<br>";
				flush();
			}

			$total['count']++;
			$total['socialscore'] += $socialcount_TOTAL;
			$total['views'] += $ga_pageviews;
			$total['comments'] += $post->comment_count;

		}

		if ($print_output) {
			echo "<hr><b>Update complete! ".$total['count']." posts updated.</b><hr>";

			echo "Average social score: ".round($total['socialscore'] / $total['count'], 2)."<br>";
			echo "Average views: ".round($total['views'] / $total['count'], 2)."<br>";
			echo "Average comments: ".round($total['comments'] / $total['count'], 2)."<br>";
			echo "<hr>";
		}

		return $total['count'];
	} // end recalculateAllScores()


	/**
	* Run a complete sync of all data. Download new stats for every single post in the DB.
	*
	* This should only be run when the plugin is first installed, or if data syncing was interrupted.
	*
	*/
	public function scheduleFullDataSync($verbose = false) {

		update_option( 'smt_last_full_sync', $this->getLocalTime() );

		$post_types = $this->smt->tracked_post_types();
		$offset     = (isset($_REQUEST['smt_sync_offset'])) ? intval($_REQUEST['smt_sync_offset']) : 0;

		$q = new WP_Query();
		$q->query(array(
			'post_type'              => $post_types,
			'order'			         => 'DESC',
			'orderby'		         => 'post_date',
			'posts_per_page'         => 50,
			'offset'                 => $offset,
			'post_status'            => array( 'publish', 'inherit' ),
			'cache_results'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false
		));

		/***************************************************
		* This prints the progress so the user can see how far we are.
		*
		* We really need a template engine here... the horror, the horror....
		***************************************************/
		if ($verbose) {
			$percent = round(($offset + $q->post_count) / max(1, $q->found_posts) * 100);
			print('<div style="width: 100%; border:1px solid #CCC; background:#EEE; border-radius: 6px; padding:20px; margin: 15px 0; box-sizing:border-box;">');
			print('<h1 style="margin-top:0;">Scheduled '.($offset + $q->post_count).' out of '.$q->found_posts.' posts.</h1>');
			print('<div style="width:100%; border:1px solid #CCC; background: #BDBFC2; border-radius: 10px; overflow: hidden;">');
			print('<div style="background: #3b5998; color:#FFF; font-size: 12px; padding: 4px; text-align:center; width: '.$percent.'%">'. $percent .'%</div>');
			print('</div>');
			print('</div>');
		}
		// End Print Progress

		$i = 1;
		foreach ($q->posts as $post ) {
			// We are going to stagger the updates so we do not overload the Wordpress cron.
			$time = time() + (5 * ($offset + $i++));

			$next = wp_next_scheduled( 'social_metrics_update_single_post', array( $post->ID, true ) );
			if ($next == false) {
				wp_schedule_single_event( $time, 'social_metrics_update_single_post', array( $post->ID, true ) );
			}

		}

		/***************************************************
		* Make them go to the next page!
		***************************************************/
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || !empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
		$domain_name = $_SERVER['HTTP_HOST'];

		if ($verbose && $offset + $q->post_count < $q->found_posts) :
			$loc = $protocol . $domain_name . add_query_arg(array('smt_sync_offset' => $offset+$q->post_count));
			?>
			<script>
			setTimeout(function() {
				window.location = "<?php echo $loc; ?>";
			}, 500);
			</script>
			<p><a href="<?php echo $loc; ?>">Click if you are not automatically redirected...</a></p>
			<?php
			return;
		endif;

		if ($verbose) {
			print('<h2>Finished!</h2>');
			print('<p><a href="'.remove_query_arg(array('smt_full_sync', 'smt_sync_offset')).'">Return to Social Metrics dashboard</a></p>');
		}

		return $q->found_posts;

	} // end scheduleFullDataSync()


	// Remove all queued updates from cron.
	public static function removeAllQueuedUpdates() {
		$crons = _get_cron_array();
		if ( !empty( $crons ) ) {
			foreach( $crons as $timestamp => $cron ) {
				// Remove single post updates
				if ( ! empty( $cron['social_metrics_update_single_post'] ) )  {
					unset( $crons[$timestamp]['social_metrics_update_single_post'] );
				}

				// Remove full post updates
				if ( ! empty( $cron['social_metrics_full_update'] ) )  {
					unset( $crons[$timestamp]['social_metrics_full_update'] );
				}
			}
			_set_cron_array( $crons );
		}

		return;
	} // end removeAllQueuedUpdates()

	public static function getQueueLength() {
		$queue = array();
		$cron = _get_cron_array();
		foreach ( $cron as $timestamp => $cronhooks ) {
			foreach ( (array) $cronhooks as $hook => $events ) {
				foreach ( (array) $events as $key => $event ) {
					if ($hook == 'social_metrics_update_single_post') {
						array_push($queue, $cron[$timestamp][$hook][$key]['args'][0]);
					}
				}
			}
		}

		return count($queue);
	}

	public static function printQueueLength() {
		$count = MetricsUpdater::getQueueLength();
		if ($count >= 1) {
			$label = ($count >=2) ? ' items' : ' item';
			printf( '<div class="updated"> <p> %s </p> </div>',  '<b>'.$count . $label.'</b> scheduled to be synced with social networks the next time WP Cron is run...');
		}
	} // end printQueueLength()

} // END CLASS

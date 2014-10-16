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

class MetricsUpdater {

	private $options;
	public $SharedCountUpdater;
	public $GoogleAnalyticsUpdater; // needs to be accessed from Settings page

	public function __construct($options = false) {

		// Set options
		$this->options = ($options) ? $options : get_option('smt_settings');

		// Import adapters for 3rd party services
		if (class_exists('SharedCountUpdater')) {
			$this->SharedCountUpdater = new SharedCountUpdater();
		}

		// If analytics are being tracked, pull update
		if (class_exists('GoogleAnalyticsUpdater')) {
			$this->GoogleAnalyticsUpdater = new GoogleAnalyticsUpdater();
		}

		// Check post on each page load
		add_action( 'wp_head', array($this, 'checkThisPost'));

		// Set up event hooks
		add_action( 'social_metrics_full_update', array( $this, 'scheduleFullDataSync' ) );
		add_action( 'social_metrics_update_single_post', array( $this, 'updatePostStats' ), 10, 1 );

	} // end constructor

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
		$types = $this->get_post_types();

		// Validation
		if (is_admin())                                  return false;
		if (is_int($post_id) && $post_id <= 0)           return false;
		if (!$post || $post->post_status != 'publish')   return false; // Allow only published posts
		if ((count($types) > 0) && !is_singular($types)) return false; // Allow singular view of enabled post types

		// Check TTL timeout
		$last_updated = get_post_meta($post_id, "socialcount_LAST_UPDATED", true);
		$ttl = $this->options['smt_options_ttl_hours'] * 3600;

		// If no timeout
		$temp = time() - $ttl;
		if ($last_updated < $temp) {

			// Schedule an update
			wp_schedule_single_event( time(), 'social_metrics_update_single_post', array( $post_id ) );

		}

		return true;
	} // end checkThisPost()

	// Return an array of post types we currently track
	public function get_post_types() {

		$types_to_track = array();

		$smt_post_types = get_post_types( array( 'public' => true ), 'names' );
		unset($smt_post_types['attachment']);

		foreach ($smt_post_types as $type) {
			if (isset($this->options['smt_options_post_types_'.$type]) && $this->options['smt_options_post_types_'.$type] == $type) $types_to_track[] = $type;
		}

		// If none selected, default post types
		return ($types_to_track) ? $types_to_track : array_values($smt_post_types);

	}

	/**
	* Fetch new stats from remote services and update post social score.
	*
	* @param  int    $post_id  The ID of the post to update
	* @return
	*/
	public function updatePostStats($post_id) {

		// Data validation
		if ($post_id <= 0) return false;

		// Remove secure protocol from URL
		$permalink = str_replace("https://", "http://", get_permalink($post_id));

		// Retrieve 3rd party data updates
		do_action('social_metrics_data_sync', $post_id, $permalink);

		// Last updated time
		update_post_meta($post_id, "socialcount_LAST_UPDATED", time());

		// Get comment count from DB
		$post = get_post($post_id);

		// Calculate aggregate score.
		$social_aggregate_score_detail = $this->calculateScoreAggregate(
																	intval(get_post_meta($post_id, 'socialcount_TOTAL', true)),
																	intval(get_post_meta($post_id, 'ga_pageviews', true)),
																	intval($post->comment_count)
																	);

		// Calculate decayed score.
		$social_aggregate_score_decayed = $this->calculateScoreDecay($social_aggregate_score_detail['total'], $post->post_date);

		update_post_meta($post_id, "social_aggregate_score", $social_aggregate_score_detail['total']);
		update_post_meta($post_id, "social_aggregate_score_detail", $social_aggregate_score_detail);
		update_post_meta($post_id, "social_aggregate_score_decayed", $social_aggregate_score_decayed);
		update_post_meta($post_id, "social_aggregate_score_decayed_last_updated", time());

		$smt_stats['social_aggregate_score'] = $social_aggregate_score_detail['total'];
		$smt_stats['social_aggregate_score_decayed'] = $social_aggregate_score_decayed;

		// Custom action hook allows us to extend this function.
		do_action('social_metrics_data_sync_complete', $post_id, $smt_stats);

		return;
	} // end updatePostStats()

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
	* @param  string  	$datePublished The date string of when the content was published; parsed with strtotime();
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

		$daysActive = (time() - $timestamp) / $SECONDS_PER_DAY;

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
			update_post_meta($post->ID, "social_aggregate_score_decayed_last_updated", time());

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
	public static function scheduleFullDataSync() {

		// We are going to stagger the updates so we do not overload the Wordpress cron.
		$nextTime = time();
		$interval = 5; // in seconds

		// Get posts that have not ever been updated.
		// In case the function does not finish, we want to start with posts that have NO data yet.
		$querydata = query_posts(array(
			'order'			=>'DESC',
			'orderby'		=>'post_date',
			'posts_per_page'=>-1,
			'post_status'   => 'publish',
			'meta_query' 	=> array(
				array(
					'key' 		=> 'socialcount_LAST_UPDATED',
					'compare' 	=> 'NOT EXISTS', // works!
					'value' 	=> '' // This is ignored, but is necessary...
				)
			)
		));

		foreach ($querydata as $querydatum ) {
			wp_schedule_single_event( $nextTime, 'social_metrics_update_single_post', array( $querydatum->ID ) );
			$nextTime = $nextTime + $interval;
		}

		// Get posts which HAVE been updated
		$querydata = query_posts(array(
			'order'			=>'DESC',
			'orderby'		=>'post_date',
			'posts_per_page'=>-1,
			'post_status'   => 'publish',
			'meta_query' 	=> array(
				array(
					'key' 		=> 'socialcount_LAST_UPDATED',
					'compare' 	=> '>=', // works!
					'value' 	=> '0' // This is ignored, but is necessary...
				)
			)
		));

		foreach ($querydata as $querydatum ) {
			wp_schedule_single_event( $nextTime, 'social_metrics_update_single_post', array( $querydatum->ID ) );
			$nextTime = $nextTime + ($interval * 2);
		}

		return true;
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

	public static function printQueueLength() {
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

		$count = count($queue);
		if ($count >= 1) {
			$label = ($count >=2) ? ' items' : ' item';
			printf( '<div class="updated"> <p> %s </p> </div>',  'Currently updating <b>'.$count . $label.'</b> with the most recent social and analytics data...');
		}
	} // end printQueueLength()

} // END CLASS

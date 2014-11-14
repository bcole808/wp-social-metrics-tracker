<?php

/***************************************************
* This table class is based on the "Custom List Table Example" provided by Matt van Andel
*
* http://wordpress.org/plugins/custom-list-table-example/
***************************************************/
if(!class_exists('WP_List_Table')){
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
class SocialMetricsTable extends WP_List_Table {

	function __construct($smt){
		global $status, $page;

		$this->smt = $smt;

		$this->gapi = new GoogleAnalyticsUpdater();

		//Set parent defaults
		parent::__construct( array(
			'singular'  => 'post',     //singular name of the listed records
			'plural'    => 'posts',    //plural name of the listed records
			'ajax'      => false        //does this table support ajax?
		) );

	}

	function column_default($item, $column_name){
		switch($column_name){

			// case 'social':
			//     return number_format($item['commentcount_total'],0,'.',',');
			// case 'views':
			//     return number_format($item['views'],0,'.',',');
			// case 'comments':
			//     return number_format($item['comment_count'],0,'.',',');
			case 'date':
				$dateString = date("M j, Y",strtotime($item['post_date']));
				return $dateString;
			default:
				return 'Not Set';
		}
	}


	function column_title($item){

		//Build row actions
		$actions = array(
			'edit'    => sprintf('<a href="post.php?post=%s&action=edit">Edit Post</a>',$item['ID']),
			'update'  => '<a href="'.add_query_arg( 'smt_sync_now', $item['ID']).'">Update Stats</a>',
			'info'    => sprintf('Updated %s',SocialMetricsTracker::timeago($item['socialcount_LAST_UPDATED']))
		);

		//Return the title contents

		return '<a href="'.$item['permalink'].'"><b>'.$item['post_title'] . '</b></a>' . $this->row_actions($actions);
	}

	// Column for Social

	function column_social($item) {

		$total = floatval($item['socialcount_total']);
		$bar_width = ($total == 0) ? 0 : round($total / max($this->data_max['socialcount_total'], 1) * 100);

		$output = '<div class="bar" style="width:'.$bar_width.'%;">';

		// print("You've got a ");
		// print_r($this->smt);

		foreach ($this->smt->updater->getSources() as $HTTPResourceUpdater) {

			$slug     = $HTTPResourceUpdater->slug;
			$name     = $HTTPResourceUpdater->name;
			$meta_key = $HTTPResourceUpdater->meta_prefix . $HTTPResourceUpdater->slug;

			$percent = floor($item[$meta_key] / max($total, 1) * 100);
			$output .= '<span class="'.$slug.'" style="width:'.$percent.'%" title="'.$name.': '.$item[$meta_key].' ('.$percent.'% of total)">'.$name.'</span>';
		}

		$output .= '</div><div class="total">'.number_format($total,0,'.',',') . '</div>';

		return $output;
	}

	// Column for views
	function column_views($item) {
		$output = '';
		$output .= '<div class="bar" style="width:'.round($item['views'] / max($this->data_max['views'], 1) * 100).'%">';
		$output .= '<div class="total">'.number_format($item['views'],0,'.',',') . '</div>';
		$output .= '</div>';

		return $output;
	}

	// Column for comments
	function column_comments($item) {
		$output = '';
		$output .= '<div class="bar" style="width:'.round($item['comment_count'] / max($this->data_max['comment_count'], 1) * 100).'%">';
		$output .= '<div class="total">'.number_format($item['comment_count'],0,'.',',') . '</div>';
		$output .= '</div>';

		return $output;
	}

	function get_columns(){

		$columns['date'] = 'Date';
		$columns['title'] = 'Title';

		$columns['social'] = 'Social Score';
		if ($this->gapi->can_sync()) {
			$columns['views'] = 'Views';
		}
		$columns['comments'] = 'Comments';

		return $columns;
	}

	function get_sortable_columns() {
		$sortable_columns = array(
			'date'      => array('post_date',true),
			//'title'     => array('title',false),
			'views'    => array('views',true),
			'social'  => array('social',true),
			'comments'  => array('comments',true)
		);
		return $sortable_columns;
	}


	function get_bulk_actions() {
		$actions = array(
			//'delete'    => 'Delete'
		);
		return $actions;
	}


	function process_bulk_action() {


	}

	function date_range_filter( $where = '' ) {

		$range = (isset($_GET['range'])) ? $_GET['range'] : $this->smt->options['smt_options_default_date_range_months'];

		if ($range <= 0) return $where;

		$range_bottom = " AND post_date >= '".date("Y-m-d", strtotime('-'.$range.' month') );
		$range_top = "' AND post_date <= '".date("Y-m-d")."'";

		$where .= $range_bottom . $range_top;
		return $where;
	}

	/*
	 * This action tweaks the query to handle sorting in the dashboard.
	 */
	function handle_dashboard_sorting($query) {

		// get order
		// this should be taken care of by default but something is interfering
		// If no order, default is DESC
		$query->set( 'order', ! empty( $_REQUEST[ 'order' ] ) ? $_REQUEST[ 'order' ] : 'DESC' );

		// get orderby
		// If no sort, then get default option
		$orderby = ! empty( $_REQUEST[ 'orderby' ] ) ? $_REQUEST[ 'orderby' ] : $this->smt->options[ 'smt_options_default_sort_column' ];

		// tweak query based on orderby
		switch( $orderby ) {

			case 'aggregate':

				$query->set( 'orderby', 'meta_value_num' );
				$query->set( 'meta_key', 'social_aggregate_score' );
				break;

			case 'comments':

				$query->set( 'orderby', 'comment_count' );
				break;

			case 'post_date':

				$query->set( 'orderby', 'post_date' );
				break;

			case 'social':

				$query->set( 'orderby', 'meta_value_num' );
				$query->set( 'meta_key', 'socialcount_TOTAL' );
				break;

			case 'views':

				$query->set( 'orderby', 'meta_value_num' );
				$query->set( 'meta_key', 'ga_pageviews' );

				break;

		}

		$query = apply_filters( 'smt_dashboard_query', $query ); // Allows developers to add additional query params

	}

	function prepare_items() {
		global $wpdb; //This is used only if making any database queries

		$per_page = 10;

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array($columns, $hidden, $sortable);

		$this->process_bulk_action();

		// Get custom post types to display in our report.
		$post_types = $this->smt->tracked_post_types();

		$limit = 30;

		// Filter our query results
		add_filter( 'posts_where', array($this, 'date_range_filter') );
		add_filter( 'pre_get_posts', array($this, 'handle_dashboard_sorting') );

		$querydata = new WP_Query(array(
			'posts_per_page'=> $limit,
			'post_status'	=> 'publish',
			'post_type'		=> $post_types
		));

		// Remove our filters
		remove_filter( 'posts_where', array($this, 'date_range_filter') );
		remove_filter( 'pre_get_posts', array($this, 'handle_dashboard_sorting') );

		$data=array();

		// Initialize array
		$this->data_max = array();
		$this->data_max['socialcount_total'] = 1;
		$this->data_max['views'] = 1;
		$this->data_max['comment_count'] = 1;

		// foreach ($querydata as $querydatum ) {
		if ( $querydata->have_posts() ) : while ( $querydata->have_posts() ) : $querydata->the_post();
			global $post;

			$item['ID'] = $post->ID;
			$item['post_title'] = $post->post_title;
			$item['post_date'] = $post->post_date;
			$item['comment_count'] = $post->comment_count;
			$item['socialcount_total'] = (get_post_meta($post->ID, "socialcount_TOTAL", true)) ? get_post_meta($post->ID, "socialcount_TOTAL", true) : 0;
			$item['socialcount_LAST_UPDATED'] = get_post_meta($post->ID, "socialcount_LAST_UPDATED", true);
			$item['views'] = (get_post_meta($post->ID, "ga_pageviews", true)) ? get_post_meta($post->ID, "ga_pageviews", true) : 0;
			$item['permalink'] = get_permalink($post->ID);

			foreach ($this->smt->updater->getSources() as $HTTPResourceUpdater) {
				$meta_key = $HTTPResourceUpdater->meta_prefix . $HTTPResourceUpdater->slug;
				$item[$meta_key] = get_post_meta($post->ID, $meta_key, true);
			}

			$this->data_max['socialcount_total'] = max($this->data_max['socialcount_total'], $item['socialcount_total']);
			$this->data_max['views'] = max($this->data_max['views'], $item['views']);
			$this->data_max['comment_count'] = max($this->data_max['comment_count'], $item['comment_count']);

		   array_push($data, $item);
		endwhile;
		endif;

		/**
		 * REQUIRED for pagination.
		 */
		$current_page = $this->get_pagenum();

		$total_items = count($data);

		$data = array_slice($data,(($current_page-1)*$per_page),$per_page);

		$this->items = $data;

		$this->set_pagination_args( array(
			'total_items' => $total_items,                  //WE have to calculate the total number of items
			'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
			'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
		) );
	}

	/**
	 * Add extra markup in the toolbars before or after the list
	 * @param string $which, helps you decide if you add the markup after (bottom) or before (top) the list
	 */
	function extra_tablenav( $which ) {
		if ( $which == "top" ){
			//The code that goes before the table is here
			$range = (isset($_GET['range'])) ? $_GET['range'] : $this->smt->options['smt_options_default_date_range_months'];
			?>
			<label for="range">Show only:</label>
					<select name="range">
						<option value="1"<?php if ($range == 1) echo 'selected="selected"'; ?>>Items published within 1 Month</option>
						<option value="3"<?php if ($range == 3) echo 'selected="selected"'; ?>>Items published within 3 Months</option>
						<option value="6"<?php if ($range == 6) echo 'selected="selected"'; ?>>Items published within 6 Months</option>
						<option value="12"<?php if ($range == 12) echo 'selected="selected"'; ?>>Items published within 12 Months</option>
						<option value="0"<?php if ($range == 0) echo 'selected="selected"'; ?>>Items published anytime</option>
					</select>

					<?php do_action( 'smt_dashboard_query_options' ); // Allows developers to add additional sort options ?>

					<input type="submit" name="filter" id="submit_filter" class="button" value="Filter">


					<a href="<?php echo add_query_arg(array('smt_full_sync' => 1)); ?>" class="button" onClick="return confirm('Are you sure? This will schedule ALL your posts to be updated and may take a long time if you have a lot of posts.')">Schedule full sync</a>

			<?php

		}
		if ( $which == "bottom" ){
			//The code that goes after the table is there
		}
	}

}

/***************************** RENDER PAGE ********************************
 *******************************************************************************
 * This function renders the admin page and the example list table. Although it's
 * possible to call prepare_items() and display() from the constructor, there
 * are often times where you may need to include logic here between those steps,
 * so we've instead called those methods explicitly. It keeps things flexible, and
 * it's the way the list tables are used in the WordPress core.
 */
function smt_render_dashboard_view($smt){

	$last_full_sync = get_option( "smt_last_full_sync" );

	if (isset($_REQUEST['smt_test_http_now'])) {
		$smt->debugger->testHTTPResourceUpdaters();
	}

	$offline_updaters = $smt->debugger->getOfflineHTTPResourceUpdaters();

	?>
	<div class="wrap">

		<h2>Social Metrics Tracker</h2>


		<?php if (isset($_REQUEST['smt_full_sync'])) : ?>
		<h3>Now scheduling a full data update...</h3>
		<p>This process must check all posts in your database and may take a short while...</p>
		<p>If you have custom post types that you would like to track or exclude please go to the configuration page!</p>
		<?php $num = $smt->updater->scheduleFullDataSync(true); ?>
		<p>Your server will work on retrieving share stats from social networks in the background. You should not need to run this again as the plugin will automatically keep items up-to-date as visitors browse and share your content. </p>
		<?php return; endif; ?>


		<form id="social-metrics-tracker" method="get" action="admin.php?page=social-metrics-tracker">
			<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
			<input type="hidden" name="orderby" value="<?php echo (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : $smt->options['smt_options_default_sort_column']; ?>" />
			<input type="hidden" name="order" value="<?php echo (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'DESC'; ?>" />


			<?php if (!$last_full_sync) : ?>
			<div class="update-nag" style="margin-bottom:30px;">
				<h3> Setup Instructions </h3>
				<p>You need to perform a one time full-sync. </p>
				<p>We will schedule it now, and it will run in the background.</p>
				<p>In general, social stats can take a little while to appear. This plugin will keep numbers up to date by periodically checking for new stats as visitors view your posts. Even after social shares occur, it can take a few hours for them to appear here. If you have custom post types, please visit the configuration page first. </p>
				<p><a href="<?php echo add_query_arg(array('smt_full_sync' => 1)); ?>" class="button">Schedule full sync</a></p>
			</div>
			<?php endif; ?>


			<?php if (!$smt->is_development_server() && $last_full_sync) : ?>

				<?php if (count($offline_updaters) == 0) : ?>
				<button id="smt-connection-status-toggle" class="smt-connection-item online">Data is being synced in the background</button>
				<?php else : ?>
				<button id="smt-connection-status-toggle" class="smt-connection-item offline">Temporary connectivity issue detected. Click for details.</button>
				<?php endif; ?>

				<div id="smt-connection-status" style="<?php echo (isset($_REQUEST['smt_test_http_now'])) ? '' : 'display:none;' ?>">
					<?php foreach ($smt->updater->getSources() as $h) { ?>
					<?php $status = $h->wpcb->getStatusDetail(); ?>
					<div class="smt-connection-item <?php echo ($status['working']) ? 'online' : 'offline'; ?>">
						<?php echo $h->name ?>
						<?php if (!$status['working']) : ?> - <?php echo $status['fail_count'] ?> failures - <?php echo $status['error_message'] ?> <br /><small>Will automatically retry <?php echo date("M j, g:i a", $status['next_query_at']); ?>.</small>
						<?php endif; ?>

						<br />
						<small>Last checked <?php echo date("M j, g:i a", $status['last_query_at']); ?></small>
					</div>
					<?php } ?>

					<?php if (count($offline_updaters) > 0 && !isset($_REQUEST['smt_test_http_now'])) : ?>
					<p><a class="button" href="<?php echo add_query_arg(array('smt_test_http_now' => 1)); ?>">Re-check all connections right now.</a></p>
					<p><small>If any of the services listed above are displaying errors, they will be automatically excluded when checking for new data. If errors do not resolve themselves within one day, there might be a problem with the servers ability to connect to social network APIs to retrieve data. </small></p>
					<?php endif; ?>

				</div>

			<?php endif; ?>


			<?php
			//Create an instance of our package class...
			$SocialMetricsTable = new SocialMetricsTable($smt);

			//Fetch, prepare, sort, and filter our data...
			$SocialMetricsTable->prepare_items();
			$SocialMetricsTable->display();
			?>

		</form>

		<?php MetricsUpdater::printQueueLength(); ?>

	</div>
	<?php
}
?>

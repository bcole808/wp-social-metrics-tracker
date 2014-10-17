<?php

/***************************************************
* This table class is based on the "Custom List Table Example" provided by Matt van Andel
*
* http://wordpress.org/plugins/custom-list-table-example/
***************************************************/
if(!class_exists('WP_List_Table')){
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
class SocialMetricsDebugTable extends WP_List_Table {

	function __construct(){
		global $status, $page;

		$this->options = get_option('smt_settings');

		//Set parent defaults
		parent::__construct( array(
			'singular'  => 'post',     //singular name of the listed records
			'plural'    => 'posts',    //plural name of the listed records
			'ajax'      => false        //does this table support ajax?
		) );

	}

	function column_default($item, $column_name){
		switch($column_name){

			// case 'aggregate':
			//     return number_format($item['commentcount_total'],0,'.',',');
			// case 'views':
			//     return number_format($item['views'],0,'.',',');
			// case 'comments':
			//     return number_format($item['comment_count'],0,'.',',');
			case 'date':
				$dateString = date("M j, Y",strtotime($item['post_date']));

				$SECONDS_PER_DAY = 60*60*24;
				$daysActive = (time() - strtotime($item['post_date'])) / $SECONDS_PER_DAY;

				$dateString .= '<br> '.round($daysActive, 1) . ' days ago';
				return $dateString;
			default:
				return 'Not Set';
				//return print_r($item,true); //Show the whole array for troubleshooting purposes
		}
	}

	function column_title($item){

		//Build row actions
		$actions = array(
			'view'      => sprintf('<a href="%s">View</a>',$item['permalink']),
			'edit'      => sprintf('<a href="post.php?post=%s&action=edit">Edit</a>',$item['ID']),
			'update'    => sprintf('Decay last calculated %s',SocialMetricsTracker::timeago($item['social_aggregate_score_decayed_last_updated']))
		);

		//Return the title contents

		return '<a href="'.$item['permalink'].'"><b>'.$item['post_title'] . '</b></a>' . $this->row_actions($actions);
	}

	// Column for Social

	function column_aggregate($item) {

		//return print_r($item,true);
		// $total = max($item['social_aggregate_score'], 1);
		$total = floatval($item['social_aggregate_score']);

		$social_score = $item['social_aggregate_score_detail']['social_points'];
		$social_score_percent = floor($social_score / max($total, 1)  * 100);

		$views = $item['social_aggregate_score_detail']['view_points'];
		$views_percent = floor($views / max($total, 1)  * 100);

		$comments = $item['social_aggregate_score_detail']['comment_points'];
		$comments_percent = floor($comments / max($total, 1) * 100);

		$bar_width = round($total / max($this->data_max['social_aggregate_score'], 1) * 100);
		if ($total == 0) $bar_width = 0;

		$bar_class = ($bar_width > 50) ? ' stats' : '';

		$output = '';
		$output .= '<div class="bar'.$bar_class.'" style="width:'.$bar_width.'%">';
		$output .= '<span class="social" style="width:'.$social_score_percent.'%">'. $social_score_percent .'% Shares</span>';
		$output .= '<span class="views" style="width:'.$views_percent.'%">'. $views_percent .'% Views</span>';
		$output .= '<span class="comments" style="width:'.$comments_percent.'%">'. $comments_percent .'% Comments</span>';
		$output .= '</div>';
		$output .= '<div class="total">'.number_format($total,2,'.',',') . '</div>';

		return $output;

	}

	// Column for views
	function column_decayed($item) {
		$output = '';
		$output .= '<div class="bar" style="width:'.round($item['social_aggregate_score_decayed'] / max($this->data_max['social_aggregate_score_decayed'], 1) * 100).'%">';
		$output .= '<div class="total">'.number_format(floatval($item['social_aggregate_score_decayed']),2,'.',',') . '</div>';
		$output .= '</div>';

		return $output;
	}

	// Column for comments
	function column_misc($item) {
		$output = '';
		// $output .= '<div class="bar" style="width:'.round($item['comment_count'] / $this->data_max['comment_count'] * 100).'%">';
		// $output .= '<div class="total">'.number_format($item['comment_count'],0,'.',',') . '</div>';
		// $output .= '</div>';

		$output .= 'S ('.$item['socialcount_TOTAL'].') V ('.$item['ga_pageviews'].') C ('.$item['comment_count'].')';

		return $output;
	}

	function get_columns(){


		$columns['date'] = 'Date';
		$columns['title'] = 'Title';

		$columns['aggregate'] = 'Aggregate Score';
		// if ($this->options['smt_options_enable_analytics']) {
			$columns['decayed'] = 'Time Decayed Score';
		// }
		$columns['misc'] = 'Original Data';

		return $columns;
	}

	function get_sortable_columns() {
		$sortable_columns = array(
			'date'      => array('post_date',true),
			//'title'     => array('title',false),
			'decayed'    => array('decayed',true),
			'aggregate'  => array('aggregate',true),
			// 'misc'  => array('misc',true)
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

	function prepare_items() {
		global $wpdb; //This is used only if making any database queries


		/**
		 * First, lets decide how many records per page to show
		 */
		$per_page = 10;


		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();


		$this->_column_headers = array($columns, $hidden, $sortable);


		$this->process_bulk_action();



		$order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'DESC'; //If no order, default
		$orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'decayed'; //If no sort, default

		// Get custom post types to display in our report.
		$post_types = get_post_types(array('public'=>true, 'show_ui'=>true));
		unset($post_types['page']);
		unset($post_types['attachment']);

		$limit = 30;

		if ($orderby == 'decayed') {
			$querydata = new WP_Query(array(
				'order'         =>$order,
				'orderby'       =>'meta_value_num',
				'meta_key'      =>'social_aggregate_score_decayed',
				'posts_per_page'=>$limit,
				'post_status'   => 'publish',
				'post_type'     => $post_types
			));
		}

		// if ($orderby == 'comments') {
		//     $querydata = new WP_Query(array(
		//         'order'         =>$order,
		//         'orderby'       =>'comment_count',
		//         'posts_per_page'=>$limit,
		//         'post_status'   => 'publish',
		//         'post_type'     => $post_types
		//     ));
		// }

		if ($orderby == 'aggregate') {
			$querydata = new WP_Query(array(
				'order'         =>$order,
				'orderby'       =>'meta_value_num',
				'meta_key'      =>'social_aggregate_score',
				'posts_per_page'=>$limit,
				'post_status'   => 'publish',
				'post_type'     => $post_types
			));
		}

		if ($orderby == 'post_date') {
			$querydata = new WP_Query(array(
				'order'         =>$order,
				'orderby'       =>'post_date',
				'posts_per_page'=>$limit,
				'post_status'   => 'publish',
				'post_type'     => $post_types
			));
		}



		$data=array();

		$this->data_max['social_aggregate_score'] = 1;
		$this->data_max['social_aggregate_score_decayed'] = 1;
		$this->data_max['comment_count'] = 1;

		// foreach ($querydata as $querydatum ) {
		if ( $querydata->have_posts() ) : while ( $querydata->have_posts() ) : $querydata->the_post();
			global $post;

			$item['ID'] = $post->ID;
			$item['post_title'] = $post->post_title;
			$item['post_date'] = $post->post_date;
			$item['comment_count'] = $post->comment_count;
			$item['ga_pageviews'] = get_post_meta($post->ID, "ga_pageviews", true);
			$item['social_aggregate_score'] = get_post_meta($post->ID, "social_aggregate_score", true);
			$item['social_aggregate_score_detail'] = get_post_meta($post->ID, "social_aggregate_score_detail", true);
			$item['social_aggregate_score_decayed'] = get_post_meta($post->ID, "social_aggregate_score_decayed", true);
			$item['socialcount_twitter'] = get_post_meta($post->ID, "socialcount_twitter", true);
			$item['socialcount_facebook'] = get_post_meta($post->ID, "socialcount_facebook", true);
			$item['socialcount_TOTAL'] = get_post_meta($post->ID, "socialcount_TOTAL", true);
			$item['socialcount_LAST_UPDATED'] = get_post_meta($post->ID, "socialcount_LAST_UPDATED", true);
			$item['social_aggregate_score_decayed_last_updated'] = get_post_meta($post->ID, "social_aggregate_score_decayed_last_updated", true);
			$item['permalink'] = get_permalink($post->ID);

			$this->data_max['social_aggregate_score'] = max($this->data_max['social_aggregate_score'], $item['social_aggregate_score']);
			// $this->data_max['social_aggregate_score']['average'] += $item['social_aggregate_score'];

			$this->data_max['social_aggregate_score_decayed'] = max($this->data_max['social_aggregate_score_decayed'], $item['social_aggregate_score_decayed']);
			// $this->data_max['views']['average'] += $item['views'];

			$this->data_max['comment_count'] = max($this->data_max['comment_count'], $item['comment_count']);
			// $this->data_max['comment_count']['average'] += $item['comment_count'];

		   array_push($data, $item);
		endwhile;
		endif;

		// Calculate the averages
		// $num_entries = count($querydatum);
		// $this->data_max['social_aggregate_score']['average'] = $this->data_max['social_aggregate_score']['average'] / $num_entries;
		// $this->data_max['views']['average'] = $this->data_max['views']['average'] / $num_entries;




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
			$range = (isset($_GET['range'])) ? $_GET['range'] : $this->options['smt_options_default_date_range_months'];
			?>

			<?php
			if (current_user_can('manage_options')) {
				$url = add_query_arg(array('smc_recalculate_all_ranks' => 1), 'admin.php?page=social-metrics-tracker-debug');
				echo "<a href='$url' class='button' onClick='return confirm(\"This will recalculate ranking info for all posts. Are you sure?\")'>Recalculate all ranks</a>";
			}
		}
		if ( $which == "bottom" ){
			//The code that goes after the table is there
		}
	}

}





/** ************************ REGISTER THE TEST PAGE ****************************
 *******************************************************************************
 * Now we just need to define an admin page. For this example, we'll add a top-level
 * menu item to the bottom of the admin menus.
 */


/***************************** RENDER TEST PAGE ********************************
 *******************************************************************************
 * This function renders the admin page and the example list table. Although it's
 * possible to call prepare_items() and display() from the constructor, there
 * are often times where you may need to include logic here between those steps,
 * so we've instead called those methods explicitly. It keeps things flexible, and
 * it's the way the list tables are used in the WordPress core.
 */
function smt_render_dashboard_debug_view($options){


	//Create an instance of our package class...
	$testListTable = new SocialMetricsDebugTable();
	//Fetch, prepare, sort, and filter our data...
	$testListTable->prepare_items();

	?>
	<div class="wrap">

		<div id="icon-users" class="icon32"><br/></div>
		<h2>Advanced Relevancy Rank Dashboard</h2>

		<?php
		if(!is_array($options)) {
			printf( '<div class="error"> <p> %s </p> </div>', "Before you can view data, you must <a class='login' href='options-general.php?page=social-metrics-tracker-settings'>configure the Social Metrics Tracker</a>." );
			die();
		}

		if (isset($_GET['smc_recalculate_all_ranks'])) {
			printf( '<div class="updated"> <p> %s </p> </div>',  'Now updating all social relevancy ranks... Do not navigate away from this page until it is complete! <a href="admin.php?page=social-metrics-tracker-debug">Return to report view</a>');

			$data_updater = new MetricsUpdater();
			$data_updater->recalculateAllScores(true);
			die();
		}
		?>

		<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
		<form id="social-metrics-tracker" method="get" action="admin.php?page=social-metrics-tracker-debug">
			<!-- For plugins, we also need to ensure that the form posts back to our current page -->
			<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
			<input type="hidden" name="orderby" value="<?php echo (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : $options['smt_options_default_sort_column']; ?>" />
			<input type="hidden" name="order" value="<?php echo (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'DESC'; ?>" />

			<!-- Now we can render the completed list table -->
			<?php $testListTable->display() ?>
		</form>

	</div>
	<?php
}

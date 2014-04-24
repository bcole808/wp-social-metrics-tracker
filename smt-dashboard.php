<?php

/***************************************************
* This table class is based on the "Custom List Table Example" provided by Matt van Andel
*
* http://wordpress.org/plugins/custom-list-table-example/
***************************************************/

if(!class_exists('WP_List_Table')){
	// We include a copy of WP_List_Table with this plugin because this class is marked as Private in the Wordpress core and could change at any time.
	require_once( 'lib/class-wp-list-table.php' );
}

class SocialMetricsTable extends WP_List_Table {

	function __construct(){
		global $status, $page;

		$this->options = get_option('smt_settings');

		$this->services = array(
			'facebook'   => 'Facebook', 
			'twitter'    => 'Twitter', 
			'googleplus' => 'Google Plus', 
			'linkedin'   => 'LinkedIn', 
			'pinterest'  => 'Pinterest', 
			'diggs'      => 'Digg.com', 
			'delicious'	 => 'Delicious', 
			'reddit'	 => 'Reddit', 
			'stumbleupon'=> 'Stumble Upon'
		);

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
			'view'      => sprintf('<a href="%s">View</a>',$item['permalink']),
			'edit'      => sprintf('<a href="post.php?post=%s&action=edit">Edit</a>',$item['ID']),
			'update'    => sprintf('Updated %s',SocialMetricsTracker::timeago($item['socialcount_LAST_UPDATED']))
		);

		//Return the title contents

		return '<a href="'.$item['permalink'].'"><b>'.$item['post_title'] . '</b></a>' . $this->row_actions($actions);
	}

	// Column for Social

	function column_social($item) {

		$total = floatval($item['socialcount_total']);
		$bar_width = ($total == 0) ? 0 : round($total / max($this->data_max['socialcount_total'], 1)  * 100);

		$output = '<div class="bar" style="width:'.$bar_width.'%;">';

		foreach ($this->services as $slug => $name) {
			$percent = floor($item['socialcount_'.$slug] / max($total, 1) * 100);
			$output .= '<span class="'.$slug.'" style="width:'.$percent.'%" title="'.$name.': '.$item['socialcount_'.$slug].' ('.$percent.'% of total)">'.$name.'</span>';
		}

		$output .= '</div><div class="total">'.number_format($total,0,'.',',') . '</div>';

		return $output;
	}

	// Column for views
	function column_views($item) {
		$output = '';
		$output .= '<div class="bar" style="width:'.round($item['views'] / $this->data_max['views'] * 100).'%">';
		$output .= '<div class="total">'.number_format($item['views'],0,'.',',') . '</div>';
		$output .= '</div>';

		return $output;
	}

	// Column for comments
	function column_comments($item) {
		$output = '';
		$output .= '<div class="bar" style="width:'.round($item['comment_count'] / $this->data_max['comment_count'] * 100).'%">';
		$output .= '<div class="total">'.number_format($item['comment_count'],0,'.',',') . '</div>';
		$output .= '</div>';

		return $output;
	}

	function get_columns(){

		$columns['date'] = 'Date';
		$columns['title'] = 'Title';

		if ($this->options['smt_options_enable_social']) {
			$columns['social'] = 'Social Score';
		}
		if ($this->options['smt_options_enable_analytics']) {
			$columns['views'] = 'Views';
		}
		if ($this->options['smt_options_enable_comments']) {
			$columns['comments'] = 'Comments';
		}

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

		$range = (isset($_GET['range'])) ? $_GET['range'] : $this->options['smt_options_default_date_range_months'];

		if ($range <= 0) return $where;

		$range_bottom = " AND post_date >= '".date("Y-m-d", strtotime('-'.$range.' month') );
		$range_top = "' AND post_date <= '".date("Y-m-d")."'";

		$where .= $range_bottom . $range_top;
		return $where;
	}


	function prepare_items() {
		global $wpdb; //This is used only if making any database queries

		$per_page = 10;

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array($columns, $hidden, $sortable);

		$this->process_bulk_action();

		$order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'DESC'; //If no order, default
		$orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : $this->options['smt_options_default_sort_column']; //If no sort, default

		// Get custom post types to display in our report.
		$post_types = get_post_types(array('public'=>true, 'show_ui'=>true));
		unset($post_types['page']);
		unset($post_types['attachment']);

		$limit = 30;

		add_filter( 'posts_where', array($this, 'date_range_filter') );

		if ($orderby == 'views') {
			$querydata = new WP_Query(array(
				'order'=>$order,
				'orderby'=>'meta_value_num',
				'meta_key'=>'ga_pageviews',
				'posts_per_page'=>$limit,
				'post_status'   => 'publish',
				'post_type'     => $post_types
			));
		}

		if ($orderby == 'comments') {
			$querydata = new WP_Query(array(
				'order'=>$order,
				'orderby'=>'comment_count',
				'posts_per_page'=>$limit,
				'post_status'   => 'publish',
				'post_type'     => $post_types
			));
		}

		if ($orderby == 'social') {
			$querydata = new WP_Query(array(
				'order'=>$order,
				'orderby'=>'meta_value_num',
				'meta_key'=>'socialcount_TOTAL',
				'posts_per_page'=>$limit,
				'post_status'   => 'publish',
				'post_type'     => $post_types
			));
		}

		if ($orderby == 'aggregate') {
			$querydata = new WP_Query(array(
				'order'=>$order,
				'orderby'=>'meta_value_num',
				'meta_key'=>'social_aggregate_score',
				'posts_per_page'=>$limit,
				'post_status'   => 'publish',
				'post_type'     => $post_types
			));
		}

		if ($orderby == 'post_date') {
			$querydata = new WP_Query(array(
				'order'=>$order,
				'orderby'=>'post_date',
				'posts_per_page'=>$limit,
				'post_status'   => 'publish',
				'post_type'     => $post_types
			));
		}

		// Remove our date filter
		remove_filter( 'posts_where', array($this, 'date_range_filter') );

		$data=array();

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

			foreach ($this->services as $slug => $name) {
				$item['socialcount_'.$slug] = get_post_meta($post->ID, "socialcount_$slug", true);
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
			$range = (isset($_GET['range'])) ? $_GET['range'] : $this->options['smt_options_default_date_range_months'];
			?>
			<label for="range">Show only:</label>
					<select name="range">
						<option value="1"<?php if ($range == 1) echo 'selected="selected"'; ?>>Items published within 1 Month</option>
						<option value="3"<?php if ($range == 3) echo 'selected="selected"'; ?>>Items published within 3 Months</option>
						<option value="6"<?php if ($range == 6) echo 'selected="selected"'; ?>>Items published within 6 Months</option>
						<option value="12"<?php if ($range == 12) echo 'selected="selected"'; ?>>Items published within 12 Months</option>
						<option value="0"<?php if ($range == 0) echo 'selected="selected"'; ?>>Items published anytime</option>
					</select>

					<input type="submit" name="filter" id="submit_filter" class="button" value="Filter">

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
function smt_render_dashboard_view($options){
	?>
	<div class="wrap">
		<h2>Social Metrics Tracker</h2>
		<?php
		if(!is_array($options)) {
			printf( '<div class="error"> <p> %s </p> </div>', "Before you can view data, you must <a class='login' href='options-general.php?page=social-metrics-tracker-settings'>configure the Social Metrics Tracker</a>." );
			die();
		}

		?>

		<form id="social-metrics-tracker" method="get" action="admin.php?page=social-metrics-tracker">
			<!-- For plugins, we also need to ensure that the form posts back to our current page -->
			<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
			<input type="hidden" name="orderby" value="<?php echo (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : $options['smt_options_default_sort_column']; ?>" />
			<input type="hidden" name="order" value="<?php echo (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'DESC'; ?>" />

			<?php
			//Create an instance of our package class...
			$SocialMetricsTable = new SocialMetricsTable();

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
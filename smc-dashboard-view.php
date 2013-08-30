<?php
/*  Copyright 2011  Matthew Van Andel  (email : matt@mattvanandel.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/



/* == NOTICE ===================================================================
 * Please do not alter this file. Instead: make a copy of the entire plugin, 
 * rename it, and work inside the copy. If you modify this plugin directly and 
 * an update is released, your changes will be lost!
 * ========================================================================== */



/*************************** LOAD THE BASE CLASS *******************************
 *******************************************************************************
 * The WP_List_Table class isn't automatically available to plugins, so we need
 * to check if it's available and load it if necessary.
 */
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}




/************************** CREATE A PACKAGE CLASS *****************************
 *******************************************************************************
 * Create a new list table package that extends the core WP_List_Table class.
 * WP_List_Table contains most of the framework for generating the table, but we
 * need to define and override some methods so that our data can be displayed
 * exactly the way we need it to be.
 * 
 * To display this example on a page, you will first need to instantiate the class,
 * then call $yourInstance->prepare_items() to handle any data manipulation, then
 * finally call $yourInstance->display() to render the table to the page.
 * 
 * Our theme for this list table is going to be movies.
 */
class TT_Example_List_Table extends WP_List_Table {
    
   
    /** ************************************************************************
     * REQUIRED. Set up a constructor that references the parent constructor. We 
     * use the parent reference to set some default configs.
     ***************************************************************************/
    function __construct(){
        global $status, $page, $data_max, $smc_options;

        $data_max = array();
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'post',     //singular name of the listed records
            'plural'    => 'posts',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
        
    }
    
    
    /** ************************************************************************
     * Recommended. This method is called when the parent class can't find a method
     * specifically build for a given column. Generally, it's recommended to include
     * one method for each column you want to render, keeping your package class
     * neat and organized. For example, if the class needs to process a column
     * named 'title', it would first see if a method named $this->column_title() 
     * exists - if it does, that method will be used. If it doesn't, this one will
     * be used. Generally, you should try to use custom column methods as much as 
     * possible. 
     * 
     * Since we have defined a column_title() method later on, this method doesn't
     * need to concern itself with any column with a name of 'title'. Instead, it
     * needs to handle everything else.
     * 
     * For more detailed insight into how columns are handled, take a look at 
     * WP_List_Table::single_row_columns()
     * 
     * @param array $item A singular item (one full row's worth of data)
     * @param array $column_name The name/slug of the column to be processed
     * @return string Text or HTML to be placed inside the column <td>
     **************************************************************************/
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
                //return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }
    
        
    /** ************************************************************************
     * Recommended. This is a custom column method and is responsible for what
     * is rendered in any column with a name/slug of 'title'. Every time the class
     * needs to render a column, it first looks for a method named 
     * column_{$column_title} - if it exists, that method is run. If it doesn't
     * exist, column_default() is called instead.
     * 
     * This example also illustrates how to implement rollover actions. Actions
     * should be an associative array formatted as 'slug'=>'link html' - and you
     * will need to generate the URLs yourself. You could even ensure the links
     * 
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (movie title only)
     **************************************************************************/
    function column_title($item){
        
        //Build row actions
        $actions = array(
            'view'      => sprintf('<a href="%s">View</a>',$item['permalink']),
            'edit'      => sprintf('<a href="post.php?post=%s&action=edit">Edit</a>',$item['ID']),
            'update'    => sprintf('Updated %s',timeago($item['socialcount_LAST_UPDATED']))
        );
        
        //Return the title contents

        return '<a href="'.$item['permalink'].'"><b>'.$item['post_title'] . '</b></a>' . $this->row_actions($actions);
    }

    // Column for Social

    function column_social($item) {

        //return print_r($item,true);
        $total = max($item['socialcount_total'], 1);

        $facebook = $item['socialcount_facebook'];
        $facebook_percent = floor($facebook / $total * 100);

        $twitter = $item['socialcount_twitter'];
        $twitter_percent = floor($twitter / $total * 100);

        $other = $total - $facebook - $twitter;
        $other_percent = floor($other / $total * 100);

        $bar_width = round($total / $this->data_max['socialcount_total'] * 100);
        if ($total == 0) $bar_width = 0;

        $bar_class = ($bar_width > 50) ? ' stats' : '';

        $output = '';
        $output .= '<div class="bar'.$bar_class.'" style="width:'.$bar_width.'%">';
        $output .= '<span class="facebook" style="width:'.$facebook_percent.'%">'. $facebook_percent .'% Facebook</span>';
        $output .= '<span class="twitter" style="width:'.$twitter_percent.'%">'. $twitter_percent .'% Twitter</span>';
        $output .= '<span class="other" style="width:'.$other_percent.'%">'. $other_percent .'% Other</span>';
        $output .= '</div>';
        $output .= '<div class="total">'.number_format($total,0,'.',',') . '</div>';

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
    
    /** ************************************************************************
     * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
     * is given special treatment when columns are processed. It ALWAYS needs to
     * have it's own method.
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (movie title only)
     **************************************************************************/
    // function column_cb($item){
    //     return sprintf(
    //         '<input type="checkbox" name="%1$s[]" value="%2$s" />',
    //         /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
    //         /*$2%s*/ $item['ID']                //The value of the checkbox should be the record's id
    //     );
    // }
    
    
    /** ************************************************************************
     * REQUIRED! This method dictates the table's columns and titles. This should
     * return an array where the key is the column slug (and class) and the value 
     * is the column's title text. If you need a checkbox for bulk actions, refer
     * to the $columns array below.
     * 
     * The 'cb' column is treated differently than the rest. If including a checkbox
     * column in your table you must create a column_cb() method. If you don't need
     * bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
     * 
     * @see WP_List_Table::::single_row_columns()
     * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
     **************************************************************************/
    function get_columns(){
        global $smc_options;

        $columns['date'] = 'Date';
        $columns['title'] = 'Title';

        if ($smc_options['socialinsight_options_enable_social']) {
            $columns['social'] = 'Social Score';
        }
        if ($smc_options['socialinsight_options_enable_analytics']) {
            $columns['views'] = 'Views';
        }
        if ($smc_options['socialinsight_options_enable_comments']) {
            $columns['comments'] = 'Comments';
        }

        return $columns;
    }
    
    /** ************************************************************************
     * Optional. If you want one or more columns to be sortable (ASC/DESC toggle), 
     * you will need to register it here. This should return an array where the 
     * key is the column that needs to be sortable, and the value is db column to 
     * sort by. Often, the key and value will be the same, but this is not always
     * the case (as the value is a column name from the database, not the list table).
     * 
     * This method merely defines which columns should be sortable and makes them
     * clickable - it does not handle the actual sorting. You still need to detect
     * the ORDERBY and ORDER querystring variables within prepare_items() and sort
     * your data accordingly (usually by modifying your query).
     * 
     * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
     **************************************************************************/
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
    
    
    /** ************************************************************************
     * Optional. If you need to include bulk actions in your list table, this is
     * the place to define them. Bulk actions are an associative array in the format
     * 'slug'=>'Visible Title'
     * 
     * If this method returns an empty value, no bulk action will be rendered. If
     * you specify any bulk actions, the bulk actions box will be rendered with
     * the table automatically on display().
     * 
     * Also note that list tables are not automatically wrapped in <form> elements,
     * so you will need to create those manually in order for bulk actions to function.
     * 
     * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
     **************************************************************************/
    function get_bulk_actions() {
        $actions = array(
            //'delete'    => 'Delete'
        );
        return $actions;
    }
    
    
    /** ************************************************************************
     * Optional. You can handle your bulk actions anywhere or anyhow you prefer.
     * For this example package, we will handle it in the class to keep things
     * clean and organized.
     * 
     * @see $this->prepare_items()
     **************************************************************************/
    function process_bulk_action() {
        
        //Detect when a bulk action is being triggered...
        // if( 'delete'===$this->current_action() ) {
        //     wp_die('Items deleted (or they would be if we had items to delete)!');
        // }
        
    }
    
    
    /** ************************************************************************
     * REQUIRED! This is where you prepare your data for display. This method will
     * usually be used to query the database, sort and filter the data, and generally
     * get it ready to be displayed. At a minimum, we should set $this->items and
     * $this->set_pagination_args(), although the following properties and methods
     * are frequently interacted with here...
     * 
     * @global WPDB $wpdb
     * @uses $this->_column_headers
     * @uses $this->items
     * @uses $this->get_columns()
     * @uses $this->get_sortable_columns()
     * @uses $this->get_pagenum()
     * @uses $this->set_pagination_args()
     **************************************************************************/
    function prepare_items() {
        global $wpdb; //This is used only if making any database queries
        global $smc_options;

        /**
         * First, lets decide how many records per page to show
         */
        $per_page = 10;
        
        
        /**
         * REQUIRED. Now we need to define our column headers. This includes a complete
         * array of columns to be displayed (slugs & titles), a list of columns
         * to keep hidden, and a list of columns that are sortable. Each of these
         * can be defined in another method (as we've done here) before being
         * used to build the value for our _column_headers property.
         */
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        
        /**
         * REQUIRED. Finally, we build an array to be used by the class for column 
         * headers. The $this->_column_headers property takes an array which contains
         * 3 other arrays. One for all columns, one for hidden columns, and one
         * for sortable columns.
         */
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        
        /**
         * Optional. You can handle your bulk actions however you see fit. In this
         * case, we'll handle them within our package just to keep things clean.
         */
        $this->process_bulk_action();
        
        
        /**
         * Instead of querying a database, we're going to fetch the example data
         * property we created for use in this plugin. This makes this example 
         * package slightly different than one you might build on your own. In 
         * this example, we'll be using array manipulation to sort and paginate 
         * our data. In a real-world implementation, you will probably want to 
         * use sort and pagination data to build a custom query instead, as you'll
         * be able to use your precisely-queried data immediately.
         */
        //$data = $this->example_data;

         // $querydata = $wpdb->get_results("SELECT * FROM wp_posts WHERE 1");
        // $now = current_time('mysql', 1);
        // $querydata = $wpdb->get_results("SELECT $wpdb->posts.ID, $wpdb->posts.post_title, $wpdb->postmeta.* 
        //     FROM $wpdb->posts, $wpdb->postmeta
        //     WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id
        //     AND $wpdb->posts.post_status = 'publish'
        //     AND $wpdb->posts.post_date <= '$now'
        //     ORDER BY $wpdb->posts.post_date ASC
        //     LIMIT 2");


        $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'DESC'; //If no order, default
        $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : $smc_options['socialinsight_options_default_sort_column']; //If no sort, default
    
        // Get custom post types to display in our report. 		
		$post_types = get_post_types(array('public'=>true, 'show_ui'=>true));
        unset($post_types['page']);
		unset($post_types['attachment']);
        
        $limit = 30;

        function filter_where( $where = '' ) {
			global $smc_options;
						
			$range = (isset($_GET['range'])) ? $_GET['range'] : $smc_options['socialinsight_options_default_date_range_months'];
			
			if ($range <= 0) return $where;
			
        	$range_bottom = " AND post_date >= '".date("Y-m-d", strtotime('-'.$range.' month') );
        	$range_top = "' AND post_date <= '".date("Y-m-d")."'";
						
            $where .= $range_bottom . $range_top;
            return $where;
        }

        add_filter( 'posts_where', 'filter_where' );

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
		
		//$querydata = new WP_Query("post_status=publish&orderby=meta_value_num&meta_key=socialcount_TOTAL");

        //print_r($querydata->query_vars);

        // Remove our date filter
        remove_filter( 'posts_where', 'filter_where' );

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
            $item['socialcount_total'] = smc_get_socialcount($post->ID, false);
            $item['socialcount_twitter'] = get_post_meta($post->ID, "socialcount_twitter", true);
            $item['socialcount_facebook'] = get_post_meta($post->ID, "socialcount_facebook", true);
			$item['socialcount_LAST_UPDATED'] = get_post_meta($post->ID, "socialcount_LAST_UPDATED", true);
            $item['views'] = smc_get_views($post->ID);
            $item['permalink'] = get_permalink($post->ID);

            $this->data_max['socialcount_total'] = max($this->data_max['socialcount_total'], $item['socialcount_total']);
            // $this->data_max['socialcount_total']['average'] += $item['socialcount_total'];

            $this->data_max['views'] = max($this->data_max['views'], $item['views']);
            // $this->data_max['views']['average'] += $item['views'];

            $this->data_max['comment_count'] = max($this->data_max['comment_count'], $item['comment_count']);
            // $this->data_max['comment_count']['average'] += $item['comment_count'];

           array_push($data, $item);
        endwhile;
        endif;

        // Calculate the averages
        // $num_entries = count($querydatum);
        // $this->data_max['socialcount_total']['average'] = $this->data_max['socialcount_total']['average'] / $num_entries;
        // $this->data_max['views']['average'] = $this->data_max['views']['average'] / $num_entries;
                
        
        /**
         * This checks for sorting input and sorts the data in our array accordingly.
         * 
         * In a real-world situation involving a database, you would probably want 
         * to handle sorting by passing the 'orderby' and 'order' values directly 
         * to a custom query. The returned data will be pre-sorted, and this array
         * sorting technique would be unnecessary.
         */
        // function usort_reorder($a,$b){
        //     $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'ID'; //If no sort, default
        //     $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'desc'; //If no order, default
        //     $result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order
        //     return ($order==='asc') ? $result : -$result; //Send final sort direction to usort
        // }
        // usort($data, 'usort_reorder');
        
        
        /***********************************************************************
         * ---------------------------------------------------------------------
         * vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv
         * 
         * In a real-world situation, this is where you would place your query.
         * 
         * ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
         * ---------------------------------------------------------------------
         **********************************************************************/
        
                
        /**
         * REQUIRED for pagination. Let's figure out what page the user is currently 
         * looking at. We'll need this later, so you should always include it in 
         * your own package classes.
         */
        $current_page = $this->get_pagenum();
        
        /**
         * REQUIRED for pagination. Let's check how many items are in our data array. 
         * In real-world use, this would be the total number of items in your database, 
         * without filtering. We'll need this later, so you should always include it 
         * in your own package classes.
         */
        $total_items = count($data);
        
        
        /**
         * The WP_List_Table class does not handle pagination for us, so we need
         * to ensure that the data is trimmed to only the current page. We can use
         * array_slice() to 
         */
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);
        
        
        
        /**
         * REQUIRED. Now we can add our *sorted* data to the items property, where 
         * it can be used by the rest of the class.
         */
        $this->items = $data;
        
        
        /**
         * REQUIRED. We also have to register our pagination options & calculations.
         */
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
        global $smc_options;
        if ( $which == "top" ){
            //The code that goes before the table is here
            $range = (isset($_GET['range'])) ? $_GET['range'] : $smc_options['socialinsight_options_default_date_range_months'];
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
            if (current_user_can('manage_options')) {
                $url = add_query_arg(array('smc_schedule_full_update' => 1), 'admin.php?page=smc-social-insight');
                echo "<a href='$url' class='button' onClick='return confirm(\"This will queue all items for an update. This may take a long time depending on the number of posts and should only be done if data becomes out of sync or after installing the plugin. Are you sure?\")'>Synchronize all data</a>";
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
// function tt_add_menu_items(){
//     add_menu_page('Example Plugin List Table', 'List Table Example', 'activate_plugins', 'tt_list_test', 'smc_render_dashboard_view');
// } add_action('admin_menu', 'tt_add_menu_items');


/***************************** RENDER TEST PAGE ********************************
 *******************************************************************************
 * This function renders the admin page and the example list table. Although it's
 * possible to call prepare_items() and display() from the constructor, there
 * are often times where you may need to include logic here between those steps,
 * so we've instead called those methods explicitly. It keeps things flexible, and
 * it's the way the list tables are used in the WordPress core.
 */
function smc_render_dashboard_view(){
    global $smc_options;

    if(!is_array($smc_options)) {
        printf( '<div class="error"> <p> %s </p> </div>', "An administrator must <a class='login' href='options-general.php?page=smc_settings'>update the plugin settings </a> in order to enable data tracking." );

        die();
    }

    if (isset($_GET['smc_schedule_full_update'])) {
        smc_do_full_update();
        printf( '<div class="updated"> <p> %s </p> </div>',  'A full data update has been scheduled. This may take some time. <a href="admin.php?page=smc-social-insight">Return to report view</a>');
        die();
    }
    
    //Create an instance of our package class...
    $testListTable = new TT_Example_List_Table();
    //Fetch, prepare, sort, and filter our data...
    $testListTable->prepare_items();
    
    ?>
    <div class="wrap">
        
        <div id="icon-users" class="icon32"><br/></div>
        <h2>Social Insight Dashboard</h2>

        <?php

        // Verify the API authorization
        require_once ('smc-ga-query.php');
        if (current_user_can('manage_options') && $smc_options['socialinsight_options_enable_analytics']) {
            smc_gapi_loginout();
        }

        ?>


        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
        <form id="smc-social-insight" method="get" action="admin.php?page=smc-social-insight">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <input type="hidden" name="orderby" value="<?php echo (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : $smc_options['socialinsight_options_default_sort_column']; ?>" />
            <input type="hidden" name="order" value="<?php echo (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'DESC'; ?>" />
           
            <!-- Now we can render the completed list table -->
            <?php $testListTable->display() ?>
        </form>

        <?php smc_queue_length(); ?>

        <h2>Frequently Asked Questions</h2>
        
        <div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
            <h3>What is Social Score?</h3>
            <p>This is the number of people who shared, liked, or tweeted a post across various social networks. This number includes Facebook, Twitter, Google+, LinkedIn, Reddit, Digg, Delicious, StumbleUpon, and Pinterest. </p>
            <h3>How are Views calculated?</h3>
            <p>Data is gathered from Google Analytics. A view is counted when someone loads a post in their browser or on a mobile device.</p>
            <h3>When is the data updated?</h3>
            <p>Data is delayed by up to a few hours. An algorithm dynamically adjusts the update frequency of each post based on its popularity. </p>
        </div>
        
    </div>
    <?php
}

function timeago($time)
{
   $periods = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
   $lengths = array("60","60","24","7","4.35","12","10");

   $now = time();

       $difference     = $now - $time;
       $tense         = "ago";

   for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
       $difference /= $lengths[$j];
   }

   $difference = round($difference);

   if($difference != 1) {
       $periods[$j].= "s";
   }

   return "$difference $periods[$j] ago";
}

?>
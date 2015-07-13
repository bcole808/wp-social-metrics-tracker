<?php

/***************************************************
* This script exports all Social Metric Tracker data as a spreadsheet. 
***************************************************/

function smt_download_export_file($smt) {

	$data = array();

	$gapi = new GoogleAnalyticsUpdater(); 
	$gapi_can_sync = $gapi->can_sync();

	$querydata = new WP_Query(array(
		'posts_per_page'=> -1,
		'post_status'	=> 'publish',
		'post_type'		=> $smt->tracked_post_types(),
		'orderby'       => 'date',
		'order'         => 'DESC'
	));

	if ( $querydata->have_posts() ) : while ( $querydata->have_posts() ) : $querydata->the_post();

		global $post;

		$item = array();

		$item['Post ID']             = $post->ID;
		$item['Title']               = $post->post_title;
		$item['Date Published']      = $post->post_date;
		$item['Main URL to Post']    = get_permalink($post->ID);
		$item['Additional URLs']     = count( get_post_meta( $post->ID, 'socialcount_url_data' ) );
		$item['Author']              = get_the_author_meta('display_name') . ' <' . get_the_author_meta('user_email') . '>';
		$item['Total Social Count']  = (get_post_meta($post->ID, "socialcount_TOTAL", true)) ? get_post_meta($post->ID, "socialcount_TOTAL", true) : 0;
		$item['Total Comment Count'] = $post->comment_count;
		
		if ($gapi_can_sync) $item['Total Page Views'] = get_post_meta($post->ID, "ga_pageviews", true);

		foreach ($smt->updater->getSources() as $HTTPResourceUpdater) {
			$item[$HTTPResourceUpdater->name] = get_post_meta($post->ID, "socialcount_".$HTTPResourceUpdater->slug, true);
		}

	   array_push($data, $item);

	endwhile;
	endif;


	// Build the spreadsheet headings
	$headings = array();
	foreach ($data[0] as $key => $value) {
		$headings[] = $key;
	}

	// Set file type to CSV for download
	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename=social_metrics.csv');

	// Create output stream
	$output = fopen('php://output', 'w');

	// Print headings
	fputcsv($output, $headings);

	// Print rows
	foreach ($data as $row) {
		fputcsv($output, $row);
	}

	exit;

}

function smt_render_export_view($smt) { ?>

	<div class="wrap">
		<h2>Social Metrics Export Tool</h2>
		<p>You can use this tool to export data collected by the Social Metrics Tracker. </p>

		<a href="<?php echo add_query_arg('smt_download_export_file', '1') ?>" class="button-primary">Download Export File »</a> 
		<p><i>There are a lot of numbers to crunch. <br /> It may take a while to prepare your download...</i></p>
	</div>

<?php
}
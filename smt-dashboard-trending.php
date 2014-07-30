<?php

function smt_render_dashboard_trending_view() {

	$days = $_REQUEST['days'] ?: 2;

?>
<div class="wrap">
<h2><?php echo $days ?> day Trending Data!!!!!</h2>

<a href="<?php echo add_query_arg(array('days'=>1));?>">1</a> |
<a href="<?php echo add_query_arg(array('days'=>2));?>">2</a> |
<a href="<?php echo add_query_arg(array('days'=>3));?>">3</a> |
<a href="<?php echo add_query_arg(array('days'=>4));?>">4</a> |
<a href="<?php echo add_query_arg(array('days'=>5));?>">5</a> |
<a href="<?php echo add_query_arg(array('days'=>6));?>">6</a> |
<a href="<?php echo add_query_arg(array('days'=>7));?>">7</a> |
<a href="<?php echo add_query_arg(array('days'=>14));?>">14</a> |
<a href="<?php echo add_query_arg(array('days'=>30));?>">30</a>

<hr><hr>
<?php

$trend_data = array();
$current_blog = get_current_blog_id();


$sites = wp_get_sites();
foreach ($sites as $blog) {
	$blogs[$blog['blog_id']] = get_blog_details($blog['blog_id']);
}


foreach ($blogs as $blog) {

	switch_to_blog($blog->blog_id);

	$result = SocialMetricsTracker::get_trending_data($days);

	for ($i = 0; $i < count($result); $i++) {
		$result[$i]['blog_id'] = $blog->blog_id;
	}

	if ($result) $trend_data = array_merge($trend_data, $result);

}

switch_to_blog($current_blog);
wp_reset_query();


usort($trend_data, arrSortObjsByKey('TOTAL'));

?>


<?php
foreach ($trend_data as $record) {

	switch_to_blog($record['blog_id']);

	echo $record['TOTAL'];
	echo ' : ';
	echo $blogs[$record['blog_id']]->path.' ';
	echo '<a href="'.get_permalink($record['post_id']).'">'.$record['post_title'].'</a>';

	echo '<br /><small style="color:#AAA;">';
	print_r($record);
	echo '</small>';

	print('<hr>');
}

?>

</div>

<?php
	
}
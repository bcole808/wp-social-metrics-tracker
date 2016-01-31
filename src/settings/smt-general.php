<?php

$smt_post_types = array();
foreach ( get_post_types( array('public'=>true, 'show_ui'=>true), 'objects' ) as $type ) {
	$smt_post_types[ $type->name ] = $type->labels->name;
}

global $wpsf_settings;

$wpsf_settings = array();

$wpsf_settings['smt'] = array(
	'section_id'          => 'options',
	'section_title'       => 'General Options',
	'section_description' => 'Configuration for the operation of the plugin and display of data.',
	'section_order'       => 10,
	'fields' => array(
		array(
            'id' => 'post_types',
            'title' => 'Post Types',
            'desc' => 'Which post types should we track? (Defaults to Posts and Pages if none are selected)',
            'type' => 'checkboxes',
            'std' => array(
                'post'
            ),
            'choices' => $smt_post_types
        ),
		array(
			'id'    => 'display_widget',
			'title' => 'Dashboard Widget',
			'desc'  => 'Show a widget on the main Dashboard with social metrics.',
			'type'  => 'checkbox',
			'std'   => 1
		),
		array(
			'id'    => 'update_mode',
			'title' => 'Update Mode',
			'desc'  => 'By default, the plugin will connect to social network APIs during the WP Cron (as a background task). This will keep your posts loading fast! If for some reason this is a problem, you can change the it to perform updates during regular page loads instead.',
			'type'  => 'select',
			'std'   => 'all',
			'choices' => array(
				'cron'     => 'WP Cron (Default - Runs in the background)',
				'pageload' => 'Page Footer (Can make posts load more slowly)',
			),
		),
		array(
			'id'    => 'update_range',
			'title' => 'Update Range',
			'desc'  => 'What posts should be automatically kept in sync? You can use this to limit the number of updates that run in case your site has a lot of older posts. It is recommended to track all posts unless you have too many.',
			'type'  => 'select',
			'std'   => 'all',
			'choices' => array(
				'all' => 'All posts',
				'none'    => 'No posts (Disables automatic updates)',
				'7'    => 'Only posts published within 1 week',
				'14'   => 'Only posts published within 2 weeks',
				'30'   => 'Only posts published within 1 month',
				'60'   => 'Only posts published within 2 months',
				'90'   => 'Only posts published within 3 months',
				'180'  => 'Only posts published within 6 months',
				'365'  => 'Only posts published within 1 year',
				'730'  => 'Only posts published within 2 years',
				'1460' => 'Only posts published within 4 years',
			)
		),
		array(
			'id'    => 'ttl_hours',
			'title' => 'Data TTL',
			'desc'  => 'Length of time to wait in between checking for new stats on each post. A shorter time will use more server resources. Longer times are recommended for sites with over 500 posts.',
			'type'  => 'select',
			'std'   => '12',
			'choices' => array(
				'1'   => '1 Hour',
				'2'   => '2 Hours',
				'4'   => '4 Hours',
				'6'   => '6 Hours',
				'8'   => '8 Hours',
				'12'  => '12 Hours',
				'24'  => '24 Hours',
				'36'  => '36 Hours',
				'48'  => '2 Days',
				'72'  => '3 Days',
				'168' => '1 Week',
				'720' => '1 Month',
			)
		),
		array(
			'id'    => 'report_visibility',
			'title' => 'Report Visibility',
			'desc'  => 'The Social Metrics Tracker reports will be visible to users who have this capability.',
			'type'  => 'select',
			'std'   => 'publish_posts',
			'choices' => array(
				'manage_network'    => 'Super Admins (Users who can manage the network)',
				'manage_options'    => 'Admins (Users who can manage options)',
				'edit_others_posts' => 'Editors (Users who can edit others posts)',
				'publish_posts'     => 'Authors (Users who can publish posts)',
				'edit_posts'        => 'Contributors (Users who can edit their own posts)',
				'read'              => 'Subscribers (Users who can read)'
			)
		),
		array(
			'id'    => 'default_sort_column',
			'title' => 'Report Sort Order',
			'desc'  => 'Which column should be sorted by default?',
			'type'  => 'select',
			'std'   => 'social',
			'choices' => array(
				'aggregate' => 'Overall Aggregate Score',
				'comments'  => 'Most Comments',
				'social'    => 'Highest Social Score',
				'post_date' => 'Post Publish Date'
			)
		),
		array(
			'id'    => 'default_date_range_months',
			'title' => 'Report Date Range',
			'desc'  => 'Reports should display posts published within this date range.',
			'type'  => 'select',
			'std'   => '0',
			'choices' => array(
				'1'  => '1 Month',
				'3'  => '3 Months',
				'6'  => '6 Months',
				'12' => '12 Months',
				'0'  => 'All Time'
			)
		),
		array(
			'id'    => 'default_posts_per_page',
			'title' => 'Report Posts per Page',
			'desc'  => 'Number of posts per page to display in reports',
			'type'  => 'select',
			'std'   => '10',
			'choices' => array(
				'10'  => '10',
				'20'  => '20',
				'30'  => '30',
				'40'  => '40',
				'50'  => '50',
				'100' => '100'
			)
		)
	)
);

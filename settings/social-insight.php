<?php

global $wpsf_settings;
$wpsf_settings = array();

$wpsf_settings[] = array(
    'section_id' => 'options',
    'section_title' => 'General Options',
    'section_description' => 'Configuration for the operation of the plugin and display of data.',
    'section_order' => 10,
    'fields' => array(
        array(
            'id' => 'report_visibility',
            'title' => 'Basic Report Visibility',
            'desc' => 'The Social Insight data reports will be visible to users who have this capability.',
            'type' => 'select',
            'std' => 'publish_posts',
            'choices' => array(
                'manage_network' => 'Super Admins (Users who can manage the network)',
                'manage_options' => 'Admins (Users who can manage options)',
                'edit_others_posts' => 'Editors (Users who can edit others posts)',
                'publish_posts' => 'Authors (Users who can publish posts)',
                'edit_posts' => 'Contributors (Users who can edit their own posts)',
                'read' => 'Subscribers (Users who can read)'
            )
        ),
        array(
            'id' => 'advanced_report_visibility',
            'title' => 'Advanced Report Visibility',
            'desc' => 'The advanced analysis reports will be visible to users who have this capability.',
            'type' => 'select',
            'std' => 'update_core',
            'choices' => array(
                'manage_network' => 'Super Admins (Users who can manage the network)',
                'manage_options' => 'Admins (Users who can manage options)',
                'edit_others_posts' => 'Editors (Users who can edit others posts)',
                'publish_posts' => 'Authors (Users who can publish posts)',
                'edit_posts' => 'Contributors (Users who can edit their own posts)',
                'read' => 'Subscribers (Users who can read)'
            )
        ),
        array(
            'id' => 'enable_social',
            'title' => 'Track Social',
            'desc' => 'Keep social data in sync.',
            'type' => 'checkbox',
            'std' => 1
        ),
        array(
            'id' => 'enable_analytics',
            'title' => 'Track Views',
            'desc' => 'Keep Google Analytics page views in sync. (Requires Google API account settings)',
            'type' => 'checkbox',
            'std' => 1
        ),
        array(
            'id' => 'enable_comments',
            'title' => 'Track Comments',
            'desc' => 'Display the number of comments on each item.',
            'type' => 'checkbox',
            'std' => 1
        ),
        array(
            'id' => 'ttl_hours',
            'title' => 'Data TTL',
            'desc' => 'Length of time to store the statistics locally before downloading new data. A lower value will use more server resources. High values are recommended for blogs with over 500 posts. This will affect your quota for Google APIs.',
            'type' => 'select',
            'std' => '12',
            'choices' => array(
                '1' => '1 Hour',
                '2' => '2 Hours',
                '4' => '4 Hours',
                '6' => '6 Hours',
                '8' => '8 Hours',
                '12' => '12 Hours',
                '24' => '24 Hours',
                '36' => '36 Hours',
                '48' => '2 Days',
                '72' => '3 Days',
                '168' => '1 Week',
            )
        ),
        array(
            'id' => 'default_sort_column',
            'title' => 'Default Sort Order',
            'desc' => 'Which column should be sorted by default?',
            'type' => 'select',
            'std' => 'aggregate',
            'choices' => array(
                'aggregate' => 'Overall Aggregate Score',
                'views' => 'Most Views',
                'comments' => 'Most Comments',
                'social' => 'Highest Social Score',
                'post_date' => 'Post Publish Date'
            )
        ),
        array(
            'id' => 'default_date_range_months',
            'title' => 'Default Date Range',
            'desc' => 'Reports should display posts published within this date range. ',
            'type' => 'select',
            'std' => '6',
            'choices' => array(
                '1' => '1 Month',
                '3' => '3 Months',
                '6' => '6 Months',
                '12' => '12 Months',
                '0' => 'All Time'
            )
        )
    )
);

$wpsf_settings[] = array(
    'section_id' => 'inside',
    'section_title' => 'Inside.Chapman.edu Link',
    'section_description' => 'Synchronize posts and meta data to Inside.Chapman.edu for publication.',
    'section_order' => 15,
    'fields' => array(
        array(
            'id' => 'push_enabled',
            'title' => 'Enable Sync',
            'desc' => 'Send posts to Inside.Chapman.edu',
            'type' => 'checkbox',
            'std' => ''
        ),
        // array(
        //     'id' => 'remote_url',
        //     'title' => 'POST URL',
        //     'desc' => 'URL to send notifications to. ',
        //     'type' => 'text',
        //     'std' => ''
        // ),
        array(
            'id' => 'debug',
            'title' => 'Email Notifications',
            'desc' => 'Used for debugging. Recipient is hard-coded. ',
            'type' => 'checkboxes',
            'std' => array(),
            'choices' => array(
                'send_update_post_emails' => 'Send emails when posts are updated. (less frequent)',
                'send_refresh_stats_emails' => 'Send emails when meta data is synchronized. (frequent)'
            )
        ),
        // array(
        //     'id' => 'emails',
        //     'title' => 'Notification Emails',
        //     'desc' => 'Seperate multiple emails with commas.',
        //     'type' => 'text',
        //     'std' => ''
        // ),
    )
);

$wpsf_settings[] = array(
    'section_id' => 'ga',
    'section_title' => 'Google Analytics API Settings',
    'section_description' => 'Enter your Google Analytics API Developer Info. You can sign up and create an API account here: <a href="https://code.google.com/apis/console/">https://code.google.com/apis/console/</a> IMPORTANT NOTE: On a Wordpress multi-site network the Google Analytics account authorization info will persist across all sites! Different profiles can be selected per site but the same login must be used.',
    'section_order' => 20,
    'fields' => array(
        array(
            'id' => 'client_id',
            'title' => 'Google API Client ID',
            'desc' => 'Obtained from Google after creating a developer account.',
            'type' => 'text',
            'std' => ''
        ),
        array(
            'id' => 'client_secret',
            'title' => 'Google API Client Secret',
            'desc' => 'Obtained from Google after creating a developer account.',
            'type' => 'text',
            'std' => ''
        ),
        array(
            'id' => 'developer_key',
            'title' => 'Google API Developer Key',
            'desc' => 'Obtained from Google after creating a developer account.',
            'type' => 'text',
            'std' => ''
        ),
        array(
            'id' => 'client_id',
            'title' => 'Google API Client ID',
            'desc' => 'Obtained from Google after creating a developer account.',
            'type' => 'text',
            'std' => ''
        ),
    )
);

/*
// General Settings section
$wpsf_settings[] = array(
    'section_id' => 'general',
    'section_title' => 'General Settings',
    'section_description' => 'Some intro description about this section.',
    'section_order' => 5,
    'fields' => array(
        array(
            'id' => 'text',
            'title' => 'Text',
            'desc' => 'This is a description.',
            'type' => 'text',
            'std' => 'This is std'
        ),
        array(
            'id' => 'textarea',
            'title' => 'Textarea',
            'desc' => 'This is a description.',
            'type' => 'textarea',
            'std' => 'This is std'
        ),
        array(
            'id' => 'select',
            'title' => 'Select',
            'desc' => 'This is a description.',
            'type' => 'select',
            'std' => 'green',
            'choices' => array(
                'red' => 'Red',
                'green' => 'Green',
                'blue' => 'Blue'
            )
        ),
        array(
            'id' => 'radio',
            'title' => 'Radio',
            'desc' => 'This is a description.',
            'type' => 'radio',
            'std' => 'green',
            'choices' => array(
                'red' => 'Red',
                'green' => 'Green',
                'blue' => 'Blue'
            )
        ),
        array(
            'id' => 'checkbox',
            'title' => 'Checkbox',
            'desc' => 'This is a description.',
            'type' => 'checkbox',
            'std' => 1
        ),
        array(
            'id' => 'checkboxes',
            'title' => 'Checkboxes',
            'desc' => 'This is a description.',
            'type' => 'checkboxes',
            'std' => array(
                'red',
                'blue'
            ),
            'choices' => array(
                'red' => 'Red',
                'green' => 'Green',
                'blue' => 'Blue'
            )
        ),
        array(
            'id' => 'color',
            'title' => 'Color',
            'desc' => 'This is a description.',
            'type' => 'color',
            'std' => '#ffffff'
        ),
        array(
            'id' => 'file',
            'title' => 'File',
            'desc' => 'This is a description.',
            'type' => 'file',
            'std' => ''
        ),
        array(
            'id' => 'editor',
            'title' => 'Editor',
            'desc' => 'This is a description.',
            'type' => 'editor',
            'std' => ''
        )
    )
);

// More Settings section
$wpsf_settings[] = array(
    'section_id' => 'more',
    'section_title' => 'More Settings',
    'section_order' => 10,
    'fields' => array(
        array(
            'id' => 'more-text',
            'title' => 'More Text',
            'desc' => 'This is a description.',
            'type' => 'text',
            'std' => 'This is std'
        ),
    )
);

*/

?>
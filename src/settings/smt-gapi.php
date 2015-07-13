<?php

global $wpsf_settings;

$wpsf_settings = array();

$wpsf_settings[] = array(
    'section_id' => 'config',
    'section_title' => 'Google Analytics API Settings',
    'section_description' => 'Enter your Google Analytics API Developer Info. ',
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
            'id' => 'developer_key',
            'title' => 'Google API Developer Key / "Email Address"',
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
        )
    )
);

if (is_multisite() && current_user_can('manage_network')) {
    $wpsf_settings[0]['fields'][] = array(
        'id' => 'network',
        'title' => 'WP Network Wide',
        'desc' => 'Use these credentials for every site in the WordPress multisite environment. This requires that your entire WordPress network be linked to exactly one Google Analytics profile. If you have a diffrent profile for each blog, you should leave this unchecked and repeat setup for each blog. ',
        'type' => 'checkbox',
        'std' => ''
    );
}
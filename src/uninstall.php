<?php

//if uninstall not called from WordPress exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

/***************************************************
* This un-install script will delete all data associated with this plugin.
* This script runs automatically when using the built in plugin un-install function.
*
* WARNING: If you want to keep your data and plan to re-install this plugin, do not un-install the plugin from the WordPress dashboard! Instead delete the files and re-add them and WordPress will bypass running this un-installation script.
***************************************************/

// Delete options
delete_option('smt_settings');
delete_option('smt_version');
delete_option('smt_gapi_data');
delete_option('smt_last_full_sync');

// Remove post meta fields
global $wpdb;

$wpdb->show_errors();

// Google Analytics Views
$wpdb->query( "DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key = 'ga_pageviews'" );

// Social Metrics
$wpdb->query( "DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key = 'socialcount_TOTAL'" );
$wpdb->query( "DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key = 'socialcount_LAST_UPDATED'" );
$wpdb->query( "DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key = 'socialcount_alt_data_LAST_UPDATED'" );

$wpdb->query( "DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key = 'socialcount_facebook'" );
$wpdb->query( "DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key = 'socialcount_twitter'" );
$wpdb->query( "DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key = 'socialcount_googleplus'" );
$wpdb->query( "DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key = 'socialcount_linkedin'" );
$wpdb->query( "DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key = 'socialcount_pinterest'" );
$wpdb->query( "DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key = 'socialcount_diggs'" );
$wpdb->query( "DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key = 'socialcount_delicious'" );
$wpdb->query( "DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key = 'socialcount_reddit'" );
$wpdb->query( "DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key = 'socialcount_stumbleupon'" );

$wpdb->query( "DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key = 'facebook_shares'" );
$wpdb->query( "DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key = 'facebook_comments'" );
$wpdb->query( "DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key = 'facebook_likes'" );

// Compound score numbers
$wpdb->query( "DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key = 'social_aggregate_score'" );
$wpdb->query( "DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key = 'social_aggregate_score_detail'" );
$wpdb->query( "DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key = 'social_aggregate_score_decayed'" );
$wpdb->query( "DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key = 'social_aggregate_score_decayed_last_updated'" );

// Social Metrics alternate source URLs
$wpdb->query( "DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key = 'socialcount_url_data'" );

// Remove all scheduled cron tasks
include_once('MetricsUpdater.class.php');
MetricsUpdater::removeAllQueuedUpdates();

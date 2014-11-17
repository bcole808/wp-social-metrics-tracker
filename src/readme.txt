=== Social Metrics Tracker ===
Contributors: bcole808
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=K2Z4QFWKJ5DM4
Tags: admin, dashboard, social, social media, facebook, twitter, metrics, analytics, tracking, engagement, share, sharing, shares
Requires at least: 3.5
Tested up to: 4.0
Stable tag: 1.3.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Collects social data and shows you which of your posts are most popular based on how many times each post has been shared on various social networks.


== Description ==

See which of your posts are most popular on social media!

This WordPress plugin collects and displays an analysis of social media interactions and view counts of posts. A new dashboard panel is created called "Social Metrics" which shows how many times each post has been shared on various social networks.

= Get stats from these social networks: =

Facebook, Twitter, LinkedIn, StumbleUpon, Pinterest, and Google+

= Focus your writing topics: =

Understand what posts your readers are sharing on social networks so that you can write more of what works well.

= Do more with the data: =

Export collected data to a spreadsheet for analysis.

For web developers, this plugin collects and stores social metrics data in a way that can be accessed by other WP plugins or themes. For example, the social metrics could be used to display a feed of the most popular posts. Metrics are stored as custom post meta fields on each individual post. This is an advanced feature which requires writing custom code.


== Installation ==

1. Install and activate the WordPress plugin.
2. Data will automatically begin syncing. This will take some time.
3. Review the plugin settings under Settings > Social Metrics

If you do not see any statistics on the Social Metrics dashboard, make sure that you have some posts published and that wp-cron.php is working correctly. This plugin relies on the WordPress Cron system to fetch data updates. This plugin will not work on local or development servers where URLs are not publicly accessible.

= Un-installation =

When you un-install this plugin through the WordPress dashboard, it will remove all traces of any social data collected by the plugin. If you wish to keep the data, manually delete the plugin files instead of using the WordPress dashboard; this will bypass the uninstall.php script which deletes the data.


== Frequently Asked Questions ==

= Where is social network data gathered from? =

The information is retrieved directly from the public APIs that each social network provides. This plugin will make requests to these APIs periodically in order to display the latest possible data.

= What social networks are measured? =

Data is collected from the following social networks: Facebook, Twitter, LinkedIn, StumbleUpon, Pinterest, and Google+

= What information is sent from my blog to other services? =

Only a permalink to each of your posts is sent to other web services. The permalink to the post is the only piece of information required to retrieve the number of shares for that link.

= When is the data updated? =

When activating the plugin, all posts are queued for an update; this takes some time to complete. After that, the data is updated every few hours using the WordPress Cron system. When a post is visited, if no update has happened recently then that post is placed in queue for an update. When the WordPress Cron runs, all posts in the queue will be updated.  You can configure the TTL (the amount of time to wait between updates) on the options page for the plugin. This method of updating ensures that site visitors do not experience any additional load time due to these data updates.

= Will this work with WordPress Multisite? =

Absolutely! This plugin was created with large-scale Multisite blog networks in mind.

= What about pageviews? =

You can link with your Google Analytics account to import pageview data for posts. This requires a free Google API Developer Key.

= Who created this? =

This plugin was created by Ben Cole, as a member of the Chapman University web marketing team. Our use for the plugin is to track posts on social networks to see which stories students, alumni, and faculty are most interested in sharing.


== Screenshots ==

1. The Social Metrics Tracker report view.
2. Configuration options


== Changelog ==

= 1.3.2 =
* Fixed an issue where some servers were not correctly connecting to Facebook and LinkedIn

= 1.3.1 =
* Added a status indicator to show if data is being collected successfully.
* Dashboard widget shows social network names instead of the word "other"
* "Schedule Full Sync" should no longer cause memory errors on large blogs.
* Fixed a bug where Google Analytics could not be configured.

= 1.3.0 =
* Data is now synced directly from social network APIs instead of relying on the sharedcount.com API
* Removed Digg.com, and Delicious.com because they no longer provide data.
* Removed Reddit.com because it was not previously working, but will re-add in a future version.
* Added uninstall.php to delete all traces of this plugin if un-installed through WordPress.
* Fixed plugin activation error on blogs with a large number of posts.
* IMPORANT: As of January 1, 2015, versions of this plugin below 1.3 will no longer work. You MUST upgrade to version 1.3 or higher before this date.

= 1.2.5 =
* Fixed a bug where social scores were not being updated.

= 1.2.4 =
* Important bug fix for ranking of posts.

= 1.2.3 =
* Fixed bar width when displaying a widget on the admin Dashboard.

= 1.2.2 =
* Plugin activation hotfix

= 1.2.1 =
* Update Google Analytics setup wizard steps
* Fix various bugs

= 1.2.0 =
* Added data export tool
* Fix various bugs

= 1.1.1 =
* Fix division by zero error message in WordPress 4.0

= 1.1.0 =
* Moved settings panel
* Added support for custom post types
* Added optional Google analytics integration
* Added ability to manually trigger data sync
* Optimized cron mechanism for keeping data in sync

= 1.0.2 =
* Compatibility fix for servers with PHP below version 5.3

= 1.0.1 =
* Added colors and labels to the graph for each of the social networks.
* Bar graph expands on hover to show detail of the breakdown.

= 1.0 =
* Plugin created, wohoo!


== Upgrade Notice ==

= 1.3.2 =
Hotfix for specific server configurations

= 1.3.1 =
Added better error messages and debug info

= 1.3 =
Major update which changes the way social data is collected

= 1.2.5 =
Fixed a bug where social scores were not being updated.

= 1.2.4 =
Important bug fix for ranking of posts.

= 1.2.3 =
Squished more bugs!

= 1.2.2 =
Plugin activation hotfix

= 1.2.1 =
Fixed various bugs

= 1.2.0 =
Added data export tool, and fixed bugs.

= 1.1.1 =
Compatibility fix for WordPress 4.0

= 1.1.0 =
Added some new features including custom post types, Google Analytics, and more.

= 1.0.1 =
Compatibility fix for servers with PHP below version 5.3

= 1.0.1 =
Added colors and labels for all nine social networks.


== Developers Guide ==

This plugin stores social metrics in a way that can be accessed by other WP plugins or themes. For example, the social metrics could be used to display a feed of the most popular posts. Metrics are stored as **custom post meta fields** on each individual post.

**Accessing the metrics**

To display the total number of social interactions, get the post meta:

`<?php echo get_post_meta(get_the_ID(), 'socialcount_TOTAL', true); ?>`

Here is a listing of all of the available data fields which you can access in that way:

socialcount_TOTAL, socialcount_facebook, socialcount_twitter, socialcount_googleplus, socialcount_linkedin, socialcount_pinterest, socialcount_stumbleupon, socialcount_LAST_UPDATED

**Extending the plugin**

There are some WordPress action hooks which can be used to extend the functionality of this plugin.

**social_metrics_post_sync** is called when an individual post is being updated, before new data is downloaded.
**social_metrics_post_sync_complete** is called when an individual post is done being updated.

**Contributing to the project**
We have a Git repository for the project which you can access here: https://github.com/chapmanu/wp-social-metrics-tracker

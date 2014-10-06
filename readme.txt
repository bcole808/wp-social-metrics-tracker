=== Social Metrics Tracker ===
Contributors: bcole808
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=K2Z4QFWKJ5DM4
Tags: admin, dashboard, social, social media, facebook, twitter, metrics, analytics, tracking, engagement, share, sharing, shares
Requires at least: 3.8.1
Tested up to: 4.0
Stable tag: 1.2.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Collects social data and shows you which of your posts are most popular based on how many times each post has been shared on various social networks.


== Description ==

See which of your posts are most popular on social media!

This WordPress plugin collects and displays an analysis of social media interactions and view counts of posts. A new dashboard panel is created called "Social Metrics" which shows how many times each post has been shared on various social networks.

= Get stats from these social networks: =

Facebook, Twitter, LinkedIn, Digg, Delicious, StumbleUpon, Pinterest, and Google+

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


== Frequently Asked Questions ==

= Where is social network data gathered from? =

Share counts and interactions are downloaded from the http://www.sharedcount.com/ API

= What social networks are measured? =

Data is collected from the following social networks: Facebook, Twitter, Reddit, LinkedIn, Digg, Delicious, StumbleUpon, Pinterest, and Google+

= What information is sent from my blog to other services? =

Only a permalink to each of your posts is sent to other web services. The permalink to the post is the only piece of information required to retrieve the number of shares for that link.

= When is the data updated? =

When activating the plugin, all posts are queued for an update; this takes some time to complete. After that, the data is updated every few hours using the WordPress Cron system. When a post is visited, if no update has happened recently then that post is placed in queue for an update. When the WordPress Cron runs, all posts in the queue will be updated.  You can configure the TTL (the amount of time to wait between updates) on the options page for the plugin. This method of updating ensures that site visitors do not experience any additional load time due to these data updates.

= Will this work with WordPress Multisite? =

Absolutely! This plugin was created with large-scale Multisite blog networks in mind. 

= What about pageviews? =

You can link with your Google Analytics account to import pageview data for posts. This requires a free Google API Developer Key. 

= Who created this? =

This plugin was created by the Chapman University web marketing team. Our use for the plugin is to track posts on social networks to see which stories students, alumni, and faculty are most interested in sharing.


== Screenshots ==

1. The Social Metrics Tracker report view.
2. Configuration options


== Changelog ==

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
* Added colors and labels to the graph for each of the nine social networks.
* Bar graph expands on hover to show detail of the breakdown. 

= 1.0 =
* Plugin created, wohoo! 


== Upgrade Notice ==

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

socialcount_TOTAL, socialcount_facebook, socialcount_twitter, socialcount_googleplus, socialcount_linkedin, socialcount_pinterest, socialcount_diggs, socialcount_delicious, socialcount_reddit, socialcount_stumbleupon, socialcount_LAST_UPDATED

**Extending the plugin**

There are some WordPress action hooks which can be used to extend the functionality of this plugin.

**social_metrics_post_sync** is called when an individual post is being updated, before new data is downloaded.
**social_metrics_post_sync_complete** is called when an individual post is done being updated.

**Contributing to the project**
We have a Git repository for the project which you can access here: https://github.com/chapmanu/wp-social-metrics-tracker

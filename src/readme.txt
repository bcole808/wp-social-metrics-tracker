=== Social Metrics Tracker ===
Contributors: bcole808
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=K2Z4QFWKJ5DM4
Tags: admin, dashboard, social, social media, facebook, twitter, metrics, analytics, tracking, stats, engagement, share, sharing, shares, likes, tweets
Requires at least: 3.5
Tested up to: 4.4.2
Stable tag: 1.6.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Collects social data and shows you which posts are most popular based on post shares across popular social networks.


== Description ==

See which of your posts are most popular on social media!

This WordPress plugin collects and displays an analysis of social media interactions and view counts of posts. A new dashboard panel is created called "Social Metrics" which shows how many times each post has been shared on various social networks.

= Get stats from these social networks: =

Facebook, Reddit, LinkedIn, StumbleUpon, Pinterest, Google+, XING, and Flattr

= Focus your writing topics: =

Understand what posts your readers are sharing on social networks so that you can write more of what works well.

= Do more with the data: =

Export collected data to a spreadsheet for analysis.

For web developers, this plugin collects and stores social metrics data in a way that can be accessed by other WP plugins or themes. For example, the social metrics could be used to display a feed of the most popular posts. Metrics are stored as custom post meta fields on each individual post. This is an advanced feature which requires writing custom code.


== Installation ==

1. Install and activate the WordPress plugin.
2. Data will automatically begin syncing. This will take some time.
3. Review the plugin settings under Social Metrics > Settings

If you do not see any statistics on the Social Metrics dashboard, make sure that you have some posts published and that wp-cron.php is working correctly. This plugin relies on the WordPress Cron system to fetch data updates. This plugin will not work on local or development servers where URLs are not publicly accessible.

= Un-installation =

When you un-install this plugin through the WordPress dashboard, it will remove all traces of any social data collected by the plugin. If you wish to keep the data, manually delete the plugin files instead of using the WordPress dashboard; this will bypass the uninstall.php script which deletes the data.


== Frequently Asked Questions ==

= Where is social network data gathered from? =

The information is retrieved directly from the public APIs that each social network provides. This plugin will make requests to these APIs periodically in order to display the latest possible data.

= What social networks are measured? =

Facebook, Reddit, LinkedIn, StumbleUpon, Pinterest, Google+, XING, and Flattr are available for tracking. By default, not all of the data sources are enabled in order to optimize performance out of the box. You can enable or disable tracking of data from any of these social networks by going to the API Connections Settings page in the plugin configuration area. It is recommended to only turn on the networks where your visitors are actively sharing content in order to conserve server resources when fetching data.

= Why is Twitter not available? =

In November of 2015, Twitter officially removed their API which provided share counts of URLS on Twitter. At this time, there is no comparable way to retrieve the number of all-time shares of a URL. Please petition with Twitter to create an API for this!

= What information is sent from my blog to other services? =

Only a permalink to each of your posts is sent to other web services. The permalink to the post is the only piece of information required to retrieve the number of shares for that link.

= When is the data updated? =

When activating the plugin, all posts are queued for an update; this takes some time to complete. After that, the data is updated every few hours using the WordPress Cron system. When a post is visited, if no update has happened recently then that post is placed in queue for an update. When the WordPress Cron runs, all posts in the queue will be updated.  You can configure the TTL (the amount of time to wait between updates) on the options page for the plugin. This method of updating ensures that site visitors do not experience any additional load time due to these data updates.

= Will this work with WordPress Multisite? =

Absolutely! This plugin was created with large-scale Multisite blog networks in mind.

= What about pageviews? =

You can link with your Google Analytics account to import pageview data for posts. This requires a free Google API Developer Key. Note: some users have been having trouble getting Google Analytics set up correctly. This section of the plugin needs to be updated in the future to be more stable. 

= What about canonical URLs? =

Ah yes, sometimes you have more than one URL to a post. For example, with or without www or with http:// or https://.  There is a tool on the configuration page of the plugin to help you configure the checking of canonical URLs. When there are multiple URLs associated with a post, there will be a new link that appears by each post called "URL Details" on the reporting dashboard which will provide detailed stats. 

= Why are share counts are different than what they should be? =

The main thing that can cause differences in share counts is different URL variants for the same post. For example, http:// or https:// will be a different URL, or the presence or absence of a trailing slash will be a different URL as well. Sometimes social networks are smart and combine counts of canonical URLs, and sometimes they do not combine them.

A good tool to figure out the "real" share count is www.SharedCount.com where you can enter a URL and the tool will tell you what the social network APIs are reporting. Try a couple of possible URL variants with that tool and see if you can figure out if maybe there is more than one version of a post URL that has shares. 

It has also been reported that sometimes social networks will suddenly reduce or reset the number of shares for a given URL. I have not been able to figure out why, but please get in touch if you have any idea why this is happening. 

= What if I migrate to a new domain? =

Most Social networks will NOT copy your old share numbers over to your new URLs. However, this plugin has a tool to continue to check and combine the numbers from your old domain name ULRs. 

= Who created this? =

This plugin was created by Ben Cole, as a member of the Chapman University web marketing team. Our use for the plugin is to track posts on social networks to see which stories students, alumni, and faculty are most interested in sharing.


== Screenshots ==

1. The Social Metrics Tracker report view.
2. The report view, with both Google Analytics and multiple URL configuration
3. Configuration options - General setup
4. Configuration options - Google Analytics setup
5. Configuration options - Advanced URL / Domain setup
6. Data exported to a .csv spreadsheet


== Changelog ==

= 1.6.6 =
* Fixed a bug where custom post types were not being listed on the settings page

= 1.6.5 =
* Added option to run updates in the page footer instead of the cron
* Added option to set an update range to disable updating of older posts
* Change wording of some options to make them easier to understand
* Fixed some PHP errors with WP Multisite

= 1.6.4 =
* Minor bug fixes: resolved some PHP errors and warnings

= 1.6.3 =
* Remove Twitter API because it has been officially discontinued by Twitter.

= 1.6.2 =
* Improved error handling for Google Plus API

= 1.6.1 =
* Added colors for XING and Flattr

= 1.6.0 =
* Added API stats for Reddit.com
* Added API stats for XING.com
* Added API stats for Flattr.com
* Allow admin to disable specific social network APIs from being used (Some APIs are now disabled by default to optimize performance out of the box)
* Added network settings page when plugin is network activated
* Changed data export tool to create .csv instead of .xls
* Improve performance of data export tool for large sites

= 1.5.3 =
* Allow attachment/media pages to be tracked
* Added "Update Stats" link to dashboard widget
* Fixed a bug where Google Analytics pageviews were not being updated

= 1.5.2 =
* Fixed an issue where Facebook stats were not collected for some websites in languages other than English
* Fixed an issue where a Facebook count of one was reported as zero

= 1.5.1 =
* Updated the Facebook Graph API to version 2.3 (latest)
* IMPORTANT: As of this version, the individual post meta fields for 'facebook_likes', 'facebook_comments' and 'facebook_shares' are no longer available. You will not notice any difference unless you have previously written a custom theme or plugin which made use of these hidden custom fields. To delete these old fields and clean up your database, you should completely un-install (and delete) this plugin from the Dashboard and then re-install it. 

= 1.5.0 =
* Compatbility with WordPress 4.2
* Fixed a bug where connection debug info sometimes did not get displayed
* Updated the way Facebook data is retrieved; added two options to settings page under "API Connection Settings". 

= 1.4.5 =
* Fixed the Facebook stat updater; temporarily switched back to the old Facebook API (version 1.0) because the new version now requires authentication.  The old version will stop working on April 30, 2015 and so another update will be required before that date. 
* Improved the error reporting debug info for connection failures
* Dashboard widget now correctly displays custom post type data

= 1.4.4 =
* Fixed a PHP warning caused by the plugin

= 1.4.3 = 
* Fixed a bug where saving the general settings page would overwrite domain migration settings
* Fixed a bug where the dashboard widget would display regardless of if it was enabled
* Load dashboard assets over SSL if enabled

= 1.4.2 =
* Fixed a bug where domain migration settings were being erased

= 1.4.1 =
* Removed some old debug code

= 1.4.0 =
* Added advanced Domain/URL setup options including:
* -- The option to check either the http:// or https:// version of post URLs for share data.
* -- A domain migration tool to keep checking for share data from old URLs/domains
* Fixed a bug which was causing "Settings saved" to be displayed more than once.
* Removed the "Debug mode" option which did not do anything useful. 
* Compatibility fix with the plugin "postMash orderby" for sort order

= 1.3.4 =
* Update plugin to use Facebook API v2.0
* Removed "Development server notice" for some configurations
* IMPORTANT: As of April 30, 2015, versions of this plugin below 1.3.4 will fail to collect data from Facebook; update to this version before then.

= 1.3.3 =
* Optimize reporting dashboard load speed
* Improved pagination on reporting dashboard (fixes an issue where only 30 posts were displayed)

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

= 1.6.6 =
Fixed a bug where custom post types were not being listed on the settings page

= 1.6.5 =
Added options to help control how and when updates occur

= 1.6.4 =
Minor bug fixes

= 1.6.3 =
Remove Twitter API because it has been officially discontinued by Twitter.

= 1.6.2 =
Improved error handling for Google Plus API

= 1.6.1 =
Added colors for XING and Flattr

= 1.6.0 =
Added new social networks for tracking

= 1.5.3 =
Allow attachments to be tracked, and misc. updates

= 1.5.2 =
Fixed a bug with Facebook stats on non-English websites

= 1.5.1 =
Switch to Facebook API version 2.3

= 1.5.0 =
Changed the way Facebook data is collected, and compatibility fixes. 

= 1.4.5 =
Temporary fix for the Facebook stat updater

= 1.4.4 =
Minor bug fix

= 1.4.3 =
Bug fixes

= 1.4.2 =
Fixed a bug where domain migration settings were being erased

= 1.4.1 =
Removed old debug code

= 1.4.0 =
Added advanced domain / SSL configuration options

= 1.3.4 =
Important update to the Facebook API

= 1.3.3 =
Optimize pagination on reporting dashboard

= 1.3.2 =
Hotfix for specific server configurations

= 1.3.1 =
Added better error messages and debug info

= 1.3.0 =
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

socialcount_TOTAL, socialcount_facebook, socialcount_googleplus, socialcount_linkedin, socialcount_pinterest, socialcount_stumbleupon, socialcount_LAST_UPDATED

**Extending the plugin**

There are some WordPress action hooks which can be used to extend the functionality of this plugin.

**social_metrics_post_sync** is called when an individual post is being updated, before new data is downloaded.
**social_metrics_post_sync_complete** is called when an individual post is done being updated.

**Contributing to the project**
We have a Git repository for the project which you can access here: https://github.com/chapmanu/wp-social-metrics-tracker

# About

This Wordpress plugin collects and display an analysis of social media interactions and view counts of posts. This project is ready for testing. Code is still being cleaned up and optimized, but please try out the plugin and report any issues you find. 

# Setup

1. Install and activate the wordpress plugin.
2. Data will automaticall begin syncing. This will take some time. 
3. Review the plugin settings under *Settings > Social Insight*

 
# Developers Guide

This plugin stores social metrics in a way that can be accessed by other WP plugins or themes. For example, the social metrics could be used to display a feed of the most popular posts. Metrics are stored as **custom post meta fields** on each individual post. 

### Accessing the metrics

To display the total number of social interactions, get the post meta:

~~~php
<?php echo get_post_meta(get_the_ID(), 'socialcount_TOTAL', true); ?>
~~~

Here is a listing of all of the available data fields which you can access in that way:

Service  | Meta Field Name
------------- | -------------
Total | socialcount_TOTAL
Facebook | socialcount_facebook
Twitter | socialcount_twitter
Google Plus | socialcount_googleplus
LinkedIn | socialcount_linkedin
Pinterest | socialcount_pinterest
Digg | socialcount_diggs
Delicious | socialcount_delicious
Reddit | socialcount_reddit
StumbleUpon | socialcount_stumbleupon
Last Updated Timestamp | socialcount_LAST_UPDATED

### Extending the plugin

There are some Wordpress action hooks which can be used to extend the functionality of this plugin. 

**social_metrics_post_sync** is called when an individual post is being updated, before new data is downloaded.
**social_metrics_post_sync_complete** is called when an individual post is done being updated. 


# FAQ

### Q: Where is social network data gathered from?

A: Share counts and interactions are gathered from the http://www.sharedcount.com/ API

### Q: What social networks are measured?

A: SharedCount.com checks the following social networks: Facebook, Twitter, Reddit, LinkedIn, Digg, Delicious, StumbleUpon, Pinterest, and Google+

### Q: When is the data updated?

A: When activating the plugin, all posts are queued for an update; this takes some time to complete. After that, the data is updated every few hours using the Wordpress Cron system. When a post is visited, if no update has happened recently then that post is placed in queue for an update. When the Wordpress Cron runs, all posts in the queue will be updated.  You can configure the TTL (the amount of time to wait between updates) on the options page for the plugin. This method of updating ensures that site visitors do not experience any additional load time due to these data updates. 

### Q: What about page views?

A: Page views will be added in a future iteration of the plugin. 

### Q: Who created this?

A: This plugin was created by Ben Cole for the Chapman University web marketing team. The purpose was to track posts on social networks to see which stories students, alumni, and faculty were most inerested in sharing. 

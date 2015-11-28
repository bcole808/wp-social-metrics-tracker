[![Build Status](https://travis-ci.org/chapmanu/wp-social-metrics-tracker.svg)](https://travis-ci.org/chapmanu/wp-social-metrics-tracker)

[ ![Codeship Status for chapmanu/wp-social-metrics-tracker](https://codeship.com/projects/3c6afdd0-ede0-0132-88d7-5a51cb58650a/status?branch=development)](https://codeship.com/projects/84147)

# Social Metrics Tracker

![screenshot](http://i.imgur.com/JdOEBV7.png)

**Social Metrics Tracker** is a Wordpress plugin for viewing and analyzing the social performance of your site. Out of the box, the plugin tracks social interactions for all of your posts and pages from a handful of popular networks, including Facebook, GooglePlus, Pinterest, LinkedIn, and StumbleUpon by collecting data directly from social network APIs. The plugin is able to track data from multiple URLs, including differing protocols, subdomains, or other alternate post URLs.

# Quick Start

1. Download the [latest release](https://github.com/chapmanu/wp-social-metrics-tracker/releases/latest).
2. Upload the folder `src` to your wp-plugins directory and rename it to `social-metrics-tracker`
3. From your [Dashboard Screen](http://codex.wordpress.org/Dashboard_Screen), add and activate the plugin.

![uploading the plugin](http://i.imgur.com/kWl0iIq.png)

That’s it.

![using the plugin](http://i.imgur.com/qey5upD.png)

 ... profit!

# Advanced Integration Guide

![Good news, everyone!](http://3.bp.blogspot.com/_J2l4ETMVCDo/TQEuvsblAFI/AAAAAAAAA3A/Olb2qTHKEZ8/s400/11111111.jpg)

**Social Metrics Tracker** stores social metrics in a way that can be accessed by other WP plugins or themes. For example, the social metrics could be used to display a feed of the most popular posts. Metrics are stored as **custom post meta fields** on each individual post.

To display the total number of social interactions, get the post meta:

```php
<?php echo get_post_meta(get_the_ID(), 'socialcount_TOTAL', true); ?>
```

Here is a listing of all of the available data fields which you can access in that way:

Service  | Meta Field Name
------------- | -------------
Total | socialcount_TOTAL
Facebook | socialcount_facebook
Google Plus | socialcount_googleplus
LinkedIn | socialcount_linkedin
Pinterest | socialcount_pinterest
StumbleUpon | socialcount_stumbleupon
Last Updated Timestamp | socialcount_LAST_UPDATED

### Setting alternate URLs

The plugin is able to associate multiple URLs with a single post (you would think canonical URLs would solve this sort of issue, but unfortunately social networks do not always or consistently obey canonical URL rules).  

To add an additional URL to track, create a custom meta field for each post you want to track. Let's imagine your primary URL for a post is `http://www.mydomain.com/?p=1` and you wanted to track some canonical URLs. Here is how you would add the custom meta fields to each post: 

Meta key  | value
------------- | -------------
socialcount_url_data | https://www.mydomain.com/?p=1
socialcount_url_data | http://mydomain.com/?p=1
socialcount_url_data | https://mydomain.com/?p=1

There is a settings page to configure some of these rules automatically, but you can also manually add alternate URLs to posts with custom meta fields. 

### Action Hooks

There are some Wordpress action hooks which can be used to extend the functionality of this plugin.

**social_metrics_post_sync** is called just before an individual post is updated.
**social_metrics_post_sync_complete** is called when an individual post update completes.

# Contributor Guide

### Setup

You don't have to use all of these tools, but they are available to help make the development process easier.  **Ruby / Rake Tasks** are used to make it easy to start a WordPress server to try things on. **Grunt** tasks are used to build/minify CSS/JS. **PHPUnit** and **Selenium** are used for testing. 

1. Fork/clone this repository. 
2. Get your own [MySQL server](https://dev.mysql.com/downloads/mysql/) up and running (MAMP works too). 
2. Create **config.yml** from the file **config.defaults.yml** and add your own development settings.
3. Install [PHPUnit](https://phpunit.de/manual/current/en/installation.html)
2. Run `npm install` (Requires [Node.js](https://nodejs.org/)) to get Grunt dependencies
3. Run `bundle install` (Requires [Ruby](https://www.ruby-lang.org)) to get dev tool dependencies 
4. Run `rake setup` to create dev environment

Ready to roll! Now you can use `rake serve` to start a local dev server.

### Building files

The SASS and JS files can be built and minified by running `grunt` (which also runs PHPUnit once).  You can also use `grunt watch` to continually build files and host a livereload server. 

### Unit Tests

To run tests locally, follow all of the setup steps and then run `rake test`. This will start up a PHP server and a Selenium Server, and then run `phpunit` for you with integration tests enabled. You can also just run `phpunit` to only execute Unit Tests. 

# FAQ

### Q: Where is social network data gathered from?

A: The information is retrieved directly from the public APIs that each social network provides. This plugin will make requests to these APIs periodically in order to display the latest possible data. 

### Q: What social networks are measured?

A: Facebook, LinkedIn, StumbleUpon, Pinterest, and Google+. Twitter used to be available, but Twitter has removed their API.

### Q: When is the data updated?

A: When activating the plugin, all posts are queued for an update; this takes some time to complete. After that, the data is updated every few hours using the Wordpress Cron system. When a post is visited, if no update has happened recently then that post is placed in queue for an update. When the Wordpress Cron runs, all posts in the queue will be updated.  You can configure the TTL (the amount of time to wait between updates) on the options page for the plugin. This method of updating ensures that site visitors do not experience any additional load time due to these data updates.

### Q: Umm, what about page views?

A: You can totally sync your page view data from Google Analytics! Just go and sign up for a Google API developer key and follow the setup wizard hidden deep within the settings panel of this plugin... good luck! 

### Q. Was this made with magic?

A: Yes, we used the tears of a baby unicorn forlock. Also, PHP and PHPUnit.

### Q: A whole University created this? Who did you pay?

A: Please direct your praise and admonishment to [Ben Cole](https://github.com/bcole808), a Chapman University graduate turned staff and web marketing ninja / rockstar / whatever hipster phrase they are throwing around these days.

### Q: Graduate turned staff?

![Ben Cole](http://i.imgur.com/5sjt6KP.png)

### Q: Why was this done?

We wanted to track posts on social networks to see which stories students, alumni, and faculty were most interested in sharing. However, the application is far from limited to higher education. So, we thought, we should share this.

### Q: But why? I mean, really, why?

[42](https://www.google.com/#q=the+answer+to+life+the+universe+and+everything).

## Special Thanks

Cross-browser compatibility testing is provided by the fantastic folks at [Browser Stack](https://www.browserstack.com). 

![Browser Stack](/assets/browser_stack.png?raw=true "Browser Stack")

<?php

class MetricUpdaterTests extends WP_UnitTestCase {

	private $updater;

	// DO BEFORE ALL TESTS
	function setUp() {
		parent::setUp();

		// Create an updater object
		$smt = new SocialMetricsTracker();
		$smt->init();
		$this->updater = new MetricsUpdater($smt);

		// MOCK FACEBOOK
		// =====================
		$this->sample_return = file_get_contents(
			dirname(__FILE__) .'/sample-data/graph.facebook.com.json'
		);

		$this->updater->sources->FacebookUpdater = $this->getMock('FacebookGraphUpdater', array('getURL'));

		$this->updater->sources->FacebookUpdater->expects($this->any())
		    ->method('getURL')
		    ->will($this->returnValue($this->sample_return));

		// MOCK TWITTER
		// =====================
		$this->sample_return = file_get_contents(
			dirname(__FILE__) .'/sample-data/urls.api.twitter.com.json'
		);

		$this->updater->sources->TwitterUpdater = $this->getMock('TwitterUpdater', array('getURL'));

		$this->updater->sources->TwitterUpdater->expects($this->any())
		    ->method('getURL')
		    ->will($this->returnValue($this->sample_return));

		// MOCK LINKEDIN
		// =====================
		$this->sample_return = file_get_contents(
			dirname(__FILE__) .'/sample-data/linkedin.com.json'
		);

		$this->updater->sources->LinkedInUpdater = $this->getMock('LinkedInUpdater', array('getURL'));

		$this->updater->sources->LinkedInUpdater->expects($this->any())
		    ->method('getURL')
		    ->will($this->returnValue($this->sample_return));

		$this->updater->dataSourcesReady = true;
	}

	// DO AFTER ALL TESTS
	function tearDown() {
		parent::tearDown();
	}

	function assert_correct_data($post_id, $skip_facebook = false) {
		// Facebook
		if (!$skip_facebook) {
			$this->assertEquals(8450, get_post_meta($post_id, 'socialcount_facebook', true));
			$this->assertEquals(331,  get_post_meta($post_id, 'facebook_comments', true));
			$this->assertEquals(7169, get_post_meta($post_id, 'facebook_shares', true));
			$this->assertEquals(950,  get_post_meta($post_id, 'facebook_likes', true));
		}

		// Twitter
		$this->assertEquals(6,    get_post_meta($post_id, 'socialcount_twitter', true));

		// LinkedIn
		$this->assertEquals(1207, get_post_meta($post_id, 'socialcount_linkedin', true));

		// Totals
		if ($skip_facebook) {
			$this->assertEquals(1213, get_post_meta($post_id, 'socialcount_TOTAL', true));
		} else {
			$this->assertEquals(9663, get_post_meta($post_id, 'socialcount_TOTAL', true));
		}

		// Timestamp / meta
		$this->assertTrue(get_post_meta($post_id, 'socialcount_LAST_UPDATED', true) >= time()-5);

		// Aggregate data
		$this->assertTrue(is_array(get_post_meta($post_id, 'social_aggregate_score_detail', true)));
		$this->assertTrue(get_post_meta($post_id, 'social_aggregate_score_decayed_last_updated', true) >= time()-5);

		if ($skip_facebook) {
			$this->assertEquals(1213, get_post_meta($post_id, 'social_aggregate_score', true));
			$this->assertTrue(get_post_meta($post_id, 'social_aggregate_score_decayed', true) >= 2420); // Estimate
		} else {
			$this->assertEquals(9663, get_post_meta($post_id, 'social_aggregate_score', true));
			$this->assertTrue(get_post_meta($post_id, 'social_aggregate_score_decayed', true) >= 19320); // Estimate
		}
	}


	function test_updatePostStats() {

		// 1. It should work correctly
		$post_id = $this->factory->post->create();
		$this->updater->updatePostStats($post_id);
		$this->assert_correct_data($post_id);

		// 2. It should accept a string as an input
		$post_id_2 = $this->factory->post->create();
		$this->updater->updatePostStats("$post_id_2");
		$this->assert_correct_data($post_id_2);


		// --------------------------------------------------
		// TESTS FOR WHEN A SERVICE (FACEBOOK) IS UNAVAILABLE

		$this->updater->sources->FacebookUpdater = $this->getMock('FacebookGraphUpdater', array('getURL'));
		$this->updater->sources->FacebookUpdater->expects($this->any())
		    ->method('getURL')
		    ->will($this->returnValue(false));

		// 3. If a service is offline, the previously saved value should be retained and not set to zero
		$this->updater->updatePostStats($post_id);
		$this->assert_correct_data($post_id);

		// 4. If a service is offline, the other services should still work
		$post_id_3 = $this->factory->post->create();
		$this->updater->updatePostStats($post_id_3);
		$this->assert_correct_data($post_id_3, true);

	}


	// Test score calculations
	function test_calculateScoreAggregate() {

		// 1: It returns an array
		$result = $this->updater->calculateScoreAggregate();
		$this->assertTrue( is_array($result), 'It should return an array.');

		// 2: Zeros should equal zero
		$result = $this->updater->calculateScoreAggregate(0,0,0);

		$expected = array(
			'total' 			=> 0.0,
			'social_points'		=> 0,
			'view_points'		=> 0.0,
			'comment_points'	=> 0
		);

		$this->assertSame( $expected, $result, 'Zeros in should create zeros out. ');

		// 3: It rejects bad input
		$result = $this->updater->calculateScoreAggregate('foo', 'bar');
		$this->assertFalse($result, 'It should reject bad input');

		// 4: It combines some numbers
		$result = $this->updater->calculateScoreAggregate(10,10,10);
		$this->assertGreaterThan(0, $result['total'], 'Should return a positive number');

	}

	// Test score calculations
	function test_calculateScoreDecay() {
		// 1: It rejects bad input
		$result = $this->updater->calculateScoreDecay('foo', 123, 'bar');
		$this->assertFalse($result, 'It should reject bad input');

		$result = $this->updater->calculateScoreDecay(-6, null);
		$this->assertFalse($result, 'It should reject bad input');

		$result = $this->updater->calculateScoreDecay(array(), 'Monkey');
		$this->assertFalse($result, 'It should reject bad input');

		// 2: It returns a float
		$result = $this->updater->calculateScoreDecay(10, 'January 1, 2014');
		$this->assertTrue(is_float($result), 'It should return a float. ');

		// 3: The return is smaller than the input
		$result = $this->updater->calculateScoreDecay(10, 'January 1, 2014');
		$this->assertTrue(($result < 10), 'It should return a smaller value. ');

		// 3: The return is larger than the input
		$result = $this->updater->calculateScoreDecay(10, date('r'));
		$this->assertTrue(($result > 10), 'It should return a larger value. ');
	}

	function test_checkThisPost_validates() {

		// 1: It rejects bad input
		$result = $this->updater->checkThisPost(0);
		$this->assertFalse($result, 'It should reject invalid post IDs');

		$result = $this->updater->checkThisPost(-6);
		$this->assertFalse($result, 'It should reject invalid post IDs');

		$result = $this->updater->checkThisPost(array());
		$this->assertFalse($result, 'It should reject array inputs');

		$result = $this->updater->checkThisPost('foo');
		$this->assertFalse($result, 'It should reject string inputs');

	}

	function test_checkThisPost_posts() {

		// SETUP: Make a post
		$post_id = $this->factory->post->create();

		$this->assertTrue($post_id > 0, 'There was some trouble creating a post.');

		// 1: It does not queue the post if it is not on the correct page
		$this->go_to( "/" );
		$result = $this->updater->checkThisPost($post_id);
		$this->assertFalse($result, 'It was not supposed to queue this post');

		// 2: It does queue if on the correct page
		$this->go_to( "/?p=$post_id" );
		$result = $this->updater->checkThisPost($post_id);
		$this->assertTrue($result, 'It should check a valid post');

		// 3: It does not update a non-published post
		wp_update_post(array('ID' => $post_id, 'post_status' => 'draft'));
		$this->go_to( "/?p=$post_id" );
		$result = $this->updater->checkThisPost($post_id);
		$this->assertFalse($result, 'It should not check draft content');

	}

	function test_checkThisPost_pages() {
		// SETUP: Make a page
		$page_id = $this->factory->post->create(array('post_type' => 'page'));

		$this->assertTrue($page_id > 0, 'There was some trouble creating a page.');

		// 1: It does not queue the post if it is not on the correct page
		$this->go_to( "/wp-admin" );
		$result = $this->updater->checkThisPost($page_id);
		$this->assertFalse($result, 'It was not supposed to queue this page');

		// 2: It does queue if on the correct page
		$this->go_to( "/?page_id=$page_id" );
		$result = $this->updater->checkThisPost($page_id);
		$this->assertTrue($result, 'It should check a valid page');

		// 3: It does not update a non-published post
		wp_update_post(array('ID' => $page_id, 'post_status' => 'private'));
		$this->go_to( "/?page_id=$page_id" );
		$result = $this->updater->checkThisPost($page_id);
		$this->assertFalse($result, 'It should not check private content');
	}

	function test_recalculateAllScores() {

		// SETUP: Make a post
		$post_id = $this->factory->post->create();

		update_post_meta($post_id, 'socialcount_LAST_UPDATED', time());

		// 1: It runs without failing
		$num = $this->updater->recalculateAllScores(false);
		$this->assertEquals(1, $num, 'It did not update the right number of posts.');
	}

	function test_scheduleFullDataSync() {
		// SETUP: Make a post
		$post_id = $this->factory->post->create();
		$second_post_id = $this->factory->post->create();

		// 1: It runs without failing
		$this->assertTrue(($this->updater->scheduleFullDataSync() == 2), 'Function failed to complete successfully.');
		$this->assertGreaterThan(0, wp_next_scheduled('social_metrics_update_single_post', array($post_id, true)), 'It did not actually schedule a cron task!');
		$this->assertGreaterThan(0, wp_next_scheduled('social_metrics_update_single_post', array($second_post_id, true)), 'It did not actually schedule a cron task!');
	}

	function test_removeAllQueuedUpdates() {
		// SETUP: Make a post
		$post_id = $this->factory->post->create();
		$this->updater->scheduleFullDataSync();
		$this->assertGreaterThan(0, wp_next_scheduled('social_metrics_update_single_post', array($post_id, true)), 'Setup for this test failed!');

		// 1: It removes scheduled tasks
		$this->updater->removeAllQueuedUpdates();
		$this->assertEquals(0, wp_next_scheduled('social_metrics_update_single_post', array($post_id, true)), 'It failed to remove items from the cron queue!');
	}

	function test_adjustProtocol() {

		$url_http  = 'http://www.wordpress.org';
		$url_https = 'https://www.wordpress.org';
		$url_no_protocol = '//www.wordpress.org';

		// 1. It should return the input when not configured
		$result = $this->updater->adjustProtocol($url_http);
		$this->assertEquals($url_http, $result);

		// 2. It should force SSL
		$this->updater->smt->set_smt_option('url_protocol', 'https');
		$result = $this->updater->adjustProtocol($url_http);
		$this->assertEquals($url_https, $result);

		// 3. It should force non-SSL
		$this->updater->smt->set_smt_option('url_protocol', 'http');
		$result = $this->updater->adjustProtocol($url_https);
		$this->assertEquals($url_http, $result);

		// 4. It should not mess up weird URLs
		$this->updater->smt->set_smt_option('url_protocol', 'http');
		$result = $this->updater->adjustProtocol('https://www.google.com/?q=https://www.wordpress.org');
		$this->assertEquals('http://www.google.com/?q=https://www.wordpress.org', $result);

		// 5. It should not mess up weird URLs
		$this->updater->smt->set_smt_option('url_protocol', 'http');
		$result = $this->updater->adjustProtocol('http://www.google.com/?q=https://www.wordpress.org');
		$this->assertEquals('http://www.google.com/?q=https://www.wordpress.org', $result);

	}


}


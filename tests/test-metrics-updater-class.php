<?php

class MetricUpdaterTests extends WP_UnitTestCase {

	private $updater;

	// DO BEFORE ALL TESTS
	function setUp() {
		parent::setUp();

		$this->updater = new MetricsUpdater(new SocialMetricsTracker());

		// MOCK FACEBOOK
		// =====================
		$this->sample_return = file_get_contents(
			dirname(__FILE__) .'/sample-data/api.facebook.com.json'
		);

		$this->updater->sources->FacebookUpdater = $this->getMock('FacebookUpdater', array('getURL'));

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

	}

	// DO AFTER ALL TESTS
	function tearDown() {
		parent::tearDown();
	}

	function assert_correct_data($post_id) {
		// Facebook
		$this->assertEquals(get_post_meta($post_id, 'socialcount_facebook', true), 1606);
		$this->assertEquals(get_post_meta($post_id, 'facebook_comments', true), 70);
		$this->assertEquals(get_post_meta($post_id, 'facebook_shares', true), 1431);
		$this->assertEquals(get_post_meta($post_id, 'facebook_likes', true), 105);

		// Twitter
		$this->assertEquals(get_post_meta($post_id, 'socialcount_twitter', true), 6);

		// LinkedIn
		$this->assertEquals(get_post_meta($post_id, 'socialcount_linkedin', true), 1207);

		// Totals
		$this->assertEquals(get_post_meta($post_id, 'socialcount_TOTAL', true), 2819);

		// Timestamp / meta
		$this->assertTrue(get_post_meta($post_id, 'socialcount_LAST_UPDATED', true) >= time()-5);
	}


	function test_updatePostStats() {

		// 1. It should work correctly
		$post_id = $this->factory->post->create();
		$this->updater->updatePostStats($post_id);
		$this->assert_correct_data($post_id);

		// 2. It shoudl accept a string as an input
		$post_id_2 = $this->factory->post->create();
		$this->updater->updatePostStats("$post_id_2");
		$this->assert_correct_data($post_id_2);

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
		$this->assertGreaterThan(0, wp_next_scheduled('social_metrics_update_single_post', array($post_id)), 'It did not actually schedule a cron task!');
		$this->assertGreaterThan(0, wp_next_scheduled('social_metrics_update_single_post', array($second_post_id)), 'It did not actually schedule a cron task!');
	}

	function test_removeAllQueuedUpdates() {
		// SETUP: Make a post
		$post_id = $this->factory->post->create();
		$this->updater->scheduleFullDataSync();
		$this->assertGreaterThan(0, wp_next_scheduled('social_metrics_update_single_post', array($post_id)), 'Setup for this test failed!');

		// 1: It removes scheduled tasks
		$this->updater->removeAllQueuedUpdates();
		$this->assertEquals(0, wp_next_scheduled('social_metrics_update_single_post', array($post_id)), 'It failed to remove items from the cron queue!');
	}


}


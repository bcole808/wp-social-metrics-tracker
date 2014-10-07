<?php

class MetricUpdaterTests extends WP_UnitTestCase {

	private $updater;

	// DO BEFORE ALL TESTS
	function setUp() {
		parent::setUp();


		// SWITCH TO CODE AFTER WRITING TESTS:

		// $this->plugin = new SocialMetricsTracker();
		// $this->plugin->init();
		// $this->updater = new MetircsUpdater($this->plugin);

		// TEMPORARY CODE:

		$this->updater = new MetricsUpdater();
	}

	// DO AFTER ALL TESTS
	function tearDown() {
		parent::tearDown();
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
		$post = array(
			'post_title'    => 'The man in the shoe.',
			'post_content'  => 'There was once a man who lived in a shoe. The end.',
			'post_status'   => 'publish',
			'post_type'     => 'post'
		);
		$post_id = wp_insert_post($post);

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
		$page = array(
			'post_title'    => 'A test page',
			'post_content'  => 'Lorem Ipsum Doler.',
			'post_status'   => 'publish',
			'post_type'     => 'page'
		);
		$page_id = wp_insert_post($page);

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
		$post = array(
			'post_title'    => 'A test page',
			'post_content'  => 'Lorem Ipsum Doler.',
			'post_status'   => 'publish',
			'post_type'     => 'post'
		);
		$post_id = wp_insert_post($post);

		update_post_meta($post_id, 'socialcount_LAST_UPDATED', time());

		// 1: It runs without failing
		$num = $this->updater->recalculateAllScores(false);
		$this->assertEquals(1, $num, 'It did not update the right number of posts.');
	}

	function test_scheduleFullDataSync() {
		// SETUP: Make a post
		$post = array(
			'post_title'    => 'A test page',
			'post_content'  => 'Lorem Ipsum Doler.',
			'post_status'   => 'publish',
			'post_type'     => 'post'
		);
		$post_id = wp_insert_post($post);

		$post = array(
			'post_title'    => 'A test page',
			'post_content'  => 'Lorem Ipsum Doler.',
			'post_status'   => 'publish',
			'post_type'     => 'post'
		);
		$post_id = wp_insert_post($post);
		update_post_meta($post_id, 'socialcount_LAST_UPDATED', time());

		// 1: It runs without failing
		$this->assertTrue($this->updater->scheduleFullDataSync(), 'Function failed to complete successfully.');
		$this->assertGreaterThan(0, wp_next_scheduled('social_metrics_update_single_post', array($post_id)), 'It did not actually schedule a cron task!');
	}

	function test_removeAllQueuedUpdates() {
		// SETUP: Make a post
		$post = array(
			'post_title'    => 'A test page',
			'post_content'  => 'Lorem Ipsum Doler.',
			'post_status'   => 'publish',
			'post_type'     => 'post'
		);
		$post_id = wp_insert_post($post);
		$this->updater->scheduleFullDataSync();
		$this->assertGreaterThan(0, wp_next_scheduled('social_metrics_update_single_post', array($post_id)), 'Setup for this test failed!');

		// 1: It removes scheduled tasks
		$this->updater->removeAllQueuedUpdates();
		$this->assertEquals(0, wp_next_scheduled('social_metrics_update_single_post', array($post_id)), 'It failed to remove items from the cron queue!');
	}


}


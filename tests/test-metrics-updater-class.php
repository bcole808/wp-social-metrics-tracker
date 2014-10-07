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

	function test_validates_checkThisPost() {
		
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

}


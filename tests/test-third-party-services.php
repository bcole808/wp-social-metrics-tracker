<?php

class ThirdPartyTests extends WP_UnitTestCase {

	private $updater;

	// DO BEFORE ALL TESTS
	function setUp() {
		parent::setUp();
	}

	// DO AFTER ALL TESTS
	function tearDown() {
		parent::tearDown();
	}

	function test_sharedCount() {
		$sharedCountUpdater = new SharedCountUpdater();

		$result = $sharedCountUpdater->getData('http://www.wordpress.org');

		$this->assertTrue(
			$result !== false && is_array($result),
			'The third party service SharedCount is not responding! '
		);
	}



}


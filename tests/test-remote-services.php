<?php

/***************************************************
* Checks our 3rd party APIs to make sure they are online and returning expected data structures! This is important for this plugin because without these 3rd party APIs, the plugin is practically useless.
***************************************************/

class TestRemoteServices extends WP_UnitTestCase {

	// DO BEFORE ALL TESTS
	function setUp() {
		parent::setUp();
	}


	// DO AFTER ALL TESTS
	function tearDown() {
		parent::tearDown();
	}


	function test_facebook() {

		$updater = new FacebookUpdater();
		$updater->setParams(1, 'http://www.wordpress.org');

		// 1. Make sure the API responds
		$updater->fetch();
		$this->assertTrue(is_array($updater->data), 'The Facebook API is unavailable!!!');

		// 2. Enforce expected data structure
		$expected_result = json_decode(file_get_contents(
			dirname(__FILE__) .'/sample-data/api.facebook.com.json'
		), true);

		$diff = array_diff_key($expected_result[0], $updater->data[0]);
		$this->assertEquals(0, count($diff), 'The Facebook API has changed!!!');

	}

}


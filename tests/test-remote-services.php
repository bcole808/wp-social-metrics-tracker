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

	function test_twitter() {

		$updater = new TwitterUpdater();
		$updater->setParams(1, 'http://www.wordpress.org');

		// 1. Make sure the API responds
		$updater->fetch();
		$this->assertTrue(is_array($updater->data), 'The Twitter API is unavailable!!!');

		// 2. Enforce expected data structure
		$expected_result = json_decode(file_get_contents(
			dirname(__FILE__) .'/sample-data/urls.api.twitter.com.json'
		), true);

		$diff = array_diff_key($expected_result, $updater->data);
		$this->assertEquals(0, count($diff), 'The Twitter API has changed!!!');

	}

	function test_linkedin() {

		$updater = new LinkedInUpdater();
		$updater->setParams(1, 'http://www.wordpress.org');

		// 1. Make sure the API responds
		$updater->fetch();
		$this->assertTrue(is_array($updater->data), 'The LinkedIn API is unavailable!!!');

		// 2. Enforce expected data structure
		$expected_result = json_decode(file_get_contents(
			dirname(__FILE__) .'/sample-data/linkedin.com.json'
		), true);

		$diff = array_diff_key($expected_result, $updater->data);
		$this->assertEquals(0, count($diff), 'The LinkedIn API has changed!!!');

	}


	function test_googleplus() {

		$updater = new GooglePlusUpdater();
		$updater->setParams(1, 'http://www.wordpress.org');

		// 1. Make sure the API responds
		$updater->fetch();
		$this->assertTrue(is_array($updater->data), 'The GooglePlus API is unavailable!!!');

		// 2. Enforce expected data structure
		$expected_result = json_decode(file_get_contents(
			dirname(__FILE__) .'/sample-data/clients6.google.com.json'
		), true);

		$diff = array_diff_key($expected_result, $updater->data);
		$this->assertEquals(0, count($diff), 'The GooglePlus API has changed!!!');



	}

}


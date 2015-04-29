<?php

class TestHTTPResourceUpdater extends WP_UnitTestCase {

	private $updater;

	// DO BEFORE ALL TESTS
	function setUp() {
		parent::setUp();

	}

	// DO AFTER ALL TESTS
	function tearDown() {
		parent::tearDown();
	}


	// It should remove secrets correctly
	function test_cleanSecrets() {

		// Build an updater
		$updater = $this->getMock('FacebookGraphUpdater', array('getURL'));

		$updater->expects($this->any())
		    ->method('getURL')
		    ->will($this->returnValue(false));


		$updater->setAccessToken('foobarapp|secret');
		$updater->setParams(0, 'http://www.wikipedia.org');

		$string = http_build_query($updater->resource_params, '', '&');

		$this->assertTrue(strpos($string, 'secret') !== false);
		$this->assertFalse(strpos($updater->cleanSecrets($string), 'secret'));

	}

}


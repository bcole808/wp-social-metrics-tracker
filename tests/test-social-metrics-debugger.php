<?php

class TestSocialMetricsDebugger extends WP_UnitTestCase {

	// DO BEFORE ALL TESTS
	function setUp() {
		parent::setUp();

		$this->smt = new SocialMetricsTracker();
		$this->smt->init();

		$this->set_all_mocks_online();

	}

	// DO AFTER ALL TESTS
	function tearDown() {
		parent::tearDown();
	}

	function set_all_mocks_online() {
		// MOCK FACEBOOK
		// =====================
		$this->sample_return = file_get_contents(
			dirname(__FILE__) .'/sample-data/graph.facebook.com.json'
		);

		$this->smt->updater->sources->FacebookGraphUpdater = $this->getMock('FacebookGraphUpdater', array('getURL'));

		$this->smt->updater->sources->FacebookGraphUpdater->expects($this->any())
		    ->method('getURL')
		    ->will($this->returnValue($this->sample_return));

		// MOCK STUMBLEUPON
		// =====================
		$this->sample_return = file_get_contents(
			dirname(__FILE__) .'/sample-data/stumbleupon.com.json'
		);

		$this->smt->updater->sources->StumbleUponUpdater = $this->getMock('StumbleUponUpdater', array('getURL'));

		$this->smt->updater->sources->StumbleUponUpdater->expects($this->any())
		    ->method('getURL')
		    ->will($this->returnValue($this->sample_return));

		// MOCK LINKEDIN
		// =====================
		$this->sample_return = file_get_contents(
			dirname(__FILE__) .'/sample-data/linkedin.com.json'
		);

		$this->smt->updater->sources->LinkedInUpdater = $this->getMock('LinkedInUpdater', array('getURL'));

		$this->smt->updater->sources->LinkedInUpdater->expects($this->any())
		    ->method('getURL')
		    ->will($this->returnValue($this->sample_return));

		// MOCK PINTEREST
		// =====================
		$this->sample_return = file_get_contents(
			dirname(__FILE__) .'/sample-data/api.pinterest.com.json'
		);

		$this->smt->updater->sources->PinterestUpdater = $this->getMock('PinterestUpdater', array('getURL'));

		$this->smt->updater->sources->PinterestUpdater->expects($this->any())
		    ->method('getURL')
		    ->will($this->returnValue($this->sample_return));

		// MOCK STUMBLEUPON
		// =====================
		$this->sample_return = file_get_contents(
			dirname(__FILE__) .'/sample-data/stumbleupon.com.json'
		);

		$this->smt->updater->sources->StumbleUponUpdater = $this->getMock('StumbleUponUpdater', array('getURL'));

		$this->smt->updater->sources->StumbleUponUpdater->expects($this->any())
		    ->method('getURL')
		    ->will($this->returnValue($this->sample_return));

		// MOCK GOOGLE PLUS
		// =====================
		$this->sample_return = file_get_contents(
			dirname(__FILE__) .'/sample-data/clients6.google.com.json'
		);

		$this->smt->updater->sources->GooglePlusUpdater = $this->getMock('GooglePlusUpdater', array('getURL'));

		$this->smt->updater->sources->GooglePlusUpdater->expects($this->any())
		    ->method('getURL')
		    ->will($this->returnValue($this->sample_return));

		$this->smt->updater->dataSourcesReady = true;
	}

	function set_facebook_offline() {
		$this->smt->updater->sources->FacebookGraphUpdater = $this->getMock('FacebookGraphUpdater', array('getURL'));

		$this->smt->updater->sources->FacebookGraphUpdater->expects($this->any())
		    ->method('getURL')
		    ->will($this->returnValue(false));

		// We need to report a failure
		$this->smt->updater->sources->FacebookGraphUpdater->wpcb->reportFailure('Test Error Message');

		$this->smt->updater->dataSourcesReady = true;

	}

	/***************************************************
	* Make sure it can test remote resources
	***************************************************/
	public function test_testHTTPResourceUpdaters() {

		// 1. The test should pass because all mocks are "online"
		$this->assertTrue($this->smt->debugger->testHTTPResourceUpdaters());

		// 2. If we make one resource offline, it should fail
		$this->set_facebook_offline();
		$result = $this->smt->debugger->testHTTPResourceUpdaters();
		$this->assertTrue(is_array($result));
		$this->assertEquals(
			array('FacebookGraphUpdater' => $this->smt->updater->sources->FacebookGraphUpdater),
			$result
		);
	}

	/***************************************************
	* Make sure it can report offline resources correctly
	***************************************************/
	public function test_getOfflineHTTPResourceUpdaters() {

		// 1. It should return an empty array if all online
		$this->assertEquals(array(), $this->smt->debugger->getOfflineHTTPResourceUpdaters());

		// 2. It should return an array of the offline service
		$this->set_facebook_offline();
		$this->assertEquals(
			array('FacebookGraphUpdater' => $this->smt->updater->sources->FacebookGraphUpdater),
			$this->smt->debugger->getOfflineHTTPResourceUpdaters()
		);
	}


}


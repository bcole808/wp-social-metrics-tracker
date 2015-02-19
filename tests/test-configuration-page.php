<?php

class TestConfigurationPage extends WP_UnitTestCase {

	// DO BEFORE ALL TESTS
	function setUp() {
		parent::setUp();
	}


	// DO AFTER ALL TESTS
	function tearDown() {
		parent::tearDown();
	}

	function test_page_loads() {
		$this->socialMetricsSettings =  new socialMetricsSettings(new SocialMetricsTracker());
		$this->go_to( "/wp-admin/admin.php?page=social-metrics-tracker-settings" );
	}


	// 1. Saving domain settings, then reloading the page does not delete domain settings

}


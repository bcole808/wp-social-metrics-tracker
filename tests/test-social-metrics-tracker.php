<?php

class SocialMetricsTrackerTests extends WP_UnitTestCase {

	private $plugin;

	// DO BEFORE ALL TESTS
	function setUp() {
		parent::setUp();
		$this->plugin = new SocialMetricsTracker();
	}

	// DO AFTER ALL TESTS
	function tearDown() {
		parent::tearDown();
	}

	/***************************************************
	* The plugin must call the init function at the right time.
	***************************************************/
	function test_has_init() {
		$this->assertTrue(
			is_int(has_action('init', array($this->plugin, 'init'))),
			'The plugin does not have an init function!'
		);
	}

	/***************************************************
	* Must ensure default options exist always.
	***************************************************/
	function test_options_defaults() {

		$this->plugin->init();

		$this->assertTrue(
			is_array($this->plugin->options),
			'The plugin does not create default options!'
		);
	}

	/***************************************************
	* 1. Tracked post types always returns an array
	* 2. Items returned must be valid, public post types
	***************************************************/
	function test_tracked_post_types() {

		// 1:
		$result = $this->plugin->tracked_post_types();

		$this->assertTrue(
			is_array($result),
			'Must return an array'
		);

		// 2:
		$valid_types = get_post_types( array( 'public' => true ), 'names' );

		$this->assertTrue(
			count(array_intersect($result, $valid_types)) == count($result),
			'Returned an WP post type that did not exist!'
		);
	}


	/***************************************************
	* Make sure upgrade tasks work well
	***************************************************/
	function test_version_check() {

		$ver = $this->plugin->version;
		$this->plugin->version_check();

		// 1. The version should be correct in the DB
		$this->assertEquals($ver, get_option('smt_version'));

		// 2. If I upgrade from below 1.3, it sets a param
		$this->assertFalse(get_option( 'smt_last_full_sync'));
		update_option('smt_version', '1.0');
		$this->plugin->version_check();
		$this->assertEquals(get_option( 'smt_last_full_sync'), 1);

		// 3. If I change the version in the DB, then run version check, it updates
		update_option('smt_version', '1.0');
		$this->plugin->version_check();
		$this->assertEquals($ver, get_option('smt_version'));




	}

}


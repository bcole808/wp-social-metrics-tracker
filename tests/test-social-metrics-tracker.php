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

		// 1: It should set some default options
		$this->plugin->init();
		
		$this->assertTrue(
			is_array($this->plugin->options),
			'The plugin does not create default options!'
		);

		// 2: Make sure the database values match the default file!
		require(dirname(dirname(__FILE__)).'/src/settings/smt-general.php');
		global $wpsf_settings;

		$expected = array();

		foreach ($wpsf_settings[0]['fields'] as $default) {
			$key   = 'smt_options_'.$default['id'];
			$value = $default['std'];

			$expected[$key] = $value;
		}

		$actual = get_option('smt_settings');

		$this->assertTrue(
			count(array_intersect_key($expected, $actual)) == count($expected),
			'There are some option keys we expected to see but could not find'
		);

		// 3: It sets other default options not from the file (this one is hard-coded, for example)
		$this->assertEquals($actual['smt_options_connection_type_facebook'], 'public');


	}

	/***************************************************
	* Make sure post types are tracked by default
	***************************************************/
	function test_tracked_post_types() {

		// 1: Tracked post types always returns an array
		$result = $this->plugin->tracked_post_types();

		$this->assertTrue(
			is_array($result),
			'Must return an array'
		);

		// 2: Items returned must be valid, public post types
		$valid_types = get_post_types( array( 'public' => true ), 'names' );

		$this->assertTrue(
			count(array_intersect($result, $valid_types)) == count($result),
			'Returned an WP post type that did not exist!'
		);

		// 3: The default post types should be "Post" and "Page"
		$this->assertTrue(
			in_array('post', $result) && in_array('page', $result),
			'It should track Posts and Pages by default.'
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

	function test_add_missing_settings() {

		// 1. It does not change existing settings
		$this->plugin->set_smt_option('display_widget', 'foobar!');
		$this->plugin->add_missing_settings();

		$this->assertEquals(
			'foobar!', $this->plugin->get_smt_option('display_widget'),
			'It changed a setting it was not supposed to.'
		);

		// 2. If a setting is missing, it gets added
		unset($this->plugin->options['smt_options_display_widget']);
		$this->plugin->add_missing_settings();
		$this->assertEquals(1, $this->plugin->get_smt_option('display_widget'));

	}

	/***************************************************
	* It should save options to the DB
	***************************************************/
	function test_set_option() {
		$this->plugin->set_smt_option('example-field', 100);

		$db_options = get_option('smt_settings');
		$this->assertEquals(100, $db_options['smt_options_example-field']);
	}

	/***************************************************
	* It should retrieve options from the DB
	***************************************************/
	function test_get_option() {
		$this->plugin->set_smt_option('example-field', 225);

		// 1. Same plugin oboject
		$this->assertFalse($this->plugin->get_smt_option('fake-field'));
		$this->assertEquals(225, $this->plugin->get_smt_option('example-field'));

		// 2. New plugin object
		$new_plugin = new SocialMetricsTracker();
		$new_plugin->init();

		$this->assertFalse($new_plugin->get_smt_option('fake-field'));
		$this->assertEquals(225, $new_plugin->get_smt_option('example-field'));
	}

	/***************************************************
	* It should erase options from the DB
	***************************************************/
	function test_delete_option() {

		// 1. It should delete options
		$this->plugin->set_smt_option('example-field', 225);
		$this->plugin->delete_smt_option('example-field');

		$db_options = get_option('smt_settings');

		$this->assertFalse(array_key_exists('smt_options_example-field', $this->plugin->options));
		$this->assertFalse(array_key_exists('smt_options_example-field', $db_options));

		// 2. It should not wipe out other settings accidentally
		$this->plugin->set_smt_option('one', 1);
		$this->plugin->set_smt_option('two', 2);

		$this->plugin->delete_smt_option('one');
		$this->assertEquals(2, $this->plugin->get_smt_option('two'));

		// 3. Without initialization, it should be okay
		$this->plugin->set_smt_option('one', 1);
		$this->plugin->set_smt_option('two', 2);

		$new_plugin = new SocialMetricsTracker();

		$new_plugin->delete_smt_option('one');
		$this->assertEquals(2, $new_plugin->get_smt_option('two'));
	}


}


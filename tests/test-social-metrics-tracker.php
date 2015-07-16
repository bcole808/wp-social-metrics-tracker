<?php

class SocialMetricsTrackerTests extends WP_UnitTestCase {

	/**
	 * @var SocialMetricsTracker
	 */
	private $plugin;

	// DO BEFORE ALL TESTS
	function setUp() {
		parent::setUp();
		$this->plugin = new SocialMetricsTracker();
	}

	// DO AFTER ALL TESTS
	function tearDown() {
		unset( $GLOBALS['current_screen'] );
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

		foreach ($wpsf_settings['smt']['fields'] as $default) {
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

	function test_set_option_network_admin() {
		if ( ! is_multisite() ) {
			return;
		}

		$this->network_enable_plugin();
		$this->switch_to_network_admin();

		$this->plugin->use_network_settings( true );

		$this->plugin->set_smt_option( 'example-field', 100 );

		$network_options = get_site_option( 'smt_settings' );
		$options = get_option( 'smt_settings' );
		$this->assertEquals( 100, $network_options['smt_options_example-field'] );
		$this->assertFalse( isset( $options['smt_options_example-field'] ) );
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

	function test_get_option_enable_network_override() {
		if ( ! is_multisite() ) {
			return;
		}

		update_site_option(
			'smt_settings',
		    array(
			    'smt_options_not_overridden' => 'value1',
			    'smt_options_overridden' => 'value2',
			    'smt_options_facebook_access_token' => 'API KEY',
		    )
		);

		update_option(
			'smt_settings',
			array(
				'smt_options_overridden' => 'value3',
				'smt_options_facebook_access_token' => 'ROGUE API KEY',
			)
		);


		$this->network_enable_plugin();
		$this->plugin->use_network_settings( true );

		$this->plugin->set_smt_option( 'overridden', 'value4' );

		$this->assertEquals( 'value1', $this->plugin->get_smt_option( 'not_overridden' ) );
		$this->assertEquals( 'value4', $this->plugin->get_smt_option( 'overridden' ) );
		$this->assertEquals( 'API KEY', $this->plugin->get_smt_option( 'facebook_access_token' ) );
	}

	function test_get_option_disable_network_override() {
		if ( ! is_multisite() ) {
			return;
		}

		update_site_option(
			'smt_settings',
			array(
				'smt_options_only_set_in_network_options' => 'my_network_value_1',
				'smt_options_set_in_both_places' => 'my_network_value_2',
			)
		);

		update_option(
			'smt_settings',
			array(
				'smt_options_set_in_both_places' => 'my_local_value',
			)
		);

		$this->network_enable_plugin();
		$this->plugin->use_network_settings( false );

		$this->plugin->set_smt_option( 'my_new_setting', 'my_new_value' );

		$this->assertEquals( false, $this->plugin->get_smt_option( 'only_set_in_network_options' ) );
		$this->assertEquals( 'my_local_value', $this->plugin->get_smt_option( 'set_in_both_places' ) );
		$this->assertEquals( 'my_new_value', $this->plugin->get_smt_option( 'my_new_setting' ) );
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


	public function switch_to_network_admin()
	{
		$stub = $this->getMockBuilder('WP_Mock_Screen')
					 ->setMethods ( array( 'in_admin' ) )
		             ->getMock();

		$stub->expects( $this->any() )
			 ->method( 'in_admin' )
		     ->will( $this->returnValue( true ) );

		$GLOBALS['current_screen'] = $stub;

		$this->assertTrue( is_network_admin() );
	}

	public function network_enable_plugin()
	{
		update_site_option(
			'active_sitewide_plugins',
			array( 'social-metrics-tracker/social-metrics-tracker.php' => time() )
		);
	}

}


<?php

/**
* @group selenium
*/
class TestNetworkSettings extends PHPUnit_Extensions_Selenium2TestCase {

	function setUp() {
		parent::setUp();

		$this->setBrowser('firefox');
		$this->setBrowserUrl( 'http://localhost:9001' );

		// $this->markTestSkipped('must be revisited.');

	}

	function tearDown() {
		parent::tearDown();
	}

	function test_multisite_settings() {

		// Install DB
		shell_exec('rake test:install_multisite');
		
		$this->login();
		$this->network_activate_plugin();

		$this->go_to_network_settings_page();

		sleep(5);

		// CHANGES
		$this->assert_default_settings();
		$this->change_settings_general();

		$this->go_to_network_settings_page('API Connection Settings');
		$this->change_settings_api();
		
		$this->go_to_network_settings_page('Advanced Domain / URL Setup');
		$this->change_settings_domain();

		// ASSERTIONS
		$this->go_to_network_settings_page('General Settings');
		$this->assert_settings_general();

		$this->go_to_network_settings_page('API Connection Settings');
		$this->assert_settings_api();

		$this->go_to_network_settings_page('Advanced Domain / URL Setup');
		$this->assert_settings_domain();

	}

	function go_to_network_settings_page($section=false) {

		$menuitem = $this->byCssSelector('#menu-settings');
		$menuitem->click();

		$menuitem = $this->byCssSelector('#menu-settings a[href="settings.php?page=social-metrics-tracker"]');
		$menuitem->click();

		$this->assertContains('Social Metrics Tracker Configuration', $this->title());

		if ( false !== $section && 'General Settings' !== $section ) {
			$this->byLinkText($section)->click();
		}
	}


	function assert_default_settings() {
		$this->assertTrue($this->byId('smt_options_post_types_post')->selected(), 'Default settings wrong!');
		$this->assertTrue($this->byId('smt_options_post_types_page')->selected(), 'Default settings wrong!');
		$this->assertFalse($this->byId('smt_options_post_types_attachment')->selected(), 'Default settings wrong!');
	}

	function change_settings_general() {
		// Posts: ON
		if ( !$this->byId('smt_options_post_types_post')->selected() )
			  $this->byId('smt_options_post_types_post')->click();

		// Pages: OFF
		if (  $this->byId('smt_options_post_types_page')->selected() )
			  $this->byId('smt_options_post_types_page')->click();

		// Media: ON
		if ( !$this->byId('smt_options_post_types_attachment')->selected() )
			  $this->byId('smt_options_post_types_attachment')->click();

		// WIDGET: OFF
		if (  $this->byId('smt_options_display_widget')->selected() )
			  $this->byId('smt_options_display_widget')->click();


		$select = $this->select($this->byId('smt_options_report_visibility'));
		$select->selectOptionByLabel('Admins (Users who can manage options)');

		// save form
		$this->byCssSelector('.button-primary')->click();
	}

	function assert_settings_general() {

		$this->assertTrue($this->byId('smt_options_post_types_post')->selected(), 'Default settings wrong!');
		$this->assertFalse($this->byId('smt_options_post_types_page')->selected(), 'Default settings wrong!');
		$this->assertTrue($this->byId('smt_options_post_types_attachment')->selected(), 'Default settings wrong!');

	}

	function change_settings_api() {

		$this->byCssSelector('input[value="graph"]')->click();

		// EXTERNAL HTTP TEST!
		// $this->byId('fb_app_id')->value('my-app-id');
		// $this->byId('fb_app_secret')->value('my-app-secret');

		// save form
		$this->byCssSelector('.button-primary')->click();

	}

	function assert_settings_api() {
		$this->assertFalse($this->byCssSelector('input[value="public"]')->selected());
		$this->assertTrue($this->byCssSelector('input[value="graph"]')->selected());
	}

	function change_settings_domain() {

		$select = $this->select($this->byId('url_protocol'));
		$select->selectOptionByLabel('Check both http:// and https:// URLs');

		$this->byId('rewrite_before_date')->value('2015-06-12');
		$this->byId('rewrite_change_to')->value('http://www.my-old-url.com');

		$select = $this->select($this->byId('alt_url_ttl_multiplier'));
		$select->selectOptionByLabel('Much slower refresh rate (better server performance)');

		// save form
		$this->byCssSelector('.button-primary')->click();

	}

	function assert_settings_domain() {

		$select = $this->select($this->byId('url_protocol'));
		$this->assertEquals('Check both http:// and https:// URLs', $select->selectedLabel());
    	$this->assertEquals('both', $select->selectedValue());

    	$this->assertEquals('2015-06-12', $this->byId('rewrite_before_date')->value());
    	$this->assertEquals('http://www.my-old-url.com', $this->byId('rewrite_change_to')->value());

		$select = $this->select($this->byId('alt_url_ttl_multiplier'));
		$this->assertEquals('Much slower refresh rate (better server performance)', $select->selectedLabel());
    	$this->assertEquals('10', $select->selectedValue());

	}

	function network_activate_plugin() {
		$this->url( '/wp-admin/network/plugins.php' );
		$activate = $this->byCssSelector('#social-metrics-tracker .row-actions .activate a');
		$activate->click();

		$this->assertContains('Plugin activated', $this->byCssSelector('#message')->text());
	}

	function login() {
		$this->timeouts()->implicitWait(10000);

		$this->url( '/wp-admin' );

		sleep(1);

		$form = $this->byId( 'loginform' );

		$this->byId( 'user_login' )->value( 'admin' );
		$this->byId( 'user_pass' )->value( 'admin' );

		$form->submit();

	}

}
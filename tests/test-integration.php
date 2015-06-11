<?php

class TestIntegration extends PHPUnit_Extensions_Selenium2TestCase {

	function setUp() {
		parent::setUp();

		// Install DB
		shell_exec('rake test:install_db');

		$this->setBrowser('firefox');
		$this->setBrowserUrl( 'http://localhost:9001' );

	}

	function tearDown() {
		parent::tearDown();
	}

	function test_login() {
		$this->url('/wp-admin');

		sleep(1);
		$form = $this->byId( 'loginform' );

		$this->byId( 'user_login' )->value( 'admin' );
		$this->byId( 'user_pass' )->value( 'admin' );

		$form->submit();

		$this->assertEquals('Dashboard ‹ SMT test site — WordPress', $this->title());

	}

}
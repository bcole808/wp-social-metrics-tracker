<?php

class TestIntegration extends PHPUnit_Extensions_SeleniumTestCase {

	function setUp() {
		parent::setUp();

		// Install DB
		shell_exec('rake test:install_db');

		$this->setBrowser('*firefox');
		$this->setBrowserUrl( 'http://localhost:7000' );

	}

	function tearDown() {
		parent::tearDown();
	}

	function test_stuff() {
		$this->open('index.php');
		$this->assertTitle('(.*)Just another WordPress site');
	}

}
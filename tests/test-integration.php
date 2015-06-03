<?php

class TestIntegration extends PHPUnit_Extensions_SeleniumTestCase {

	// DO BEFORE ALL TESTS
	function setUp() {
		parent::setUp();
				
		$this->setHost('localhost');
		$this->setBrowser('*firefox');
        $this->setBrowserUrl('http://localhost:8000/');
	}


	// DO AFTER ALL TESTS
	function tearDown() {
		parent::tearDown();
	}

	function test_page_loads() {

		$this->open('http://localhost:8000/');
        $this->assertTitle('WordPress › Installation');

	}



}


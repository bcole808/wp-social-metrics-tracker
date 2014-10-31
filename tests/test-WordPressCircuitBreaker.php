<?php

class TestWordPressCircuitBreaker extends WP_UnitTestCase {

	public $current_time;

	// DO BEFORE ALL TESTS
	function setUp() {
		parent::setUp();

		$this->current_time = time();

		$this->wpcb = $this->getMock(
			                        'WordPressCircuitBreaker',
			                        array('getTime'),
			                        array('test_breaker',
			                            array('max_failures' => 2, 'time_to_wait' => 60)
			                        )
		);

		$this->wpcb->expects($this->any())
		    ->method('getTime')
		    ->will($this->returnCallback(array($this, 'getTime')));
	}

	// DO AFTER ALL TESTS
	function tearDown() {
		parent::tearDown();
	}

	function getTime() {
		return $this->current_time;
	}

	function moveTimeForward($num) {
		$this->current_time += $num;
	}


	/***************************************************
	* Normal conditions, remote service never goes down
	***************************************************/
	function test_working_connections() {

		// 1. It starts off ready
		$this->assertTrue($this->wpcb->readyToConnect());

		// 2. After a success, it's still ready
		$this->wpcb->reportSuccess();
		$this->assertTrue($this->wpcb->readyToConnect());

		// 3. Even after a long day, it's still going strong
		$this->wpcb->reportSuccess();
		$this->wpcb->reportSuccess();
		$this->wpcb->reportSuccess();
		$this->wpcb->reportSuccess();
		$this->wpcb->reportSuccess();
		$this->assertTrue($this->wpcb->readyToConnect());

	}

	/***************************************************
	* Intermittent failures
	***************************************************/
	function test_intermittent_failure() {

		// 1. It starts off ready
		$this->assertTrue($this->wpcb->readyToConnect());

		// 2. After one failure, still ready
		$this->wpcb->reportFailure();
		$this->assertTrue($this->wpcb->readyToConnect());

		// 3. After two failures, goes down
		$this->wpcb->reportFailure();
		$this->assertFalse($this->wpcb->readyToConnect());

		// 4. Still down, after some time
		$this->moveTimeForward(30);
		$this->assertFalse($this->wpcb->readyToConnect());

		// 5. But then, it's ready to test once
		$this->moveTimeForward(100);
		$this->assertTrue($this->wpcb->readyToConnect());

		// 6. But then not again.
		$this->assertTrue($this->wpcb->readyToConnect());
		$this->wpcb->reportFailure();
		$this->assertFalse($this->wpcb->readyToConnect());
		$this->assertFalse($this->wpcb->readyToConnect());

		// 7. And Success restores service
		$this->wpcb->reportSuccess();
		$this->assertTrue($this->wpcb->readyToConnect());
		$this->assertTrue($this->wpcb->readyToConnect());
		$this->assertTrue($this->wpcb->readyToConnect());

	}


	/***************************************************
	* It persists state
	***************************************************/
	function test_persist_state() {

		// 1. Cause a failure
		$this->wpcb->reportFailure();
		$this->wpcb->reportFailure();
		$this->assertFalse($this->wpcb->readyToConnect());

		// 2. Make sure it persists
		$my_new_wpcb = new WordPressCircuitBreaker('test_breaker');
		$this->assertFalse($my_new_wpcb->readyToConnect());

	}

}


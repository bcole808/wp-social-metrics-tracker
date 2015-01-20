<?php

class TestFacebookUpdater extends WP_UnitTestCase {

	// DO BEFORE ALL TESTS
	function setUp() {
		parent::setUp();

		$this->sample_return = file_get_contents(
			dirname(__FILE__) .'/sample-data/graph.facebook.com.json'
		);

		$this->updater = $this->getMock('FacebookUpdater', array('getURL'));

		$this->updater->expects($this->any())
		    ->method('getURL')
		    ->will($this->returnValue($this->sample_return));


		$this->offlineUpdater = $this->getMock('FacebookUpdater', array('getURL'));

		$this->offlineUpdater->expects($this->any())
		    ->method('getURL')
		    ->will($this->returnValue(false));

	}


	// DO AFTER ALL TESTS
	function tearDown() {
		parent::tearDown();
	}

	function assertMatchingMetaProperty() {
		$this->assertEquals($this->updater->meta['socialcount_facebook'], 8450);
		$this->assertEquals($this->updater->meta['facebook_comments'], 331);
		$this->assertEquals($this->updater->meta['facebook_shares'], 7169);
		$this->assertEquals($this->updater->meta['facebook_likes'], 950);
	}


	/***************************************************
	* Make sure setParams works well
	***************************************************/
	function test_setParams() {
		$post_id = $this->factory->post->create();

		$this->updater->setParams($post_id);

		// 1. Post data is saved
		$this->assertAttributeEquals($post_id, 'post_id', $this->updater);
		$this->assertAttributeEquals(get_permalink($post_id), 'post_url', $this->updater);

		// 2. Params are configured
		$this->assertTrue(count($this->updater->resource_params) > 0, 'It did not set params');
		$this->assertTrue(isset($this->updater->resource_params['q']));

		// 3. Resetting params should clear the instance variables
		$this->updater->fetch();
		$this->updater->setParams($post_id);
		$this->assertEmpty($this->updater->data);
		$this->assertEmpty($this->updater->complete);
	}


	/***************************************************
	* Test fetching of data
	***************************************************/
	function test_fetch() {
		$post_id = $this->factory->post->create();

		// 1. It should fail when params not set
		$result = $this->updater->fetch();
		$this->assertFalse($result);

		// 2. It should return an array when params set
		$this->updater->setParams($post_id);
		$result = $this->updater->fetch();
		$this->assertTrue(is_array($result));

		// 3. It should set the instance variable
		$this->assertAttributeEquals(json_decode($this->sample_return, true), 'data', $this->updater);

		// 5. It should return false if the remote service is down
		$this->offlineUpdater->setParams($post_id);
		$this->assertFalse($result = $this->offlineUpdater->fetch(), 'It should be false if service down');
	}


	/***************************************************
	* Test parsing of data
	***************************************************/
	function test_parse() {
		$post_id = $this->factory->post->create();

		// 1. It should do nothing if there is no data
		$this->assertFalse($this->updater->parse(), 'It should do nothing');

		// 2. It should assign some meta variables
		$this->updater->setParams($post_id);
		$this->updater->fetch();
		$this->updater->parse();

		$this->assertMatchingMetaProperty();
	}


	/***************************************************
	* Test saving of data
	***************************************************/
	function test_save() {
		$post_id = $this->factory->post->create();
		$this->updater->setParams($post_id);

		// 1. It should do nothing when there is nothing to save
		$this->assertFalse($this->updater->save());

		// 2. It should not save a field with a falsy value
		$this->updater->meta['zero_value']     = 0;
		$this->updater->meta['bad_boolean']    = false;
		$this->updater->meta['empty_array']    = array();
		$this->updater->meta['negative_value'] = -1;

		$this->assertFalse($this->updater->save());

		// 2. It should save all the meta fields
		$this->updater->meta['my_yummy_value']   = 'pizza';
		$this->updater->meta['my_numeric_value'] = 15;
		$this->updater->save();

		$this->assertEquals('pizza', get_post_meta($post_id, 'my_yummy_value', true));
		$this->assertEquals(15,      get_post_meta($post_id, 'my_numeric_value', true));

		// 4. It should set $this->complete to true if saving successfully
		$this->assertTrue($this->updater->complete);
	}


	/***************************************************
	* Test the most important thing; behavior of sync!
	***************************************************/
	function test_sync() {
		$post_id = $this->factory->post->create();

		$original_meta = get_post_meta($post_id);

		// 1. It should not affect the DB if the remote service is down
		$this->offlineUpdater->sync($post_id, get_permalink($post_id));
		$this->assertEquals($original_meta, get_post_meta($post_id));

		// 2. It should do nothing if I pass it bad params
		$this->assertFalse($this->updater->sync('NotAnInteger', 'fooBar'));
		$this->assertFalse($this->updater->sync(123, array('not_a_string')));

		// 3. It should not affect the DB if we set $return_instead_of_save
		$this->updater->sync($post_id, get_permalink($post_id), true);
		$this->assertEquals($original_meta, get_post_meta($post_id));

		// 4. It should save correct meta field/values
		$this->updater->sync($post_id, get_permalink($post_id));
		$this->assertMatchingMetaProperty();

		// 5. It should not affect existing social data if remote service is down
		$this->offlineUpdater->sync($post_id, get_permalink($post_id));
		$this->assertMatchingMetaProperty();

	}


	/***************************************************
	* Should return only the value we want contributed toward the total
	***************************************************/
	function test_get_total() {
		$post_id = $this->factory->post->create();
		$this->updater->sync($post_id, get_permalink($post_id));

		// 1. It should return the total
		$this->assertEquals($this->updater->get_total(), 8450);
	}


	/***************************************************
	* In case a remote service goes down, circuit breaker should stop future attempts
	***************************************************/
	function test_circuit_breaker() {
		$post_id = $this->factory->post->create();
		$num = $this->offlineUpdater->updater->wpcb->get('max_failures');

		// NOTE: We expect that getURL will be called exactly the number of times as the 'max_failures' property as set in the circuit breaker.
		$cb_updater = $this->getMock('FacebookUpdater', array('getURL'));
		$cb_updater->expects($this->exactly($num))
		    ->method('getURL')
		    ->will($this->returnValue(false));


		// 1. The first three attempst should go through
		$this->assertTrue($cb_updater->wpcb->readyToConnect()); // Allow attempt 1
		$cb_updater->sync($post_id, get_permalink($post_id));

		$this->assertTrue($cb_updater->wpcb->readyToConnect()); // Allow attempt 2
		$cb_updater->sync($post_id, get_permalink($post_id));

		$this->assertTrue($cb_updater->wpcb->readyToConnect()); // Allow attempt 3
		$cb_updater->sync($post_id, get_permalink($post_id));

		$this->assertFalse($cb_updater->wpcb->readyToConnect()); // Block attempt 4
		$cb_updater->sync($post_id, get_permalink($post_id));
	}


	/***************************************************
	* Test getMetaFields()
	***************************************************/
	function test_getMetaFields() {
		$post_id = $this->factory->post->create();

		// 1. It should return false if the fetch failed
		$this->offlineUpdater->setParams($post_id, get_permalink($post_id));
		$this->offlineUpdater->fetch();
		$this->offlineUpdater->parse();

		$this->assertEquals(
			$this->offlineUpdater->getMetaFields(),
			false
		);

		// 2. It should return an array if the fetch worked
		$this->updater->setParams($post_id, get_permalink($post_id));
		$this->updater->fetch();
		$this->updater->parse();

		// There should be an array with four keys for Facebook
		$this->assertTrue(
			is_array($this->updater->getMetaFields()) &&
			count($this->updater->getMetaFields()) == 4
		);

	}

}

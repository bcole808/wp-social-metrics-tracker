<?php
/************************************
* In this test suite, you must:
*
* Call $FacebookUpdater->setParams(ID, 'path_to_sample.json');
*
* And then it will fetch that data when running the test. 
*************************************/

/**
* @requires PHP 5.4
*/
class MetricUpdaterAltURLTests extends WP_UnitTestCase {

	private $updater;
	private $correct_alt_data;

	private $override_all_sample_data = false;

	// How to use: 
	// A) Set value to true/false to set updater online/offline
	// B) Set array (true, false, false, true) to indicate that updater should be online, then offline twice, then online. After fourth, Updater returns to regular online state. 
	private $available = array(
		'FacebookUpdater' => true,
		'TwitterUpdater'  => true,
		'LinkedInUpdater' => true,
	);

	// DO BEFORE ALL TESTS
	function setUp() {
		parent::setUp();

		// Create an updater object
		$this->smt = new SocialMetricsTracker();
		$this->smt->init();

		$this->updater = $this->getMockBuilder('\MetricsUpdater')
			->setConstructorArgs(array($this->smt))
		    ->setMethods(array('isValidURL', 'getLocalTime'))
		    ->getMock();

		$this->updater->expects($this->any())
		    ->method('isValidURL')
		    ->will($this->returnValue(true));

		$this->updater->expects($this->any())
		    ->method('getLocalTime')
		    ->will($this->returnCallback(array($this, 'getLocalTime')));


		// MOCK HTTP-RESOURCE-UPDATERS
		// =====================
		$updater_classes = array('FacebookUpdater', 'TwitterUpdater', 'LinkedInUpdater');

		// Create a mock object for each of the desired updater classes. 
		foreach ($updater_classes as $class_name) {
			$this->updater->sources->{$class_name} = $this->getMock($class_name, array('getURL'));
			$this->updater->sources->{$class_name}->expects($this->any())
			    ->method('getURL')
			    ->will($this->returnCallback(function() use ($class_name) {
			    	$status = (is_array($this->available[$class_name])) ? array_shift($this->available[$class_name]) : $this->available[$class_name];
			    	
			    	if ($status === null) {
			    		$status = true;
			    		$this->available[$class_name] = true;
			    	}

			    	$path = str_replace('service', $this->updater->sources->{$class_name}->slug, $this->updater->sources->{$class_name}->post_url);
			    	return ($status) ? $this->get_sample_data($path, $this->updater->sources->{$class_name}->slug) : false;
			    })
			);
		}

		$this->updater->dataSourcesReady = true;

		$this->correct_alt_data = array(
			'canonical-not-set/service-1.json' => array(
				'permalink'                => 'canonical-not-set/service-1.json',
				'socialcount_facebook'     => 1019,
				'facebook_comments'        => 163,
				'facebook_shares'          => 685,
				'facebook_likes'           => 171,
				'socialcount_twitter'      => 111,
				'socialcount_linkedin'     => 988,
				// 'socialcount_LAST_UPDATED' => time()
			),
			'canonical-not-set/service-2.json' => array(
				'permalink'                => 'canonical-not-set/service-2.json',
				'socialcount_facebook'     => 1019,
				'facebook_comments'        => 163,
				'facebook_shares'          => 685,
				'facebook_likes'           => 171,
				'socialcount_twitter'      => 0,
				'socialcount_linkedin'     => 988,
				// 'socialcount_LAST_UPDATED' => time()
			),
			'canonical-not-set/service-3.json' => array(
				'permalink'                => 'canonical-not-set/service-3.json',
				'socialcount_facebook'     => 110624,
				'facebook_comments'        => 15537,
				'facebook_shares'          => 71650,
				'facebook_likes'           => 23437,
				'socialcount_twitter'      => 163021,
				'socialcount_linkedin'     => 988,
				// 'socialcount_LAST_UPDATED' => time()
			),
			'canonical-not-set/service-4.json' => array(
				'permalink'                => 'canonical-not-set/service-4.json',
				'socialcount_facebook'     => 110624,
				'facebook_comments'        => 15537,
				'facebook_shares'          => 71650,
				'facebook_likes'           => 23437,
				'socialcount_twitter'      => 168,
				'socialcount_linkedin'     => 988,
				// 'socialcount_LAST_UPDATED' => time()
			),
			// Canonical
			'canonical-set/service-1.json' => array(
				'permalink'                => 'canonical-set/service-1.json',
				'socialcount_facebook'     => 171759,
				'facebook_comments'        => 16579,
				'facebook_shares'          => 131060,
				'facebook_likes'           => 24120,
				'socialcount_twitter'      => 5,
				'socialcount_linkedin'     => 241,
				// 'socialcount_LAST_UPDATED' => time()
			),
			'canonical-set/service-2.json' => array(
				'permalink'                => 'canonical-set/service-2.json',
				'socialcount_facebook'     => 171759,
				'facebook_comments'        => 16579,
				'facebook_shares'          => 131060,
				'facebook_likes'           => 24120,
				'socialcount_twitter'      => 2379,
				'socialcount_linkedin'     => 241,
				// 'socialcount_LAST_UPDATED' => time()
			),
			'canonical-set/service-3.json' => array(
				'permalink'                => 'canonical-set/service-3.json',
				'socialcount_facebook'     => 171759,
				'facebook_comments'        => 16579,
				'facebook_shares'          => 131060,
				'facebook_likes'           => 24120,
				'socialcount_twitter'      => 7,
				'socialcount_linkedin'     => 241,
				// 'socialcount_LAST_UPDATED' => time()
			),
			'canonical-set/service-4.json' => array(
				'permalink'                => 'canonical-set/service-4.json',
				'socialcount_facebook'     => 171759,
				'facebook_comments'        => 16579,
				'facebook_shares'          => 131060,
				'facebook_likes'           => 24120,
				'socialcount_twitter'      => 69221,
				'socialcount_linkedin'     => 241,
				// 'socialcount_LAST_UPDATED' => time()
			)
		);

	}

	// DO AFTER ALL TESTS
	function tearDown() {
		parent::tearDown();
	}

	// Set the time
	function setTime($time) {
		$this->current_time = $time;
	}

	// Get the time
	function getLocalTime() {
		if (!isset($this->current_time)) $this->setTime(current_time( 'timestamp' ));
		return $this->current_time;
	}

	// Opens and returns the sample data file, or false
	function get_sample_data($file, $slug) {
		if ($this->override_all_sample_data) {
			$result = @file_get_contents(dirname(__FILE__).'/sample-data/canonical-set/'.$slug.'-1.json');
		} else {
			$result = @file_get_contents(dirname(__FILE__).'/sample-data/'.$file);
		}
		
		return ($result) ? $result : false;
	}

	// Asserts that the social data saved in the alt_data field matches $data
	function verify_correct_alt_data($post_id, $data) {
		$expected_result = (is_array($data)) ? $data : $this->correct_alt_data[$data];

		// Find the matching meta key
		$alt_data = get_post_meta($post_id, 'socialcount_url_data');
		$count = 0;
		foreach ($alt_data as $item) {
			if ($item['permalink'] == $expected_result['permalink']) {
				$current_result = $item;
				$count++;
			}
		}

		// 1. Assert presence of meta
		$this->assertTrue(isset($current_result), 'Could not find a matching meta key!'.print_r($alt_data, true));

		// 2. Assert only one field
		$this->assertTrue($count == 1, 'There was more than one meta field saved!!!');

		// 3. Assert correct values
		$diff = array_diff_assoc($expected_result, $current_result);
		$this->assertEquals(0, count($diff), 'The result in the DB was missing these fields: '.print_r($diff, true));

	}

	// Asserts that the primary social data saved on the post matches $data
	function verify_correct_primary_data($post_id, $data) {
		$expected = (is_array($data)) ? $data : $this->correct_alt_data[$data];

		$keys = array('socialcount_facebook', 'socialcount_twitter', 'socialcount_linkedin', 'facebook_comments', 'facebook_likes', 'facebook_shares');

		foreach($keys as $key) {
			$this->assertEquals($expected[$key], get_post_meta($post_id, $key, true), 'The key "'.$key.'" did not have the right value.');
		}

		if (array_key_exists('socialcount_TOTAL', $expected))
			$this->assertEquals($expected['socialcount_TOTAL'], get_post_meta($post_id, 'socialcount_TOTAL', true), 'The key "socialcount_TOTAL" did not have the right value.');
	}

	function remove_meta_by_url($post_id, $alt_url) {
		$alt_data = get_post_meta($post_id, 'socialcount_url_data');

		foreach ($alt_data as $item) {
			if ($item['permalink'] == $alt_url) {
				delete_post_meta($post_id, 'socialcount_url_data', $item);
				break;
			}
		}
	}

	function test_data_validation() {

		// Create an updater object without mocks for this test
		// NOTE: This updater will likely fail any real updates because it does not have mock services. 
		$smt = new SocialMetricsTracker();
		$smt->init();
		$this->updater = new MetricsUpdater($smt);

		// MOCK HTTP-RESOURCE-UPDATERS
		// =====================
		$updater_classes = array('FacebookUpdater', 'TwitterUpdater', 'LinkedInUpdater');

		// Create a mock object for each of the desired updater classes. 
		foreach ($updater_classes as $class_name) {
			$this->updater->sources->{$class_name} = $this->getMock($class_name, array('getURL'));
			$this->updater->sources->{$class_name}->expects($this->any())
			    ->method('getURL')
			    ->will($this->returnCallback(function() use ($class_name) {
			    	$status = (is_array($this->available[$class_name])) ? array_shift($this->available[$class_name]) : $this->available[$class_name];
			    	
			    	if ($status === null) {
			    		$status = true;
			    		$this->available[$class_name] = true;
			    	}

			    	$path = str_replace('service', $this->updater->sources->{$class_name}->slug, $this->updater->sources->{$class_name}->post_url);
			    	return ($status) ? $this->get_sample_data($path, $this->updater->sources->{$class_name}->slug) : false;
			    })
			);
		}

		$this->updater->dataSourcesReady = true;

		$post_id = $this->factory->post->create();

		// 1. Validation: Bad input does not break things
		$this->updater->updatePostStats(null);
		$this->updater->updatePostStats(true);
		$this->updater->updatePostStats(99999);
		$this->updater->updatePostStats('fooBarBadData');
		$this->updater->updatePostStats(array(10, 20, 'foo'));


		// 2. Validation: Bad meta fields do not breka things
		add_post_meta($post_id, 'socialcount_url_data', null);
		add_post_meta($post_id, 'socialcount_url_data', true);
		add_post_meta($post_id, 'socialcount_url_data', false);
		add_post_meta($post_id, 'socialcount_url_data', 99999);
		add_post_meta($post_id, 'socialcount_url_data', -12.2);
		add_post_meta($post_id, 'socialcount_url_data', 'fooBarBadData');
		add_post_meta($post_id, 'socialcount_url_data', array(10, 20, 'foo'));
		add_post_meta($post_id, 'socialcount_url_data', 'canonical-set/service-3.json'); // note: this should get filtered as an invalid URL in this test

		$this->updater->updatePostStats($post_id, true, 'canonical-set/service-3.json'); // note: this should be allowed in this test
		$this->verify_correct_primary_data($post_id, 'canonical-set/service-3.json');

		$meta = get_post_meta($post_id, 'socialcount_url_data');
		$this->assertEquals(0, count($meta), 'The following meta data should have been deleted: '.print_r($meta, true));

	}

	// Test to make sure the mock updaters are working as intended
	function test_mock_status() {
		$expected = file_get_contents(dirname(__FILE__).'/sample-data/canonical-not-set/facebook-1.json');
		$this->updater->sources->FacebookUpdater->setParams(1, 'canonical-not-set/service-1.json');

		// 1. It returns some data
		$result = $this->updater->sources->FacebookUpdater->getURL('canonical-not-set/service-1.json');
		$this->assertEquals($expected, $result, 'There is a problem with the test suite simulating online services!');

		// 2. When offline, it returns no data
		$this->available['FacebookUpdater'] = false;

		$this->assertFalse($this->updater->sources->FacebookUpdater->getURL('canonical-not-set/service-1.json'));
		$this->assertFalse($this->updater->sources->FacebookUpdater->getURL('canonical-not-set/service-1.json'));

		// 3. We can do fancy patterns
		$this->available['FacebookUpdater'] = array(true, false, false, true, false);

		$this->assertTrue(false !== $this->updater->sources->FacebookUpdater->getURL('canonical-not-set/service-1.json'));
		$this->assertFalse($this->updater->sources->FacebookUpdater->getURL('canonical-not-set/service-1.json'));
		$this->assertFalse($this->updater->sources->FacebookUpdater->getURL('canonical-not-set/service-1.json'));
		$this->assertTrue(false !== $this->updater->sources->FacebookUpdater->getURL('canonical-not-set/service-1.json'));
		$this->assertFalse($this->updater->sources->FacebookUpdater->getURL('canonical-not-set/service-1.json'));
		
		$this->assertTrue(false !== $this->updater->sources->FacebookUpdater->getURL('canonical-not-set/service-1.json'));
		$this->assertTrue(false !== $this->updater->sources->FacebookUpdater->getURL('canonical-not-set/service-1.json'));

	}

	function test_duplicate_filtering() {
		$post_id = $this->factory->post->create();


		// 1. Validation: Using the same URL as the primary URL does not break it
		update_post_meta($post_id, 'socialcount_url_data', 'canonical-set/service-1.json');
		$this->updater->updatePostStats($post_id, true, 'canonical-set/service-1.json');

		$this->verify_correct_alt_data($post_id, 'canonical-set/service-1.json');
		$this->verify_correct_primary_data($post_id, 'canonical-set/service-1.json');


		// 2. Numberes are added correctly
		$post_id = $this->factory->post->create();

		add_post_meta($post_id, 'socialcount_url_data', 'canonical-not-set/service-1.json');
		add_post_meta($post_id, 'socialcount_url_data', 'canonical-not-set/service-2.json');
		add_post_meta($post_id, 'socialcount_url_data', 'canonical-not-set/service-3.json');
		add_post_meta($post_id, 'socialcount_url_data', 'canonical-not-set/service-4.json');

		$this->updater->updatePostStats($post_id, true, 'canonical-not-set/service-1.json');

		$this->verify_correct_alt_data($post_id, 'canonical-not-set/service-1.json');
		$this->verify_correct_alt_data($post_id, 'canonical-not-set/service-2.json');
		$this->verify_correct_alt_data($post_id, 'canonical-not-set/service-3.json');
		$this->verify_correct_alt_data($post_id, 'canonical-not-set/service-4.json');

		$this->verify_correct_primary_data($post_id, array(
			'socialcount_facebook'     => 111643,
			'facebook_comments'        => 15700,
			'facebook_shares'          => 72335,
			'facebook_likes'           => 23608,
			'socialcount_twitter'      => 163300,
			'socialcount_linkedin'     => 988,
			'socialcount_TOTAL'        => 275931,
			'socialcount_LAST_UPDATED' => time()
		));
		

		// 3. Validation: Duplicate meta fields do not break it
		$post_id = $this->factory->post->create();

		add_post_meta($post_id, 'socialcount_url_data', 'canonical-set/service-4.json');
		add_post_meta($post_id, 'socialcount_url_data', 'canonical-set/service-4.json');
		add_post_meta($post_id, 'socialcount_url_data', 'canonical-set/service-4.json');

		$this->updater->updatePostStats($post_id, true, 'canonical-set/service-4.json');

		$this->assertTrue(count(get_post_meta($post_id, 'socialcount_url_data')) === 1, 'too many fields saved');
		$this->verify_correct_alt_data($post_id, 'canonical-set/service-4.json');

	}

	function test_network_connectivity() {
		$post_id = $this->factory->post->create();

		$expected_data = array(
			'permalink'                => 'canonical-set/service-1.json',
			'socialcount_facebook'     => 171759, // The sum of facebook-1, 3, and 4 (which are identical)
			'facebook_comments'        => 16579,  // The sum of facebook-1, 3, and 4 (which are identical)
			'facebook_shares'          => 131060, // The sum of facebook-1, 3, and 4 (which are identical)
			'facebook_likes'           => 24120,  // The sum of facebook-1, 3, and 4 (which are identical)
			'socialcount_twitter'      => 71612,  // the sum of twitter-1, 2, 3, and 4
			'socialcount_linkedin'     => 241,     // the sum of linkedin-1, 2, 3, and 4 (which are identical)
			'socialcount_TOTAL'        => 243612
		);

		// 1. If service is partially offline, it does not break
		$this->available['FacebookUpdater'] = array(true, false);

		add_post_meta($post_id, 'socialcount_url_data', 'canonical-set/service-2.json');

		$this->updater->updatePostStats($post_id, true, 'canonical-set/service-1.json');

		$this->verify_correct_primary_data($post_id, array(
			'permalink'                => 'canonical-set/service-1.json',
			'socialcount_facebook'     => 171759, // Only the value from facebook-1
			'facebook_comments'        => 16579,
			'facebook_shares'          => 131060,
			'facebook_likes'           => 24120,
			'socialcount_twitter'      => 2384, // the sum of twitter-1 and twitter-2
			'socialcount_linkedin'     => 241,
			'socialcount_TOTAL'        => 174384
		));

		// 2. If service is partially offline, it counts everything it can
		$post_id = $this->factory->post->create();
		$this->available['TwitterUpdater'] = array(true, true, false, true);

		add_post_meta($post_id, 'socialcount_url_data', 'canonical-set/service-2.json');
		add_post_meta($post_id, 'socialcount_url_data', 'canonical-set/service-3.json');
		add_post_meta($post_id, 'socialcount_url_data', 'canonical-set/service-4.json');

		$this->updater->updatePostStats($post_id, true, 'canonical-set/service-1.json');

		$this->verify_correct_primary_data($post_id, array(
			'permalink'                => 'canonical-set/service-1.json',
			'socialcount_facebook'     => 171759, // The sum of facebook-1, 3, and 4 (which are identical)
			'facebook_comments'        => 16579,  // The sum of facebook-1, 3, and 4 (which are identical)
			'facebook_shares'          => 131060, // The sum of facebook-1, 3, and 4 (which are identical)
			'facebook_likes'           => 24120,  // The sum of facebook-1, 3, and 4 (which are identical)
			'socialcount_twitter'      => 71605,  // the sum of twitter-1, 2, (not 3), and 4
			'socialcount_linkedin'     => 241,    // the sum of linkedin-1, 2, 3, and 4 (which are identical)
			'socialcount_TOTAL'        => 243605
		));

		// 3. Then later, the remaining data is added
		$this->updater->updatePostStats($post_id, true, 'canonical-set/service-1.json');
		$this->verify_correct_primary_data($post_id, $expected_data);

		// 4. When a service goes completely offline, no data is lost
		$this->available['FacebookUpdater'] = false;
		$this->updater->updatePostStats($post_id, true, 'canonical-set/service-1.json');
		$this->verify_correct_primary_data($post_id, $expected_data);

		// 5. And if it is intermittent, no data is lost
		$this->available['FacebookUpdater'] = array(true, false, false, false);
		$this->updater->updatePostStats($post_id, true, 'canonical-set/service-1.json');
		$this->verify_correct_primary_data($post_id, $expected_data);

	}

	function test_report_network_failure() {
		$post_id = $this->factory->post->create();

		// 1. Network failure should not be reported
		$result = $this->updater->fetchPostStats($post_id, true, 'canonical-set/service-2.json');
		$this->assertFalse($result['network_failure']);

		// 2. Network failure should be reported
		$this->available['FacebookUpdater'] = false;
		$result = $this->updater->fetchPostStats($post_id, true, 'canonical-set/service-2.json');
		$this->assertTrue($result['network_failure']);

		// 3. A failure on a secondary request should be reported
		$this->available['FacebookUpdater'] = array(true, false);
		add_post_meta($post_id, 'socialcount_url_data', 'canonical-set/service-4.json');

		$result = $this->updater->fetchPostStats($post_id, true, 'canonical-set/service-2.json');
		$this->assertTrue($result['network_failure']);
	}

	function test_removal_of_urls() {
		$post_id = $this->factory->post->create();

		$expected_total = array(
			'socialcount_facebook'     => 111643,
			'facebook_comments'        => 15700,
			'facebook_shares'          => 72335,
			'facebook_likes'           => 23608,
			'socialcount_twitter'      => 163300,
			'socialcount_linkedin'     => 988,
			'socialcount_TOTAL'        => 275763,
			'socialcount_LAST_UPDATED' => time()
		);

		// 1. If I have a post with some extra URLs and remove one, the count is deducted
		add_post_meta($post_id, 'socialcount_url_data', 'canonical-not-set/service-2.json');
		add_post_meta($post_id, 'socialcount_url_data', 'canonical-not-set/service-3.json');
		add_post_meta($post_id, 'socialcount_url_data', 'canonical-not-set/service-4.json');

		$this->updater->updatePostStats($post_id, true, 'canonical-not-set/service-1.json');

		$this->remove_meta_by_url($post_id, 'canonical-not-set/service-4.json');

		$this->updater->updatePostStats($post_id, true, 'canonical-not-set/service-1.json');

		$expected = $expected_total;
		$expected['socialcount_twitter'] = 163132; // Minus service-4

		$this->verify_correct_primary_data($post_id, $expected);
		
	}

	// When configured, plugin should automatically adjust url_data fields
	function test_ssl_protocol_configuration() {

		$this->override_all_sample_data = true;
		$post_id = $this->factory->post->create();


		// 0. Input validation
		$this->smt->set_smt_option('url_protocol', 'foobar');
		$this->updater->updatePostStats($post_id, true, 'http://www.wordpress.org');
		$this->assertEquals(0, count(get_post_meta($post_id, 'socialcount_url_data')));

		$this->smt->set_smt_option('url_protocol', array('foo', 'bar'));
		$this->updater->updatePostStats($post_id, true, 'http://www.wordpress.org');
		$this->assertEquals(0, count(get_post_meta($post_id, 'socialcount_url_data')));

		$this->smt->set_smt_option('url_protocol', array('foo', -9755));
		$this->updater->updatePostStats($post_id, true, 'http://www.wordpress.org');
		$this->assertEquals(0, count(get_post_meta($post_id, 'socialcount_url_data')));

		$this->smt->set_smt_option('url_protocol', array('foo', true));
		$this->updater->updatePostStats($post_id, true, 'http://www.wordpress.org');
		$this->assertEquals(0, count(get_post_meta($post_id, 'socialcount_url_data')));


		// 1. Auto should not affect the url_data fields
		$this->smt->set_smt_option('url_protocol', 'auto');
		$this->updater->updatePostStats($post_id, true, 'http://www.wordpress.org');

		$alt_data = get_post_meta($post_id, 'socialcount_url_data');
		$this->assertEquals(0, count($alt_data));


		// 2. Both should prioritize the protocol that the home_url is on, and set the other as an alt field
		$this->smt->set_smt_option('url_protocol', 'both');
		$this->updater->updatePostStats($post_id, true, 'http://www.wordpress.org');

		$alt_data = get_post_meta($post_id, 'socialcount_url_data');
		$this->assertEquals(1, count($alt_data));
		$this->assertTrue($alt_data[0]['permalink'] == 'https://www.wordpress.org');


		// 2a. The other way should work too! 
		update_option('siteurl','https://example.com');
		update_option('home','https://example.com');

		$this->updater->updatePostStats($post_id, true, 'http://www.wordpress.org');

		$alt_data = get_post_meta($post_id, 'socialcount_url_data');
		$this->assertEquals(1, count($alt_data));
		$this->assertTrue($alt_data[0]['permalink'] == 'http://www.wordpress.org');


		// 3. http should remove https url_data fields, and use http as primary
		$this->smt->set_smt_option('url_protocol', 'http');
		$this->updater->updatePostStats($post_id, true, 'http://www.wordpress.org');

		$alt_data = get_post_meta($post_id, 'socialcount_url_data');
		$this->assertEquals(0, count($alt_data));


		// 4. https should remove http url_data fields, and use https as primary
		$this->smt->set_smt_option('url_protocol', 'both');
		$this->updater->updatePostStats($post_id, true, 'http://www.wordpress.org');

		$this->smt->set_smt_option('url_protocol', 'https');
		$this->updater->updatePostStats($post_id, true, 'http://www.wordpress.org');

		$alt_data = get_post_meta($post_id, 'socialcount_url_data');
		$this->assertEquals(0, count($alt_data));


		// 5. If configured for both, then the site protocol changes!
		update_option('siteurl','http://example.com');
		update_option('home','http://example.com');

		$this->smt->set_smt_option('url_protocol', 'both');
		$this->updater->updatePostStats($post_id, true, 'http://www.wordpress.org');

		$alt_data = get_post_meta($post_id, 'socialcount_url_data');
		$this->assertEquals(1, count($alt_data));
		$this->assertTrue($alt_data[0]['permalink'] == 'https://www.wordpress.org');

		update_option('siteurl','https://example.com');
		update_option('home','https://example.com');

		$this->updater->updatePostStats($post_id, true, 'https://www.wordpress.org');

		$alt_data = get_post_meta($post_id, 'socialcount_url_data');
		$this->assertEquals(1, count($alt_data));
		$this->assertTrue($alt_data[0]['permalink'] == 'http://www.wordpress.org');


		// 6. It should not affect other fields
		$this->smt->set_smt_option('url_protocol', 'http');
		$this->updater->updatePostStats($post_id, true, 'https://www.wordpress.org');

		add_post_meta($post_id, 'socialcount_url_data', 'canonical-not-set/service-2.json');
		add_post_meta($post_id, 'socialcount_url_data', 'canonical-not-set/service-3.json');

		$this->updater->updatePostStats($post_id, true, 'https://www.wordpress.org');

		$this->smt->set_smt_option('url_protocol', 'both');

		$this->updater->updatePostStats($post_id, true, 'https://www.wordpress.org');
		$alt_data = get_post_meta($post_id, 'socialcount_url_data');
		$this->assertEquals(3, count($alt_data), print_r($alt_data, true));

	}

	// When configured, plugin should automatically add url_data fields
	function test_domain_migration_configuration_1() {
		$this->override_all_sample_data = true;
		$post_id = $this->factory->post->create();

		$url_rewrites = array(
			array(
				'rewrite_match_from'  => 'https://www.wordpress.org/',
				'rewrite_change_to'   => 'http://old.domain.com/',
				'rewrite_before_date' => ''
			)
		);

		$this->smt->set_smt_option('url_rewrites', $url_rewrites);


		// 1. When there is no date, should add url_data field to all posts
		$this->updater->updatePostStats($post_id, true, 'https://www.wordpress.org/?p=1');

		$alt_data = get_post_meta($post_id, 'socialcount_url_data');
		$this->assertEquals(1, count($alt_data));
		$this->assertTrue($alt_data[0]['permalink'] == 'http://old.domain.com/?p=1', print_r($alt_data, true));

		
		// Input validation - it should ignore bad dates
		$post_id = $this->factory->post->create();

		$url_rewrites = array(
			array(
				'rewrite_match_from'  => 'https://www.wordpress.org/',
				'rewrite_change_to'   => 'http://old.domain.com/',
				'rewrite_before_date' => 'wrong date!!!'
			)
		);
		$this->smt->set_smt_option('url_rewrites', $url_rewrites);

		$this->updater->updatePostStats($post_id, true, 'https://www.wordpress.org/?p=1');

		$alt_data = get_post_meta($post_id, 'socialcount_url_data');
		$this->assertEquals(1, count($alt_data));
		$this->assertTrue($alt_data[0]['permalink'] == 'http://old.domain.com/?p=1', print_r($alt_data, true));
		

		// Input validation - it should not attempt bad URLs
		$post_id = $this->factory->post->create();

		$url_rewrites = array(
			array(
				'rewrite_match_from'  => 'super crazy bad input',
				'rewrite_change_to'   => 'http://old.domain.com/',
				'rewrite_before_date' => 'wrong date'
			)
		);
		$this->smt->set_smt_option('url_rewrites', $url_rewrites);

		$this->updater->updatePostStats($post_id, true, 'https://www.wordpress.org/?p=1');

		$alt_data = get_post_meta($post_id, 'socialcount_url_data');
		$this->assertEquals(0, count($alt_data), print_r($alt_data, true));


		// Input validation - it should not attempt bad URLs
		$post_id = $this->factory->post->create();

		$url_rewrites = array(
			array(
				'rewrite_match_from'  => 'http://old.domain.com/',
				'rewrite_change_to'   => 'super crazy bad input',
				'rewrite_before_date' => 'wrong date'
			)
		);
		$this->smt->set_smt_option('url_rewrites', $url_rewrites);

		$this->updater->updatePostStats($post_id, true, 'https://www.wordpress.org/?p=1');

		$alt_data = get_post_meta($post_id, 'socialcount_url_data');
		$this->assertEquals(0, count($alt_data), print_r($alt_data, true));

	}

	// 2. When there is a date, should only add url_data field for correct posts
	function test_domain_migration_configuration_2() {
		
		$this->override_all_sample_data = true;

		$url_rewrites = array(
			array(
				'rewrite_match_from'  => 'https://www.wordpress.org/',
				'rewrite_change_to'   => 'http://old.domain.com/',
				'rewrite_before_date' => '2015-01-10' // must use slashes
			)
		);

		$this->smt->set_smt_option('url_rewrites', $url_rewrites);


		// 2b. It should not add a newer post
		$post_id = $this->factory->post->create();
		$post = get_post($post_id);
		$post->post_date = '2015-01-11 12:00:00';
		wp_update_post($post);

		$this->updater->updatePostStats($post_id, true, 'https://www.wordpress.org/?p=2');
		$alt_data = get_post_meta($post_id, 'socialcount_url_data');

		$this->assertEquals(0, count($alt_data));


		// 2a. It should add a post in the past
		$post_id = $this->factory->post->create();
		$post = get_post($post_id);
		$post->post_date = '2015-01-09 12:00:00';
		wp_update_post($post);

		$this->updater->updatePostStats($post_id, true, 'https://www.wordpress.org/?p=2');
		$alt_data = get_post_meta($post_id, 'socialcount_url_data');

		$this->assertEquals(1, count($alt_data));
		$this->assertTrue($alt_data[0]['permalink'] == 'http://old.domain.com/?p=2', print_r($alt_data, true));

		
		// 2c. When migrating multiple times, old url_data should not be erased
		$url_rewrites = array(
			array(
				'rewrite_match_from'  => 'https://www.wordpress.org/',
				'rewrite_change_to'   => 'http://super.old.domain.com/',
				'rewrite_before_date' => '2015-01-10' // must use slashes
			)
		);

		$this->smt->set_smt_option('url_rewrites', $url_rewrites);

		$this->updater->updatePostStats($post_id, true, 'https://www.wordpress.org/?p=2');
		$alt_data = get_post_meta($post_id, 'socialcount_url_data');

		$this->assertEquals(2, count($alt_data));
		$this->assertTrue($alt_data[0]['permalink'] == 'http://old.domain.com/?p=2', print_r($alt_data, true));
		$this->assertTrue($alt_data[1]['permalink'] == 'http://super.old.domain.com/?p=2', print_r($alt_data, true));
	}

	// 0. Input validation
	function test_performance_option_configuration_0() {
		$this->smt->set_smt_option('alt_url_ttl_multiplier', 'something_invalid');

		// This post has 2 URLs to check
		$post_id = $this->factory->post->create();
		add_post_meta($post_id, 'socialcount_url_data', 'canonical-not-set/service-2.json');

		$this->updater->sources->FacebookUpdater->expects($this->exactly(6))->method('getURL');
		$this->updater->sources->TwitterUpdater->expects($this->exactly(6))->method('getURL');
		$this->updater->sources->LinkedInUpdater->expects($this->exactly(6))->method('getURL');

		// Only one of these should go through
		$this->updater->updatePostStats($post_id, false, 'canonical-not-set/service-1.json'); // 2 requests
		$this->updater->updatePostStats($post_id, false, 'canonical-not-set/service-1.json'); // 0 requests
		$this->updater->updatePostStats($post_id, false, 'canonical-not-set/service-1.json'); // 0 requests

		// Time travel to the future
		$ttl = $this->smt->options['smt_options_ttl_hours'] * HOUR_IN_SECONDS;
		$new_time = $this->getLocalTime() + ($ttl) + 1;
		$this->setTime($new_time);

		$this->updater->updatePostStats($post_id, false, 'canonical-not-set/service-1.json'); // 2 requests -- invalid value simply means regular TTL

		// Time travel to the future
		$ttl = $this->smt->options['smt_options_ttl_hours'] * HOUR_IN_SECONDS;
		$new_time = $this->getLocalTime() + ($ttl * 4) + 1;
		$this->setTime($new_time);

		// Now try again, it should go through
		$this->updater->updatePostStats($post_id, false, 'canonical-not-set/service-1.json'); // 2 requests
	}

	// 1. When multiplier is set to 1, should always update if time elapsed
	function test_performance_option_configuration_1() {

		$this->smt->set_smt_option('alt_url_ttl_multiplier', '1');

		// This post has 2 URLs to check
		$post_id = $this->factory->post->create();
		add_post_meta($post_id, 'socialcount_url_data', 'canonical-not-set/service-2.json');

		$this->updater->sources->FacebookUpdater->expects($this->exactly(4))->method('getURL');
		$this->updater->sources->TwitterUpdater->expects($this->exactly(4))->method('getURL');
		$this->updater->sources->LinkedInUpdater->expects($this->exactly(4))->method('getURL');

		// Fire update twice, should update total of 4 URLs
		$this->updater->updatePostStats($post_id, false, 'canonical-not-set/service-1.json'); // 2 requests

		// Time travel to the future
		$ttl = $this->smt->options['smt_options_ttl_hours'] * HOUR_IN_SECONDS;
		$new_time = $this->getLocalTime() + ($ttl * 5) + 1;
		$this->setTime($new_time);

		$this->updater->updatePostStats($post_id, false, 'canonical-not-set/service-1.json'); // 2 requests
		
	}

	// 2. It should not allow consecutive calls
	function test_performance_option_configuration_2() {
		$this->smt->set_smt_option('alt_url_ttl_multiplier', '1');

		// This post has 2 URLs to check
		$post_id = $this->factory->post->create();
		add_post_meta($post_id, 'socialcount_url_data', 'canonical-not-set/service-2.json');

		$this->updater->sources->FacebookUpdater->expects($this->exactly(2))->method('getURL');
		$this->updater->sources->TwitterUpdater->expects($this->exactly(2))->method('getURL');
		$this->updater->sources->LinkedInUpdater->expects($this->exactly(2))->method('getURL');

		// Fire update twice, should update total of 2 URLs because of TTL
		$this->updater->updatePostStats($post_id, false, 'canonical-not-set/service-1.json');
		$this->updater->updatePostStats($post_id, false, 'canonical-not-set/service-1.json');
		$this->updater->updatePostStats($post_id, false, 'canonical-not-set/service-1.json');
		$this->updater->updatePostStats($post_id, false, 'canonical-not-set/service-1.json');
		$this->updater->updatePostStats($post_id, false, 'canonical-not-set/service-1.json');
		$this->updater->updatePostStats($post_id, false, 'canonical-not-set/service-1.json');
	}

	

	// 3. When set to 5, it should allow the call through after the amount of time has passed but not before
	function test_performance_option_configuration_3() {
		$this->smt->set_smt_option('alt_url_ttl_multiplier', '5');

		// This post has 2 URLs to check
		$post_id = $this->factory->post->create();
		add_post_meta($post_id, 'socialcount_url_data', 'canonical-not-set/service-2.json');

		$this->updater->sources->FacebookUpdater->expects($this->exactly(5))->method('getURL');
		$this->updater->sources->TwitterUpdater->expects($this->exactly(5))->method('getURL');
		$this->updater->sources->LinkedInUpdater->expects($this->exactly(5))->method('getURL');

		// Only one of these should go through
		$this->updater->updatePostStats($post_id, false, 'canonical-not-set/service-1.json'); // 2 requests
		$this->updater->updatePostStats($post_id, false, 'canonical-not-set/service-1.json'); // 0 requests
		$this->updater->updatePostStats($post_id, false, 'canonical-not-set/service-1.json'); // 0 requests

		// Time travel to the future
		$ttl = $this->smt->options['smt_options_ttl_hours'] * HOUR_IN_SECONDS;
		$new_time = $this->getLocalTime() + ($ttl) + 1;
		$this->setTime($new_time);

		$this->updater->updatePostStats($post_id, false, 'canonical-not-set/service-1.json'); // 1 requests

		// Time travel to the future
		$ttl = $this->smt->options['smt_options_ttl_hours'] * HOUR_IN_SECONDS;
		$new_time = $this->getLocalTime() + ($ttl * 4) + 1;
		$this->setTime($new_time);

		// Now try again, it should go through
		$this->updater->updatePostStats($post_id, false, 'canonical-not-set/service-1.json'); // 2 requests
	}	

	// 4. Manual update should skip TTL. 
	function test_performance_option_configuration_4() {
		$post_id = $this->factory->post->create();
	}


}

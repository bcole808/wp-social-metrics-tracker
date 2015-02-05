<?php
/************************************
* In this test suite, you must:
*
* Call $FacebookUpdater->setParams(ID, 'path_to_sample.json');
*
* And then it will fetch that data when running the test. 
*************************************/

class MetricUpdaterAltURLTests extends WP_UnitTestCase {

	private $updater;
	private $correct_alt_data;

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
		$smt = new SocialMetricsTracker();
		$smt->init();

		$this->updater = $this->getMockBuilder('\MetricsUpdater')
			->setConstructorArgs(array($smt))
		    ->setMethods(array('isValidURL'))
		    ->getMock();

		$this->updater->expects($this->any())
		    ->method('isValidURL')
		    ->will($this->returnValue(true));


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
			    	return ($status) ? $this->get_sample_data($path) : false;
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

	// Opens and returns the sample data file, or false
	function get_sample_data($file) {
		$result = @file_get_contents(dirname(__FILE__).'/sample-data/'.$file);
		return ($result) ? $result : false;
	}

	// Asserts that the social data saved in the alt_data field matches $data
	function verify_correct_alt_data($post_id, $data) {
		$expected_result = (is_array($data)) ? $data : $this->correct_alt_data[$data];

		// Find the matching meta key
		$alt_data = get_post_meta($post_id, 'socialcount_alt_data');
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
		$alt_data = get_post_meta($post_id, 'socialcount_alt_data');

		foreach ($alt_data as $item) {
			if ($item['permalink'] == $alt_url) {
				delete_post_meta($post_id, 'socialcount_alt_data', $item);
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
			    	return ($status) ? $this->get_sample_data($path) : false;
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
		add_post_meta($post_id, 'socialcount_alt_data', null);
		add_post_meta($post_id, 'socialcount_alt_data', true);
		add_post_meta($post_id, 'socialcount_alt_data', false);
		add_post_meta($post_id, 'socialcount_alt_data', 99999);
		add_post_meta($post_id, 'socialcount_alt_data', -12.2);
		add_post_meta($post_id, 'socialcount_alt_data', 'fooBarBadData');
		add_post_meta($post_id, 'socialcount_alt_data', array(10, 20, 'foo'));
		add_post_meta($post_id, 'socialcount_alt_data', 'canonical-set/service-3.json'); // note: this should get filtered as an invalid URL in this test

		$this->updater->updatePostStats($post_id, 'canonical-set/service-3.json'); // note: this should be allowed in this test
		$this->verify_correct_primary_data($post_id, 'canonical-set/service-3.json');

		$meta = get_post_meta($post_id, 'socialcount_alt_data');
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
		update_post_meta($post_id, 'socialcount_alt_data', 'canonical-set/service-1.json');
		$this->updater->updatePostStats($post_id, 'canonical-set/service-1.json');

		$this->verify_correct_alt_data($post_id, 'canonical-set/service-1.json');
		$this->verify_correct_primary_data($post_id, 'canonical-set/service-1.json');


		// 2. Numberes are added correctly
		$post_id = $this->factory->post->create();

		add_post_meta($post_id, 'socialcount_alt_data', 'canonical-not-set/service-1.json');
		add_post_meta($post_id, 'socialcount_alt_data', 'canonical-not-set/service-2.json');
		add_post_meta($post_id, 'socialcount_alt_data', 'canonical-not-set/service-3.json');
		add_post_meta($post_id, 'socialcount_alt_data', 'canonical-not-set/service-4.json');

		$this->updater->updatePostStats($post_id, 'canonical-not-set/service-1.json');

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

		add_post_meta($post_id, 'socialcount_alt_data', 'canonical-set/service-4.json');
		add_post_meta($post_id, 'socialcount_alt_data', 'canonical-set/service-4.json');
		add_post_meta($post_id, 'socialcount_alt_data', 'canonical-set/service-4.json');

		$this->updater->updatePostStats($post_id, 'canonical-set/service-4.json');

		$this->assertTrue(count(get_post_meta($post_id, 'socialcount_alt_data')) === 1, 'too many fields saved');
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

		add_post_meta($post_id, 'socialcount_alt_data', 'canonical-set/service-2.json');

		$this->updater->updatePostStats($post_id, 'canonical-set/service-1.json');

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

		add_post_meta($post_id, 'socialcount_alt_data', 'canonical-set/service-2.json');
		add_post_meta($post_id, 'socialcount_alt_data', 'canonical-set/service-3.json');
		add_post_meta($post_id, 'socialcount_alt_data', 'canonical-set/service-4.json');

		$this->updater->updatePostStats($post_id, 'canonical-set/service-1.json');

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
		$this->updater->updatePostStats($post_id, 'canonical-set/service-1.json');
		$this->verify_correct_primary_data($post_id, $expected_data);

		// 4. When a service goes completely offline, no data is lost
		$this->available['FacebookUpdater'] = false;
		$this->updater->updatePostStats($post_id, 'canonical-set/service-1.json');
		$this->verify_correct_primary_data($post_id, $expected_data);

		// 5. And if it is intermittent, no data is lost
		$this->available['FacebookUpdater'] = array(true, false, false, false);
		$this->updater->updatePostStats($post_id, 'canonical-set/service-1.json');
		$this->verify_correct_primary_data($post_id, $expected_data);

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
		add_post_meta($post_id, 'socialcount_alt_data', 'canonical-not-set/service-2.json');
		add_post_meta($post_id, 'socialcount_alt_data', 'canonical-not-set/service-3.json');
		add_post_meta($post_id, 'socialcount_alt_data', 'canonical-not-set/service-4.json');

		$this->updater->updatePostStats($post_id, 'canonical-not-set/service-1.json');

		$this->remove_meta_by_url($post_id, 'canonical-not-set/service-4.json');

		$this->updater->updatePostStats($post_id, 'canonical-not-set/service-1.json');

		$expected = $expected_total;
		$expected['socialcount_twitter'] = 163132; // Minus service-4

		$this->verify_correct_primary_data($post_id, $expected);
		
	}

}

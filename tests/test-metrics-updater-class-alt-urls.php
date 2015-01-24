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

	// DO BEFORE ALL TESTS
	function setUp() {
		parent::setUp();

		// Create an updater object
		$this->updater = new MetricsUpdater(new SocialMetricsTracker());

		// MOCK HTTP-RESOURCE-UPDATERS
		// =====================
		$updater_classes = array('FacebookUpdater', 'TwitterUpdater', 'LinkedInUpdater');

		// Create a mock object for each of the desired updater classes. 
		foreach ($updater_classes as $class_name) {
			$this->updater->sources->{$class_name} = $this->getMock($class_name, array('getURL'));
			$this->updater->sources->{$class_name}->expects($this->any())
			    ->method('getURL')
			    ->will($this->returnCallback(function() use ($class_name) {
			    	return $this->get_sample_data($this->updater->sources->{$class_name}->post_url);
			    })
			);
		}

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

	function test_example() {

		// print("\nHere is some social data: \n");
		// print_r($this->updater->sources->FacebookUpdater->sync(1, 'canonical-set/facebook-1.json'));

	}

	function test_updatePostStats() {

		// --------------------------------------------------
		// TESTS FOR WHEN ALT URLS ARE PRESENT


		// 1. Validation: Bad meta fields do not break it

		// 2. Validation: Duplicate meta fields do not break it

		// 3. Validation: Using the same URL as the primary URL does not break it

		// 4. Validation: Valid input, but returns zero shares should be removed. 

		// 5. Canonical URLs are not duplicate counted

		// 6. Non-canonical URLs are summed correctly

		// 7. If one or more services is offline, counting continues

		// 8. The TTL is obeyed?

	}
}

<?php
/***************************************************
* This class attaches an updater function which runs when
* the Shared Count plugin updates an individual post.
***************************************************/

class GoogleAnalyticsUpdater {

	public $is_ready = false;
	private $data; // stores our session data

	public function __construct() {

		$this->load_gapi_data();

		// hook into post updater
		add_action('social_metrics_data_sync', array($this, 'sync_data'), 10, 2);

		// Display notices on plugin page
		add_action('admin_notices', array($this, 'notices'));

		if ($_POST && $_GET['section'] == 'gapi') {

			// Multisite mode
			if (isset($_POST['multisite_mode'])) 		$this->set_multisite_mode($_POST['multisite_mode']);

			// GAPI Credentials
			if (isset($_POST['gapi_client_id'])) 		$this->data['gapi_client_id'] 		= $_POST['gapi_client_id'];
			if (isset($_POST['gapi_client_secret'])) 	$this->data['gapi_client_secret'] 	= $_POST['gapi_client_secret'];
			if (isset($_POST['gapi_developer_key'])) 	$this->data['gapi_developer_key'] 	= $_POST['gapi_developer_key'];

			// Profile selection
			if (isset($_POST['gapi_profile_id'])) {

				$elems = explode(',', $_POST['gapi_profile_id'], 2);
				$this->data['gapi_profile_id']   = $elems[0];
				$this->data['gapi_profile_name'] = $elems[1];

			}

			// Save to DB
			$this->update_gapi_data();

		}

	}

	public function notices() {
		if (!current_user_can('manage_options')) return false;

		$screen = get_current_screen();

		if (!in_array($screen->base, array('social-metrics_page_social-metrics-tracker-settings', 'toplevel_page_social-metrics-tracker', 'social-metrics-tracker_page_social-metrics-tracker-debug'))) {
			return false;
		}

		if (!isset($this->data['gapi_client_id'])) {
			return false;
		}

		if (strlen($this->data['gapi_client_id']) > 1 && strlen($this->data['gapi_client_secret']) > 1 && strlen($this->data['gapi_developer_key']) > 1 && (isset($this->data['data_is_flowing']) && $this->data['data_is_flowing'] === false)) {

			$message = '<h3 style="margin-top:0;">Google Analytics connection not activate</h3> Please <a href="admin.php?page=social-metrics-tracker-settings&section=gapi">visit the setup wizard</a> to complete necessary steps, or <a href="admin.php?page=social-metrics-tracker-settings&section=gapi&go_to_step=1">disable Google Analytics integration</a>. <ul>';

			printf( '<div class="error"> <p> %s </p> </div>', $message);
		}

	}

	// Runs on every post sync
	public function sync_data($post_id, $post_url) {

		$this->connect();

		// Validation
		if (!$this->is_ready) return false;
		if (!isset($post_id) || !isset($post_url))  return;

		// Get pageviews
		$value = $this->get_pageviews($post_url);

		// Save data
		if ($value > 0) {
			update_post_meta($post_id, 'ga_pageviews', $value);
		}

	}

	// Turns multisite mode on or off. ***Changing this disrupts all saved data.
	public function set_multisite_mode($bool) {
		if (is_multisite()) {
			update_site_option( 'smt_gapi_multisite_mode', (bool) $bool );
			$this->multisite_mode = (bool) $bool;
		}
	}

	/***************************************************
	* Save the current data array to the database
	***************************************************/
	public function update_gapi_data() {

		if ($this->multisite_mode) {
			update_site_option( 'smt_gapi_data', $this->data );
		} else {
			update_option( 'smt_gapi_data', $this->data );
		}

	}

	/***************************************************
	* Load the data array from the database
	***************************************************/
	public function load_gapi_data() {

		if (!isset($this->multisite_mode)) {
			$this->multisite_mode = (is_multisite()) ? (bool) get_site_option( 'smt_gapi_multisite_mode' ) : false;
		}

		if ($this->multisite_mode) {
			$this->data = get_site_option( 'smt_gapi_data', array() );
		} else {
			$this->data = get_option( 'smt_gapi_data', array() );
		}

	}

	// Get function
	public function get_gapi_keys() {
		$values['gapi_client_id'] 		= isset($this->data['gapi_client_id'])     ? $this->data['gapi_client_id']     : null;
		$values['gapi_client_secret'] 	= isset($this->data['gapi_client_secret']) ? $this->data['gapi_client_secret'] : null;
		$values['gapi_developer_key'] 	= isset($this->data['gapi_developer_key']) ? $this->data['gapi_developer_key'] : null;

		return $values;
	}

	// Get function
	public function get_profile_name() {
		return $this->data['gapi_profile_name'];
	}

	// Returns true if can sync, false if not.
	public function can_sync() {
		return (isset($this->data['gapi_profile_id']) && strlen($this->data['gapi_profile_id']) > 0 && isset($this->data['gapi_token']));
	}

	// Geenrate the URL for auth login
	public function get_oauth_login_url() {
		$this->connect();
		$this->gapi->setScopes(array('https://www.googleapis.com/auth/analytics.readonly'));
		$this->gapi->setAccessType('offline');
		$this->gapi->setApprovalPrompt('force');
		return $this->gapi->createAuthUrl();
	}

	public function connect() {

		if (isset($this->gapi)) return $this->is_ready; // only one connection allowed


		$this->redirect_uri = admin_URL('/admin.php?page=social-metrics-tracker-settings&section=gapi');

		// Include the Google API Library
		set_include_path(plugin_dir_path( dirname(__FILE__)) . 'lib/');
		require_once('Google/Client.php');
		require_once 'Google/Service/Analytics.php';
		restore_include_path();

		try {

			/*
			* Step 1: Connection to the Google API
			*************/
			$this->step = 1;

			// Do we have the necessary credentials?
			if (!$this->data)                       return false;
			if (!$this->data['gapi_client_id'])		return false;
			if (!$this->data['gapi_client_secret'])	return false;
			if (!$this->data['gapi_developer_key']) return false;

			// Configure the Google API
			$this->gapi = new Google_Client();
			$this->gapi->setApplicationName(	get_bloginfo('name') . ' Social Metrics Tracker');
			$this->gapi->setClientId(			$this->data['gapi_client_id']);
			$this->gapi->setClientSecret(		$this->data['gapi_client_secret']);
			$this->gapi->setDeveloperKey(		$this->data['gapi_developer_key']);
			$this->gapi->setRedirectUri(		$this->redirect_uri);

			$this->analytics = new Google_Service_Analytics($this->gapi);

			/*
			* Step 2: OAuth token verification
			*************/
			$this->step = 2;

			// Did we just receive an authorization code?
			if (isset($_GET['code']) && $_GET['code'] && !$this->data['gapi_token']) {
				$this->gapi->authenticate($_GET['code']);
				$this->data['gapi_token'] = serialize($this->gapi->getAccessToken());

				$this->update_gapi_data(); // save to DB
			}

			// We really do need that token...
			if (!isset($this->data['gapi_token'])) return false;

	  		$this->gapi->setAccessToken(unserialize($this->data['gapi_token']));

	  		$smt_gapi_token_obj = json_decode(unserialize($this->data['gapi_token']));

	  		// Refresh the token if needed
	  		if (($smt_gapi_token_obj->created + $smt_gapi_token_obj->expires_in) < time()) {
		  		$this->gapi->refreshToken($smt_gapi_token_obj->refresh_token);
		  		$this->data['gapi_token'] = serialize($this->gapi->getAccessToken());
		  		$this->update_gapi_data(); // save to DB
		  	}

		  	/*
		  	* Step 3: Link to Analytics reporting profile
		  	*************/
		  	$this->step = 3;

			// Check for linked profile
			if (!$this->data['gapi_profile_id']) return false;

			/*
			* Ready to roll!
			*************/
			$this->step = 4;
			$this->is_ready = true;

			$this->data['data_is_flowing'] = true;
	  		$this->update_gapi_data(); // save to DB

			return true;

		} catch(Exception $e) {
			print('<span style="color:red">An error occured with the Google API Library: '.$e->getMessage().'</span>');
			print('Please <a href="'.add_query_arg('go_to_step', '1').'">re-configure your settings</a> from step 1.');
		}

	}

	// Get the number of pageviews for a given URL.
	public function get_pageviews($full_url) {

		if (strlen($full_url) < 1) 				return false;
		if (!$this->data['gapi_profile_id']) 	return false;
		if (!$this->data['gapi_token']) 		return false;

		$url_parts = parse_url($full_url);
		$url_path = rtrim($url_parts['path'], '/');

		try {

			// Note: This query needs to be improved!
			$options = array(
				'dimensions'	=> 'ga:pagePath',
				'sort' 			=> '-ga:pageviews',
				'filters'		=> 'ga:pagePath=@'.$url_path, // starts with this string
				'max-results'	=> '1'
			);

			$result = $this->analytics->data_ga->get(
				'ga:' . $this->data['gapi_profile_id'], // profile id
				'2005-01-01', // start
				date('Y-m-d'), // end
				'ga:pageviews',
				$options
			);

			$single_result = $result->getRows();
			$single_result = $single_result[0][1];

			return ($single_result) ? $single_result : false;

		} catch (Exception $e) {
			// There was an error querying the Google Analytics service.

			$this->data['data_is_flowing'] = false;
			$this->update_gapi_data();

			if ($_GET['smt_sync_now']) die( $e->getMessage() ); // Only use for debug

			return false;
		}
	}

	public function go_to_step($num) {
		switch ($num) {
			case 1:
				// Remove API Keys
				unset($this->data['gapi_client_id']);
				unset($this->data['gapi_client_secret']);
				unset($this->data['gapi_developer_key']);

				// Fall through to next case
			case 2:
				// Remove OAUTH credentials
				unset($this->data['gapi_token']);

				// Fall through to next case

			case 3:
				// Remove selected profile
				unset($this->data['gapi_profile_id']);
				unset($this->data['gapi_profile_name']);
				break;

			default:
				break;
		}

		$this->update_gapi_data();

		header("Location: ".remove_query_arg( 'go_to_step' ));
	}


	// Return an array with all of the profile IDs associated with the current Analytics account.
	public function get_profile_list() {
		$profiles = array();

		$result = $this->analytics->management_accounts->listManagementAccounts();
		$accounts = $result->items;
		foreach ($accounts as $account) {

		    $result = $this->analytics->management_webproperties->listManagementWebproperties($account->id);
		    $webProperties = $result->items;

		    foreach ($webProperties as $webProperty) {

		        $result = $this->analytics->management_profiles->listManagementProfiles($account->id, $webProperty->id);
		        $items = $result->items;
		        foreach ($items as $item) array_push($profiles, array('id'=> $item->id,'name'=> $item->name, 'parent'=>$account->name));

		    }
		}

		return $profiles;
	}

}

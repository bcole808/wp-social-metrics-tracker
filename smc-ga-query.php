<?php
global $smc_options;

define('GAPI_CLIENT_ID', $smc_options['socialinsight_ga_client_id']);
define('GAPI_CLIENT_SECRET', $smc_options['socialinsight_ga_client_secret']);
define('GAPI_DEVELOPER_KEY', $smc_options['socialinsight_ga_developer_key']);
define('GAPI_REDIRECT_URI', admin_URL('/admin.php?page=smc-social-insight'));
define('GAPI_APPLICATION_NAME', get_bloginfo('name'));

require_once 'lib/google-api-php-client/Google_Client.php';
require_once 'lib/google-api-php-client/contrib/Google_AnalyticsService.php';

function smc_gapi_loginout() {

	$client = new Google_Client();
	$client->setApplicationName(GAPI_APPLICATION_NAME);

	// Visit https://code.google.com/apis/console?api=analytics to generate your
	// client id, client secret, and to register your redirect uri.
	$client->setClientId(GAPI_CLIENT_ID);
	$client->setClientSecret(GAPI_CLIENT_SECRET);
	$client->setRedirectUri(GAPI_REDIRECT_URI);
	$client->setDeveloperKey(GAPI_DEVELOPER_KEY);
	$service = new Google_AnalyticsService($client);



	// If we are logging out

	if (isset($_GET['logout'])) {
	    delete_site_option('smc_ga_token');
	    delete_option('smc_ga_profile');
	}

	// If we are receiving an auth code from Google
	if (isset($_GET['code'])) {
	    $client->authenticate();
	    update_site_option('smc_ga_token', serialize($client->getAccessToken()));

	    // $redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
	    // header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
	    // return true;
	}

	$smc_ga_token = unserialize(get_site_option('smc_ga_token'));
	$smc_ga_profile = unserialize(get_option('smc_ga_profile'));

	// If the API details have not been entered
	if (strlen(GAPI_CLIENT_ID) <= 0 || strlen(GAPI_CLIENT_SECRET) <= 0 || strlen(GAPI_DEVELOPER_KEY) <= 0) {
		printf( '<div class="error"> <p> %s </p> </div>', "Please <a class='login' href='options-general.php?page=smc_settings'>add your Google API account detailes.</a>" );
		return false;
	}

	// No token found, display login. 
	if (strlen($smc_ga_token) <= 0) {
		
		$authUrl = $client->createAuthUrl();
		printf( '<div class="error"> <p> %s </p> </div>', "Please <a class='login' href='$authUrl'>sign in to Google Analytics.</a> Social Insights will not receive data from Google Analytics until you do." );
		return false;
	}

	// No profile found, display select page. 
	if (strlen($smc_ga_profile['id']) <= 0) {

		if (isset($_GET['profile_id'])) {

			$profile_array = array('id'=>$_GET['profile_id'], 'name'=>urldecode($_GET['profile_name']));

			update_option('smc_ga_profile', serialize($profile_array));
			$smc_ga_profile = unserialize(get_option('smc_ga_profile'));

		} else {

			// Authenticate
			try {
		  		$client->setAccessToken($smc_ga_token);

		  		$smc_ga_token_obj = json_decode($smc_ga_token);
		  		$refresh_token = $smc_ga_token_obj->refresh_token;

		  		if (($smc_ga_token_obj->created + $smc_ga_token_obj->expires_in) < time()) {
			  		$client->refreshToken($refresh_token);
			  		update_site_option('smc_ga_token', serialize($client->getAccessToken()));
			  	}
			} catch (Google_AuthException $e) {
				// The authentication failed!
				// We delete the failed token and force the user to re-auth. 
				echo "Authentication error.";
				delete_site_option('smc_ga_token');
				delete_option('smc_ga_profile');
				echo $e->getMessage();
			}

			$message = "<h3>Select the profile associated with this Wordpress blog:</h3>";
			$profiles = getProfilesArray($service);
			foreach ($profiles as $profile) {
				$message .= $profile['parent'].': <a href="admin.php?page=smc-social-insight&profile_id='.$profile['id'].'&profile_name='.urlencode($profile['name']).'">'.$profile['name'] . ' ('.$profile['id'].')</a><br>';
			}
			printf( '<div class="error"> <p> %s </p> </div>', $message );
			return false;
		}
	}


	// Token found
	if (strlen($smc_ga_token) > 1 && current_user_can('manage_options')) {

	    //$url_parts = parse_url(home_url());
	    //$url_path = $url_parts['path'] . '/';

	    $logout_url = add_query_arg(array('logout'=>1), 'admin.php?page=smc-social-insight');
	    echo '<br>Google API is connected to '.$smc_ga_profile['name'].' <a href="'.$logout_url.'">Disconnect Google Analytics</a>';

	} 

}

// Return an array with all of the profile IDs associated with the current Analytics account. 
function getProfilesArray($analytics) {
	
	$return = array();

	$result = $analytics->management_accounts->listManagementAccounts();
	$accounts = $result->items;
	foreach ($accounts as $account) {
	    // print "Found an account with an ID of {$account->id} and a name of {$account->name}\n"; 

	    $accountId = $account->id;
	    $result = $analytics->management_webproperties->listManagementWebproperties($accountId);
	    $webProperties = $result->items;
	     
	    foreach ($webProperties as $webProperty) {
	        // print "Found a web property for the site {$webProperty->websiteUrl}, with an ID of {$webProperty->id} and a name of {$webProperty->name}<br>"; 

	        $webPropertyId = $webProperty->id;
	        $result = $analytics->management_profiles->listManagementProfiles($accountId, $webPropertyId);
	        $profiles = $result->items;
	         
	        foreach ($profiles as $profile) {
	            // print "Found a profile with an ID of {$profile->id} and a name of {$profile->name}<br>"; 
	            array_push($return, array('id'=> $profile->id,'name'=> $profile->name, 'parent'=>$account->name));
	        }
	         
	    }
	}

	return $return;
}

function smc_queue_length() {

	$queue = array();
	$cron = _get_cron_array();
	foreach ( $cron as $timestamp => $cronhooks ) {
		foreach ( (array) $cronhooks as $hook => $events ) {
			foreach ( (array) $events as $key => $event ) {
				if ($hook == 'smc_update_single_post') {
					array_push($queue, $cron[$timestamp][$hook][$key]['args'][0]);
				}
			}
		}
	}

	$count = count($queue);
	if ($count >= 1) {
		$label = ($count >=2) ? ' items' : ' item';
		printf( '<div class="updated"> <p> %s </p> </div>',  'Currently updating <b>'.$count . $label.'</b> with the most recent social and analytics data...');
	}

}


function smc_ga_getPageviewsByURL($full_url, $ga_token = '') {
	
	$url_parts = parse_url($full_url);
	$url_path = $url_parts['path'];

	$smc_ga_profile = unserialize(get_option('smc_ga_profile'));

	$client = new Google_Client();
	$client->setApplicationName(GAPI_APPLICATION_NAME);

	// Visit https://code.google.com/apis/console?api=analytics to generate your
	// client id, client secret, and to register your redirect uri.
	$client->setClientId(GAPI_CLIENT_ID);
	$client->setClientSecret(GAPI_CLIENT_SECRET);
	$client->setRedirectUri(GAPI_REDIRECT_URI);
	$client->setDeveloperKey(GAPI_DEVELOPER_KEY);
	$service = new Google_AnalyticsService($client);

	if (strlen($ga_token) > 1) {
		//echo "<br>Restored saved token from the database!<br>";
		try {

	  		$client->setAccessToken($ga_token);

	  		$ga_token_obj = json_decode($ga_token);
	  		$refresh_token = $ga_token_obj->refresh_token;

	  		if (($ga_token_obj->created + $ga_token_obj->expires_in) < time()) {
		  		$client->refreshToken($refresh_token);
		  		update_site_option('smc_ga_token', serialize($client->getAccessToken()));
		  	}

		} catch (Google_AuthException $e) {
			// The authentication failed!
			// We delete the failed token and force the user to re-auth. 
			delete_site_option('smc_ga_token');
			delete_option('smc_ga_profile');

			echo $e->getMessage();
		}
	} else {
		return false;
	}

	if ($client->getAccessToken()) {
		try {

			//echo "token: ".print_r($client->getAccessToken());

			//echo '<hr>';

			$options = array(
				'dimensions'	=> 'ga:pagePath',
				'sort' 			=> '-ga:pageviews',
				'filters'		=> 'ga:pagePath=='.$url_path,
				'max-results'	=> '1'
			);

			$result = $service->data_ga->get(
				'ga:' . $smc_ga_profile['id'], // profile id
				'2005-01-01', // start
				date('Y-m-d'), // end
				'ga:pageviews',
				$options
			); 

			//print_r($result->getRows());
			//echo '<hr><hr>';
			$single_result = 0;
			$single_result = $result->getRows();
			$single_result = $single_result[0][1];

			if (!$single_result) $single_result = 0;

			
			//echo "Database token updated.";

			return ($single_result);

		} catch (Exception $e) {
			delete_site_option('smc_ga_token');
			delete_option('smc_ga_profile');
			echo $e->getMessage();
		}

	} else {
		$authUrl = $client->createAuthUrl();
		print "<a class='login' href='$authUrl'>Connect Me!</a>";
	}
}

// echo smc_ga_getPageviewsByURL('/happenings/');
?>
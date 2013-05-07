<?php

define('GAPI_CLIENT_ID', "526667588909.apps.googleusercontent.com");
define('GAPI_CLIENT_SECRET', "1TrAYMW2XgNng6C_I_bf8YSE");
define('GAPI_DEVELOPER_KEY', "AIzaSyBfagnRehDAvx6wnArifJV5x01X96T3m5M");
define('GAPI_REDIRECT_URI', admin_URL('/admin.php?page=smc-social-insight'));
define('GAPI_APPLICATION_NAME', get_bloginfo('name'));
define('GAPI_PROFILE_ID', 58596075); // 58596075 = blogs.chapman.edu


function smc_gapi_loginout() {

	if (isset($_GET['logout'])) {
	    delete_site_option('smc_ga_token');
	}

	// session_start();
	require_once 'lib/google-api-php-client/Google_Client.php';
	require_once 'lib/google-api-php-client/contrib/Google_AnalyticsService.php';

	$client = new Google_Client();
	$client->setApplicationName(GAPI_APPLICATION_NAME);

	// Visit https://code.google.com/apis/console?api=analytics to generate your
	// client id, client secret, and to register your redirect uri.
	$client->setClientId(GAPI_CLIENT_ID);
	$client->setClientSecret(GAPI_CLIENT_SECRET);
	$client->setRedirectUri(GAPI_REDIRECT_URI);
	$client->setDeveloperKey(GAPI_DEVELOPER_KEY);
	$service = new Google_AnalyticsService($client);

	// Authorize if returning from server
	if (isset($_GET['code'])) {
	    $client->authenticate();
	    update_site_option('smc_ga_token', serialize($client->getAccessToken()));

	    $redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
	    header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
	    return true;
	}

	$smc_ga_token = unserialize(get_site_option('smc_ga_token'));


	// Token found, connect and test
	if (strlen($smc_ga_token) > 1) {

	    $url_parts = parse_url(home_url());
	    $url_path = $url_parts['path'] . '/';

	    $ga_pageviews = smc_ga_getPageviewsByURL($url_path,$smc_ga_token);
	    if ($ga_pageviews > 0) {
	        echo "Connected to ga API successfully. Returned $ga_pageviews views for ".$url_path;
	    }

	    $logout_url = add_query_arg('logout', 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
	    echo '<br><a href="'.$logout_url.'">Logout</a>';

	} else {
	// No token found, display login. 

		$authUrl = $client->createAuthUrl();
		printf( '<div class="error"> <p> %s </p> </div>', "Social Insights is not receiving data from Google Analytics. Please <a class='login' href='$authUrl'>sign in to Google Analytics.</a> " );
	}

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

	$url = add_query_arg(array('smc_schedule_full_update' => 1), 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
	echo "<a href='$url' class='button' onClick='return confirm(\"This will queue all posts for an update. This may take a long time depending on the number of posts and should only be done if data becomes out of sync. Are you sure?\")'>Synchronize all data now</a>";

}


function smc_ga_getPageviewsByURL($full_url, $ga_token = '') {
	
	$url_parts = parse_url($full_url);
	$url_path = $url_parts['path'];

	// session_start();
	require_once 'lib/google-api-php-client/Google_Client.php';
	require_once 'lib/google-api-php-client/contrib/Google_AnalyticsService.php';

	$client = new Google_Client();
	$client->setApplicationName(GAPI_APPLICATION_NAME);

	// Visit https://code.google.com/apis/console?api=analytics to generate your
	// client id, client secret, and to register your redirect uri.
	$client->setClientId(GAPI_CLIENT_ID);
	$client->setClientSecret(GAPI_CLIENT_SECRET);
	$client->setRedirectUri(GAPI_REDIRECT_URI);
	$client->setDeveloperKey(GAPI_DEVELOPER_KEY);
	$service = new Google_AnalyticsService($client);


	//echo "Token Data:<br>";
	//print_r(strlen($smc_ga_token));

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

			echo $e->getMessage();
		}
	} else {
		// there is no valid token


		// try {

		// 	$token = array(
		// 		"access_token" => "ya29.AHES6ZSe8SYY6rVyBOpom4WQRDnXbuT3NQgwwb9f1SMq1c8IDgvQ9w",
		// 		"token_type"=>"Bearer",
		// 		"expires_in"=>3600,
		// 		"refresh_token"=>"1\/G9AcbBD4yOzXgjM5P63n0_1N_Q2BepbV305P2Bqm3xU",
		// 		"created"=>1367598587
		// 	);

		//     $client->setAccessToken(json_encode($token));

	 //    } catch (Google_AuthException $e) {
	 //    	echo $e->getMessage();
	 //    }


	}

	if ($client->getAccessToken()) {
		try {

			//echo "token: ".print_r($client->getAccessToken());

			//echo '<hr>';

			$options = array(
				'dimensions'	=> 'ga:pagePath',
				'sort' 			=> '-ga:pageviews',
				'filters'		=> 'ga:pagePath=='.$url_path,
				'max-results'	=> '5'
			);

			$result = $service->data_ga->get(
				'ga:' . GAPI_PROFILE_ID, // profile id
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

			
			//echo "Database token updated.";

			return ($single_result);

		} catch (Exception $e) {
			delete_site_option('smc_ga_token');
			echo $e->getMessage();
		}

	} else {
		$authUrl = $client->createAuthUrl();
		print "<a class='login' href='$authUrl'>Connect Me!</a>";
	}
}

// echo smc_ga_getPageviewsByURL('/happenings/');
?>
<?php
// Include and create a new WordPressSettingsFramework
require_once( 'lib/wp-settings-framework.php' );

class socialMetricsSettings {

	private $wpsf;

	private $facebook_auth_error;

	function __construct($smt) {

		$this->smt = $smt;

		add_action( 'admin_menu', array(&$this, 'admin_menu'), 99 );

		$pages = array('social-metrics-tracker', 'social-metrics-tracker-export', 'social-metrics-tracker-settings');

		if (isset($_REQUEST['page']) && in_array($_REQUEST['page'], $pages)) {
			$this->section = (isset($_REQUEST['section'])) ? $_REQUEST['section'] : 'general';
			$this->wpsf = new WordPressSettingsFramework( plugin_dir_path( __FILE__ ) .'settings/smt-'.$this->section.'.php', 'smt' );
		}

	}

	function admin_menu() {

		add_submenu_page('social-metrics-tracker', 'Social Metrics Tracker Configuration', 'Configuration', 'manage_options', 'social-metrics-tracker-settings',  array($this, 'render_settings_page'));
	}

	// Display list of all and current option pages
	function nav_links() {


		$args = array(
			'menu_items' => array(
				array(
					'slug'    => 'general',
					'label'   => 'General Settings',
					'url'     => 'admin.php?page=social-metrics-tracker-settings',
					'current' => $this->section == 'general'
				),
				array(
					'slug'    => 'connections',
					'label'   => 'API Connection Settings',
					'url'     => add_query_arg('section', 'connections'),
					'current' => $this->section == 'connections'
				),
				array(
					'slug'    => 'gapi',
					'label'   => 'Google Analytics Setup',
					'url'     => add_query_arg('section', 'gapi'),
					'current' => $this->section == 'gapi'
				),
				array(
					'slug'    => 'urls',
					'label'   => 'Advanced Domain / URL Setup',
					'url'     => add_query_arg('section', 'urls'),
					'current' => $this->section == 'urls'
				),
			)
		);

		print($this->smt->renderTemplate('settings-nav', $args));
	}


	function render_settings_page() { 

		switch ($this->section) {
			case 'gapi':
				$this->smt->updater->setupDataSources();
				$this->gapi = $this->smt->updater->GoogleAnalyticsUpdater;

				if (isset($_GET['go_to_step']) && $_GET['go_to_step']) $this->gapi->go_to_step($_GET['go_to_step']);

				break;

			case 'connections':
				if (count($_POST) > 0) {
					$this->process_connections_form();
				}
				break;

			case 'urls':
				if (count($_POST) > 0) {
					$this->process_urls_form();
				}

				break;

			case 'test':
				break;

			default:
				if (count($_POST) > 0) {
					$this->process_general_form();
				}

				break;
		}

		?>
		<div class="wrap">
			<h2>Social Metrics Tracker Configuration</h2>
			<?php $this->nav_links(); ?>
			<?php call_user_func(array($this, $this->section.'_section')); ?>

		</div>
	<?php
	}


	// Render the general settings page
	function general_section() {
		$this->wpsf->settings('');
	}

	// Saves the general settings page
	function process_general_form() {
		if (!isset($_POST) || count($_POST) == 0) return;
		$this->smt->merge_smt_options($_POST['smt_settings']);
	}

	function connections_section() {

		$args = array(
			'facebook_public_checked' => checked('public', $this->smt->get_smt_option('connection_type_facebook'), false),
			'facebook_graph_checked'  => checked('graph',  $this->smt->get_smt_option('connection_type_facebook'), false),
			'facebook_access_token_valid' => strlen($this->smt->get_smt_option('facebook_access_token')) > 0,
			'facebook_auth_error' => $this->facebook_auth_error,
			'fb_app_id'     => isset($_POST['fb_app_id']) ? $_POST['fb_app_id'] : '',
			'fb_app_secret' => isset($_POST['fb_app_secret']) ? $_POST['fb_app_secret'] : '',
		);

		print($this->smt->renderTemplate('settings-connections', $args));
	}

	function process_connections_form() {
		if (!isset($_POST) || count($_POST) == 0) return;

		// Save FB connection type
		$this->smt->set_smt_option('connection_type_facebook', $_POST['connection_type_facebook']);

		// Process token delete request
		if ($_POST['action'] == 'Delete saved access token') {
			$this->smt->delete_smt_option('facebook_access_token');
		}

		// Generate Access Token
		if ($_POST['action'] == 'Save Changes' && ($_POST['fb_app_id'] || $_POST['fb_app_secret']) ) {

			// Need this to verify token
			$fb = new FacebookGraphUpdater();

			if (strlen($_POST['fb_app_id']) == 0) {
				$this->facebook_auth_error = 'You must enter an App ID';

			} elseif (strlen($_POST['fb_app_secret']) == 0) {
				$this->facebook_auth_error = 'You must enter an App Secret';

			} elseif (false === $access_token = $fb->requestAccessToken($_POST['fb_app_id'], $_POST['fb_app_secret'])) {
				$this->facebook_auth_error = 'The info you entered did not validate with Facebook.';

				if ($fb->error_message) {
					$this->facebook_auth_error .= ' Facebook said: '.$fb->error_message;
				}

			} else {
				// Save the valid access token!
				$this->smt->set_smt_option('facebook_access_token', $access_token);

			}

		} 

	}

	// Renders the Test Tool page
	function test_section() {

		$args = array();

		$test_id = (isset($_POST['smt-test-post-id'])) ? $_POST['smt-test-post-id'] : $this->getPopularPostID();

		if ($test_id > 0) {
			$args['test-data']        = $this->fetchTestData($test_id);
			$args['smt-test-post-id'] = $test_id;
		}

		print($this->smt->renderTemplate('settings-test', $args));
	}


	// Save the URL form
	function process_urls_form() {
		if (!isset($_POST) || count($_POST) == 0) return;

		if (isset($_POST['url_protocol'])) $this->smt->set_smt_option('url_protocol', $_POST['url_protocol']);


		// Domain rewrites
		if (isset($_POST['rewrite_change_to'])) {
			$url_rewrites = array(
				array(
					'rewrite_match_from'  => isset($_POST['rewrite_match_from']) ? $_POST['rewrite_match_from'] : get_home_url(),
					'rewrite_change_to'   => $_POST['rewrite_change_to'],
					'rewrite_before_date' => $_POST['rewrite_before_date']
				)
			);

			$this->smt->set_smt_option('url_rewrites', $url_rewrites);
		} else {
			$this->smt->delete_smt_option('url_rewrites');
		}

		// Performacne
		if (isset($_POST['alt_url_ttl_multiplier'])) {
			$this->smt->set_smt_option('alt_url_ttl_multiplier', $_POST['alt_url_ttl_multiplier']);
		}

	}

	// Render the URLs settings page
	function urls_section() {

		$args = array();

		// URL Protocol
		$url_protocol = $this->smt->get_smt_option('url_protocol');

		$args['protocol_options'] = array(
			array(
				'key'   => 'auto',
				'label' => 'Auto (let WordPress decide)',
				'selected' => ($url_protocol == 'auto')
			),
			array(
				'key'   => 'http',
				'label' => 'Check only http:// versions of URLs',
				'selected' => ($url_protocol == 'http')
			),
			array(
				'key'   => 'https',
				'label' => 'Check only https:// versions of URLs',
				'selected' => ($url_protocol == 'https')
			),
			array(
				'key'   => 'both',
				'label' => 'Check both http:// and https:// URLs',
				'selected' => ($url_protocol == 'both')
			),
		);


		// Domain migration
		$url_rewrites = $this->smt->get_smt_option('url_rewrites');

		$args['rewrite_match_from']  = $url_rewrites[0]['rewrite_match_from'] ? $url_rewrites[0]['rewrite_match_from'] : get_home_url();
		$args['rewrite_change_to']   = $url_rewrites[0]['rewrite_change_to'] ? $url_rewrites[0]['rewrite_change_to'] : '';
		$args['rewrite_before_date'] = $url_rewrites[0]['rewrite_before_date'] ? $url_rewrites[0]['rewrite_before_date'] : '';

		$args['example_match_from'] = get_permalink($this->getPopularPostID());


		// Performance Option
		$current_alt_ttl_option = ($this->smt->get_smt_option('alt_url_ttl_multiplier')) ? $this->smt->get_smt_option('alt_url_ttl_multiplier') : 5;

		$args['alt_url_ttl_multiplier_options'] = array(
			array(
				'key'   => '1',
				'label' => 'Same as main URL refresh rate (uses more network requests)',
				'selected' => ($current_alt_ttl_option == '1')
			),
			array(
				'key'   => '5',
				'label' => 'Slightly slower refresh rate',
				'selected' => ($current_alt_ttl_option == '5')
			),
			array(
				'key'   => '10',
				'label' => 'Much slower refresh rate (better server performance)',
				'selected' => ($current_alt_ttl_option == '10')
			)
		);
		
		print($this->smt->renderTemplate('settings-urls', $args));
	}

	// Returns some live data from social networks for a variety of URL filters
	function fetchTestData($post_id) {
		$permalink = $this->smt->updater->adjustProtocol(get_permalink($post_id));
		return $this->smt->updater->fetchPostStats($post_id, $permalink);
	}

	// Returns a post ID for the most shared post
	function getPopularPostID() {

		$args = array(
			'posts_per_page'   => 1,
			'offset'           => 0,
			'orderby'          => 'meta_value',
			'order'            => 'DESC',
			'meta_key'         => 'social_aggregate_score',
			'post_status'      => 'publish',
			'suppress_filters' => true 
		);
		$posts_array = get_posts( $args );

		return $posts_array[0]->ID;

	}

	// Render the Google API config page
	//
	// Beware: Messy PHP code within.....
	function gapi_section() {

		if (!$this->gapi) {
			print('<h1>Google Analytics Integration</h1><h3 style="color:red;">Sorry, Google Analytics integration can\'t be configured because this appears to be a development server. </h3>');
			return;
		}

		?>

		<h1>Google Analytics Integration</h1>
		<p>This plugin can sync and display page view data from Google Analytics. </p>
		<hr>

		<?php

		// Display progress navigator
		$this->gapi_wizard_progress();

		// Display a different HTML block per each wizard step
		switch ($this->gapi->step) {
			/*************************************************/
			case 1:
		  	/*************************************************/
			?>
			<form method="POST" action="admin.php?page=social-metrics-tracker-settings&section=gapi">

			<?php if (is_multisite() && current_user_can( 'manage_network' )) : ?>
				<h2>Multisite Configuration</h2>

				<table class="form-table">
				<tbody>
					<tr class="blue-box">
						<th scope="row">WP Multisite Mode</th>
						<td>
							<select name="multisite_mode">
							  <option value="0"<?php if (!$this->gapi->multisite_mode) echo ' selected'; ?>>Different credentials per blog (default)</option>
							  <option value="1"<?php if ($this->gapi->multisite_mode) echo ' selected'; ?>>Shared credentials for all blogs</option>
							</select>
							<p class="description">With <b>shared credentials</b>, all blogs on this WP Multisite network will automatically use the Google Analytics profile you set up to sync data and you will only need to complete this setup wizard once. With <b>different credentials</b>, each blog will need to complete this wizard but each blog will be able to link to a seperate Gooogle Analytics account.</p>
						</td>
					</tr>
				</tbody>
				</table>

			<?php endif; ?>

			<h2>Google Developer account info</h2>
			<p>Setting up a Google Developer account and entering your details here gives this plugin the ability to interact with the API for Google Services. This is different than your Google Analytics account, which will link to on the next page.</p>

			<ol>
				<li><a href="https://console.developers.google.com" target="_new">Set up a free Google Developer account</a></li>
				<li>Create a new project</li>
				<li>Enable the Google Analytics API for the project</li>
				<li>Under <b>APIs & auth > Credentials</b> click "Create new Client ID" and select "Web Appplication"</li>
				<li>Add the following authorized redirect URI: <input value="<?php echo $this->gapi->redirect_uri; ?>" onClick="this.select();" style="min-width:300px; background:white; cursor:text; font-size:11px" readonly></li>
				<li>Enter the information you receive below: </li>
			</ol>

			<?php $keys = $this->gapi->get_gapi_keys(); ?>
			<table class="form-table yellow-box">
			<tbody>
				<tr>
					<th scope="row">Client ID</th>
					<td><input type="text" name="gapi_client_id" id="gapi_client_id" value="<?php echo $keys['gapi_client_id']; ?>" class="regular-text" placeholder="example: 123.apps.googleusercontent.com"></td>
				</tr>
				<tr>
					<th scope="row">Email Address (API Key)</th>
					<td><input type="text" name="gapi_developer_key" id="gapi_developer_key" value="<?php echo $keys['gapi_developer_key']; ?>" class="regular-text" placeholder="example: 123@developer.gserviceaccount.com"></td>
				</tr>
				<tr>
					<th scope="row">Client Secret</th>
					<td><input type="text" name="gapi_client_secret" id="gapi_client_secret" value="<?php echo $keys['gapi_client_secret']; ?>" class="regular-text" placeholder="example: 123abc"></td>
				</tr>

			</tbody>
			</table>

			<input type="submit" name="submit" id="submit" class="button button-primary button-large" value="Continue to next step">

			</form>

			<?php
			/*************************************************/
			break; case 2:
		  	/*************************************************/
			?>

			<h2>Google Analytics sign-in</h2>
			<p>Sign in to your Google Analytics account and grant access to share read-only data with this plugin. The button below will take you to Google to sign-in. This plugin will save your OAuth permissions locally on this blog until you revoke them. </p>

			<a class="button-primary" id="gapi-sign-in" href="<?php echo $this->gapi->get_oauth_login_url(); ?>">Sign in with Google Analytics</a>

			<br />

			<div class="yellow-box">
				<p>If you see "Error: redirect_uri_mismatch" then you probably still need to add the authorization URI below to your <a href="https://console.developers.google.com" target="_new">Google Developer account</a>.</p>

				<label><b>Redirect URI Authorization</b></label><br />
				<small>You must enter this when you create your Google Developer web application</small><br />
				<input value="<?php echo $this->gapi->redirect_uri; ?>" onClick="this.select();" style="width:600px; background:white; cursor:text; font-size:11px" readonly><br />
				<br />
			</div>

			<?php
			/*************************************************/
			break; case 3:
		  	/*************************************************/
			?>

			<h2>Select a reporting profile</h2>

			<form method="POST" action="admin.php?page=social-metrics-tracker-settings&section=gapi">

			<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">Analytics Profile</th>
					<td>
						<select name="gapi_profile_id">
						<?php $profiles = $this->gapi->get_profile_list(); ?>
						<?php foreach($profiles as $profile) : ?>
							<option value="<?php echo $profile['id'].',['.$profile['parent'].'] '. $profile['name']; ?>">
								<?php echo '['.$profile['parent'].'] '. $profile['name']; ?>
							</option>
						<?php endforeach; ?>
						</select>

						<?php if (is_multisite() && current_user_can( 'manage_network' )) : ?>

						<p class="description">With <b>shared credentials</b>, all blogs on this WP Multisite network will automatically use the Google Analytics profile you set up to sync data and you will only need to complete this setup wizard once. With <b>different credentials</b>, each blog will need to complete this wizard but each blog will be able to link to a seperate Gooogle Analytics account.</p>

						<?php endif; ?>

					</td>
				</tr>
			</tbody>
			</table>

			<input type="submit" name="submit" id="submit" class="button button-primary button-large" value="Continue to next step">

			</form>

			<?php
			/*************************************************/
			break; case 4:
		  	/*************************************************/
			?>

			<h2>Setup complete. Connected to: </h2>
			<h3><?php echo $this->gapi->get_profile_name(); ?></h3>
			<p>Data will sync in the background. It will take up to one day for all data to be imported the first time around. </p>

			<?php if ($this->gapi->multisite_mode) : ?>
				<h3>Multisite data sharing is enabled</h3>
				<p>All blogs will sync with this Google Analytics account.</p>
			<?php endif; ?>

			<p>If you wish to disable data sync, navigate back to any of the previous setup steps and data syncing will stop.</p>

			<?php
			/*************************************************/
			break; default:
		  	/*************************************************/
			?>
			<h2>Something isn't working right...</h2>
			<?php
			/*************************************************/
			break;
		  	/*************************************************/

		} // end switch


	} // end gapi_section();

	// Displays a <ol> nav bar with the current step highlighted
	function gapi_wizard_progress() {

		$this->gapi->connect();

		$steps = array(
			1 => '1. Set up Google API developer keys',
			2 => '2. Login with OAuth',
			3 => '3. Select reporting profile',
			4 => 'Sync with Google Analytics'
		);

		if ($this->gapi->is_ready) {
			print('<ol id="smt_gapi_steps" class="ready">');
		} else {
			print('<ol id="smt_gapi_steps">');
		}

		foreach ($steps as $num => $step) {

			// Step already completed
			if ($this->gapi->step > $num) {
				print('<li class="done"><a href="admin.php?page=social-metrics-tracker-settings&section=gapi&go_to_step='.$num.'" class="btn" onclick="return confirm(\'If you go back to this step, data will not sync until all steps are complete.\')">'.$step.'</a></li>');
			}

			// Step in progress
			if ($this->gapi->step == $num) {
				print('<li class="current"><div class="btn">'.$step.'</div></li>');
			}

			// Steps coming up next
			if ($this->gapi->step < $num) {
				print('<li class="future"><div class="btn">'.$step.'</div></li>');
			}

		}

		print('</ol>');
	}

}

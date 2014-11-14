<?php
// Include and create a new WordPressSettingsFramework
require_once( 'lib/wp-settings-framework.php' );

class socialMetricsSettings {

	private $wpsf;

	function __construct($smt) {

		add_action( 'admin_menu', array(&$this, 'admin_menu'), 99 );
		// add_action( 'wpsf_before_settings_fields', array($this, 'section_id'));

		$section = (isset($_REQUEST['section'])) ? $_REQUEST['section'] : false;
		switch ($section) {
			case 'gapi':

				$this->section = 'gapi';

				$smt->updater->setupDataSources();
				$this->gapi = $smt->updater->GoogleAnalyticsUpdater;

				if (isset($_GET['go_to_step']) && $_GET['go_to_step']) $this->gapi->go_to_step($_GET['go_to_step']);

				break;

			default:
				$this->section = 'general';
				$this->wpsf = new WordPressSettingsFramework( plugin_dir_path( __FILE__ ) .'settings/smt-'.$this->section.'.php', 'smt' );
				break;
		}

	}

	function admin_menu() {

		add_submenu_page('social-metrics-tracker', 'Social Metrics Tracker Configuration', 'Configuration', 'manage_options', 'social-metrics-tracker-settings',  array($this, 'render_settings_page'));
	}

	// Display list of all and current option pages
	function nav_links() {
		print ($this->section == 'general') ? 'General Settings' : '<a href="admin.php?page=social-metrics-tracker-settings">General Settings</a>';
		print(' | ');
		print ($this->section == 'gapi') ? 'Google Analytics Setup' : '<a href="'.add_query_arg('section', 'gapi').'">Google Analytics Setup</a>';
	}

	function render_settings_page() { ?>
		<div class="wrap">
			<h2>Social Metrics Tracker Configuration</h2>
			<?php $this->nav_links(); ?>
			<?php call_user_func(array($this, $this->section.'_section')); ?>

		</div>
	<?php }

	// Render the general settings page
	function general_section() {
		$this->wpsf->settings();
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

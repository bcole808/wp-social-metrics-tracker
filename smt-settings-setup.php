<?php
class socialMetricsSettings {

	private $plugin_path;
	private $plugin_url;
	private $l10n;
	private $socialMetricsSettings;

	function __construct() {
		$this->plugin_path = plugin_dir_path( __FILE__ );
		$this->plugin_url = plugin_dir_url( __FILE__ );
		$this->l10n = 'wp-settings-framework';
		add_action( 'admin_menu', array(&$this, 'admin_menu'), 99 );

		// Include and create a new WordPressSettingsFramework
		require_once( $this->plugin_path .'lib/wp-settings-framework.php' );
		$this->socialMetricsSettings = new WordPressSettingsFramework( $this->plugin_path .'smt-settings.php', 'smt' );

		// Add an optional settings validation filter (recommended)
		add_filter( $this->socialMetricsSettings->get_option_group() .'_settings_validate', array(&$this, 'validate_settings') );
	}

	function admin_menu() {
		add_options_page( 'Social Metrics Tracker Settings', 'Social Metrics', 'manage_options', 'social-metrics-tracker-settings', array(&$this, 'settings_page') );
	}

	function settings_page() {
		// Your settings page
		?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"></div>
			<h2>Social Metrics Tracker Configuration</h2>
			<?php

			// Output your settings form
			$this->socialMetricsSettings->settings();
			?>
		</div>
		<?php

	}

	function validate_settings( $input ) {
		// Do your settings validation here
		// Same as $sanitize_callback from http://codex.wordpress.org/Function_Reference/register_setting
		return $input;
	}

}

new socialMetricsSettings();

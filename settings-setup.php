<?php
class socialInsightSettings {

    private $plugin_path;
    private $plugin_url;
    private $l10n;
    private $socialInsightSettings;

    function __construct() {	
        $this->plugin_path = plugin_dir_path( __FILE__ );
        $this->plugin_url = plugin_dir_url( __FILE__ );
        $this->l10n = 'wp-settings-framework';
        add_action( 'admin_menu', array(&$this, 'admin_menu'), 99 );
        
        // Include and create a new WordPressSettingsFramework
        require_once( $this->plugin_path .'lib/wp-settings-framework.php' );
        $this->socialInsightSettings = new WordPressSettingsFramework( $this->plugin_path .'settings.php', 'socialinsight' );

        // Add an optional settings validation filter (recommended)
        add_filter( $this->socialInsightSettings->get_option_group() .'_settings_validate', array(&$this, 'validate_settings') );
    }
    
    function admin_menu() {
        add_options_page( 'Social Insight Settings', 'Social Insight', 'manage_options', 'social-insight-settings', array(&$this, 'settings_page') );
    }
    
    function settings_page() {
	    // Your settings page
	    ?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"></div>
			<h2>Social Insight Settings</h2>
			<?php 

            // Verify the API authorization
            require_once ('google-analytics.php');
            if (current_user_can('manage_options') && $smc_options['socialinsight_options_enable_analytics']) {
                smc_gapi_loginout();
            }
            
			// Output your settings form
			$this->socialInsightSettings->settings(); 
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
new socialInsightSettings();

?>
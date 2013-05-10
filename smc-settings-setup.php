<?php
class SMC_Settings {

    private $plugin_path;
    private $plugin_url;
    private $l10n;
    private $smc_settings;

    function __construct() 
    {	
        $this->plugin_path = plugin_dir_path( __FILE__ );
        $this->plugin_url = plugin_dir_url( __FILE__ );
        $this->l10n = 'wp-settings-framework';
        add_action( 'admin_menu', array(&$this, 'admin_menu'), 99 );
        
        // Include and create a new WordPressSettingsFramework
        require_once( $this->plugin_path .'lib/wp-settings-framework.php' );
        $this->smc_settings = new WordPressSettingsFramework( $this->plugin_path .'settings/social-insight.php' );
        // Add an optional settings validation filter (recommended)
        add_filter( $this->smc_settings->get_option_group() .'_settings_validate', array(&$this, 'validate_settings') );
    }
    
    function admin_menu()
    {
        //$page_hook = add_menu_page( __( 'smc_settings', $this->l10n ), __( 'smc_settings', $this->l10n ), 'update_core', 'smc_settings', array(&$this, 'settings_page') );
        //add_submenu_page( 'smc_settings', __( 'Settings', $this->l10n ), __( 'Settings', $this->l10n ), 'update_core', 'smc_settings', array(&$this, 'settings_page') );

        add_options_page( 'Social Insight Settings', 'Social Insight', 'manage_options', 'smc_settings', array(&$this, 'settings_page') );
    }
    
    function settings_page()
	{
	    // Your settings page
	    ?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"></div>
			<h2>Social Insight Configuration</h2>
			<?php 
			// Output your settings form
			$this->smc_settings->settings(); 
			?>
		</div>
		<?php
		
		// Get settings
		//$settings = smc_settings_get_settings( $this->plugin_path .'settings/settings-general.php' );
		//echo '<pre>'.print_r($settings,true).'</pre>';
		
		// Get individual setting
		//$setting = smc_settings_get_setting( smc_settings_get_option_group( $this->plugin_path .'settings/settings-general.php' ), 'general', 'text' );
		//var_dump($setting);
	}
	
	function validate_settings( $input )
	{
	    // Do your settings validation here
	    // Same as $sanitize_callback from http://codex.wordpress.org/Function_Reference/register_setting
    	return $input;
	}

}
new SMC_Settings();

?>
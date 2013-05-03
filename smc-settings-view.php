<?php

function smc_text_field() {

	echo "Blah";
}

function smc_render_settings_view() {

	register_setting('smc_social_insight_options','smc_ga_api_auth');
	register_setting('smc_social_insight_options','smc_ga_keys');

	add_settings_section(
		'smc_social_insight_ga_settings',
		'Google API Setup', 
		'smc_social_insight_ga_settings_desc',
		'smc-social-insight-settings'
	);

	add_settings_field(
		'smc_ga_api_auth',
		'GA Auth', 
		'smc_text_field',
		'smc-social-insight-settings',
		'smc_social_insight_ga_settings'
	);

	add_settings_field(
		'smc_ga_api_keys',
		'GA Keys', 
		'smc_text_field',
		'smc-social-insight-settings',
		'smc_social_insight_ga_settings'
	);


	echo "hello world";

	 global $submenu;
	// access page settings 
	 $page_data = array();
	 foreach($submenu['options-general.php'] as $i => $menu_item) {
	 if($submenu['options-general.php'][$i][2] == 'smc-social-insight-settings')
	 $page_data = $submenu['options-general.php'][$i];
	 }

	// output 
	?>
	<div class="wrap">
	<?php screen_icon();?>
	<h2><?php echo $page_data[3];?></h2>
	<form id="smc_social_insight_options" action="options.php" method="post">
	<?php
	settings_fields('smc_social_insight_options');
	do_settings_sections('smc-social-insight-settings'); 
	submit_button('Save options', 'primary', 'smc_social_insight_options_submit');
	?>
	 </form>
	</div>

<?php } ?>
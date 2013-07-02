<?php

add_action( 'publish_post', 'queue_smcNotificationPush_update', 10, 2);
function queue_smcNotificationPush_update($post_ID, $post_data) {
	smcNotificationPush($post_ID, 'update_post', $post_data);
}

add_action( 'wp_trash_post', 'queue_smcNotificationPush_trash', 10, 1);
function queue_smcNotificationPush_trash($post_ID) {
	smcNotificationPush($post_ID, 'trash_post');
}

add_action( 'delete_post', 'queue_smcNotificationPush_delete', 10, 1);
function queue_smcNotificationPush_delete($post_ID) {
	smcNotificationPush($post_ID, 'delete_post');
}

add_action( 'smc_social_insight_sync', 'queue_smcNotificationPush_stats', 10, 2);
function queue_smcNotificationPush_stats($post_ID, $cached_stats) {
	smcNotificationPush($post_ID, 'refresh_stats', false, $cached_stats);
}

/**
 * Sends a POST request to a remote server when posts are updated on this blog. 
 *
 * @param  integer  $post_ID   The ID of the post. 
 * @param  string  	$action    The action that triggered this notification. 
 * @param  array  	$cached_stats    An array of stats from the Social Insight plugin. Optional. If present, will increase function performance. 
 * @return boolean				Returns TRUE on success and FALSE on failure. 
 */ 
if (!function_exists('smcNotificationPush')) {
	function smcNotificationPush($post_ID, $action = 'debug', $post_data = false, $cached_stats = false) {

		// global $smc_options;
		$smc_options = get_option('socialinsight_settings');

		// Configs
		$post_url 					= ''; // URL to send a POST notification to
		$emails 					= 'cole@chapman.edu'; // seperate multiple emails with commas
		$domain_restriction			= ''; // Only execute if we are running on this domain (useful for dev/production env). Leave blank to allow all. 

		$send_post_notifications 	= filter_var($smc_options['socialinsight_inside_push_enabled'], FILTER_VALIDATE_BOOLEAN); // Used for production
		$send_update_post_emails 	= filter_var($smc_options['socialinsight_inside_debug_send_update_post_emails'], FILTER_VALIDATE_BOOLEAN); // Used for debugging
		$send_refresh_stats_emails 	= filter_var($smc_options['socialinsight_inside_debug_send_refresh_stats_emails'], FILTER_VALIDATE_BOOLEAN); // Used for debugging

		// $send_post_notifications 	= false; // Used for production
		// $send_update_post_emails 	= true; // Used for debugging
		// $send_refresh_stats_emails 	= false; // Used for debugging

		$domain_current_site = (defined('DOMAIN_CURRENT_SITE')) ? DOMAIN_CURRENT_SITE : $_SERVER['SERVER_NAME'];

		/***************************************************
		* Restrictions
		***************************************************/

		// Do not run this function if we are not on an allowed domain
		if ($domain_restriction && $domain_restriction != $domain_current_site) {
			//Domain restriction failed
			return false; 
		}

		// Do not execute a push if the blog is not public!
		if (!get_option('blog_public')) {
			// BLOG IS NOT PUBLIC
			return false;
		}

		/***************************************************
		* Set up data
		***************************************************/

		if (!$post_data) $post_data = get_post($post_ID);
		$author_id = $post_data->post_author;

		if ($action != 'refresh_stats') {

			if (strlen($post_data->post_excerpt) > 0) {
				// Get the post excerpt
				$smc_post_excerpt = $post_data->post_excerpt;
			} else {
				// Create a post excerpt
				$excerpt_length = 55; //Sets excerpt length by word count
				$smc_post_excerpt = $post_data->post_content; //Gets post_content to be used as a basis for the excerpt
				$smc_post_excerpt = strip_tags(strip_shortcodes($smc_post_excerpt)); //Strips tags and images
				$words = explode(' ', $smc_post_excerpt, $excerpt_length + 1);
				if(count($words) > $excerpt_length) :
					array_pop($words);
					array_push($words, '…');
					$smc_post_excerpt = implode(' ', $words);
				endif;
			}
		}

		/***************************************************
		* Build JSON and POST to destination
		***************************************************/

		if ($action == 'refresh_stats') {

			$data = array(
				'action' => $action,
				'source' => array (
					'domain'		=> $domain_current_site,
					'home_url'		=> home_url()
				),
				'post' => array (
					'guid'			=> $post_data->guid,
					'stats'			=> array(
						'ga_pageviews'	=> ($cached_stats) ? $cached_stats['ga_pageviews'] : get_post_meta($post_ID, 'ga_pageviews', true),
						'comment_count'	=> $post_data->comment_count,
						'social_score'	=> array(
							'total'			=> ($cached_stats) ? $cached_stats['socialcount_TOTAL'] 		: get_post_meta($post_ID, 'socialcount_TOTAL', true),
							'facebook'		=> ($cached_stats) ? $cached_stats['socialcount_facebook'] 		: get_post_meta($post_ID, 'socialcount_facebook', true),
							'twitter'		=> ($cached_stats) ? $cached_stats['socialcount_twitter'] 		: get_post_meta($post_ID, 'socialcount_twitter', true),
							'googleplus'	=> ($cached_stats) ? $cached_stats['socialcount_googleplus'] 	: get_post_meta($post_ID, 'socialcount_googleplus', true),
							'linkedin'		=> ($cached_stats) ? $cached_stats['socialcount_linkedin'] 		: get_post_meta($post_ID, 'socialcount_linkedin', true),
							'pinterest'		=> ($cached_stats) ? $cached_stats['socialcount_pinterest'] 	: get_post_meta($post_ID, 'socialcount_pinterest', true),
							'diggs'			=> ($cached_stats) ? $cached_stats['socialcount_diggs'] 		: get_post_meta($post_ID, 'socialcount_diggs', true),
							'delicious'		=> ($cached_stats) ? $cached_stats['socialcount_delicious'] 	: get_post_meta($post_ID, 'socialcount_delicious', true),
							'reddit'		=> ($cached_stats) ? $cached_stats['socialcount_reddit'] 		: get_post_meta($post_ID, 'socialcount_reddit', true),
							'stumbleupon'	=> ($cached_stats) ? $cached_stats['socialcount_stumbleupon'] 	: get_post_meta($post_ID, 'socialcount_stumbleupon', true)
						)
					)
				) 
			); // end $data

		} else {
			
			$data = array(
				'action' => $action,
				'source' => array (
					'name'			=> get_bloginfo('name'),
					'description'	=> get_bloginfo('description'),
					'domain'		=> $domain_current_site,
					'home_url'		=> home_url()
				),
				'post' => array (
					'ID' 			=> $post_data->ID,
					'post_author' 	=> array(
						'id'			=> $post_data->post_author,
						'display_name'	=> get_the_author_meta('display_name', $author_id),
						'first_name'	=> get_the_author_meta('user_firstname', $author_id),
						'last_name'		=> get_the_author_meta('user_lastname', $author_id),
						'description'	=> get_the_author_meta('user_description', $author_id),
						'twitter'		=> get_the_author_meta('twitter', $author_id),
						'user_email'	=> get_the_author_meta('user_email', $author_id),
					),
					'post_date'		=> $post_data->post_date,
					'post_modified'	=> $post_data->post_modified,
					'post_title'	=> get_the_title($post_ID),
					'post_content'	=> $post_data->post_content,
					'post_excerpt'	=> $smc_post_excerpt,
					'guid'			=> $post_data->guid,
					'permalink'		=> get_permalink($post_ID),
					'tags'			=> wp_get_post_terms($post_ID, 'post_tag', array('orderby' => 'count', 'order' => 'DESC', 'fields' => 'names')),
					'categories'	=> wp_get_post_terms($post_ID, 'category', array('orderby' => 'count', 'order' => 'DESC', 'fields' => 'names')),
					'image'			=> findImageData($post_ID, 'full') // array
				) 
			); // end $data

		}

		$json = json_encode($data);

		if (($send_update_post_emails && $data['action'] != 'refresh_stats')  || ($send_refresh_stats_emails && $data['action'] == 'refresh_stats')) {

			$headers = 'From: '.get_bloginfo('name').' <no-reply@mail.chapman.edu>' . "\r\n" .
			'Reply-To: no-reply@mail.chapman.edu' . "\r\n";
			$headers .= 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			
			$to = $emails;
			$subject = $data['source']['domain'].': '.$data['action']. ': '.$data['post']['post_title']; ;

			$message = '<h3>DEBUG NOTICE: smcNotificationPush() was called.</h3><br><pre>'.print_r($data, true).'</pre>';

			wp_mail($to, $subject, $message, $headers);
		}

		if ($send_post_notifications && $post_url) {

			// Create a cURL connection
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt ($ch, CURLOPT_HTTPHEADER, Array("Content-Type: application/json"));
			$response = curl_exec($ch);
			curl_close($ch);

			// If the cURL response was not received, notify admin
			if (!$response) mail($emails,'smcNotificationPush() error', 'The cURL POST failed. The JSON which was sent was: '.$json);

			return $response;
		} 
	} // end smcNotificationPush()
}

/**
 * Retrieve an image representing the post. First check for a featured image, then find an inline image
 *
 * @param  int    	$post_ID 	The ID of the post
 * @param  String   $size  		The size of the thumbnail to return. 
 * @return array            	The array with some data
 */ 
if (!function_exists('findImageData')) {
	function findImageData($post_ID, $size = 'full', $minimum_size = 100) { 

		// First check for a featured image
		if (has_post_thumbnail( $post_ID ) ) { 
			$image_data = wp_get_attachment_image_src( get_post_thumbnail_id( $post_ID ), $size );
			$data_source = 'featured_image';

		// Next look for an attached image
		} else {

			// Get the newest image attachment by date uploaded
			// (If we wanted to expand this, we could get ALL images and compare file resolution)
			$files = get_posts(
				array(
					'post_parent'	=> $post_ID,
					'post_type'		=> 'attachment',
					'post_mime_type'	=> 'image',
					'orderby'		=> 'post_date',
					'order'			=> 'DESC',
					'posts_per_page' => 1
				)
			);
			if ($files) {
				foreach($files as $file) {
					$image_data = wp_get_attachment_image_src($file->ID, $size);
				}
				$data_source = 'inline_image';
			}
		}

		if (!$image_data) return false;
		if ($image_data[1] < $minimum_size) return false;
		if ($image_data[2] < $minimum_size) return false;

		$data = array(
			'source' 	=> $data_source,
			'url'		=> $image_data[0],
			'width'		=> $image_data[1],
			'height'	=> $image_data[2]
		);

		return $data;
	}
}
?>
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
		// $post_url 					= 'http://hummingbird.insidechapman.net:3001/callback/wordpress'; // URL to send a POST notification to
		$post_url 					= 'http://hummingbird.insidechapman.net/callback'; // URL to send a POST notification to
		$emails 					= 'cole@chapman.edu, miles.zimm@gmail.com'; // seperate multiple emails with commas
		$domain_restriction			= 'blogs.chapman.edu'; // Only execute if we are running on this domain. Leave blank to allow all. 

		$timeout_on_failed_push	= 120; // This is added to itself for each successive failure within 1 hour of expiry. 

		$send_post_notifications 	= ($smc_options['socialinsight_inside_push_enabled'] == 1); // Used for production
		$send_update_post_emails 	= ($smc_options['socialinsight_inside_debug_send_update_post_emails'] == 'send_update_post_emails'); // Used for debugging
		$send_refresh_stats_emails 	= ($smc_options['socialinsight_inside_debug_send_refresh_stats_emails'] == 'send_refresh_stats_emails'); // Used for debugging

		// $send_post_notifications 	= false; // Used for production
		// $send_update_post_emails 	= false; // Used for debugging
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

		// Do not execute if thetimeout restriction is active
		$is_restricted = get_site_transient( 'inside_chapman_link_suspended' ); 
		if ($is_restricted) {
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
					'ID'			=> $post_data->ID,
					'guid'			=> $post_data->guid,
					'stats'			=> array(
						'social_aggregate_score'			=> ($cached_stats) ? $cached_stats['social_aggregate_score'] 		 : get_post_meta($post_ID, 'social_aggregate_score', true),
						'social_aggregate_score_decayed'	=> ($cached_stats) ? $cached_stats['social_aggregate_score_decayed'] : get_post_meta($post_ID, 'social_aggregate_score_decayed', true),
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
					'post_title'	=> apply_filters('the_title', $post_data->post_title),
					'post_content'	=> apply_filters('the_content', $post_data->post_content),
					'post_excerpt'	=> $smc_post_excerpt,
					'guid'			=> $post_data->guid,
					'permalink'		=> get_permalink($post_ID),
					'tags'			=> wp_get_post_terms($post_ID, 'post_tag', array('orderby' => 'count', 'order' => 'DESC', 'fields' => 'names')),
					'categories'	=> wp_get_post_terms($post_ID, 'category', array('orderby' => 'count', 'order' => 'DESC', 'fields' => 'names')),
					'image'			=> createImageDataArray($post_ID, 'full') // array
				) 
			); // end $data

			// Append additional data
			if ($data['post']['categories'][0]) {
				$data['post']['top_category_url'] = get_category_link( get_cat_ID($data['post']['categories'][0]) );
			}

		}

		$json = json_encode($data);

		if (($send_update_post_emails && $data['action'] != 'refresh_stats')  || ($send_refresh_stats_emails && $data['action'] == 'refresh_stats')) {

			$headers = 'From: '.get_bloginfo('name').' <no-reply@mail.chapman.edu>' . "\r\n" .
			'Reply-To: no-reply@mail.chapman.edu' . "\r\n";
			$headers .= 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			
			$to = $emails;
			$subject = $data['source']['domain'].': '.$data['action']. ': '.$data['post']['post_title']; ;

			$message = '<h3>DEBUG NOTICE: smcNotificationPush() was called. PHP Data:</h3><br><pre>'.print_r($data, true).'</pre><br><br><b>JSON formatted:</b><br><pre>'.print_r($json, true).'</pre>';

			wp_mail($to, $subject, $message, $headers);
		}

		if ($send_post_notifications && $post_url) {

			// Create a cURL connection
			$ch = curl_init($post_url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
			curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: application/json"));
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			$response = curl_exec($ch);

			// If the cURL response was not received, notify admin
			if ($response === false) {

				$next_timeout = get_site_transient( 'inside_chapman_link_suspended_next_timeout' ) ?: $timeout_on_failed_push; 

				set_site_transient( 'inside_chapman_link_suspended', true, $next_timeout ); // Suspend on error
				set_site_transient( 'inside_chapman_link_suspended_next_timeout', $next_timeout + $timeout_on_failed_push, $next_timeout + 60*60 ); // Suspend on error



				$time_back = date("M j, g:i a", time() + $next_timeout);  

				$error = curl_error($ch);
				mail($emails,'smcNotificationPush() error', 'The Inside.Chapman link has been suspended until '.$time_back.' because the cURL POST failed. Error message: '.$error.'.  The JSON which was sent was: '.$json);
			}

			curl_close($ch);

			// If the server requested more info, send another update
			$response_json = json_decode($response);
			if ($response_json !== null && $action == 'refresh_stats') {
				if (strlen($response_json->article_not_in_db) > 0) {

					// Trigger additional cURL update
					smcNotificationPush($post_ID, 'update_post', $post_data);

				}
			}


			return $response;
		} 
	} // end smcNotificationPush()
}

/**
 * Retrieve an image representing the post. First check for a featured image, then find an inline image
 *
 * @param  int    	$post_ID 	The ID of the post
 * @param  
 * @return array            	The array with some data
 */ 
if (!function_exists('createImageDataArray')) {
	function createImageDataArray($post_ID, $size = '', $minimum_size = 500) { 

		// First check for a featured image
		if (has_post_thumbnail( $post_ID ) ) { 
			$image_id = get_post_thumbnail_id( $post_ID );
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

				// Can be enhanced by finding the largest image instead of just taking the last image. 
				foreach($files as $file) {
					$image_id = $file->ID;
				}
				$data_source = 'inline_image';
			}
		}

		$image_data = wp_get_attachment_image_src($image_id, 'full');

		if (!$image_data) return false;
		if ($image_data[1] < $minimum_size) return false;
		if ($image_data[2] < ($minimum_size / 2)) return false;

		// Do not allow portrait orientation images
		if ($image_data[1] < $image_data[2]) return false;

		$image_data_thumbnail 	= wp_get_attachment_image_src($image_id, 'thumbnail');
		$image_data_medium 		= wp_get_attachment_image_src($image_id, 'Featured Rectangle'); // "Featured Rectangle"
		$image_data_large 		= wp_get_attachment_image_src($image_id, 'Medium Masthead'); // "Medium Masthead"

		// Dynamic switch for the massive image size. Taken from CU Template
		if ($image_data[1] >= 1200) {

			switch($image_data[1]) {
				case ($image_data[1] > 1924) :
					$thumbnail_data_massive = wp_get_attachment_image_src( $image_id, '1924 Masthead' );
				break;

				case ($image_data[1] > 1560) :
					$thumbnail_data_massive = wp_get_attachment_image_src( $image_id, '1560 Masthead' );
				break;

				case ($image_data[1] > 1200) :
					$thumbnail_data_massive = wp_get_attachment_image_src( $image_id, '1200 Masthead' );
				break;

				default : 
					$thumbnail_data_massive = $image_data;
				break;

			} // end switch
		} else {
			$thumbnail_data_massive = $image_data;
		}

		$data = array(
			'source' 	=> $data_source,
			'url'		=> $image_data[0],
			'width'		=> $image_data[1],
			'height'	=> $image_data[2],
			'downsized'		=> array( 
				'thumbnail'		=>	$image_data_thumbnail[0],
				'medium'		=>	$image_data_medium[0],
				'large'			=> 	$image_data_large[0],
				'massive'		=>	$thumbnail_data_massive[0]
			)
		);

		return $data;
	}
}
?>
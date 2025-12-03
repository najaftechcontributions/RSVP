<?php
/**
 * ACF Return URL Filter
 * Appends event ID to email campaigns redirect URL after event creation
 * 
 * @package EventRSVPPlugin
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Modify ACF form return URL to include event ID
 */
function event_rsvp_modify_acf_return_url($return, $form, $post_id) {
	// Only modify if we're creating a new event (not editing)
	if (is_numeric($post_id) && get_post_type($post_id) === 'event') {
		// Check if the return URL is for email campaigns
		if (strpos($return, '/email-campaigns/') !== false && strpos($return, 'new_event=1') !== false) {
			// Append the event ID
			$return = add_query_arg('event_id', $post_id, $return);
		}
	}
	
	return $return;
}
add_filter('acf/pre_save_post/return', 'event_rsvp_modify_acf_return_url', 10, 3);

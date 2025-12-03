<?php
/**
 * ACF Compatibility Layer
 * Ensures ACF Free fields work correctly throughout the system
 * Note: ACF Free does not support repeater fields
 */

// No compatibility filters needed - fields are already simple
// This file is kept for future compatibility needs

/**
 * Helper function to get hashtags as array
 * Converts textarea field to array format
 */
if (!function_exists('event_rsvp_get_hashtags_array')) {
	function event_rsvp_get_hashtags_array($event_id) {
		$hashtags_array = array();
		
		// Get hashtags from textarea field
		$event_hashtags = get_field('event_hashtags', $event_id);
		
		if (!empty($event_hashtags)) {
			// Split by newlines or commas
			$hashtags_raw = preg_split('/[\r\n,]+/', $event_hashtags);
			foreach ($hashtags_raw as $tag) {
				$tag = trim($tag);
				$tag = ltrim($tag, '#'); // Remove # if user added it
				if (!empty($tag)) {
					$hashtags_array[] = $tag;
				}
			}
		}
		
		// Fallback to legacy single hashtag field
		if (empty($hashtags_array)) {
			$event_hashtag = get_field('event_hashtag', $event_id);
			if (!empty($event_hashtag)) {
				$hashtags_array[] = $event_hashtag;
			}
		}
		
		return $hashtags_array;
	}
}

/**
 * Helper function to get social links as array
 * Converts individual URL fields to array format
 */
if (!function_exists('event_rsvp_get_social_links_array')) {
	function event_rsvp_get_social_links_array($event_id) {
		$social_links_data = array();
		
		$social_facebook = get_field('social_facebook', $event_id);
		$social_twitter = get_field('social_twitter', $event_id);
		$social_instagram = get_field('social_instagram', $event_id);
		$social_linkedin = get_field('social_linkedin', $event_id);
		$social_youtube = get_field('social_youtube', $event_id);
		$social_website = get_field('social_website', $event_id);
		
		if (!empty($social_facebook)) {
			$social_links_data[] = array('platform' => 'Facebook', 'url' => $social_facebook, 'icon' => '📘');
		}
		if (!empty($social_twitter)) {
			$social_links_data[] = array('platform' => 'Twitter', 'url' => $social_twitter, 'icon' => '🐦');
		}
		if (!empty($social_instagram)) {
			$social_links_data[] = array('platform' => 'Instagram', 'url' => $social_instagram, 'icon' => '📸');
		}
		if (!empty($social_linkedin)) {
			$social_links_data[] = array('platform' => 'LinkedIn', 'url' => $social_linkedin, 'icon' => '💼');
		}
		if (!empty($social_youtube)) {
			$social_links_data[] = array('platform' => 'YouTube', 'url' => $social_youtube, 'icon' => '📺');
		}
		if (!empty($social_website)) {
			$social_links_data[] = array('platform' => 'Website', 'url' => $social_website, 'icon' => '🌐');
		}
		
		return $social_links_data;
	}
}

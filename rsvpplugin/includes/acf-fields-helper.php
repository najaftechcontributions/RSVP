<?php
/**
 * Helper functions for ACF fields compatibility
 * Converts new field formats to arrays for backward compatibility
 */

if (!function_exists('event_rsvp_parse_hashtags')) {
	/**
	 * Parse hashtags from textarea field
	 * Converts comma/newline separated text to array
	 */
	function event_rsvp_parse_hashtags($hashtags_text) {
		$hashtags_array = array();
		
		if (empty($hashtags_text)) {
			return $hashtags_array;
		}
		
		// Split by comma or newline
		$hashtags_raw = preg_split('/[\r\n,]+/', $hashtags_text);
		
		foreach ($hashtags_raw as $tag) {
			$tag = trim($tag);
			// Remove # if user added it
			$tag = ltrim($tag, '#');
			if (!empty($tag)) {
				$hashtags_array[] = $tag;
			}
		}
		
		return $hashtags_array;
	}
}

if (!function_exists('event_rsvp_get_social_links')) {
	/**
	 * Get social links from individual fields
	 * Converts individual URL fields to array format
	 */
	function event_rsvp_get_social_links($event_id = null) {
		$social_links_data = array();
		
		// Use current post ID if not provided
		if (!$event_id) {
			$event_id = get_the_ID();
		}
		
		// Get social link fields
		$social_facebook = get_field('social_facebook', $event_id);
		$social_twitter = get_field('social_twitter', $event_id);
		$social_instagram = get_field('social_instagram', $event_id);
		$social_linkedin = get_field('social_linkedin', $event_id);
		$social_youtube = get_field('social_youtube', $event_id);
		$social_website = get_field('social_website', $event_id);
		
		if (!empty($social_facebook)) {
			$social_links_data[] = array(
				'platform' => 'Facebook',
				'url' => $social_facebook,
				'icon' => '📘'
			);
		}
		
		if (!empty($social_twitter)) {
			$social_links_data[] = array(
				'platform' => 'Twitter/X',
				'url' => $social_twitter,
				'icon' => '🐦'
			);
		}
		
		if (!empty($social_instagram)) {
			$social_links_data[] = array(
				'platform' => 'Instagram',
				'url' => $social_instagram,
				'icon' => '📸'
			);
		}
		
		if (!empty($social_linkedin)) {
			$social_links_data[] = array(
				'platform' => 'LinkedIn',
				'url' => $social_linkedin,
				'icon' => '💼'
			);
		}
		
		if (!empty($social_youtube)) {
			$social_links_data[] = array(
				'platform' => 'YouTube',
				'url' => $social_youtube,
				'icon' => '📺'
			);
		}
		
		if (!empty($social_website)) {
			$social_links_data[] = array(
				'platform' => 'Website',
				'url' => $social_website,
				'icon' => '🌐'
			);
		}
		
		return $social_links_data;
	}
}

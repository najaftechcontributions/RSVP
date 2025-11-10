<?php
/**
 * Helper Functions
 * 
 * @package EventRSVPPlugin
 */

if (!defined('ABSPATH')) {
	exit;
}

function event_rsvp_get_event_stats($event_id) {
	$attendees = get_posts(array(
		'post_type' => 'attendee',
		'posts_per_page' => -1,
		'meta_query' => array(
			array(
				'key' => 'linked_event',
				'value' => $event_id,
				'compare' => '='
			)
		),
		'fields' => 'ids'
	));
	
	$total = count($attendees);
	$checked_in = 0;
	
	foreach ($attendees as $attendee_id) {
		if (get_post_meta($attendee_id, 'checkin_status', true)) {
			$checked_in++;
		}
	}
	
	$not_checked_in = $total - $checked_in;
	$percentage = $total > 0 ? round(($checked_in / $total) * 100) : 0;
	
	return array(
		'total' => $total,
		'checked_in' => $checked_in,
		'not_checked_in' => $not_checked_in,
		'percentage' => $percentage
	);
}

function event_rsvp_get_attendee_by_email($email, $event_id = 0) {
	$args = array(
		'post_type' => 'attendee',
		'posts_per_page' => 1,
		'meta_query' => array(
			array(
				'key' => 'attendee_email',
				'value' => $email,
				'compare' => '='
			)
		)
	);
	
	if ($event_id > 0) {
		$args['meta_query'][] = array(
			'key' => 'linked_event',
			'value' => $event_id,
			'compare' => '='
		);
	}
	
	$attendees = get_posts($args);
	
	return !empty($attendees) ? $attendees[0] : null;
}

function event_rsvp_get_upcoming_events($limit = 5) {
	$today = date('Y-m-d');
	
	$events = get_posts(array(
		'post_type' => 'event',
		'posts_per_page' => $limit,
		'meta_key' => 'event_date',
		'orderby' => 'meta_value',
		'order' => 'ASC',
		'meta_query' => array(
			array(
				'key' => 'event_date',
				'value' => $today,
				'compare' => '>=',
				'type' => 'DATE'
			)
		)
	));
	
	return $events;
}

function event_rsvp_get_user_events($user_id = 0) {
	if (!$user_id) {
		$user_id = get_current_user_id();
	}
	
	if (!$user_id) {
		return array();
	}
	
	return get_posts(array(
		'post_type' => 'event',
		'author' => $user_id,
		'posts_per_page' => -1,
		'orderby' => 'date',
		'order' => 'DESC'
	));
}

function event_rsvp_get_attendees_by_event($event_id, $status = '') {
	$args = array(
		'post_type' => 'attendee',
		'posts_per_page' => -1,
		'meta_query' => array(
			array(
				'key' => 'linked_event',
				'value' => $event_id,
				'compare' => '='
			)
		)
	);
	
	if (!empty($status)) {
		$args['meta_query'][] = array(
			'key' => 'rsvp_status',
			'value' => $status,
			'compare' => '='
		);
	}
	
	return get_posts($args);
}

function event_rsvp_get_active_vendor_ads($location = '') {
	$today = date('Y-m-d');
	
	$args = array(
		'post_type' => 'vendor_ad',
		'posts_per_page' => -1,
		'meta_query' => array(
			'relation' => 'AND',
			array(
				'key' => 'ad_start_date',
				'value' => $today,
				'compare' => '<=',
				'type' => 'DATE'
			),
			array(
				'key' => 'ad_end_date',
				'value' => $today,
				'compare' => '>=',
				'type' => 'DATE'
			)
		)
	);
	
	if (!empty($location)) {
		$args['meta_query'][] = array(
			'key' => 'slot_location',
			'value' => $location,
			'compare' => '='
		);
	}
	
	return get_posts($args);
}

function event_rsvp_check_capacity($event_id) {
	$max_attendees = get_post_meta($event_id, 'max_attendees', true);
	
	if (empty($max_attendees)) {
		return true;
	}
	
	$current_attendees = get_posts(array(
		'post_type' => 'attendee',
		'posts_per_page' => -1,
		'meta_query' => array(
			array(
				'key' => 'linked_event',
				'value' => $event_id,
				'compare' => '='
			),
			array(
				'key' => 'rsvp_status',
				'value' => 'yes',
				'compare' => '='
			)
		),
		'fields' => 'ids'
	));
	
	return count($current_attendees) < $max_attendees;
}

function event_rsvp_is_event_full($event_id) {
	return !event_rsvp_check_capacity($event_id);
}

function event_rsvp_get_available_spots($event_id) {
	$max_attendees = get_post_meta($event_id, 'max_attendees', true);
	
	if (empty($max_attendees)) {
		return -1;
	}
	
	$current_attendees = get_posts(array(
		'post_type' => 'attendee',
		'posts_per_page' => -1,
		'meta_query' => array(
			array(
				'key' => 'linked_event',
				'value' => $event_id,
				'compare' => '='
			),
			array(
				'key' => 'rsvp_status',
				'value' => 'yes',
				'compare' => '='
			)
		),
		'fields' => 'ids'
	));
	
	return max(0, $max_attendees - count($current_attendees));
}

function event_rsvp_display_vendor_ad($location) {
	$ads = event_rsvp_get_active_vendor_ads($location);
	
	if (empty($ads)) {
		return '';
	}
	
	$ad = $ads[array_rand($ads)];
	
	$thumbnail = get_the_post_thumbnail($ad->ID, 'medium');
	$click_url = get_post_meta($ad->ID, 'click_url', true);
	
	if (empty($thumbnail)) {
		return '';
	}
	
	$current_impressions = intval(get_post_meta($ad->ID, 'ad_impressions', true));
	update_post_meta($ad->ID, 'ad_impressions', $current_impressions + 1);
	
	return sprintf(
		'<div class="vendor-ad vendor-ad-%s"><a href="%s" target="_blank" rel="noopener sponsored" class="vendor-ad-link" data-ad-id="%d">%s</a></div>',
		esc_attr($location),
		esc_url($click_url),
		$ad->ID,
		$thumbnail
	);
}

function event_rsvp_generate_qr_code($data) {
	if (function_exists('qrc_generate_qr_code')) {
		return qrc_generate_qr_code($data);
	}
	
	return 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($data);
}

function event_rsvp_parse_hashtags($content) {
	if (empty($content)) {
		return $content;
	}

	$pattern = '/(^|\s)(#[a-zA-Z0-9_]+)/';

	$content = preg_replace_callback($pattern, function($matches) {
		$full_match = $matches[0];
		$hashtag = $matches[2];
		$prefix = $matches[1];

		$hashtag_text = substr($hashtag, 1);
		$search_url = home_url('/events/?hashtag=' . urlencode($hashtag_text));

		return $prefix . '<span class="event-hashtag-link"><a href="' . esc_url($search_url) . '">' . esc_html($hashtag) . '</a></span>';
	}, $content);

	return $content;
}

function event_rsvp_parse_location_maps($content) {
	if (empty($content)) {
		return $content;
	}

	$pattern = '/#(map|location):([a-zA-Z0-9_\+\-]+)/i';

	$content = preg_replace_callback($pattern, function($matches) {
		$location = $matches[2];
		$location_decoded = str_replace('_', ' ', $location);

		if (strpos($location, 'http') === 0 || strpos($location, 'maps.app.goo.gl') !== false) {
			$map_url = $location;
		} else {
			$map_url = 'https://maps.google.com/maps?q=' . urlencode($location_decoded) . '&output=embed';
		}

		$embed_html = '<div class="event-map-embed-container">';
		$embed_html .= '<div class="map-location-label">📍 ' . esc_html($location_decoded) . '</div>';
		$embed_html .= '<iframe class="event-location-map" src="' . esc_url($map_url) . '" width="100%" height="400" style="border:0; border-radius: var(--event-radius);" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>';
		$embed_html .= '</div>';

		return $embed_html;
	}, $content);

	return $content;
}

function event_rsvp_parse_map_shortcodes($content) {
	if (empty($content)) {
		return $content;
	}

	$pattern = '/\[map\](https?:\/\/[^\s\[\]]+)\[\/map\]/i';

	$content = preg_replace_callback($pattern, function($matches) {
		$url = $matches[1];

		$embed_html = '<div class="event-map-embed-container">';
		$embed_html .= '<div class="map-location-label">📍 Location Map</div>';
		$embed_html .= '<iframe class="event-location-map" src="' . esc_url($url) . '" width="100%" height="450" style="border:0; border-radius: var(--event-radius);" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>';
		$embed_html .= '</div>';

		return $embed_html;
	}, $content);

	return $content;
}

function event_rsvp_filter_event_content($content) {
	$content = event_rsvp_parse_map_shortcodes($content);
	$content = event_rsvp_parse_location_maps($content);
	$content = event_rsvp_parse_hashtags($content);
	return $content;
}
add_filter('the_content', 'event_rsvp_filter_event_content', 20);
add_filter('the_excerpt', 'event_rsvp_filter_event_content', 20);

function event_rsvp_export_attendees($event_id) {
	if (!current_user_can('edit_posts')) {
		wp_die('Unauthorized');
	}

	$attendees = get_posts(array(
		'post_type' => 'attendee',
		'posts_per_page' => -1,
		'meta_key' => 'linked_event',
		'meta_value' => $event_id,
	));

	header('Content-Type: text/csv');
	header('Content-Disposition: attachment; filename="attendees-' . $event_id . '-' . date('Y-m-d') . '.csv"');

	$output = fopen('php://output', 'w');

	fputcsv($output, array('Name', 'Email', 'Phone', 'RSVP Status', 'Check-in Status', 'Check-in Time'));

	foreach ($attendees as $attendee) {
		fputcsv($output, array(
			get_the_title($attendee->ID),
			get_post_meta($attendee->ID, 'attendee_email', true),
			get_post_meta($attendee->ID, 'attendee_phone', true),
			get_post_meta($attendee->ID, 'rsvp_status', true),
			get_post_meta($attendee->ID, 'checkin_status', true) ? 'Yes' : 'No',
			get_post_meta($attendee->ID, 'checkin_time', true),
		));
	}

	fclose($output);
	exit;
}

function event_rsvp_handle_export() {
	if (isset($_GET['action']) && $_GET['action'] === 'export_attendees' && isset($_GET['event_id'])) {
		$event_id = intval($_GET['event_id']);
		event_rsvp_export_attendees($event_id);
	}
}
add_action('init', 'event_rsvp_handle_export');

function event_rsvp_fullcalendar_events($events) {
	$event_posts = get_posts(array(
		'post_type' => 'event',
		'posts_per_page' => -1,
	));

	foreach ($event_posts as $event_post) {
		$event_date = get_post_meta($event_post->ID, 'event_date', true);
		$event_end_date = get_post_meta($event_post->ID, 'event_end_date', true);

		if (!empty($event_date)) {
			$events[] = array(
				'title' => get_the_title($event_post->ID),
				'start' => $event_date,
				'end' => $event_end_date ?: $event_date,
				'url' => get_permalink($event_post->ID),
				'id' => $event_post->ID,
			);
		}
	}

	return $events;
}
add_filter('wpfc_events', 'event_rsvp_fullcalendar_events');

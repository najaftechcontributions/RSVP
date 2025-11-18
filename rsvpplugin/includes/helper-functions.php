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
			),
			array(
				'key' => 'ad_status',
				'value' => 'active',
				'compare' => '='
			),
			array(
				'key' => 'ad_approval_status',
				'value' => 'approved',
				'compare' => '='
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

function event_rsvp_is_event_past($event_id) {
	$event_date = get_field('event_date', $event_id);

	if (empty($event_date)) {
		return false;
	}

	$event_timestamp = strtotime($event_date);
	$current_timestamp = current_time('timestamp');

	return $current_timestamp > $event_timestamp;
}

function event_rsvp_get_ad_locations() {
	return array(
		'home_1' => 'Homepage Slot 1',
		'home_2' => 'Homepage Slot 2',
		'home_3' => 'Homepage Slot 3',
		'sidebar_1' => 'Sidebar Slot 1',
		'sidebar_2' => 'Sidebar Slot 2',
		'sidebar_3' => 'Sidebar Slot 3',
		'sidebar_4' => 'Sidebar Slot 4',
		'events_1' => 'Events Page Slot 1',
		'events_2' => 'Events Page Slot 2',
		'events_3' => 'Events Page Slot 3',
		'events_4' => 'Events Page Slot 4',
	);
}

function event_rsvp_display_vendor_ad($location, $preview = false, $show_all = true) {
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
	
	if (!$preview) {
		$args['meta_query'][] = array(
			'key' => 'ad_status',
			'value' => 'active',
			'compare' => '='
		);
		$args['meta_query'][] = array(
			'key' => 'ad_approval_status',
			'value' => 'approved',
			'compare' => '='
		);
	}
	
	if (!empty($location)) {
		$args['meta_query'][] = array(
			'key' => 'slot_location',
			'value' => $location,
			'compare' => '='
		);
	}
	
	$ads = get_posts($args);
	
	if (empty($ads)) {
		if ($preview && (is_admin() || current_user_can('administrator'))) {
			return '<div class="vendor-ad-preview-notice" style="padding: 20px; background: #f8f9fa; border: 2px dashed #ddd; border-radius: 8px; text-align: center; margin: 10px 0;"><strong>📢 No active ads for this location</strong><br><small>Create and approve ads to display them here</small></div>';
		}
		return '';
	}
	
	// If more than 1 ad assigned to a location, show them stacked
	if (count($ads) > 1 || $show_all) {
		ob_start();
		echo '<div class="vendor-ads-multiple vendor-ads-stacked vendor-ads-location-' . esc_attr($location) . '" style="display: flex; flex-direction: column; gap: 20px;">';
		foreach ($ads as $ad) {
			echo event_rsvp_render_single_ad($ad->ID, $location, $preview);
		}
		echo '</div>';
		return ob_get_clean();
	}
	
	// Single ad - show random one
	$ad = $ads[array_rand($ads)];
	return event_rsvp_render_single_ad($ad->ID, $location, $preview);
}

function event_rsvp_render_single_ad($ad_id, $location = '', $preview = false) {
	$thumbnail_url = get_the_post_thumbnail_url($ad_id, 'large');
	$click_url = get_post_meta($ad_id, 'click_url', true);
	$ad_title = get_the_title($ad_id);
	$ad_status = get_post_meta($ad_id, 'ad_status', true);
	$approval_status = get_post_meta($ad_id, 'ad_approval_status', true);
	$rendering_style = get_post_meta($ad_id, 'rendering_style', true) ?: 'default';
	
	if (empty($thumbnail_url)) {
		if ($preview && (is_admin() || current_user_can('administrator'))) {
			return '<div class="vendor-ad-preview-notice" style="padding: 20px; background: #fff3cd; border: 2px dashed #ffc107; border-radius: 8px; text-align: center; margin: 10px 0;"><strong>🖼️ Ad has no image</strong><br><small>Please add a featured image to the ad</small></div>';
		}
		return '';
	}
	
	// Check if ad should be displayed
	if (!$preview) {
		if ($ad_status !== 'active' || $approval_status !== 'approved') {
			return '';
		}
		
		$current_impressions = intval(get_post_meta($ad_id, 'ad_impressions', true));
		update_post_meta($ad_id, 'ad_impressions', $current_impressions + 1);
	}
	
	$preview_label = $preview ? '<div class="ad-preview-label" style="position: absolute; top: 10px; left: 10px; background: rgba(0,0,0,0.8); color: #fff; padding: 5px 10px; border-radius: 4px; font-size: 0.8rem; z-index: 10;">PREVIEW</div>' : '';
	
	$style_class = $rendering_style !== 'default' ? ' vendor-ad-style-' . $rendering_style : '';
	
	ob_start();
	?>
	<div class="vendor-ad vendor-ad-<?php echo esc_attr($location); ?> vendor-ad-wrapper<?php echo esc_attr($style_class); ?>" data-ad-id="<?php echo $ad_id; ?>" data-style="<?php echo esc_attr($rendering_style); ?>">
		<div class="vendor-ad-container" style="position: relative;">
			<?php echo $preview_label; ?>
			<?php if (!empty($click_url)) : ?>
				<a href="<?php echo esc_url($click_url); ?>" target="_blank" rel="noopener sponsored" class="vendor-ad-link" data-ad-id="<?php echo $ad_id; ?>" <?php echo $preview ? 'onclick="return false;"' : ''; ?>>
					<div class="vendor-ad-image">
						<img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($ad_title); ?>" loading="lazy">
					</div>
					<div class="vendor-ad-overlay">
						<span class="vendor-ad-title"><?php echo esc_html($ad_title); ?></span>
						<span class="vendor-ad-cta">Learn More →</span>
					</div>
				</a>
			<?php else : ?>
				<div class="vendor-ad-image">
					<img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($ad_title); ?>" loading="lazy">
				</div>
				<div class="vendor-ad-overlay">
					<span class="vendor-ad-title"><?php echo esc_html($ad_title); ?></span>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
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

<?php
/**
 * AJAX Request Handlers
 * 
 * @package EventRSVPPlugin
 */

if (!defined('ABSPATH')) {
	exit;
}

function event_rsvp_ajax_get_stats() {
	check_ajax_referer('event_rsvp_checkin', 'nonce');
	
	$event_id = intval($_POST['event_id'] ?? 0);
	
	if (!$event_id) {
		wp_send_json_error('Invalid event ID');
		return;
	}
	
	$stats = event_rsvp_get_event_stats($event_id);
	
	wp_send_json_success($stats);
}
add_action('wp_ajax_event_rsvp_get_stats', 'event_rsvp_ajax_get_stats');
add_action('wp_ajax_nopriv_event_rsvp_get_stats', 'event_rsvp_ajax_get_stats');

function event_rsvp_checkin_attendee() {
	check_ajax_referer('event_rsvp_checkin', 'nonce');

	if (!current_user_can('edit_posts')) {
		wp_send_json_error('Unauthorized');
		return;
	}

	$qr_data = sanitize_text_field($_POST['qr_data'] ?? '');

	if (empty($qr_data)) {
		wp_send_json_error('Invalid QR code');
		return;
	}

	$decoded_data = json_decode(base64_decode($qr_data), true);

	if (!isset($decoded_data['attendee_id'])) {
		wp_send_json_error('Invalid QR code data');
		return;
	}

	$attendee_id = intval($decoded_data['attendee_id']);
	$stored_qr_data = get_post_meta($attendee_id, 'qr_data', true);

	if ($stored_qr_data !== $qr_data) {
		wp_send_json_error('QR code verification failed');
		return;
	}

	update_post_meta($attendee_id, 'checkin_status', true);
	update_post_meta($attendee_id, 'checkin_time', current_time('mysql'));

	$attendee_name = get_the_title($attendee_id);

	wp_send_json_success(array(
		'message' => sprintf('Checked in: %s', $attendee_name),
		'attendee_id' => $attendee_id,
		'attendee_name' => $attendee_name
	));
}
add_action('wp_ajax_event_rsvp_checkin', 'event_rsvp_checkin_attendee');
add_action('wp_ajax_nopriv_event_rsvp_checkin', 'event_rsvp_checkin_attendee');

function event_rsvp_search_attendees() {
	check_ajax_referer('event_rsvp_checkin', 'nonce');

	$query = sanitize_text_field($_POST['query'] ?? '');
	$event_id = intval($_POST['event_id'] ?? 0);
	$current_user = wp_get_current_user();

	if (empty($query)) {
		wp_send_json_error('Query is required');
		return;
	}

	$args = array(
		'post_type' => 'attendee',
		'posts_per_page' => 20,
		's' => $query,
		'meta_query' => array()
	);

	if ($event_id > 0) {
		$args['meta_query'][] = array(
			'key' => 'linked_event',
			'value' => $event_id,
			'compare' => '='
		);
	} elseif (!current_user_can('administrator')) {
		$host_events = event_rsvp_get_user_events($current_user->ID);
		$host_event_ids = wp_list_pluck($host_events, 'ID');

		if (empty($host_event_ids)) {
			wp_send_json_success(array('attendees' => array()));
			return;
		}

		$args['meta_query'][] = array(
			'key' => 'linked_event',
			'value' => $host_event_ids,
			'compare' => 'IN'
		);
	}

	$attendees = get_posts($args);

	if (empty($attendees)) {
		$args = array(
			'post_type' => 'attendee',
			'posts_per_page' => 20,
			'meta_query' => array(
				array(
					'key' => 'attendee_email',
					'value' => $query,
					'compare' => 'LIKE'
				)
			)
		);

		if ($event_id > 0) {
			$args['meta_query'][] = array(
				'key' => 'linked_event',
				'value' => $event_id,
				'compare' => '='
			);
		} elseif (!current_user_can('administrator')) {
			$host_events = event_rsvp_get_user_events($current_user->ID);
			$host_event_ids = wp_list_pluck($host_events, 'ID');

			if (!empty($host_event_ids)) {
				$args['meta_query'][] = array(
					'key' => 'linked_event',
					'value' => $host_event_ids,
					'compare' => 'IN'
				);
			}
		}

		$attendees = get_posts($args);
	}

	$results = array();
	foreach ($attendees as $attendee) {
		$email = get_post_meta($attendee->ID, 'attendee_email', true);
		$qr_data = get_post_meta($attendee->ID, 'qr_data', true);
		$checked_in = get_post_meta($attendee->ID, 'checkin_status', true);

		$results[] = array(
			'id' => $attendee->ID,
			'name' => get_the_title($attendee->ID),
			'email' => $email,
			'qr_data' => $qr_data,
			'checked_in' => (bool) $checked_in
		);
	}

	wp_send_json_success(array('attendees' => $results));
}
add_action('wp_ajax_event_rsvp_search_attendees', 'event_rsvp_search_attendees');
add_action('wp_ajax_nopriv_event_rsvp_search_attendees', 'event_rsvp_search_attendees');

function event_rsvp_get_checked_in_attendees() {
	check_ajax_referer('event_rsvp_checkin', 'nonce');

	$event_id = intval($_POST['event_id'] ?? 0);
	$current_user = wp_get_current_user();

	$args = array(
		'post_type' => 'attendee',
		'posts_per_page' => -1,
		'orderby' => 'modified',
		'order' => 'DESC',
		'meta_query' => array(
			array(
				'key' => 'checkin_status',
				'value' => '1',
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
	} elseif (!current_user_can('administrator')) {
		$host_events = event_rsvp_get_user_events($current_user->ID);
		$host_event_ids = wp_list_pluck($host_events, 'ID');

		if (empty($host_event_ids)) {
			wp_send_json_success(array('attendees' => array()));
			return;
		}

		$args['meta_query'][] = array(
			'key' => 'linked_event',
			'value' => $host_event_ids,
			'compare' => 'IN'
		);
	}

	$attendees = get_posts($args);

	$results = array();
	foreach ($attendees as $attendee) {
		$email = get_post_meta($attendee->ID, 'attendee_email', true);
		$phone = get_post_meta($attendee->ID, 'attendee_phone', true);
		$checkin_time = get_post_meta($attendee->ID, 'checkin_time', true);
		$event_linked = get_post_meta($attendee->ID, 'linked_event', true);

		$results[] = array(
			'id' => $attendee->ID,
			'name' => get_the_title($attendee->ID),
			'email' => $email,
			'phone' => $phone,
			'checkin_time' => $checkin_time ? date('M j, Y g:i A', strtotime($checkin_time)) : get_the_modified_time('M j, Y g:i A', $attendee->ID),
			'event_id' => $event_linked,
			'event_title' => $event_linked ? get_the_title($event_linked) : ''
		);
	}

	wp_send_json_success(array('attendees' => $results));
}
add_action('wp_ajax_event_rsvp_get_checked_in_attendees', 'event_rsvp_get_checked_in_attendees');
add_action('wp_ajax_nopriv_event_rsvp_get_checked_in_attendees', 'event_rsvp_get_checked_in_attendees');

function event_rsvp_get_attendee_counts() {
	check_ajax_referer('event_rsvp_counts', 'nonce');

	$event_id = intval($_POST['event_id'] ?? 0);

	if (!$event_id) {
		wp_send_json_error('Invalid event ID');
		return;
	}

	$attendees = event_rsvp_get_attendees_by_event($event_id);

	$total = count($attendees);
	$yes_count = 0;
	$maybe_count = 0;
	$no_count = 0;

	foreach ($attendees as $attendee) {
		$rsvp_status = get_post_meta($attendee->ID, 'rsvp_status', true);
		if ($rsvp_status === 'yes') {
			$yes_count++;
		} elseif ($rsvp_status === 'maybe') {
			$maybe_count++;
		} elseif ($rsvp_status === 'no') {
			$no_count++;
		}
	}

	wp_send_json_success(array(
		'total' => $total,
		'yes' => $yes_count,
		'maybe' => $maybe_count,
		'no' => $no_count
	));
}
add_action('wp_ajax_event_rsvp_get_attendee_counts', 'event_rsvp_get_attendee_counts');
add_action('wp_ajax_nopriv_event_rsvp_get_attendee_counts', 'event_rsvp_get_attendee_counts');

function event_rsvp_get_all_attendees() {
	check_ajax_referer('event_rsvp_checkin', 'nonce');

	$event_id = intval($_POST['event_id'] ?? 0);
	$current_user = wp_get_current_user();

	$args = array(
		'post_type' => 'attendee',
		'posts_per_page' => -1,
		'orderby' => 'date',
		'order' => 'DESC'
	);

	if ($event_id > 0) {
		$args['meta_query'] = array(
			array(
				'key' => 'linked_event',
				'value' => $event_id,
				'compare' => '='
			)
		);
	} elseif (!current_user_can('administrator')) {
		$host_events = event_rsvp_get_user_events($current_user->ID);
		$host_event_ids = wp_list_pluck($host_events, 'ID');

		if (empty($host_event_ids)) {
			wp_send_json_success(array('attendees' => array()));
			return;
		}

		$args['meta_query'] = array(
			array(
				'key' => 'linked_event',
				'value' => $host_event_ids,
				'compare' => 'IN'
			)
		);
	}

	$attendees = get_posts($args);

	$results = array();
	foreach ($attendees as $attendee) {
		$email = get_post_meta($attendee->ID, 'attendee_email', true);
		$phone = get_post_meta($attendee->ID, 'attendee_phone', true);
		$checkin_status = get_post_meta($attendee->ID, 'checkin_status', true);
		$checkin_time = get_post_meta($attendee->ID, 'checkin_time', true);
		$qr_data = get_post_meta($attendee->ID, 'qr_data', true);
		$event_linked = get_post_meta($attendee->ID, 'linked_event', true);

		$results[] = array(
			'id' => $attendee->ID,
			'name' => get_the_title($attendee->ID),
			'email' => $email,
			'phone' => $phone,
			'qr_data' => $qr_data,
			'checked_in' => (bool) $checkin_status,
			'checkin_time' => $checkin_time ? date('M j, Y g:i A', strtotime($checkin_time)) : '',
			'event_id' => $event_linked,
			'event_title' => $event_linked ? get_the_title($event_linked) : ''
		);
	}

	wp_send_json_success(array('attendees' => $results));
}
add_action('wp_ajax_event_rsvp_get_all_attendees', 'event_rsvp_get_all_attendees');
add_action('wp_ajax_nopriv_event_rsvp_get_all_attendees', 'event_rsvp_get_all_attendees');

function event_rsvp_get_pending_attendees() {
	check_ajax_referer('event_rsvp_checkin', 'nonce');

	$event_id = intval($_POST['event_id'] ?? 0);
	$current_user = wp_get_current_user();

	$args = array(
		'post_type' => 'attendee',
		'posts_per_page' => -1,
		'orderby' => 'date',
		'order' => 'DESC',
		'meta_query' => array(
			array(
				'relation' => 'OR',
				array(
					'key' => 'checkin_status',
					'compare' => 'NOT EXISTS'
				),
				array(
					'key' => 'checkin_status',
					'value' => '0',
					'compare' => '='
				),
				array(
					'key' => 'checkin_status',
					'value' => '',
					'compare' => '='
				)
			)
		)
	);

	if ($event_id > 0) {
		$args['meta_query'][] = array(
			'key' => 'linked_event',
			'value' => $event_id,
			'compare' => '='
		);
	} elseif (!current_user_can('administrator')) {
		$host_events = event_rsvp_get_user_events($current_user->ID);
		$host_event_ids = wp_list_pluck($host_events, 'ID');

		if (empty($host_event_ids)) {
			wp_send_json_success(array('attendees' => array()));
			return;
		}

		$args['meta_query'][] = array(
			'key' => 'linked_event',
			'value' => $host_event_ids,
			'compare' => 'IN'
		);
	}

	$attendees = get_posts($args);

	$results = array();
	foreach ($attendees as $attendee) {
		$email = get_post_meta($attendee->ID, 'attendee_email', true);
		$phone = get_post_meta($attendee->ID, 'attendee_phone', true);
		$qr_data = get_post_meta($attendee->ID, 'qr_data', true);
		$event_linked = get_post_meta($attendee->ID, 'linked_event', true);

		$results[] = array(
			'id' => $attendee->ID,
			'name' => get_the_title($attendee->ID),
			'email' => $email,
			'phone' => $phone,
			'qr_data' => $qr_data,
			'checked_in' => false,
			'checkin_time' => '',
			'event_id' => $event_linked,
			'event_title' => $event_linked ? get_the_title($event_linked) : ''
		);
	}

	wp_send_json_success(array('attendees' => $results));
}
add_action('wp_ajax_event_rsvp_get_pending_attendees', 'event_rsvp_get_pending_attendees');
add_action('wp_ajax_nopriv_event_rsvp_get_pending_attendees', 'event_rsvp_get_pending_attendees');

function event_rsvp_track_ad_click() {
	check_ajax_referer('event_rsvp_checkin', 'nonce');
	
	$ad_id = intval($_POST['ad_id'] ?? 0);
	
	if (!$ad_id) {
		wp_send_json_error('Invalid ad ID');
		return;
	}
	
	$current_clicks = intval(get_post_meta($ad_id, 'ad_clicks', true));
	update_post_meta($ad_id, 'ad_clicks', $current_clicks + 1);
	
	wp_send_json_success(array('clicks' => $current_clicks + 1));
}
add_action('wp_ajax_event_rsvp_track_ad_click', 'event_rsvp_track_ad_click');
add_action('wp_ajax_nopriv_event_rsvp_track_ad_click', 'event_rsvp_track_ad_click');

function event_rsvp_approve_ad() {
	check_ajax_referer('event_rsvp_ad_management', 'nonce');

	if (!current_user_can('administrator')) {
		wp_send_json_error('Unauthorized');
		return;
	}

	$ad_id = intval($_POST['ad_id'] ?? 0);

	if (!$ad_id) {
		wp_send_json_error('Invalid ad ID');
		return;
	}

	update_post_meta($ad_id, 'ad_approval_status', 'approved');

	wp_send_json_success(array(
		'message' => 'Ad approved successfully!'
	));
}
add_action('wp_ajax_event_rsvp_approve_ad', 'event_rsvp_approve_ad');

function event_rsvp_reject_ad() {
	check_ajax_referer('event_rsvp_ad_management', 'nonce');

	if (!current_user_can('administrator')) {
		wp_send_json_error('Unauthorized');
		return;
	}

	$ad_id = intval($_POST['ad_id'] ?? 0);

	if (!$ad_id) {
		wp_send_json_error('Invalid ad ID');
		return;
	}

	update_post_meta($ad_id, 'ad_approval_status', 'rejected');
	update_post_meta($ad_id, 'ad_status', 'inactive');

	wp_send_json_success(array(
		'message' => 'Ad rejected successfully!'
	));
}
add_action('wp_ajax_event_rsvp_reject_ad', 'event_rsvp_reject_ad');

function event_rsvp_toggle_ad_status() {
	check_ajax_referer('event_rsvp_ad_management', 'nonce');

	if (!current_user_can('administrator')) {
		wp_send_json_error('Unauthorized');
		return;
	}

	$ad_id = intval($_POST['ad_id'] ?? 0);
	$status = sanitize_text_field($_POST['status'] ?? '');

	if (!$ad_id || !in_array($status, array('activate', 'deactivate', 'pause'))) {
		wp_send_json_error('Invalid parameters');
		return;
	}

	if ($status === 'activate') {
		$new_status = 'active';
		$message = 'Ad activated successfully!';
	} elseif ($status === 'pause') {
		$new_status = 'paused';
		$message = 'Ad paused successfully!';
	} else {
		$new_status = 'inactive';
		$message = 'Ad deactivated successfully!';
	}

	update_post_meta($ad_id, 'ad_status', $new_status);

	wp_send_json_success(array(
		'message' => $message,
		'new_status' => $new_status
	));
}
add_action('wp_ajax_event_rsvp_toggle_ad_status', 'event_rsvp_toggle_ad_status');

function event_rsvp_delete_ad() {
	check_ajax_referer('event_rsvp_ad_management', 'nonce');

	if (!current_user_can('administrator')) {
		wp_send_json_error('Unauthorized');
		return;
	}

	$ad_id = intval($_POST['ad_id'] ?? 0);

	if (!$ad_id) {
		wp_send_json_error('Invalid ad ID');
		return;
	}

	$result = wp_delete_post($ad_id, true);

	if ($result) {
		wp_send_json_success(array(
			'message' => 'Ad deleted successfully!'
		));
	} else {
		wp_send_json_error('Failed to delete ad');
	}
}
add_action('wp_ajax_event_rsvp_delete_ad', 'event_rsvp_delete_ad');

function event_rsvp_change_ad_location() {
	check_ajax_referer('event_rsvp_ad_management', 'nonce');

	if (!current_user_can('administrator')) {
		wp_send_json_error('Unauthorized');
		return;
	}

	$ad_id = intval($_POST['ad_id'] ?? 0);
	$location = sanitize_text_field($_POST['location'] ?? '');

	if (!$ad_id || empty($location)) {
		wp_send_json_error('Invalid parameters');
		return;
	}

	$valid_locations = array_keys(event_rsvp_get_ad_locations());

	if (!in_array($location, $valid_locations)) {
		wp_send_json_error('Invalid location');
		return;
	}

	update_post_meta($ad_id, 'slot_location', $location);

	wp_send_json_success(array(
		'message' => 'Ad location changed successfully!'
	));
}
add_action('wp_ajax_event_rsvp_change_ad_location', 'event_rsvp_change_ad_location');

function event_rsvp_get_ad_preview() {
	check_ajax_referer('event_rsvp_ad_management', 'nonce');

	if (!current_user_can('edit_posts')) {
		wp_send_json_error('Unauthorized');
		return;
	}

	$ad_id = intval($_POST['ad_id'] ?? 0);

	if (!$ad_id) {
		wp_send_json_error('Invalid ad ID');
		return;
	}

	$ad = get_post($ad_id);
	if (!$ad || $ad->post_type !== 'vendor_ad') {
		wp_send_json_error('Ad not found');
		return;
	}

	// Generate preview HTML using shortcode
	$preview_html = do_shortcode('[ad id="' . $ad_id . '" preview="true"]');

	if (empty($preview_html)) {
		wp_send_json_error('Failed to generate preview');
		return;
	}

	wp_send_json_success(array(
		'html' => $preview_html,
		'ad_id' => $ad_id,
		'title' => get_the_title($ad_id)
	));
}
add_action('wp_ajax_event_rsvp_get_ad_preview', 'event_rsvp_get_ad_preview');

function event_rsvp_resend_qr_email() {
	check_ajax_referer('event_rsvp_resend_qr', 'nonce');

	if (!current_user_can('edit_posts')) {
		wp_send_json_error('Unauthorized');
		return;
	}

	$attendee_id = intval($_POST['attendee_id'] ?? 0);

	if (!$attendee_id) {
		wp_send_json_error('Invalid attendee ID');
		return;
	}

	$attendee = get_post($attendee_id);
	if (!$attendee || $attendee->post_type !== 'attendee') {
		wp_send_json_error('Attendee not found');
		return;
	}

	$event_id = get_post_meta($attendee_id, 'linked_event', true);
	$event = get_post($event_id);

	if (!current_user_can('administrator') && get_current_user_id() != $event->post_author) {
		wp_send_json_error('You do not have permission to send emails for this event');
		return;
	}

	$result = event_rsvp_send_qr_email_now($attendee_id);

	if ($result) {
		$email = get_post_meta($attendee_id, 'attendee_email', true);
		$sent_time = get_post_meta($attendee_id, 'email_sent_time', true);
		wp_send_json_success(array(
			'message' => 'QR code email sent successfully!',
			'email' => $email,
			'sent_time' => $sent_time ? date('M j, Y g:i A', strtotime($sent_time)) : current_time('M j, Y g:i A')
		));
	} else {
		wp_send_json_error('Failed to send email. Please check email configuration.');
	}
}
add_action('wp_ajax_event_rsvp_resend_qr_email', 'event_rsvp_resend_qr_email');

function event_rsvp_set_featured_image() {
	check_ajax_referer('set_featured_image', 'nonce');

	if (!current_user_can('edit_posts')) {
		wp_send_json_error('Unauthorized');
		return;
	}

	$event_id = intval($_POST['event_id'] ?? 0);
	$attachment_id = intval($_POST['attachment_id'] ?? 0);

	if (!$event_id || !$attachment_id) {
		wp_send_json_error('Invalid parameters');
		return;
	}

	$event = get_post($event_id);
	if (!$event || $event->post_type !== 'event') {
		wp_send_json_error('Event not found');
		return;
	}

	if (!current_user_can('administrator') && get_current_user_id() != $event->post_author) {
		wp_send_json_error('You do not have permission to edit this event');
		return;
	}

	$result = set_post_thumbnail($event_id, $attachment_id);

	if ($result) {
		wp_send_json_success(array(
			'message' => 'Featured image set successfully!',
			'thumbnail_url' => get_the_post_thumbnail_url($event_id, 'large')
		));
	} else {
		wp_send_json_error('Failed to set featured image');
	}
}
add_action('wp_ajax_set_event_featured_image', 'event_rsvp_set_featured_image');

function event_rsvp_remove_featured_image() {
	check_ajax_referer('remove_featured_image', 'nonce');

	if (!current_user_can('edit_posts')) {
		wp_send_json_error('Unauthorized');
		return;
	}

	$event_id = intval($_POST['event_id'] ?? 0);

	if (!$event_id) {
		wp_send_json_error('Invalid event ID');
		return;
	}

	$event = get_post($event_id);
	if (!$event || $event->post_type !== 'event') {
		wp_send_json_error('Event not found');
		return;
	}

	if (!current_user_can('administrator') && get_current_user_id() != $event->post_author) {
		wp_send_json_error('You do not have permission to edit this event');
		return;
	}

	$result = delete_post_thumbnail($event_id);

	if ($result) {
		wp_send_json_success(array(
			'message' => 'Featured image removed successfully!'
		));
	} else {
		wp_send_json_error('Failed to remove featured image');
	}
}
add_action('wp_ajax_remove_event_featured_image', 'event_rsvp_remove_featured_image');

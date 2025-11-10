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

function event_rsvp_register_user() {
	check_ajax_referer('event_rsvp_register', 'nonce');

	$username = sanitize_user($_POST['username'] ?? '');
	$email = sanitize_email($_POST['email'] ?? '');
	$password = $_POST['password'] ?? '';
	$first_name = sanitize_text_field($_POST['first_name'] ?? '');
	$last_name = sanitize_text_field($_POST['last_name'] ?? '');
	$user_role = sanitize_text_field($_POST['user_role'] ?? 'subscriber');

	if (empty($username) || empty($email) || empty($password)) {
		wp_send_json_error('Please fill in all required fields.');
		return;
	}

	if (!is_email($email)) {
		wp_send_json_error('Please enter a valid email address.');
		return;
	}

	if (strlen($password) < 8) {
		wp_send_json_error('Password must be at least 8 characters long.');
		return;
	}

	if (username_exists($username)) {
		wp_send_json_error('Username already exists. Please choose another.');
		return;
	}

	if (email_exists($email)) {
		wp_send_json_error('Email address already registered. Please login instead.');
		return;
	}

	$valid_roles = array('event_host', 'vendor', 'pro', 'subscriber');
	if (!in_array($user_role, $valid_roles)) {
		$user_role = 'subscriber';
	}

	$user_id = wp_create_user($username, $password, $email);

	if (is_wp_error($user_id)) {
		wp_send_json_error($user_id->get_error_message());
		return;
	}

	wp_update_user(array(
		'ID' => $user_id,
		'first_name' => $first_name,
		'last_name' => $last_name,
		'display_name' => $first_name . ' ' . $last_name,
		'role' => $user_role
	));

	$user = get_user_by('id', $user_id);
	wp_set_current_user($user_id, $user->user_login);
	wp_set_auth_cookie($user_id, true);
	do_action('wp_login', $user->user_login, $user);

	$redirect_url = ($user_role === 'event_host' || $user_role === 'pro') ? home_url('/host-dashboard/') : home_url('/events/');

	wp_send_json_success(array(
		'message' => 'Account created successfully!',
		'redirect' => $redirect_url
	));
}
add_action('wp_ajax_nopriv_event_rsvp_register_user', 'event_rsvp_register_user');
add_action('wp_ajax_event_rsvp_register_user', 'event_rsvp_register_user');

<?php
/**
 * Form Submission Handlers
 * 
 * @package EventRSVPPlugin
 */

if (!defined('ABSPATH')) {
	exit;
}

function event_rsvp_handle_rsvp_submission() {
	if (!isset($_POST['event_rsvp_nonce']) || !wp_verify_nonce($_POST['event_rsvp_nonce'], 'event_rsvp_submit')) {
		wp_die('Security check failed');
	}

	$attendee_name = sanitize_text_field($_POST['attendee-name'] ?? '');
	$attendee_email = sanitize_email($_POST['attendee-email'] ?? '');
	$attendee_phone = sanitize_text_field($_POST['attendee-phone'] ?? '');
	$rsvp_status = sanitize_text_field($_POST['rsvp-status'] ?? 'yes');
	$event_id = intval($_POST['event-id'] ?? 0);

	if (empty($attendee_name) || empty($attendee_email) || !$event_id) {
		wp_redirect(add_query_arg('rsvp', 'error', get_permalink($event_id)));
		exit;
	}

	if (event_rsvp_is_event_past($event_id)) {
		wp_redirect(add_query_arg('rsvp', 'past', get_permalink($event_id)));
		exit;
	}

	if (event_rsvp_is_event_full($event_id)) {
		wp_redirect(add_query_arg('rsvp', 'full', get_permalink($event_id)));
		exit;
	}

	$existing = event_rsvp_get_attendee_by_email($attendee_email, $event_id);

	if ($existing) {
		update_post_meta($existing->ID, 'rsvp_status', $rsvp_status);
		update_post_meta($existing->ID, 'attendee_phone', $attendee_phone);
		$attendee_id = $existing->ID;

		$email_sent = event_rsvp_send_qr_email_now($attendee_id);

		if ($email_sent) {
			wp_redirect(add_query_arg(array('rsvp' => 'success', 'email' => 'sent'), get_permalink($event_id)));
		} else {
			wp_redirect(add_query_arg(array('rsvp' => 'success', 'email' => 'failed'), get_permalink($event_id)));
		}
		exit;
	} else {
		$attendee_id = wp_insert_post(array(
			'post_type' => 'attendee',
			'post_title' => $attendee_name,
			'post_status' => 'publish',
		));

		if (is_wp_error($attendee_id)) {
			wp_redirect(add_query_arg('rsvp', 'error', get_permalink($event_id)));
			exit;
		}

		update_post_meta($attendee_id, 'attendee_email', $attendee_email);
		update_post_meta($attendee_id, 'attendee_phone', $attendee_phone);
		update_post_meta($attendee_id, 'rsvp_status', $rsvp_status);
		update_post_meta($attendee_id, 'linked_event', $event_id);
		update_post_meta($attendee_id, 'checkin_status', false);

		$qr_data = base64_encode(json_encode(array(
			'attendee_id' => $attendee_id,
			'event_id' => $event_id,
			'email' => $attendee_email,
			'verification' => wp_hash($attendee_id . $event_id . $attendee_email)
		)));

		update_post_meta($attendee_id, 'qr_data', $qr_data);

		$email_sent = event_rsvp_send_qr_email_now($attendee_id);

		if ($email_sent) {
			wp_redirect(add_query_arg(array('rsvp' => 'success', 'email' => 'sent'), get_permalink($event_id)));
		} else {
			wp_redirect(add_query_arg(array('rsvp' => 'success', 'email' => 'failed'), get_permalink($event_id)));
		}
		exit;
	}

	wp_redirect(add_query_arg('rsvp', 'success', get_permalink($event_id)));
	exit;
}
add_action('admin_post_nopriv_event_rsvp_submit', 'event_rsvp_handle_rsvp_submission');
add_action('admin_post_event_rsvp_submit', 'event_rsvp_handle_rsvp_submission');

function event_rsvp_handle_login() {
	if (!isset($_POST['login_nonce']) || !wp_verify_nonce($_POST['login_nonce'], 'event_rsvp_login')) {
		wp_die('Security check failed');
	}

	$username = sanitize_user($_POST['log'] ?? '');
	$password = $_POST['pwd'] ?? '';
	$remember = isset($_POST['rememberme']);
	$redirect_to = $_POST['redirect_to'] ?? home_url('/my-account/');

	if (empty($username) || empty($password)) {
		wp_redirect(add_query_arg('login', 'empty', home_url('/login/')));
		exit;
	}

	$creds = array(
		'user_login' => $username,
		'user_password' => $password,
		'remember' => $remember
	);

	$user = wp_signon($creds, is_ssl());

	if (is_wp_error($user)) {
		wp_redirect(add_query_arg('login', 'failed', home_url('/login/')));
		exit;
	}

	wp_redirect($redirect_to);
	exit;
}
add_action('admin_post_nopriv_event_rsvp_login', 'event_rsvp_handle_login');
add_action('admin_post_event_rsvp_login', 'event_rsvp_handle_login');

function event_rsvp_logout_redirect() {
	wp_redirect(add_query_arg('logout', 'success', home_url('/login/')));
	exit;
}
add_action('wp_logout', 'event_rsvp_logout_redirect');

function event_rsvp_handle_acf_event_submission($post_id) {
	if (get_post_type($post_id) !== 'event') {
		return;
	}

	if (isset($_POST['event_featured_image_id']) && !empty($_POST['event_featured_image_id'])) {
		$attachment_id = intval($_POST['event_featured_image_id']);

		if ($attachment_id > 0) {
			set_post_thumbnail($post_id, $attachment_id);
		}
	}
}
add_action('acf/save_post', 'event_rsvp_handle_acf_event_submission', 20);

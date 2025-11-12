<?php
/**
 * Stripe AJAX Handlers
 * 
 * @package EventRSVPPlugin
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Handle registration and payment link creation
 */
function event_rsvp_create_payment_link() {
	check_ajax_referer('event_rsvp_register', 'nonce');

	$username = sanitize_user($_POST['username'] ?? '');
	$email = sanitize_email($_POST['email'] ?? '');
	$password = $_POST['password'] ?? '';
	$first_name = sanitize_text_field($_POST['first_name'] ?? '');
	$last_name = sanitize_text_field($_POST['last_name'] ?? '');
	$pricing_plan = sanitize_text_field($_POST['pricing_plan'] ?? '');
	
	// Validation
	if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
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
	
	// For free plan, create account immediately
	if (empty($pricing_plan) || $pricing_plan === 'attendee') {
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
			'role' => 'subscriber'
		));

		$user = get_user_by('id', $user_id);
		wp_set_current_user($user_id, $user->user_login);
		wp_set_auth_cookie($user_id, true);
		do_action('wp_login', $user->user_login, $user);

		wp_send_json_success(array(
			'message' => 'Account created successfully!',
			'redirect' => home_url('/browse-events/'),
			'requires_payment' => false
		));
		return;
	}
	
	// For paid plans, create payment link
	$stripe = Event_RSVP_Stripe_Integration::get_instance();
	
	$user_data = array(
		'username' => $username,
		'email' => $email,
		'password' => $password,
		'first_name' => $first_name,
		'last_name' => $last_name
	);
	
	$result = $stripe->create_payment_link($pricing_plan, $user_data);
	
	if (is_wp_error($result)) {
		wp_send_json_error($result->get_error_message());
		return;
	}
	
	wp_send_json_success(array(
		'message' => 'Redirecting to secure payment...',
		'checkout_url' => $result['checkout_url'],
		'requires_payment' => true
	));
}
add_action('wp_ajax_nopriv_event_rsvp_create_payment_link', 'event_rsvp_create_payment_link');
add_action('wp_ajax_event_rsvp_create_payment_link', 'event_rsvp_create_payment_link');

/**
 * Verify payment completion token
 */
function event_rsvp_verify_payment_token() {
	check_ajax_referer('event_rsvp_verify_token', 'nonce');
	
	$token = sanitize_text_field($_POST['token'] ?? '');
	$session_id = sanitize_text_field($_POST['session_id'] ?? '');
	
	if (empty($token) || empty($session_id)) {
		wp_send_json_error('Invalid verification data.');
		return;
	}
	
	global $wpdb;
	$table_name = $wpdb->prefix . 'event_rsvp_pending_registrations';
	
	// Find completed registration with this token
	$registrations = $wpdb->get_results(
		"SELECT * FROM $table_name WHERE stripe_session_id = '" . esc_sql($session_id) . "' AND status = 'completed'"
	);
	
	if (empty($registrations)) {
		wp_send_json_error('Registration not found or not yet processed. Please wait a moment and try again.');
		return;
	}
	
	$registration = $registrations[0];
	
	// Verify token
	if (!wp_check_password($token, $registration->token_hash)) {
		wp_send_json_error('Invalid verification token.');
		return;
	}
	
	// Find the created user
	$user = get_user_by('email', $registration->email);
	
	if (!$user) {
		wp_send_json_error('Account not found. Please contact support.');
		return;
	}
	
	// Log the user in
	wp_set_current_user($user->ID, $user->user_login);
	wp_set_auth_cookie($user->ID, true);
	do_action('wp_login', $user->user_login, $user);
	
	wp_send_json_success(array(
		'message' => 'Payment verified! Logging you in...',
		'redirect' => home_url('/host-dashboard/')
	));
}
add_action('wp_ajax_nopriv_event_rsvp_verify_payment_token', 'event_rsvp_verify_payment_token');
add_action('wp_ajax_event_rsvp_verify_payment_token', 'event_rsvp_verify_payment_token');

/**
 * Check registration status
 */
function event_rsvp_check_registration_status() {
	check_ajax_referer('event_rsvp_verify_token', 'nonce');
	
	$session_id = sanitize_text_field($_POST['session_id'] ?? '');
	
	if (empty($session_id)) {
		wp_send_json_error('Session ID required.');
		return;
	}
	
	global $wpdb;
	$table_name = $wpdb->prefix . 'event_rsvp_pending_registrations';
	
	$registration = $wpdb->get_row($wpdb->prepare(
		"SELECT * FROM $table_name WHERE stripe_session_id = %s",
		$session_id
	));
	
	if (!$registration) {
		wp_send_json_error('Registration not found.');
		return;
	}
	
	wp_send_json_success(array(
		'status' => $registration->status,
		'email' => $registration->email,
		'username' => $registration->username
	));
}
add_action('wp_ajax_nopriv_event_rsvp_check_registration_status', 'event_rsvp_check_registration_status');
add_action('wp_ajax_event_rsvp_check_registration_status', 'event_rsvp_check_registration_status');

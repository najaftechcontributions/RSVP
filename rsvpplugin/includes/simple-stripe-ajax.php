<?php
/**
 * Simple Stripe AJAX Handlers
 * 
 * @package EventRSVPPlugin
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Handle user registration with payment
 */
function event_rsvp_register_with_payment() {
	check_ajax_referer('event_rsvp_register', 'nonce');
	
	$pricing_plan = isset($_POST['pricing_plan']) ? sanitize_text_field($_POST['pricing_plan']) : '';
	$is_paid_plan = isset($_POST['is_paid_plan']) && $_POST['is_paid_plan'] === '1';
	
	// Validate required fields
	$required_fields = array('username', 'email', 'password', 'first_name', 'last_name');
	foreach ($required_fields as $field) {
		if (empty($_POST[$field])) {
			wp_send_json_error('All fields are required.');
			return;
		}
	}
	
	// Prepare user data
	$user_data = array(
		'username' => sanitize_user($_POST['username']),
		'email' => sanitize_email($_POST['email']),
		'password' => $_POST['password'],
		'first_name' => sanitize_text_field($_POST['first_name']),
		'last_name' => sanitize_text_field($_POST['last_name']),
		'plan' => $pricing_plan
	);
	
	// Validate email
	if (!is_email($user_data['email'])) {
		wp_send_json_error('Invalid email address.');
		return;
	}
	
	$stripe = Event_RSVP_Simple_Stripe::get_instance();
	
	// For free attendee plan
	if (!$is_paid_plan || empty($pricing_plan) || $pricing_plan === 'attendee') {
		// Check if username exists
		if (username_exists($user_data['username'])) {
			wp_send_json_error('Username already exists.');
			return;
		}
		
		// Check if email exists
		if (email_exists($user_data['email'])) {
			wp_send_json_error('Email already exists.');
			return;
		}
		
		// Create free attendee account
		$user_id = wp_create_user(
			$user_data['username'],
			$user_data['password'],
			$user_data['email']
		);
		
		if (is_wp_error($user_id)) {
			wp_send_json_error($user_id->get_error_message());
			return;
		}
		
		// Update user details
		wp_update_user(array(
			'ID' => $user_id,
			'first_name' => $user_data['first_name'],
			'last_name' => $user_data['last_name'],
			'display_name' => $user_data['first_name'] . ' ' . $user_data['last_name'],
			'role' => 'subscriber'
		));
		
		update_user_meta($user_id, 'event_rsvp_plan', 'attendee');
		
		// Send welcome email
		$subject = 'Welcome to ' . get_bloginfo('name');
		$message = "Hi {$user_data['first_name']},\n\n";
		$message .= "Your free Attendee account has been created!\n\n";
		$message .= "Username: {$user_data['username']}\n";
		$message .= "You can log in at: " . home_url('/login/') . "\n\n";
		$message .= "Start browsing events: " . home_url('/browse-events/') . "\n\n";
		$message .= "Best regards,\n" . get_bloginfo('name');
		
		wp_mail($user_data['email'], $subject, $message);
		
		wp_send_json_success(array(
			'message' => 'Account created successfully!',
			'redirect' => home_url('/browse-events/?welcome=1')
		));
		return;
	}
	
	// For paid plans
	$payment_url = $stripe->get_payment_url($pricing_plan, $user_data);
	
	if (is_wp_error($payment_url)) {
		wp_send_json_error($payment_url->get_error_message());
		return;
	}
	
	if (!$payment_url) {
		wp_send_json_error('Payment link not configured for this plan. Please contact support.');
		return;
	}
	
	wp_send_json_success(array(
		'message' => 'Account created! Redirecting to payment...',
		'checkout_url' => $payment_url
	));
}
add_action('wp_ajax_nopriv_event_rsvp_register_user', 'event_rsvp_register_with_payment');
add_action('wp_ajax_event_rsvp_register_user', 'event_rsvp_register_with_payment');

/**
 * Verify payment token
 */
function event_rsvp_verify_payment_token() {
	check_ajax_referer('event_rsvp_verify_token', 'nonce');
	
	$token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
	$plan = isset($_POST['plan']) ? sanitize_text_field($_POST['plan']) : '';
	
	if (empty($token) || empty($plan)) {
		wp_send_json_error('Invalid verification data.');
		return;
	}
	
	$stripe = Event_RSVP_Simple_Stripe::get_instance();
	$result = $stripe->verify_payment_and_upgrade($token, $plan);
	
	if ($result['success']) {
		$redirect_map = array(
			'event_host' => home_url('/host-dashboard/?welcome=1'),
			'vendor' => home_url('/vendor-dashboard/?welcome=1'),
			'pro' => home_url('/host-dashboard/?welcome=1&pro=1')
		);
		
		$redirect = isset($redirect_map[$plan]) ? $redirect_map[$plan] : home_url('/');
		
		wp_send_json_success(array(
			'message' => 'Payment verified! Your account has been upgraded.',
			'redirect' => $redirect
		));
	} else {
		wp_send_json_error($result['message']);
	}
}
add_action('wp_ajax_nopriv_event_rsvp_verify_payment_token', 'event_rsvp_verify_payment_token');
add_action('wp_ajax_event_rsvp_verify_payment_token', 'event_rsvp_verify_payment_token');

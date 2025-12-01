<?php
/**
 * Upgrade AJAX Handler
 * Handles upgrade requests from logged-in users
 * 
 * @package EventRSVPPlugin
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Handle upgrade request from logged-in user
 */
function event_rsvp_initiate_upgrade() {
	// Verify user is logged in
	if (!is_user_logged_in()) {
		wp_send_json_error('You must be logged in to upgrade.');
		return;
	}
	
	// Verify nonce
	check_ajax_referer('event_rsvp_upgrade', 'nonce');
	
	$plan_slug = isset($_POST['plan']) ? sanitize_text_field($_POST['plan']) : '';
	
	if (empty($plan_slug)) {
		wp_send_json_error('Invalid plan selected.');
		return;
	}
	
	$user_id = get_current_user_id();
	$current_plan = Event_RSVP_Simple_Stripe::get_user_plan($user_id);
	
	// Validate upgrade path
	if ($current_plan === $plan_slug) {
		wp_send_json_error('You are already on this plan.');
		return;
	}
	
	// Get upgrade payment URL using helper function
	$payment_url = event_rsvp_get_upgrade_payment_url($plan_slug, $user_id);
	
	if (!$payment_url) {
		wp_send_json_error('Payment link not configured for this plan. Please contact support.');
		return;
	}
	
	wp_send_json_success(array(
		'message' => 'Redirecting to payment...',
		'checkout_url' => $payment_url
	));
}
add_action('wp_ajax_event_rsvp_initiate_upgrade', 'event_rsvp_initiate_upgrade');

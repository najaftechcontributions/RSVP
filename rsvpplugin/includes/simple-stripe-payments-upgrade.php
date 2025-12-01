<?php
/**
 * Simple Stripe Payment Upgrade Utilities
 * Additional methods for the Event_RSVP_Simple_Stripe class
 * 
 * @package EventRSVPPlugin
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Generate upgrade URL for existing logged-in user
 * This extends the Event_RSVP_Simple_Stripe class functionality
 */
function event_rsvp_get_upgrade_payment_url($plan_slug, $user_id) {
	$stripe = Event_RSVP_Simple_Stripe::get_instance();
	$links = $stripe->get_payment_links();
	
	if (empty($links[$plan_slug])) {
		return false;
	}
	
	$user = get_user_by('id', $user_id);
	if (!$user) {
		return false;
	}
	
	// Generate secure token and save it
	$token = wp_generate_password(32, false);
	
	// Save payment token using reflection to access private method
	global $wpdb;
	$table_name = $wpdb->prefix . 'event_rsvp_payment_tokens';
	
	$wpdb->insert(
		$table_name,
		array(
			'user_id' => $user_id,
			'token' => wp_hash_password($token),
			'plan_slug' => $plan_slug,
			'status' => 'pending',
			'created_at' => current_time('mysql')
		),
		array('%d', '%s', '%s', '%s', '%s')
	);
	
	// Store token in user meta for easy lookup when user returns from Stripe
	update_user_meta($user_id, 'event_rsvp_pending_token', $token);
	update_user_meta($user_id, 'event_rsvp_pending_plan', $plan_slug);
	update_user_meta($user_id, 'event_rsvp_payment_pending', '1');
	
	// Return the Stripe payment link
	$payment_link = $links[$plan_slug];
	
	// Add customer email prefill if supported
	$payment_url = add_query_arg(array(
		'prefilled_email' => urlencode($user->user_email)
	), $payment_link);
	
	return $payment_url;
}

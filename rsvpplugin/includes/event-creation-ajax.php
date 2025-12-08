<?php
/**
 * Event Creation AJAX Handlers
 * Handles event limit checks and email campaign creation
 * 
 * @package EventRSVPPlugin
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Check if user can create event before ACF form submission
 */
function event_rsvp_check_event_limit() {
	check_ajax_referer('acf_nonce', 'nonce');
	
	if (!is_user_logged_in()) {
		wp_send_json_error('You must be logged in to create events');
		return;
	}
	
	$user_id = get_current_user_id();
	
	if (!function_exists('event_rsvp_can_create_event')) {
		require_once get_template_directory() . '/rsvpplugin/includes/event-limit-functions.php';
	}
	
	$can_create = event_rsvp_can_create_event($user_id);
	
	if (!$can_create) {
		$limit = event_rsvp_get_event_limit($user_id);
		$current_count = event_rsvp_get_user_event_count($user_id);
		$plan = Event_RSVP_Simple_Stripe::get_user_plan($user_id);
		
		if ($limit === 0) {
			wp_send_json_error('Your plan does not allow event creation. <a href="' . home_url('/pricing/') . '">Upgrade to create events</a>.');
		} else {
			wp_send_json_error('You have reached your event limit (' . $limit . ' event' . ($limit > 1 ? 's' : '') . '). <a href="' . home_url('/pricing/') . '">Upgrade your plan</a>.');
		}
		return;
	}
	
	wp_send_json_success(array(
		'can_create' => true,
		'message' => 'You can create this event'
	));
}
add_action('wp_ajax_event_rsvp_check_event_limit', 'event_rsvp_check_event_limit');

/**
 * After ACF form submission, handle event creation success
 * Triggered via ACF hook
 */
function event_rsvp_after_event_created($post_id) {
	// Only run for event post type
	if (get_post_type($post_id) !== 'event') {
		return;
	}
	
	// Only run for new events (not updates)
	if (isset($_POST['acf']['_validate']) || (isset($_POST['_acf_post_id']) && strpos($_POST['_acf_post_id'], 'new_post') === false)) {
		return;
	}
	
	// Store event ID in session to redirect to campaign creation
	if (!session_id()) {
		session_start();
	}
	$_SESSION['newly_created_event_id'] = $post_id;
}
add_action('acf/save_post', 'event_rsvp_after_event_created', 20);

/**
 * Get newly created event ID and prompt for email campaign
 */
function event_rsvp_get_new_event_prompt() {
	check_ajax_referer('event_rsvp_checkin', 'nonce');
	
	if (!session_id()) {
		session_start();
	}
	
	if (isset($_SESSION['newly_created_event_id'])) {
		$event_id = $_SESSION['newly_created_event_id'];
		$event_title = get_the_title($event_id);
		
		// Clear the session
		unset($_SESSION['newly_created_event_id']);
		
		wp_send_json_success(array(
			'event_id' => $event_id,
			'event_title' => $event_title,
			'show_campaign_prompt' => true
		));
	} else {
		wp_send_json_success(array(
			'show_campaign_prompt' => false
		));
	}
}
add_action('wp_ajax_event_rsvp_get_new_event_prompt', 'event_rsvp_get_new_event_prompt');

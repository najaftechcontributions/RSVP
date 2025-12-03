<?php
/**
 * Event Limit Functions
 * Check event creation limits based on user plans
 * 
 * @package EventRSVPPlugin
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Get event limit for a user based on their plan
 */
function event_rsvp_get_event_limit($user_id = null) {
	if (!$user_id) {
		$user_id = get_current_user_id();
	}
	
	if (!$user_id) {
		return 0;
	}
	
	// Admins get unlimited events
	if (user_can($user_id, 'administrator')) {
		return -1;
	}
	
	$plan = Event_RSVP_Simple_Stripe::get_user_plan($user_id);
	
	$limits = array(
		'attendee' => 0,
		'pay_as_you_go' => 1,
		'event_planner' => 5,
		'event_host' => -1, // Unlimited (legacy)
		'vendor' => 0,
		'pro' => -1, // Unlimited
	);
	
	return isset($limits[$plan]) ? $limits[$plan] : 0;
}

/**
 * Check if user can create more events
 */
function event_rsvp_can_create_event($user_id = null) {
	if (!$user_id) {
		$user_id = get_current_user_id();
	}
	
	if (!$user_id) {
		return false;
	}
	
	// Admins can always create events
	if (user_can($user_id, 'administrator')) {
		return true;
	}
	
	$limit = event_rsvp_get_event_limit($user_id);
	
	// -1 means unlimited
	if ($limit === -1) {
		return true;
	}
	
	// 0 means no events allowed
	if ($limit === 0) {
		return false;
	}
	
	// Count current events
	$current_count = event_rsvp_get_user_event_count($user_id);
	
	return $current_count < $limit;
}

/**
 * Get current event count for user
 */
function event_rsvp_get_user_event_count($user_id = null) {
	if (!$user_id) {
		$user_id = get_current_user_id();
	}
	
	if (!$user_id) {
		return 0;
	}
	
	$events = get_posts(array(
		'post_type' => 'event',
		'author' => $user_id,
		'posts_per_page' => -1,
		'post_status' => 'publish',
		'fields' => 'ids'
	));
	
	return count($events);
}

/**
 * Get remaining events user can create
 */
function event_rsvp_get_remaining_events($user_id = null) {
	if (!$user_id) {
		$user_id = get_current_user_id();
	}
	
	$limit = event_rsvp_get_event_limit($user_id);
	
	if ($limit === -1) {
		return -1; // Unlimited
	}
	
	if ($limit === 0) {
		return 0;
	}
	
	$current_count = event_rsvp_get_user_event_count($user_id);
	$remaining = $limit - $current_count;
	
	return max(0, $remaining);
}

/**
 * Display event limit notice on event creation page
 */
function event_rsvp_display_event_limit_notice() {
	$user_id = get_current_user_id();
	
	if (!$user_id) {
		return '';
	}
	
	$limit = event_rsvp_get_event_limit($user_id);
	$current_count = event_rsvp_get_user_event_count($user_id);
	$remaining = event_rsvp_get_remaining_events($user_id);
	$plan = Event_RSVP_Simple_Stripe::get_user_plan($user_id);
	
	if ($limit === -1) {
		$message = user_can($user_id, 'administrator') 
			? '✓ You have unlimited event creation (Administrator)'
			: '✓ You can create unlimited events!';
		return '<div class="event-limit-notice unlimited-notice">' . $message . '</div>';
	}
	
	if ($limit === 0) {
		return '<div class="event-limit-notice no-events-notice">⚠ Your current plan does not allow event creation. <a href="' . home_url('/pricing/') . '">Upgrade to create events</a></div>';
	}
	
	if ($remaining === 0) {
		return '<div class="event-limit-notice limit-reached-notice">⚠ You have reached your event limit (' . $limit . ' event' . ($limit > 1 ? 's' : '') . '). <a href="' . home_url('/pricing/') . '">Upgrade your plan</a> or delete old events to create new ones.</div>';
	}
	
	$notice_class = $remaining <= 1 ? 'low-limit-notice' : 'normal-limit-notice';
	
	return '<div class="event-limit-notice ' . $notice_class . '">📊 You can create ' . $remaining . ' more event' . ($remaining > 1 ? 's' : '') . ' (' . $current_count . ' of ' . $limit . ' used)</div>';
}

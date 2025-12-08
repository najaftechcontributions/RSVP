<?php
/**
 * Admin Area Redirects
 * Redirect Event Hosts, Vendors, and Pro users away from wp-admin to their frontend dashboards
 * 
 * @package EventRSVPPlugin
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Redirect non-admin users away from wp-admin
 */
function event_rsvp_redirect_from_admin() {
	if (!is_admin() || wp_doing_ajax()) {
		return;
	}

	$current_user = wp_get_current_user();
	
	// Don't redirect administrators
	if (in_array('administrator', $current_user->roles)) {
		return;
	}

	// Check if user should be redirected to frontend
	$redirect_roles = array('event_host', 'vendor', 'pro', 'subscriber');
	$user_roles = (array) $current_user->roles;
	$should_redirect = !empty(array_intersect($redirect_roles, $user_roles));

	if ($should_redirect) {
		// Determine redirect destination based on role
		$redirect_url = home_url('/my-account/');
		
		if (in_array('event_host', $user_roles) && !in_array('vendor', $user_roles) && !in_array('pro', $user_roles)) {
			$redirect_url = home_url('/host-dashboard/');
		} elseif (in_array('vendor', $user_roles) && !in_array('event_host', $user_roles) && !in_array('pro', $user_roles)) {
			$redirect_url = home_url('/vendor-dashboard/');
		} elseif (in_array('pro', $user_roles)) {
			$redirect_url = home_url('/my-account/');
		}

		wp_redirect($redirect_url);
		exit;
	}
}
add_action('admin_init', 'event_rsvp_redirect_from_admin');

/**
 * Hide admin bar for non-admin users
 */
function event_rsvp_hide_admin_bar($show) {
	$current_user = wp_get_current_user();
	
	// Hide admin bar for event hosts, vendors, pro, and subscribers
	if (in_array('event_host', $current_user->roles) || 
	    in_array('vendor', $current_user->roles) || 
	    in_array('pro', $current_user->roles) || 
	    in_array('subscriber', $current_user->roles)) {
		return false;
	}
	
	return $show;
}
add_filter('show_admin_bar', 'event_rsvp_hide_admin_bar');

/**
 * Redirect users to appropriate page after login
 */
function event_rsvp_login_redirect($redirect_to, $request, $user) {
	// Check if user object exists
	if (isset($user->roles) && is_array($user->roles)) {
		// Redirect administrators to admin dashboard
		if (in_array('administrator', $user->roles)) {
			return admin_url();
		}
		
		// Redirect event hosts to host dashboard
		if (in_array('event_host', $user->roles) && !in_array('vendor', $user->roles) && !in_array('pro', $user->roles)) {
			return home_url('/host-dashboard/');
		}
		
		// Redirect vendors to vendor dashboard
		if (in_array('vendor', $user->roles) && !in_array('event_host', $user->roles) && !in_array('pro', $user->roles)) {
			return home_url('/vendor-dashboard/');
		}
		
		// Redirect pro users to my account
		if (in_array('pro', $user->roles)) {
			return home_url('/my-account/');
		}
		
		// Redirect subscribers to my account
		if (in_array('subscriber', $user->roles)) {
			return home_url('/my-account/');
		}
	}
	
	return $redirect_to;
}
add_filter('login_redirect', 'event_rsvp_login_redirect', 10, 3);

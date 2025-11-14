<?php
/**
 * Custom User Roles
 * 
 * @package EventRSVPPlugin
 */

if (!defined('ABSPATH')) {
	exit;
}

function event_rsvp_add_custom_roles() {
	remove_role('event_host');
	remove_role('vendor');
	remove_role('pro');
	remove_role('event_staff');

	add_role('event_host', 'Event Host', array(
		'read' => true,
		'edit_posts' => true,
		'delete_posts' => true,
		'publish_posts' => true,
		'upload_files' => true,
		'edit_published_posts' => true,
		'delete_published_posts' => true,
	));

	add_role('vendor', 'Vendor', array(
		'read' => true,
		'upload_files' => true,
		'edit_posts' => true,
		'delete_posts' => true,
		'publish_posts' => true,
		'edit_published_posts' => true,
		'delete_published_posts' => true,
	));

	add_role('pro', 'Pro (Event Host + Vendor)', array(
		'read' => true,
		'edit_posts' => true,
		'delete_posts' => true,
		'publish_posts' => true,
		'upload_files' => true,
		'edit_published_posts' => true,
		'delete_published_posts' => true,
	));

	add_role('event_staff', 'Event Staff', array(
		'read' => true,
	));

	update_option('event_rsvp_roles_version', '2.1.0');
}

function event_rsvp_check_and_add_roles() {
	$current_version = get_option('event_rsvp_roles_version', '0');

	if (version_compare($current_version, '2.1.0', '<')) {
		event_rsvp_add_custom_roles();
	}
}
add_action('init', 'event_rsvp_check_and_add_roles', 5);
add_action('after_switch_theme', 'event_rsvp_add_custom_roles');

function event_rsvp_restrict_admin_menu() {
	$current_user = wp_get_current_user();

	if (in_array('vendor', $current_user->roles)) {
		remove_menu_page('edit.php');
		remove_menu_page('edit.php?post_type=page');
		remove_menu_page('edit.php?post_type=event');
		remove_menu_page('edit.php?post_type=attendee');
		remove_menu_page('edit.php?post_type=product');
		remove_menu_page('tools.php');
		remove_menu_page('edit-comments.php');
	} elseif (in_array('event_host', $current_user->roles)) {
		remove_menu_page('edit.php');
		remove_menu_page('edit.php?post_type=page');
		remove_menu_page('edit.php?post_type=vendor_ad');
		remove_menu_page('tools.php');
		remove_menu_page('edit-comments.php');
	}
}
add_action('admin_menu', 'event_rsvp_restrict_admin_menu', 999);

function event_rsvp_restrict_post_type_access() {
	global $pagenow, $typenow;

	if (!is_admin()) {
		return;
	}

	$current_user = wp_get_current_user();

	if (in_array('vendor', $current_user->roles)) {
		$restricted_types = array('event', 'attendee', 'product', 'post', 'page');

		if (in_array($typenow, $restricted_types) && in_array($pagenow, array('edit.php', 'post-new.php', 'post.php'))) {
			wp_die('You do not have permission to access this post type.');
		}
	} elseif (in_array('event_host', $current_user->roles)) {
		$restricted_types = array('vendor_ad', 'post', 'page');

		if (in_array($typenow, $restricted_types) && in_array($pagenow, array('edit.php', 'post-new.php', 'post.php'))) {
			wp_die('You do not have permission to access this post type.');
		}
	}
}
add_action('admin_init', 'event_rsvp_restrict_post_type_access');

function event_rsvp_enable_acf_frontend() {
	if (function_exists('acf_form_head')) {
		acf_form_head();
	}
}
add_action('wp_head', 'event_rsvp_enable_acf_frontend');

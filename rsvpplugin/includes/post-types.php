<?php
/**
 * Custom Post Types Registration
 * 
 * @package EventRSVPPlugin
 */

if (!defined('ABSPATH')) {
	exit;
}

function event_rsvp_register_post_types() {
	register_post_type('event', array(
		'labels' => array(
			'name' => 'Events',
			'singular_name' => 'Event',
			'add_new' => 'Add New Event',
			'add_new_item' => 'Add New Event',
			'edit_item' => 'Edit Event',
			'view_item' => 'View Event',
		),
		'public' => true,
		'show_in_rest' => true,
		'supports' => array('title', 'editor', 'thumbnail', 'author', 'excerpt'),
		'has_archive' => true,
		'rewrite' => array('slug' => 'events'),
		'menu_icon' => 'dashicons-calendar-alt',
		'capability_type' => 'post',
		'map_meta_cap' => true,
	));

	register_post_type('attendee', array(
		'labels' => array(
			'name' => 'Attendees',
			'singular_name' => 'Attendee',
			'add_new' => 'Add New Attendee',
			'edit_item' => 'Edit Attendee',
		),
		'public' => false,
		'show_ui' => true,
		'show_in_rest' => true,
		'supports' => array('title'),
		'menu_icon' => 'dashicons-groups',
		'capability_type' => 'post',
		'map_meta_cap' => true,
	));

	register_post_type('event_product', array(
		'labels' => array(
			'name' => 'Event Products',
			'singular_name' => 'Event Product',
			'add_new' => 'Add New Event Product',
			'edit_item' => 'Edit Event Product',
		),
		'public' => true,
		'show_in_rest' => true,
		'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
		'has_archive' => true,
		'rewrite' => array('slug' => 'event-products'),
		'menu_icon' => 'dashicons-cart',
		'capability_type' => 'post',
		'map_meta_cap' => true,
	));

	register_post_type('vendor_ad', array(
		'labels' => array(
			'name' => 'Vendor Ads',
			'singular_name' => 'Vendor Ad',
			'add_new' => 'Add New Ad',
			'edit_item' => 'Edit Ad',
		),
		'public' => true,
		'show_in_rest' => true,
		'supports' => array('title', 'thumbnail'),
		'menu_icon' => 'dashicons-megaphone',
		'capability_type' => 'post',
		'map_meta_cap' => true,
	));
}
add_action('init', 'event_rsvp_register_post_types');

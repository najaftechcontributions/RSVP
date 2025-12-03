<?php
/**
 * ACF Field Groups Configuration
 * Import this file via ACF Tools > Import
 * Or run event_rsvp_register_acf_fields() to programmatically register
 */

if (!function_exists('event_rsvp_register_acf_fields')) :
function event_rsvp_register_acf_fields() {
	if (!function_exists('acf_add_local_field_group')) {
		return;
	}

	acf_add_local_field_group(array(
		'key' => 'group_event_fields',
		'title' => 'Event Fields',
		'fields' => array(
			array(
				'key' => 'field_event_host',
				'label' => 'Event Host',
				'name' => 'event_host',
				'type' => 'text',
				'instructions' => 'Enter the name of the event host (optional). If left empty, the author name will be displayed.',
				'placeholder' => 'e.g., John Doe or ABC Organization',
			),
			array(
				'key' => 'field_event_date',
				'label' => 'Event Date',
				'name' => 'event_date',
				'type' => 'date_picker',
				'required' => 1,
				'display_format' => 'F j, Y',
				'return_format' => 'Y-m-d',
			),
			array(
				'key' => 'field_event_end_date',
				'label' => 'Event End Date',
				'name' => 'event_end_date',
				'type' => 'date_picker',
				'display_format' => 'F j, Y',
				'return_format' => 'Y-m-d',
			),
			array(
				'key' => 'field_venue_address',
				'label' => 'Venue Address',
				'name' => 'venue_address',
				'type' => 'text',
			),
			array(
				'key' => 'field_venue_map_url',
				'label' => 'Venue Map URL',
				'name' => 'venue_map_url',
				'type' => 'textarea',
				'rows' => 4,
				'instructions' => 'Paste Google Maps embed iframe code or just the URL. The system will automatically extract the map URL.',
				'placeholder' => 'Paste Google Maps iframe or URL here...',
			),
			array(
				'key' => 'field_event_hashtag',
				'label' => 'Event Hashtag',
				'name' => 'event_hashtag',
				'type' => 'text',
				'prepend' => '#',
			),
			array(
				'key' => 'field_social_links',
				'label' => 'Social Links',
				'name' => 'social_links',
				'type' => 'repeater',
				'layout' => 'table',
				'button_label' => 'Add Social Link',
				'sub_fields' => array(
					array(
						'key' => 'field_platform',
						'label' => 'Platform',
						'name' => 'platform',
						'type' => 'select',
						'choices' => array(
							'facebook' => 'Facebook',
							'twitter' => 'Twitter',
							'instagram' => 'Instagram',
							'linkedin' => 'LinkedIn',
							'youtube' => 'YouTube',
						),
					),
					array(
						'key' => 'field_url',
						'label' => 'URL',
						'name' => 'url',
						'type' => 'url',
					),
				),
			),
			array(
				'key' => 'field_visibility',
				'label' => 'Event Visibility',
				'name' => 'visibility',
				'type' => 'radio',
				'choices' => array(
					'public' => 'Public',
					'private' => 'Private',
				),
				'default_value' => 'public',
				'layout' => 'horizontal',
			),
			array(
				'key' => 'field_max_attendees',
				'label' => 'Max Attendees',
				'name' => 'max_attendees',
				'type' => 'number',
				'min' => 1,
			),
			array(
				'key' => 'field_qr_schedule_days',
				'label' => 'QR Code Email Schedule (Days Before Event)',
				'name' => 'qr_schedule_days',
				'type' => 'number',
				'default_value' => 7,
				'min' => 1,
				'max' => 30,
			),
		),
		'location' => array(
			array(
				array(
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'event',
				),
			),
		),
	));

	acf_add_local_field_group(array(
		'key' => 'group_attendee_fields',
		'title' => 'Attendee Fields',
		'fields' => array(
			array(
				'key' => 'field_attendee_email',
				'label' => 'Email',
				'name' => 'attendee_email',
				'type' => 'email',
				'required' => 1,
			),
			array(
				'key' => 'field_attendee_phone',
				'label' => 'Phone',
				'name' => 'attendee_phone',
				'type' => 'text',
			),
			array(
				'key' => 'field_rsvp_status',
				'label' => 'RSVP Status',
				'name' => 'rsvp_status',
				'type' => 'select',
				'choices' => array(
					'invited' => 'Invited',
					'yes' => 'Yes',
					'no' => 'No',
					'maybe' => 'Maybe',
				),
				'default_value' => 'invited',
			),
			array(
				'key' => 'field_linked_event',
				'label' => 'Linked Event',
				'name' => 'linked_event',
				'type' => 'post_object',
				'post_type' => array('event'),
				'return_format' => 'id',
			),
			array(
				'key' => 'field_checkin_status',
				'label' => 'Check-in Status',
				'name' => 'checkin_status',
				'type' => 'true_false',
				'ui' => 1,
			),
			array(
				'key' => 'field_qr_data',
				'label' => 'QR Data',
				'name' => 'qr_data',
				'type' => 'text',
				'readonly' => 1,
			),
		),
		'location' => array(
			array(
				array(
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'attendee',
				),
			),
		),
	));

	acf_add_local_field_group(array(
		'key' => 'group_product_fields',
		'title' => 'Event Product Fields',
		'fields' => array(
			array(
				'key' => 'field_price',
				'label' => 'Price',
				'name' => 'price',
				'type' => 'number',
				'prepend' => '$',
				'step' => '0.01',
			),
			array(
				'key' => 'field_inventory',
				'label' => 'Inventory',
				'name' => 'inventory',
				'type' => 'number',
				'min' => 0,
			),
			array(
				'key' => 'field_product_linked_event',
				'label' => 'Linked Event',
				'name' => 'linked_event',
				'type' => 'post_object',
				'post_type' => array('event'),
				'return_format' => 'id',
				'allow_null' => 1,
			),
			array(
				'key' => 'field_product_visibility',
				'label' => 'Visibility',
				'name' => 'visibility',
				'type' => 'select',
				'choices' => array(
					'public' => 'Public',
					'attendees_only' => 'Attendees Only',
				),
				'default_value' => 'public',
			),
		),
		'location' => array(
			array(
				array(
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'event_product',
				),
			),
		),
	));

	acf_add_local_field_group(array(
		'key' => 'group_vendor_ad_fields',
		'title' => 'Vendor Ad Fields',
		'fields' => array(
			array(
				'key' => 'field_ad_start_date',
				'label' => 'Ad Start Date',
				'name' => 'ad_start_date',
				'type' => 'date_picker',
				'required' => 1,
				'display_format' => 'F j, Y',
				'return_format' => 'Y-m-d',
			),
			array(
				'key' => 'field_ad_end_date',
				'label' => 'Ad End Date',
				'name' => 'ad_end_date',
				'type' => 'date_picker',
				'required' => 1,
				'display_format' => 'F j, Y',
				'return_format' => 'Y-m-d',
			),
			array(
				'key' => 'field_slot_location',
				'label' => 'Slot Location',
				'name' => 'slot_location',
				'type' => 'select',
				'choices' => array(
					'sidebar' => 'Sidebar',
					'footer' => 'Footer',
					'homepage' => 'Homepage',
				),
				'required' => 1,
			),
			array(
				'key' => 'field_click_url',
				'label' => 'Click URL',
				'name' => 'click_url',
				'type' => 'url',
				'required' => 1,
			),
		),
		'location' => array(
			array(
				array(
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'vendor_ad',
				),
			),
		),
	));
}
endif;

add_action('acf/init', 'event_rsvp_register_acf_fields');

/**
 * Allow iframe tags in venue_map_url field
 * This allows users to paste Google Maps embed codes
 */
add_filter('acf/update_value/key=field_venue_map_url', 'allow_iframe_in_venue_map_url', 10, 3);
function allow_iframe_in_venue_map_url($value, $post_id, $field) {
	// Remove any sanitization - return raw value to preserve iframe tags
	remove_filter('content_save_pre', 'wp_filter_post_kses');
	remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
	return $value;
}

/**
 * Format venue_map_url on load to preserve iframe content
 */
add_filter('acf/load_value/key=field_venue_map_url', 'load_venue_map_url_value', 10, 3);
function load_venue_map_url_value($value, $post_id, $field) {
	// Return the raw value without any filtering
	return $value;
}

/**
 * Format venue_map_url for output
 */
add_filter('acf/format_value/key=field_venue_map_url', 'format_venue_map_url_value', 10, 3);
function format_venue_map_url_value($value, $post_id, $field) {
	// Don't escape iframe tags when displaying
	return $value;
}

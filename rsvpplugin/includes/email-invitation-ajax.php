<?php

/**
 * Email Invitation AJAX Handlers
 * 
 * @package EventRSVPPlugin
 */

if (!defined('ABSPATH')) {
	exit;
}

function event_rsvp_ajax_create_email_campaign()
{
	check_ajax_referer('event_rsvp_email_campaign', 'nonce');

	if (!current_user_can('edit_posts')) {
		wp_send_json_error('Unauthorized');
		return;
	}

	$event_id = intval($_POST['event_id'] ?? 0);
	$campaign_name = sanitize_text_field($_POST['campaign_name'] ?? '');
	$template_id = intval($_POST['template_id'] ?? 0);
	$subject = sanitize_text_field($_POST['subject'] ?? '');

	if (!$event_id || !$campaign_name || !$subject) {
		wp_send_json_error('Missing required fields');
		return;
	}

	$event = get_post($event_id);
	if (!$event || $event->post_type !== 'event') {
		wp_send_json_error('Invalid event');
		return;
	}

	if (!current_user_can('administrator') && get_current_user_id() != $event->post_author) {
		wp_send_json_error('You do not have permission to create campaigns for this event');
		return;
	}

	$campaign_id = event_rsvp_create_campaign(array(
		'event_id' => $event_id,
		'host_id' => get_current_user_id(),
		'campaign_name' => $campaign_name,
		'template_id' => $template_id > 0 ? $template_id : null,
		'subject' => $subject,
		'status' => 'draft'
	));

	if ($campaign_id) {
		wp_send_json_success(array(
			'campaign_id' => $campaign_id,
			'message' => 'Campaign created successfully!'
		));
	} else {
		wp_send_json_error('Failed to create campaign');
	}
}
add_action('wp_ajax_event_rsvp_create_email_campaign', 'event_rsvp_ajax_create_email_campaign');

function event_rsvp_ajax_upload_csv_recipients()
{
	check_ajax_referer('event_rsvp_email_campaign', 'nonce');

	if (!current_user_can('edit_posts')) {
		wp_send_json_error('Unauthorized');
		return;
	}

	$campaign_id = intval($_POST['campaign_id'] ?? 0);

	if (!$campaign_id) {
		wp_send_json_error('Invalid campaign ID');
		return;
	}

	$campaign = event_rsvp_get_campaign($campaign_id);
	if (!$campaign) {
		wp_send_json_error('Campaign not found');
		return;
	}

	if (!current_user_can('administrator') && get_current_user_id() != $campaign->host_id) {
		wp_send_json_error('Unauthorized');
		return;
	}

	if (!isset($_FILES['csv_file'])) {
		wp_send_json_error('No file uploaded');
		return;
	}

	$file = $_FILES['csv_file'];
	$upload_overrides = array('test_form' => false);
	$movefile = wp_handle_upload($file, $upload_overrides);

	if ($movefile && !isset($movefile['error'])) {
		$recipients = event_rsvp_parse_csv_recipients($movefile['file']);

		if (empty($recipients)) {
			wp_send_json_error('No valid email addresses found in CSV');
			return;
		}

		$result = event_rsvp_add_campaign_recipients($campaign_id, $recipients);

		@unlink($movefile['file']);

		$message = sprintf('%d recipient(s) added!', $result['added']);
		if ($result['duplicates'] > 0) {
			$message .= sprintf(' (%d duplicate(s) skipped)', $result['duplicates']);
		}
		if ($result['skipped'] > 0) {
			$message .= sprintf(' (%d invalid)', $result['skipped']);
		}

		wp_send_json_success(array(
			'message' => $message,
			'added' => $result['added'],
			'total' => $result['total']
		));
	} else {
		wp_send_json_error($movefile['error']);
	}
}
add_action('wp_ajax_event_rsvp_upload_csv_recipients', 'event_rsvp_ajax_upload_csv_recipients');

function event_rsvp_ajax_add_manual_recipients()
{
	check_ajax_referer('event_rsvp_email_campaign', 'nonce');

	if (!current_user_can('edit_posts')) {
		wp_send_json_error('Unauthorized');
		return;
	}

	$campaign_id = intval($_POST['campaign_id'] ?? 0);
	$emails = isset($_POST['emails']) ? $_POST['emails'] : '';

	if (!$campaign_id || empty($emails)) {
		wp_send_json_error('Missing required fields');
		return;
	}

	$campaign = event_rsvp_get_campaign($campaign_id);
	if (!$campaign) {
		wp_send_json_error('Campaign not found');
		return;
	}

	if (!current_user_can('administrator') && get_current_user_id() != $campaign->host_id) {
		wp_send_json_error('Unauthorized');
		return;
	}

	$email_lines = explode("\n", $emails);
	$recipients = array();

	foreach ($email_lines as $line) {
		$line = trim($line);
		if (empty($line)) {
			continue;
		}

		$parts = preg_split('/[,\t]+/', $line, 2);
		$email = trim($parts[0]);
		$name = isset($parts[1]) ? trim($parts[1]) : '';

		if (is_email($email)) {
			$recipients[] = array(
				'email' => $email,
				'name' => $name
			);
		}
	}

	if (empty($recipients)) {
		wp_send_json_error('No valid email addresses found');
		return;
	}

	$result = event_rsvp_add_campaign_recipients($campaign_id, $recipients);

	if ($result['added'] === 0) {
		if ($result['duplicates'] > 0) {
			wp_send_json_error(sprintf('All %d email(s) already exist', $result['duplicates']));
		} else {
			wp_send_json_error('No valid emails added');
		}
		return;
	}

	$message = sprintf('%d recipient(s) added!', $result['added']);
	if ($result['duplicates'] > 0) {
		$message .= sprintf(' (%d duplicate(s) skipped)', $result['duplicates']);
	}
	if ($result['skipped'] > 0) {
		$message .= sprintf(' (%d invalid)', $result['skipped']);
	}

	wp_send_json_success(array(
		'message' => $message,
		'added' => $result['added'],
		'total' => $result['total']
	));
}
add_action('wp_ajax_event_rsvp_add_manual_recipients', 'event_rsvp_ajax_add_manual_recipients');

function event_rsvp_ajax_get_campaign_recipients()
{
	check_ajax_referer('event_rsvp_email_campaign', 'nonce');

	if (!current_user_can('edit_posts')) {
		wp_send_json_error('Unauthorized');
		return;
	}

	$campaign_id = intval($_POST['campaign_id'] ?? 0);

	if (!$campaign_id) {
		wp_send_json_error('Invalid campaign ID');
		return;
	}

	$campaign = event_rsvp_get_campaign($campaign_id);
	if (!$campaign) {
		wp_send_json_error('Campaign not found');
		return;
	}

	if (!current_user_can('administrator') && get_current_user_id() != $campaign->host_id) {
		wp_send_json_error('Unauthorized');
		return;
	}

	$recipients = event_rsvp_get_campaign_recipients($campaign_id);

	wp_send_json_success(array(
		'recipients' => $recipients
	));
}
add_action('wp_ajax_event_rsvp_get_campaign_recipients', 'event_rsvp_ajax_get_campaign_recipients');

function event_rsvp_ajax_send_test_email()
{
	check_ajax_referer('event_rsvp_email_campaign', 'nonce');

	if (!current_user_can('edit_posts')) {
		wp_send_json_error('Unauthorized');
		return;
	}

	$campaign_id = intval($_POST['campaign_id'] ?? 0);
	$test_email = sanitize_email($_POST['test_email'] ?? '');

	if (!$campaign_id || !is_email($test_email)) {
		wp_send_json_error('Invalid parameters');
		return;
	}

	$campaign = event_rsvp_get_campaign($campaign_id);
	if (!$campaign) {
		wp_send_json_error('Campaign not found');
		return;
	}

	if (!current_user_can('administrator') && get_current_user_id() != $campaign->host_id) {
		wp_send_json_error('Unauthorized');
		return;
	}

	global $wpdb;
	$recipients_table = $wpdb->prefix . 'event_email_recipients';

	$test_recipient_id = $wpdb->get_var($wpdb->prepare(
		"SELECT id FROM $recipients_table WHERE campaign_id = %d AND email = %s LIMIT 1",
		$campaign_id,
		$test_email
	));

	if (!$test_recipient_id) {
		$tracking_token = wp_generate_password(32, false, false);

		$wpdb->insert(
			$recipients_table,
			array(
				'campaign_id' => $campaign_id,
				'email' => $test_email,
				'name' => 'Test User',
				'tracking_token' => $tracking_token,
				'sent_status' => 'pending'
			),
			array('%d', '%s', '%s', '%s', '%s')
		);

		$test_recipient_id = $wpdb->insert_id;
	}

	$result = event_rsvp_send_campaign_email($test_recipient_id);

	if ($result) {
		wp_send_json_success(array(
			'message' => 'Test email sent successfully!'
		));
	} else {
		wp_send_json_error('Failed to send test email');
	}
}
add_action('wp_ajax_event_rsvp_send_test_email', 'event_rsvp_ajax_send_test_email');

function event_rsvp_ajax_send_campaign()
{
	check_ajax_referer('event_rsvp_email_campaign', 'nonce');

	if (!current_user_can('edit_posts')) {
		wp_send_json_error('Unauthorized');
		return;
	}

	$campaign_id = intval($_POST['campaign_id'] ?? 0);

	if (!$campaign_id) {
		wp_send_json_error('Invalid campaign ID');
		return;
	}

	$campaign = event_rsvp_get_campaign($campaign_id);
	if (!$campaign) {
		wp_send_json_error('Campaign not found');
		return;
	}

	if (!current_user_can('administrator') && get_current_user_id() != $campaign->host_id) {
		wp_send_json_error('Unauthorized');
		return;
	}

	$recipients = event_rsvp_get_campaign_recipients($campaign_id);

	if (empty($recipients)) {
		wp_send_json_error('No recipients added to this campaign');
		return;
	}

	event_rsvp_update_campaign($campaign_id, array(
		'status' => 'sending',
		'sent_time' => current_time('mysql')
	));

	wp_schedule_single_event(time() + 10, 'event_rsvp_process_campaign_batch', array($campaign_id));

	wp_send_json_success(array(
		'message' => 'Campaign started! Emails are being sent in the background.',
		'total_recipients' => count($recipients)
	));
}
add_action('wp_ajax_event_rsvp_send_campaign', 'event_rsvp_ajax_send_campaign');

function event_rsvp_process_campaign_batch($campaign_id, $batch_size = 10)
{
	global $wpdb;
	$recipients_table = $wpdb->prefix . 'event_email_recipients';

	$pending_recipients = $wpdb->get_results($wpdb->prepare(
		"SELECT * FROM $recipients_table WHERE campaign_id = %d AND sent_status = 'pending' LIMIT %d",
		$campaign_id,
		$batch_size
	));

	foreach ($pending_recipients as $recipient) {
		event_rsvp_send_campaign_email($recipient->id);
		usleep(100000);
	}

	$remaining = $wpdb->get_var($wpdb->prepare(
		"SELECT COUNT(*) FROM $recipients_table WHERE campaign_id = %d AND sent_status = 'pending'",
		$campaign_id
	));

	if ($remaining > 0) {
		wp_schedule_single_event(time() + 30, 'event_rsvp_process_campaign_batch', array($campaign_id, $batch_size));
	} else {
		event_rsvp_update_campaign($campaign_id, array(
			'status' => 'sent'
		));
	}
}
add_action('event_rsvp_process_campaign_batch', 'event_rsvp_process_campaign_batch', 10, 2);

function event_rsvp_ajax_get_campaign_stats()
{
	check_ajax_referer('event_rsvp_email_campaign', 'nonce');

	if (!current_user_can('edit_posts')) {
		wp_send_json_error('Unauthorized');
		return;
	}

	$campaign_id = intval($_POST['campaign_id'] ?? 0);

	if (!$campaign_id) {
		wp_send_json_error('Invalid campaign ID');
		return;
	}

	$campaign = event_rsvp_get_campaign($campaign_id);
	if (!$campaign) {
		wp_send_json_error('Campaign not found');
		return;
	}

	if (!current_user_can('administrator') && get_current_user_id() != $campaign->host_id) {
		wp_send_json_error('Unauthorized');
		return;
	}

	$stats = event_rsvp_get_campaign_stats($campaign_id);

	wp_send_json_success(array(
		'stats' => $stats,
		'campaign' => $campaign
	));
}
add_action('wp_ajax_event_rsvp_get_campaign_stats', 'event_rsvp_ajax_get_campaign_stats');

function event_rsvp_ajax_delete_campaign()
{
	check_ajax_referer('event_rsvp_email_campaign', 'nonce');

	if (!current_user_can('edit_posts')) {
		wp_send_json_error('Unauthorized');
		return;
	}

	$campaign_id = intval($_POST['campaign_id'] ?? 0);

	if (!$campaign_id) {
		wp_send_json_error('Invalid campaign ID');
		return;
	}

	$campaign = event_rsvp_get_campaign($campaign_id);
	if (!$campaign) {
		wp_send_json_error('Campaign not found');
		return;
	}

	if (!current_user_can('administrator') && get_current_user_id() != $campaign->host_id) {
		wp_send_json_error('Unauthorized');
		return;
	}

	event_rsvp_delete_campaign($campaign_id);

	wp_send_json_success(array(
		'message' => 'Campaign deleted successfully!'
	));
}
add_action('wp_ajax_event_rsvp_delete_campaign', 'event_rsvp_ajax_delete_campaign');

function event_rsvp_ajax_get_email_templates()
{
	check_ajax_referer('event_rsvp_email_campaign', 'nonce');

	if (!current_user_can('edit_posts')) {
		wp_send_json_error('Unauthorized');
		return;
	}

	$templates = event_rsvp_get_email_templates();

	wp_send_json_success(array(
		'templates' => $templates
	));
}
add_action('wp_ajax_event_rsvp_get_email_templates', 'event_rsvp_ajax_get_email_templates');

function event_rsvp_ajax_preview_email_template()
{
	check_ajax_referer('event_rsvp_email_campaign', 'nonce');

	if (!current_user_can('edit_posts')) {
		wp_send_json_error('Unauthorized');
		return;
	}

	$template_id = intval($_POST['template_id'] ?? 0);
	$event_id = intval($_POST['event_id'] ?? 0);

	if (!$template_id) {
		wp_send_json_error('Invalid template ID');
		return;
	}

	$template = event_rsvp_get_email_template($template_id);
	if (!$template) {
		wp_send_json_error('Template not found');
		return;
	}

	$event_date = '';
	$event_time = '';
	$venue_address = '';
	$event_description = '';
	$event_name = 'Sample Event';

	if ($event_id > 0) {
		$event = get_post($event_id);
		if ($event) {
			$event_name = get_the_title($event_id);
			$event_date = get_post_meta($event_id, 'event_date', true);
			$event_time = get_post_meta($event_id, 'event_time', true);
			$venue_address = get_post_meta($event_id, 'venue_address', true);
			$event_description = get_the_excerpt($event_id);
		}
	}

	$current_user = wp_get_current_user();

	$template_data = array(
		'event_name' => $event_name,
		'event_date' => $event_date ? date('F j, Y', strtotime($event_date)) : 'August 15, 2024',
		'event_time' => $event_time ? $event_time : '7:00 PM',
		'event_location' => $venue_address ? $venue_address : '123 Main Street, City',
		'event_description' => $event_description ? $event_description : 'Join us for an amazing event!',
		'host_name' => $current_user->display_name,
		'tracking_url' => '#',
		'unsubscribe_url' => '#',
		'recipient_name' => 'Guest'
	);

	$html = event_rsvp_parse_email_template($template->html_content, $template_data);

	wp_send_json_success(array(
		'html' => $html,
		'template' => $template
	));
}
add_action('wp_ajax_event_rsvp_preview_email_template', 'event_rsvp_ajax_preview_email_template');

function event_rsvp_ajax_record_email_response()
{
	check_ajax_referer('event_rsvp_email_response', 'nonce');

	$token = sanitize_text_field($_POST['token'] ?? '');
	$response = sanitize_text_field($_POST['response'] ?? '');

	if (!$token || !in_array($response, array('yes', 'no'))) {
		wp_send_json_error('Invalid parameters');
		return;
	}

	$result = event_rsvp_record_email_response($token, $response);

	if ($result) {
		wp_send_json_success(array(
			'message' => 'Response recorded successfully!'
		));
	} else {
		wp_send_json_error('Failed to record response');
	}
}
add_action('wp_ajax_event_rsvp_record_email_response', 'event_rsvp_ajax_record_email_response');
add_action('wp_ajax_nopriv_event_rsvp_record_email_response', 'event_rsvp_ajax_record_email_response');

function event_rsvp_ajax_record_email_attendance()
{
	check_ajax_referer('event_rsvp_email_response', 'nonce');

	$token = sanitize_text_field($_POST['token'] ?? '');
	$event_id = intval($_POST['event_id'] ?? 0);
	$attendee_name = sanitize_text_field($_POST['attendee_name'] ?? '');
	$attendee_email = sanitize_email($_POST['attendee_email'] ?? '');
	$attendee_phone = sanitize_text_field($_POST['attendee_phone'] ?? '');

	if (!$token || !$event_id || !$attendee_name || !$attendee_email) {
		wp_send_json_error('Missing required fields');
		return;
	}

	if (!is_email($attendee_email)) {
		wp_send_json_error('Invalid email address');
		return;
	}

	// Check if attendee already exists for this event
	$existing_attendee = event_rsvp_get_attendee_by_email($attendee_email, $event_id);

	if ($existing_attendee) {
		// Update existing attendee
		$attendee_id = $existing_attendee->ID;

		wp_update_post(array(
			'ID' => $attendee_id,
			'post_title' => $attendee_name
		));

		update_post_meta($attendee_id, 'attendee_phone', $attendee_phone);
		update_post_meta($attendee_id, 'rsvp_status', 'yes');
	} else {
		// Create new attendee
		$attendee_id = wp_insert_post(array(
			'post_type' => 'attendee',
			'post_title' => $attendee_name,
			'post_status' => 'publish',
		));

		if (is_wp_error($attendee_id)) {
			wp_send_json_error('Failed to create attendee');
			return;
		}

		update_post_meta($attendee_id, 'attendee_email', $attendee_email);
		update_post_meta($attendee_id, 'attendee_phone', $attendee_phone);
		update_post_meta($attendee_id, 'rsvp_status', 'yes');
		update_post_meta($attendee_id, 'linked_event', $event_id);
		update_post_meta($attendee_id, 'checkin_status', false);

		// Generate QR code
		$qr_data = base64_encode(json_encode(array(
			'attendee_id' => $attendee_id,
			'event_id' => $event_id,
			'email' => $attendee_email,
			'verification' => wp_hash($attendee_id . $event_id . $attendee_email)
		)));

		update_post_meta($attendee_id, 'qr_data', $qr_data);

		// Send QR email
		event_rsvp_schedule_qr_email($attendee_id, $event_id);
	}

	// Record email response
	event_rsvp_record_email_response($token, 'yes');

	// Link recipient to attendee
	global $wpdb;
	$recipients_table = $wpdb->prefix . 'event_email_recipients';

	$wpdb->update(
		$recipients_table,
		array('attendee_id' => $attendee_id),
		array('tracking_token' => $token),
		array('%d'),
		array('%s')
	);

	wp_send_json_success(array(
		'message' => 'RSVP submitted successfully!',
		'attendee_id' => $attendee_id
	));
}
add_action('wp_ajax_event_rsvp_record_email_attendance', 'event_rsvp_ajax_record_email_attendance');
add_action('wp_ajax_nopriv_event_rsvp_record_email_attendance', 'event_rsvp_ajax_record_email_attendance');

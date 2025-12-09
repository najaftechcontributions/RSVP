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
	$custom_image = isset($_POST['custom_image']) ? esc_url_raw($_POST['custom_image']) : '';

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
		// Save custom image URL as campaign meta if provided
		if (!empty($custom_image)) {
			global $wpdb;
			$campaigns_table = $wpdb->prefix . 'event_email_campaigns';
			$custom_data = array('custom_image' => $custom_image);

			$update_result = $wpdb->update(
				$campaigns_table,
				array('custom_data' => json_encode($custom_data)),
				array('id' => $campaign_id),
				array('%s'),
				array('%d')
			);

			if ($update_result === false) {
				error_log("Failed to save custom image for campaign {$campaign_id}: " . $wpdb->last_error);
			}
		}

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
			@unlink($movefile['file']);
			wp_send_json_error('No valid email addresses found in CSV. Make sure your CSV has an "email" column with valid email addresses.');
			return;
		}

		$result = event_rsvp_add_campaign_recipients($campaign_id, $recipients);

		@unlink($movefile['file']);

		if ($result['added'] === 0) {
			if ($result['duplicates'] > 0) {
				wp_send_json_error(sprintf('All %d email(s) from CSV already exist in this campaign.', $result['duplicates']));
			} else {
				wp_send_json_error('Failed to add emails from CSV. Please check the file format and try again.');
			}
			return;
		}

		$message = sprintf('✓ Successfully added %d recipient(s)!', $result['added']);
		if ($result['duplicates'] > 0) {
			$message .= sprintf(' (%d duplicate(s) skipped)', $result['duplicates']);
		}
		if ($result['skipped'] > 0) {
			$message .= sprintf(' (%d invalid email(s) skipped)', $result['skipped']);
		}

		wp_send_json_success(array(
			'message' => $message,
			'added' => $result['added'],
			'total' => $result['total']
		));
	} else {
		wp_send_json_error('File upload failed: ' . (isset($movefile['error']) ? $movefile['error'] : 'Unknown error'));
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

	// Normalize line breaks to handle different OS formats
	$emails = str_replace(array("\r\n", "\r"), "\n", $emails);

	// Also handle comma-separated emails (but preserve "email, Name" format)
	// We'll process this line by line
	$email_lines = explode("\n", $emails);
	$recipients = array();
	$invalid_emails = array();

	foreach ($email_lines as $line) {
		$line = trim($line);

		if (empty($line)) {
			continue;
		}

		// Check if line contains "email, Name" format (comma indicates name)
		if (strpos($line, ',') !== false) {
			// Split by comma to separate email and name
			$parts = array_map('trim', explode(',', $line, 2));
			$email_part = $parts[0];
			$name = isset($parts[1]) ? $parts[1] : '';

			// Validate email BEFORE sanitization to catch format issues
			if (empty($email_part) || !filter_var($email_part, FILTER_VALIDATE_EMAIL)) {
				if (!empty($line)) {
					$invalid_emails[] = $line;
				}
				continue;
			}

			// Now sanitize the validated email
			$email = sanitize_email($email_part);

			// Double-check after sanitization
			if (!empty($email) && is_email($email)) {
				$recipients[] = array(
					'email' => $email,
					'name' => sanitize_text_field($name)
				);
			} else {
				$invalid_emails[] = $line;
			}
		} else {
			// No comma - could be space-separated emails or single email with name
			// Split by whitespace to check for multiple items
			$parts = preg_split('/[\s\t]+/', $line);

			// Check if we have multiple email addresses (space-separated)
			$has_multiple_emails = false;
			$email_count = 0;
			foreach ($parts as $part) {
				if (strpos($part, '@') !== false && filter_var($part, FILTER_VALIDATE_EMAIL)) {
					$email_count++;
				}
			}

			// If we have multiple emails on one line, process each separately
			if ($email_count > 1) {
				foreach ($parts as $part) {
					$part = trim($part);
					if (empty($part)) continue;

					// Validate email
					if (filter_var($part, FILTER_VALIDATE_EMAIL)) {
						$email = sanitize_email($part);
						if (!empty($email) && is_email($email)) {
							$recipients[] = array(
								'email' => $email,
								'name' => ''
							);
						} else {
							$invalid_emails[] = $part;
						}
					} else {
						// Not an email - could be part of a name or invalid
						if (strpos($part, '@') !== false) {
							// Contains @ but invalid format
							$invalid_emails[] = $part;
						}
					}
				}
			} else {
				// Single email, possibly followed by name
				$email_part = $parts[0];
				$name = '';

				// If there are additional parts and first part is an email, rest is name
				if (count($parts) > 1 && strpos($email_part, '@') !== false) {
					array_shift($parts); // Remove email from parts
					$name = implode(' ', $parts); // Join remaining parts as name
				}

				// Validate email BEFORE sanitization
				if (empty($email_part) || !filter_var($email_part, FILTER_VALIDATE_EMAIL)) {
					if (!empty($line)) {
						$invalid_emails[] = $line;
					}
					continue;
				}

				// Sanitize the validated email
				$email = sanitize_email($email_part);

				// Double-check after sanitization
				if (!empty($email) && is_email($email)) {
					$recipients[] = array(
						'email' => $email,
						'name' => sanitize_text_field($name)
					);
				} else {
					$invalid_emails[] = $line;
				}
			}
		}
	}

	if (empty($recipients)) {
		if (!empty($invalid_emails)) {
			$error_msg = 'No valid email addresses found. Check format of: ' . implode(', ', array_slice($invalid_emails, 0, 3));
			if (count($invalid_emails) > 3) {
				$error_msg .= ' (and ' . (count($invalid_emails) - 3) . ' more)';
			}
			$error_msg .= '. Use format: email@example.com or email@example.com, Name';
			wp_send_json_error($error_msg);
		} else {
			wp_send_json_error('No email addresses entered. Please enter at least one valid email address.');
		}
		return;
	}

	$result = event_rsvp_add_campaign_recipients($campaign_id, $recipients);

	if ($result['added'] === 0) {
		if ($result['duplicates'] > 0) {
			wp_send_json_error(sprintf('All %d email(s) already exist in this campaign. Try adding new email addresses.', $result['duplicates']));
		} elseif ($result['skipped'] > 0) {
			// Provide more context about what might have gone wrong
			$error_details = 'Failed to add email(s). Possible reasons: invalid email format, special characters, or database error. ';
			$error_details .= 'Check browser console for details or contact support.';
			wp_send_json_error($error_details);
		} else {
			wp_send_json_error('Failed to add emails. Please check database connection or contact support.');
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

	if (!$campaign_id || $campaign_id <= 0) {
		wp_send_json_error('Invalid campaign ID - Please refresh the page and try again');
		return;
	}

	if (!is_email($test_email)) {
		wp_send_json_error('Invalid email address - Please enter a valid email address');
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
		wp_send_json_error('Unauthorized - You do not have permission to send campaigns');
		return;
	}

	$campaign_id = intval($_POST['campaign_id'] ?? 0);

	if (!$campaign_id || $campaign_id <= 0) {
		wp_send_json_error('Invalid campaign ID - Please refresh the page and try again');
		return;
	}

	$campaign = event_rsvp_get_campaign($campaign_id);
	if (!$campaign) {
		wp_send_json_error('Campaign not found - The campaign may have been deleted');
		return;
	}

	if (!current_user_can('administrator') && get_current_user_id() != $campaign->host_id) {
		wp_send_json_error('Unauthorized - You can only send your own campaigns');
		return;
	}

	// Verify event exists
	$event = get_post($campaign->event_id);
	if (!$event || $event->post_type !== 'event') {
		wp_send_json_error('Event not found - The event associated with this campaign may have been deleted');
		return;
	}

	$recipients = event_rsvp_get_campaign_recipients($campaign_id);

	if (empty($recipients)) {
		wp_send_json_error('No recipients added - Please add email recipients before sending the campaign');
		return;
	}

	// Check SMTP configuration
	$smtp_enabled = get_option('event_rsvp_smtp_enabled', false);
	$smtp_username = get_option('event_rsvp_smtp_username', '');
	$smtp_password = get_option('event_rsvp_smtp_password', '');
	$smtp_host = get_option('event_rsvp_smtp_host', '');

	if (!$smtp_enabled) {
		error_log('Campaign send attempt with SMTP disabled');
		wp_send_json_error('SMTP is disabled - Please enable SMTP in the email settings before sending campaigns');
		return;
	}

	if (empty($smtp_username) || empty($smtp_password) || empty($smtp_host)) {
		error_log('Campaign send attempt with incomplete SMTP configuration');
		wp_send_json_error('SMTP is not properly configured - Please configure SMTP host, username, and password in the email settings');
		return;
	}

	error_log("Starting campaign {$campaign_id} with " . count($recipients) . " recipients");

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
	$recipients = event_rsvp_get_campaign_recipients($campaign_id);
	$event_name = get_the_title($campaign->event_id);

	wp_send_json_success(array(
		'stats' => $stats,
		'campaign' => $campaign,
		'recipients' => $recipients,
		'event_name' => $event_name
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

	try {
		$templates = event_rsvp_get_email_templates();

		if ($templates === false || $templates === null) {
			error_log('Email templates query returned null/false - database might not be initialized');
			$templates = array();
		}

		error_log('Email templates loaded: ' . count($templates) . ' templates found');

		wp_send_json_success(array(
			'templates' => $templates
		));
	} catch (Exception $e) {
		error_log('Error loading email templates: ' . $e->getMessage());
		wp_send_json_error('Failed to load templates: ' . $e->getMessage());
	}
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
	$custom_image = isset($_POST['custom_image']) ? esc_url_raw($_POST['custom_image']) : '';

	// Allow template_id = 0 for default template
	$template = null;
	if ($template_id > 0) {
		$template = event_rsvp_get_email_template($template_id);
		if (!$template) {
			wp_send_json_error('Template not found');
			return;
		}
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
			$event_start_time = get_post_meta($event_id, 'event_start_time', true);
			$venue_address = get_post_meta($event_id, 'venue_address', true);
			$event_description = get_the_excerpt($event_id);

			// Format event time from 24-hour format to 12-hour format
			if ($event_start_time) {
				$time_obj = DateTime::createFromFormat('H:i:s', $event_start_time);
				if (!$time_obj) {
					$time_obj = DateTime::createFromFormat('H:i', $event_start_time);
				}
				if ($time_obj) {
					$event_time = $time_obj->format('g:i A');
				} else {
					$event_time = $event_start_time;
				}
			}
		}
	}

	// Get event host name from event meta, fallback to event creator
	$event_host_name = '';
	if ($event_id > 0) {
		if (function_exists('get_field')) {
			$event_host_name = get_field('event_host', $event_id);
		} else {
			$event_host_name = get_post_meta($event_id, 'event_host', true);
		}

		// If event host is not set, use event creator (post author)
		if (empty($event_host_name)) {
			$event_author_id = get_post_field('post_author', $event_id);
			if ($event_author_id) {
				$author = get_userdata($event_author_id);
				$event_host_name = $author ? $author->display_name : get_bloginfo('name');
			} else {
				$event_host_name = get_bloginfo('name');
			}
		}
	}

	// Fallback to current user if no event selected
	if (empty($event_host_name)) {
		$current_user = wp_get_current_user();
		$event_host_name = $current_user->display_name;
	}

	$template_data = array(
		'event_name' => $event_name,
		'event_date' => $event_date ? date('F j, Y', strtotime($event_date)) : 'August 15, 2024',
		'event_time' => $event_time ? $event_time : '7:00 PM',
		'event_location' => $venue_address ? $venue_address : '123 Main Street, City',
		'event_description' => $event_description ? $event_description : 'Join us for an amazing event!',
		'host_name' => $event_host_name,
		'tracking_url' => '#',
		'unsubscribe_url' => '#',
		'recipient_name' => 'Guest',
		'custom_image' => $custom_image ? $custom_image : 'https://via.placeholder.com/600x300?text=Event+Image'
	);

	// Handle both template and default template
	if ($template) {
		$html = event_rsvp_parse_email_template($template->html_content, $template_data);
		$template_name = $template->name;
	} else {
		$html = event_rsvp_get_default_email_html($template_data);
		$template_name = 'Default HTML Template';
	}

	wp_send_json_success(array(
		'html' => $html,
		'template_name' => $template_name,
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

function event_rsvp_ajax_get_campaign_preview()
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

	$template = null;
	if ($campaign->template_id) {
		$template = event_rsvp_get_email_template($campaign->template_id);
	}

	$event = get_post($campaign->event_id);
	if (!$event) {
		wp_send_json_error('Event not found');
		return;
	}

	$event_date = get_post_meta($campaign->event_id, 'event_date', true);
	$event_start_time = get_post_meta($campaign->event_id, 'event_start_time', true);
	$venue_address = get_post_meta($campaign->event_id, 'venue_address', true);
	$event_description = get_the_excerpt($campaign->event_id);

	// Format event time from 24-hour format to 12-hour format
	$event_time = 'TBD';
	if ($event_start_time) {
		$time_obj = DateTime::createFromFormat('H:i:s', $event_start_time);
		if (!$time_obj) {
			$time_obj = DateTime::createFromFormat('H:i', $event_start_time);
		}
		if ($time_obj) {
			$event_time = $time_obj->format('g:i A');
		} else {
			$event_time = $event_start_time;
		}
	}

	// Get event host name from event meta, fallback to event creator
	$event_host_name = '';
	if (function_exists('get_field')) {
		$event_host_name = get_field('event_host', $campaign->event_id);
	} else {
		$event_host_name = get_post_meta($campaign->event_id, 'event_host', true);
	}

	// If event host is not set, use event creator (post author)
	if (empty($event_host_name)) {
		$event_author_id = get_post_field('post_author', $campaign->event_id);
		if ($event_author_id) {
			$author = get_userdata($event_author_id);
			$event_host_name = $author ? $author->display_name : get_bloginfo('name');
		} else {
			$event_host_name = get_bloginfo('name');
		}
	}

	$host_name = $event_host_name;

	// Get custom image from campaign custom_data
	$custom_image = '';
	if (!empty($campaign->custom_data)) {
		$custom_data = json_decode($campaign->custom_data, true);
		if (isset($custom_data['custom_image'])) {
			$custom_image = $custom_data['custom_image'];
		}
	}

	$template_data = array(
		'event_name' => get_the_title($campaign->event_id),
		'event_date' => $event_date ? date('F j, Y', strtotime($event_date)) : 'TBD',
		'event_time' => $event_time,
		'event_location' => $venue_address ? $venue_address : 'TBD',
		'event_description' => $event_description ? $event_description : '',
		'host_name' => $host_name,
		'tracking_url' => '#',
		'unsubscribe_url' => '#',
		'recipient_name' => 'Guest',
		'custom_image' => $custom_image ? $custom_image : 'https://via.placeholder.com/600x300?text=Event+Image'
	);

	// Handle both template and default template
	if ($template) {
		$html = event_rsvp_parse_email_template($template->html_content, $template_data);
		$template_name = $template->name;
	} else {
		$html = event_rsvp_get_default_email_html($template_data);
		$template_name = 'Default HTML Template';
	}

	wp_send_json_success(array(
		'html' => $html,
		'template_name' => $template_name
	));
}
add_action('wp_ajax_event_rsvp_get_campaign_preview', 'event_rsvp_ajax_get_campaign_preview');

function event_rsvp_ajax_get_campaign_settings()
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

	// Get custom image from campaign custom_data
	$custom_image = '';
	if (!empty($campaign->custom_data)) {
		$custom_data = json_decode($campaign->custom_data, true);
		if (isset($custom_data['custom_image'])) {
			$custom_image = $custom_data['custom_image'];
		}
	}

	// Get template info to check if it needs custom image
	$template_needs_image = false;
	if ($campaign->template_id) {
		$template = event_rsvp_get_email_template($campaign->template_id);
		if ($template && strpos($template->html_content, '{{custom_image}}') !== false) {
			$template_needs_image = true;
		}
	}

	wp_send_json_success(array(
		'custom_image' => $custom_image,
		'campaign_name' => $campaign->campaign_name,
		'subject' => $campaign->subject,
		'template_id' => $campaign->template_id ? $campaign->template_id : 0,
		'event_id' => $campaign->event_id,
		'template_needs_image' => $template_needs_image
	));
}
add_action('wp_ajax_event_rsvp_get_campaign_settings', 'event_rsvp_ajax_get_campaign_settings');

function event_rsvp_ajax_update_campaign_settings()
{
	check_ajax_referer('event_rsvp_email_campaign', 'nonce');

	if (!current_user_can('edit_posts')) {
		wp_send_json_error('Unauthorized');
		return;
	}

	$campaign_id = intval($_POST['campaign_id'] ?? 0);
	$custom_image = isset($_POST['custom_image']) ? esc_url_raw($_POST['custom_image']) : '';
	$campaign_name = isset($_POST['campaign_name']) ? sanitize_text_field($_POST['campaign_name']) : '';
	$subject = isset($_POST['subject']) ? sanitize_text_field($_POST['subject']) : '';
	$event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
	$template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : null;

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

	// Allow editing campaigns regardless of status
	// Note: Recipients cannot be modified for sent campaigns, only settings

	global $wpdb;
	$campaigns_table = $wpdb->prefix . 'event_email_campaigns';

	// Get existing custom_data and merge with new image
	$existing_custom_data = array();
	if (!empty($campaign->custom_data)) {
		$existing_custom_data = json_decode($campaign->custom_data, true);
		if (!is_array($existing_custom_data)) {
			$existing_custom_data = array();
		}
	}

	// Update the custom_image field
	$existing_custom_data['custom_image'] = $custom_image;

	// Prepare update data
	$update_data = array('custom_data' => json_encode($existing_custom_data));
	$update_format = array('%s');

	// Add campaign name if provided
	if (!empty($campaign_name)) {
		$update_data['campaign_name'] = $campaign_name;
		$update_format[] = '%s';
	}

	// Add subject if provided
	if (!empty($subject)) {
		$update_data['subject'] = $subject;
		$update_format[] = '%s';
	}

	// Add event_id if provided
	if ($event_id > 0) {
		$update_data['event_id'] = $event_id;
		$update_format[] = '%d';
	}

	// Add template_id if provided (allow 0 for default template)
	if (isset($_POST['template_id'])) {
		$update_data['template_id'] = $template_id > 0 ? $template_id : null;
		$update_format[] = $template_id > 0 ? '%d' : '%s';
	}

	$result = $wpdb->update(
		$campaigns_table,
		$update_data,
		array('id' => $campaign_id),
		$update_format,
		array('%d')
	);

	// Check for database errors
	if ($result === false) {
		$error_message = $wpdb->last_error ? $wpdb->last_error : 'Database update failed';
		error_log("Campaign settings update failed for campaign {$campaign_id}: {$error_message}");
		wp_send_json_error('Failed to update campaign settings: ' . $error_message);
		return;
	}

	// Success - result is 0 (no change) or 1 (updated)
	wp_send_json_success(array(
		'message' => 'Campaign settings updated successfully!',
		'custom_image' => $custom_image,
		'campaign_name' => $campaign_name,
		'subject' => $subject,
		'event_id' => $event_id,
		'template_id' => $template_id,
		'rows_affected' => $result
	));
}
add_action('wp_ajax_event_rsvp_update_campaign_settings', 'event_rsvp_ajax_update_campaign_settings');

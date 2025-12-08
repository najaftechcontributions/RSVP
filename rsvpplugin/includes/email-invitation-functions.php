<?php

/**
 * Email Invitation Functions
 * 
 * @package EventRSVPPlugin
 */

if (!defined('ABSPATH')) {
	exit;
}

function event_rsvp_get_email_templates()
{
	global $wpdb;
	$table = $wpdb->prefix . 'event_email_templates';

	// Check if table exists
	$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table;

	if (!$table_exists) {
		error_log('Email templates table does not exist. Creating tables...');
		event_rsvp_create_email_invitation_tables();
	}

	$results = $wpdb->get_results("SELECT * FROM $table ORDER BY is_default DESC, name ASC");

	if ($wpdb->last_error) {
		error_log('Database error in get_email_templates: ' . $wpdb->last_error);
		return array();
	}

	return $results ? $results : array();
}

function event_rsvp_get_email_template($id)
{
	global $wpdb;
	$table = $wpdb->prefix . 'event_email_templates';

	return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
}

function event_rsvp_parse_email_template($html, $data)
{
	$parsed = $html;

	foreach ($data as $key => $value) {
		if (is_string($value) || is_numeric($value)) {
			$parsed = str_replace('{{' . $key . '}}', $value, $parsed);
		}
	}

	$parsed = preg_replace_callback('/{{#([^}]+)}}(.*?){{\/\1}}/s', function ($matches) use ($data) {
		$key = $matches[1];
		$content = $matches[2];

		if (isset($data[$key]) && !empty($data[$key])) {
			return str_replace('{{' . $key . '}}', $data[$key], $content);
		}
		return '';
	}, $parsed);

	$parsed = preg_replace('/{{#[^}]+}}.*?{{\/[^}]+}}/s', '', $parsed);
	$parsed = str_replace(array('{{', '}}'), '', $parsed);

	return $parsed;
}

function event_rsvp_create_campaign($data)
{
	global $wpdb;
	$table = $wpdb->prefix . 'event_email_campaigns';

	$defaults = array(
		'event_id' => 0,
		'host_id' => get_current_user_id(),
		'campaign_name' => '',
		'template_id' => null,
		'subject' => '',
		'status' => 'draft',
		'scheduled_time' => null,
	);

	$campaign_data = wp_parse_args($data, $defaults);

	$wpdb->insert($table, $campaign_data, array('%d', '%d', '%s', '%d', '%s', '%s', '%s'));

	return $wpdb->insert_id;
}

function event_rsvp_get_campaign($id)
{
	global $wpdb;
	$table = $wpdb->prefix . 'event_email_campaigns';

	return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
}

function event_rsvp_get_campaigns_by_event($event_id)
{
	global $wpdb;
	$table = $wpdb->prefix . 'event_email_campaigns';

	return $wpdb->get_results($wpdb->prepare(
		"SELECT * FROM $table WHERE event_id = %d ORDER BY created_at DESC",
		$event_id
	));
}

function event_rsvp_get_campaigns_by_host($host_id)
{
	global $wpdb;
	$table = $wpdb->prefix . 'event_email_campaigns';

	return $wpdb->get_results($wpdb->prepare(
		"SELECT * FROM $table WHERE host_id = %d ORDER BY created_at DESC",
		$host_id
	));
}

function event_rsvp_update_campaign($campaign_id, $data)
{
	global $wpdb;
	$table = $wpdb->prefix . 'event_email_campaigns';

	return $wpdb->update(
		$table,
		$data,
		array('id' => $campaign_id),
		null,
		array('%d')
	);
}

function event_rsvp_delete_campaign($campaign_id)
{
	global $wpdb;
	$campaigns_table = $wpdb->prefix . 'event_email_campaigns';
	$recipients_table = $wpdb->prefix . 'event_email_recipients';
	$tracking_table = $wpdb->prefix . 'event_email_tracking';

	$wpdb->delete($tracking_table, array('campaign_id' => $campaign_id), array('%d'));
	$wpdb->delete($recipients_table, array('campaign_id' => $campaign_id), array('%d'));
	$wpdb->delete($campaigns_table, array('id' => $campaign_id), array('%d'));

	return true;
}

function event_rsvp_add_campaign_recipients($campaign_id, $recipients)
{
	global $wpdb;
	$table = $wpdb->prefix . 'event_email_recipients';

	$added = 0;
	$skipped = 0;
	$duplicates = 0;

	foreach ($recipients as $recipient) {
		// Ensure email key exists
		if (!isset($recipient['email']) || empty($recipient['email'])) {
			error_log('Recipient missing email: ' . print_r($recipient, true));
			$skipped++;
			continue;
		}

		// Sanitize email
		$clean_email = sanitize_email(trim($recipient['email']));

		// Validate sanitized email
		if (empty($clean_email)) {
			error_log('Email became empty after sanitization: ' . $recipient['email']);
			$skipped++;
			continue;
		}

		if (!is_email($clean_email)) {
			error_log('Email failed validation: ' . $clean_email);
			$skipped++;
			continue;
		}

		// Check for duplicate emails
		$existing = $wpdb->get_var($wpdb->prepare(
			"SELECT id FROM $table WHERE campaign_id = %d AND email = %s",
			$campaign_id,
			$clean_email
		));

		if ($existing) {
			$duplicates++;
			continue;
		}

		$tracking_token = wp_generate_password(32, false, false);
		$recipient_name = isset($recipient['name']) ? sanitize_text_field(trim($recipient['name'])) : '';

		$insert_result = $wpdb->insert(
			$table,
			array(
				'campaign_id' => $campaign_id,
				'email' => $clean_email,
				'name' => $recipient_name,
				'tracking_token' => $tracking_token,
				'sent_status' => 'pending'
			),
			array('%d', '%s', '%s', '%s', '%s')
		);

		if ($insert_result !== false && $wpdb->insert_id) {
			$added++;
		} else {
			// Log database error for debugging
			if ($wpdb->last_error) {
				error_log('Email recipient insert failed for ' . $clean_email . ': ' . $wpdb->last_error);
			} else {
				error_log('Email recipient insert failed for ' . $clean_email . ' (unknown error)');
			}
			$skipped++;
		}
	}

	// Get accurate total count
	$total_recipients = $wpdb->get_var($wpdb->prepare(
		"SELECT COUNT(*) FROM $table WHERE campaign_id = %d",
		$campaign_id
	));

	$wpdb->update(
		$wpdb->prefix . 'event_email_campaigns',
		array('total_recipients' => $total_recipients),
		array('id' => $campaign_id),
		array('%d'),
		array('%d')
	);

	return array(
		'added' => $added,
		'skipped' => $skipped,
		'duplicates' => $duplicates,
		'total' => $total_recipients
	);
}

function event_rsvp_get_campaign_recipients($campaign_id)
{
	global $wpdb;
	$table = $wpdb->prefix . 'event_email_recipients';

	return $wpdb->get_results($wpdb->prepare(
		"SELECT * FROM $table WHERE campaign_id = %d ORDER BY created_at DESC",
		$campaign_id
	));
}

function event_rsvp_parse_csv_recipients($file_path)
{
	$recipients = array();

	if (!file_exists($file_path)) {
		return $recipients;
	}

	$handle = fopen($file_path, 'r');
	if ($handle === false) {
		return $recipients;
	}

	$header = fgetcsv($handle);
	$email_col = 0;
	$name_col = false;

	if ($header) {
		foreach ($header as $index => $col) {
			$col = strtolower(trim($col));
			if (in_array($col, array('email', 'email address', 'e-mail'))) {
				$email_col = $index;
			}
			if (in_array($col, array('name', 'full name', 'fullname'))) {
				$name_col = $index;
			}
		}
	} else {
		rewind($handle);
	}

	while (($row = fgetcsv($handle)) !== false) {
		if (isset($row[$email_col])) {
			$email_part = trim($row[$email_col]);

			// Validate email format before adding
			if (!empty($email_part) && filter_var($email_part, FILTER_VALIDATE_EMAIL)) {
				$clean_email = sanitize_email($email_part);

				// Double-check after sanitization
				if (!empty($clean_email) && is_email($clean_email)) {
					$name_value = '';
					if ($name_col !== false && isset($row[$name_col])) {
						$name_value = sanitize_text_field(trim($row[$name_col]));
					}

					$recipients[] = array(
						'email' => $clean_email,
						'name' => $name_value
					);
				}
			}
		}
	}

	fclose($handle);

	return $recipients;
}

function event_rsvp_send_campaign_email($recipient_id)
{
	global $wpdb;
	$recipients_table = $wpdb->prefix . 'event_email_recipients';
	$campaigns_table = $wpdb->prefix . 'event_email_campaigns';

	$recipient = $wpdb->get_row($wpdb->prepare(
		"SELECT * FROM $recipients_table WHERE id = %d",
		$recipient_id
	));

	if (!$recipient) {
		return false;
	}

	$campaign = $wpdb->get_row($wpdb->prepare(
		"SELECT * FROM $campaigns_table WHERE id = %d",
		$recipient->campaign_id
	));

	if (!$campaign) {
		return false;
	}

	$event = get_post($campaign->event_id);
	if (!$event) {
		return false;
	}

	$template = null;
	if ($campaign->template_id) {
		$template = event_rsvp_get_email_template($campaign->template_id);
	}

	$event_date = get_post_meta($campaign->event_id, 'event_date', true);
	$event_time = get_post_meta($campaign->event_id, 'event_time', true);
	$venue_address = get_post_meta($campaign->event_id, 'venue_address', true);

	$host = get_userdata($campaign->host_id);
	$host_name = $host ? $host->display_name : get_bloginfo('name');

	$tracking_url = add_query_arg(array(
		'email_track' => $recipient->tracking_token,
		'event_id' => $campaign->event_id
	), get_permalink($campaign->event_id));

	$unsubscribe_url = add_query_arg(array(
		'email_unsubscribe' => $recipient->tracking_token
	), home_url('/'));

	// Get custom image from campaign custom_data
	$custom_image = '';
	if (!empty($campaign->custom_data)) {
		$custom_data = json_decode($campaign->custom_data, true);
		if (isset($custom_data['custom_image'])) {
			$custom_image = $custom_data['custom_image'];
		}
	}

	$template_data = array(
		'event_name' => html_entity_decode(get_the_title($campaign->event_id), ENT_QUOTES, 'UTF-8'),
		'event_date' => $event_date ? date('F j, Y', strtotime($event_date)) : 'TBD',
		'event_time' => $event_time ? $event_time : 'TBD',
		'event_location' => $venue_address ? $venue_address : 'TBD',
		'event_description' => html_entity_decode(get_the_excerpt($campaign->event_id), ENT_QUOTES, 'UTF-8'),
		'host_name' => html_entity_decode($host_name, ENT_QUOTES, 'UTF-8'),
		'tracking_url' => $tracking_url,
		'unsubscribe_url' => $unsubscribe_url,
		'recipient_name' => $recipient->name ? html_entity_decode($recipient->name, ENT_QUOTES, 'UTF-8') : 'there',
		'custom_image' => $custom_image
	);

	$subject = event_rsvp_parse_email_template($campaign->subject, $template_data);

	if ($template) {
		$message = event_rsvp_parse_email_template($template->html_content, $template_data);
	} else {
		$message = event_rsvp_get_default_email_html($template_data);
	}

	$smtp_from_email = get_option('event_rsvp_smtp_from_email', '');
	$smtp_from_name = get_option('event_rsvp_smtp_from_name', $host_name);
	$smtp_username = get_option('event_rsvp_smtp_username', get_option('admin_email'));
	$smtp_host = get_option('event_rsvp_smtp_host', '');

	// Check if this is a known provider that requires matching FROM and username
	$requires_match = false;
	$known_providers = array('hostinger.com', 'gmail.com', 'outlook.com', 'yahoo.com', 'office365.com', 'mail.yahoo.com');
	foreach ($known_providers as $provider) {
		if (strpos($smtp_host, $provider) !== false) {
			$requires_match = true;
			break;
		}
	}

	if ($requires_match || empty($smtp_from_email) || !is_email($smtp_from_email)) {
		// Use SMTP username as sender to avoid "Sender address rejected" errors
		$from_header = sprintf('From: %s <%s>', $smtp_from_name, $smtp_username);
	} else {
		$from_header = sprintf('From: %s <%s>', $smtp_from_name, $smtp_from_email);
	}

	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		$from_header,
		'Reply-To: ' . $smtp_username
	);

	// Enhanced error logging for debugging
	$error_logged = false;
	$error_message = '';
	add_action('wp_mail_failed', function($wp_error) use ($recipient, &$error_logged, &$error_message) {
		$error_message = $wp_error->get_error_message();
		error_log('Campaign Email Failed for ' . $recipient->email . ': ' . $error_message);
		$error_logged = true;
	});

	// Verify SMTP configuration before sending
	$smtp_enabled = get_option('event_rsvp_smtp_enabled', false);
	$smtp_username = get_option('event_rsvp_smtp_username', '');
	$smtp_password = get_option('event_rsvp_smtp_password', '');

	error_log("Attempting to send campaign email to {$recipient->email} (Campaign ID: {$campaign->id}, Event: {$template_data['event_name']})");
	error_log("SMTP Enabled: " . ($smtp_enabled ? 'Yes' : 'No') . ", Username configured: " . (!empty($smtp_username) ? 'Yes' : 'No'));

	$result = wp_mail($recipient->email, $subject, $message, $headers);

	if ($result) {
		$wpdb->update(
			$recipients_table,
			array(
				'sent_status' => 'sent',
				'sent_time' => current_time('mysql')
			),
			array('id' => $recipient_id),
			array('%s', '%s'),
			array('%d')
		);

		$total_sent = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM $recipients_table WHERE campaign_id = %d AND sent_status = 'sent'",
			$campaign->id
		));

		$wpdb->update(
			$campaigns_table,
			array('total_sent' => $total_sent),
			array('id' => $campaign->id),
			array('%d'),
			array('%d')
		);

		error_log("✓ Campaign Email sent successfully to: {$recipient->email} (Subject: {$subject})");
	} else {
		$wpdb->update(
			$recipients_table,
			array('sent_status' => 'failed'),
			array('id' => $recipient_id),
			array('%s'),
			array('%d')
		);

		if (!$error_logged) {
			error_log("✗ Campaign Email failed to send to: {$recipient->email} (wp_mail returned false, check SMTP settings)");
		} else {
			error_log("✗ Campaign Email error details: " . $error_message);
		}
	}

	return $result;
}

function event_rsvp_get_default_email_html($data)
{
	ob_start();
?>
	<!DOCTYPE html>
	<html>

	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Event Invitation</title>
	</head>

	<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; background-color: #f5f5f5;">
		<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f5f5f5; padding: 40px 20px;">
			<tr>
				<td align="center">
					<table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
						<tr>
							<td style="background-color: #503AA8; padding: 40px; text-align: center;">
								<h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;">You're Invited!</h1>
							</td>
						</tr>
						<tr>
							<td style="padding: 40px;">
								<p style="margin: 0 0 20px 0; font-size: 16px; line-height: 1.6; color: #333333;">Hello!</p>
								<p style="margin: 0 0 30px 0; font-size: 16px; line-height: 1.6; color: #333333;"><?php echo esc_html($data['host_name']); ?> has invited you to:</p>

								<div style="background-color: #f8f9fa; padding: 25px; margin: 30px 0; border-radius: 8px; border-left: 4px solid #503AA8;">
									<h2 style="margin: 0 0 15px 0; font-size: 22px; color: #503AA8;"><?php echo esc_html($data['event_name']); ?></h2>
									<p style="margin: 0 0 10px 0; color: #555555; font-size: 15px;">
										<strong>📅 Date:</strong> <?php echo esc_html($data['event_date']); ?>
									</p>
									<p style="margin: 0 0 10px 0; color: #555555; font-size: 15px;">
										<strong>🕐 Time:</strong> <?php echo esc_html($data['event_time']); ?>
									</p>
									<p style="margin: 0; color: #555555; font-size: 15px;">
										<strong>📍 Location:</strong> <?php echo esc_html($data['event_location']); ?>
									</p>
								</div>

								<?php if (!empty($data['event_description'])) : ?>
									<p style="margin: 30px 0; font-size: 15px; line-height: 1.7; color: #555555;"><?php echo esc_html($data['event_description']); ?></p>
								<?php endif; ?>

								<div style="text-align: center; margin: 40px 0;">
									<a href="<?php echo esc_url($data['tracking_url']); ?>" style="display: inline-block; padding: 14px 36px; background-color: #503AA8; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px;">View Event & RSVP</a>
								</div>

								<p style="margin: 30px 0 0 0; font-size: 14px; line-height: 1.6; color: #666666; text-align: center;">
									See you there!<br>
									<strong><?php echo esc_html($data['host_name']); ?></strong>
								</p>
							</td>
						</tr>
						<tr>
							<td style="background-color: #f8f9fa; padding: 25px 40px; text-align: center; border-top: 1px solid #e0e0e0;">
								<p style="margin: 0; font-size: 12px; color: #999999; line-height: 1.6;">
									This invitation was sent by <?php echo esc_html($data['host_name']); ?><br>
									<a href="<?php echo esc_url($data['unsubscribe_url']); ?>" style="color: #999999; text-decoration: underline;">Unsubscribe</a>
								</p>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</body>

	</html>
<?php
	return ob_get_clean();
}

function event_rsvp_track_email_open($token)
{
	global $wpdb;
	$recipients_table = $wpdb->prefix . 'event_email_recipients';
	$tracking_table = $wpdb->prefix . 'event_email_tracking';

	$recipient = $wpdb->get_row($wpdb->prepare(
		"SELECT * FROM $recipients_table WHERE tracking_token = %s",
		$token
	));

	if (!$recipient) {
		return false;
	}

	if ($recipient->clicked_status == 0) {
		$wpdb->update(
			$recipients_table,
			array(
				'clicked_status' => 1,
				'clicked_time' => current_time('mysql'),
				'ip_address' => event_rsvp_get_client_ip(),
				'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''
			),
			array('id' => $recipient->id),
			array('%d', '%s', '%s', '%s'),
			array('%d')
		);

		$wpdb->query($wpdb->prepare(
			"UPDATE {$wpdb->prefix}event_email_campaigns 
			SET total_clicked = (SELECT COUNT(*) FROM $recipients_table WHERE campaign_id = %d AND clicked_status = 1)
			WHERE id = %d",
			$recipient->campaign_id,
			$recipient->campaign_id
		));
	}

	$wpdb->insert(
		$tracking_table,
		array(
			'recipient_id' => $recipient->id,
			'campaign_id' => $recipient->campaign_id,
			'event_type' => 'click',
			'ip_address' => event_rsvp_get_client_ip(),
			'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''
		),
		array('%d', '%d', '%s', '%s', '%s')
	);

	return $recipient;
}

function event_rsvp_record_email_response($token, $response)
{
	global $wpdb;
	$recipients_table = $wpdb->prefix . 'event_email_recipients';
	$campaigns_table = $wpdb->prefix . 'event_email_campaigns';
	$tracking_table = $wpdb->prefix . 'event_email_tracking';

	$recipient = $wpdb->get_row($wpdb->prepare(
		"SELECT * FROM $recipients_table WHERE tracking_token = %s",
		$token
	));

	if (!$recipient) {
		return false;
	}

	$wpdb->update(
		$recipients_table,
		array(
			'response' => $response,
			'response_time' => current_time('mysql')
		),
		array('id' => $recipient->id),
		array('%s', '%s'),
		array('%d')
	);

	if ($response === 'yes') {
		$wpdb->query($wpdb->prepare(
			"UPDATE $campaigns_table 
			SET total_yes = (SELECT COUNT(*) FROM $recipients_table WHERE campaign_id = %d AND response = 'yes')
			WHERE id = %d",
			$recipient->campaign_id,
			$recipient->campaign_id
		));
	} elseif ($response === 'no') {
		$wpdb->query($wpdb->prepare(
			"UPDATE $campaigns_table 
			SET total_no = (SELECT COUNT(*) FROM $recipients_table WHERE campaign_id = %d AND response = 'no')
			WHERE id = %d",
			$recipient->campaign_id,
			$recipient->campaign_id
		));
	}

	$wpdb->insert(
		$tracking_table,
		array(
			'recipient_id' => $recipient->id,
			'campaign_id' => $recipient->campaign_id,
			'event_type' => 'response',
			'ip_address' => event_rsvp_get_client_ip(),
			'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
			'metadata' => json_encode(array('response' => $response))
		),
		array('%d', '%d', '%s', '%s', '%s', '%s')
	);

	return true;
}

function event_rsvp_get_client_ip()
{
	$ip = '';

	if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		$ip = $_SERVER['REMOTE_ADDR'];
	}

	return sanitize_text_field($ip);
}

function event_rsvp_get_campaign_stats($campaign_id)
{
	global $wpdb;
	$recipients_table = $wpdb->prefix . 'event_email_recipients';

	$stats = $wpdb->get_row($wpdb->prepare(
		"SELECT 
			COUNT(*) as total,
			SUM(CASE WHEN sent_status = 'sent' THEN 1 ELSE 0 END) as sent,
			SUM(CASE WHEN sent_status = 'pending' THEN 1 ELSE 0 END) as pending,
			SUM(CASE WHEN sent_status = 'failed' THEN 1 ELSE 0 END) as failed,
			SUM(CASE WHEN clicked_status = 1 THEN 1 ELSE 0 END) as clicked,
			SUM(CASE WHEN response = 'yes' THEN 1 ELSE 0 END) as yes_responses,
			SUM(CASE WHEN response = 'no' THEN 1 ELSE 0 END) as no_responses
		FROM $recipients_table 
		WHERE campaign_id = %d",
		$campaign_id
	));

	// Handle NULL values from SUM() when no rows exist
	if (!$stats) {
		$stats = (object) array(
			'total' => 0,
			'sent' => 0,
			'pending' => 0,
			'failed' => 0,
			'clicked' => 0,
			'yes_responses' => 0,
			'no_responses' => 0,
			'click_rate' => 0,
			'yes_rate' => 0
		);
	} else {
		// Convert NULL values to 0
		$stats->total = intval($stats->total);
		$stats->sent = intval($stats->sent);
		$stats->pending = intval($stats->pending);
		$stats->failed = intval($stats->failed);
		$stats->clicked = intval($stats->clicked);
		$stats->yes_responses = intval($stats->yes_responses);
		$stats->no_responses = intval($stats->no_responses);

		// Calculate rates
		if ($stats->sent > 0) {
			$stats->click_rate = round(($stats->clicked / $stats->sent) * 100, 2);
			$stats->yes_rate = round(($stats->yes_responses / $stats->sent) * 100, 2);
		} else {
			$stats->click_rate = 0;
			$stats->yes_rate = 0;
		}
	}

	return $stats;
}

<?php
/**
 * Add Image Upload Email Template
 * 
 * @package EventRSVPPlugin
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Insert Image Upload Template if it doesn't exist
 */
function event_rsvp_add_image_upload_template() {
	global $wpdb;
	$templates_table = $wpdb->prefix . 'event_email_templates';
	
	// Check if table exists
	if ($wpdb->get_var("SHOW TABLES LIKE '$templates_table'") != $templates_table) {
		return;
	}
	
	// Check if template already exists
	$existing = $wpdb->get_var($wpdb->prepare(
		"SELECT COUNT(*) FROM $templates_table WHERE name = %s",
		'Image Upload Template'
	));
	
	if ($existing > 0) {
		return; // Already exists
	}
	
	// Insert the new template
	$wpdb->insert(
		$templates_table,
		array(
			'name' => 'Image Upload Template',
			'description' => 'Template with custom image upload and event button',
			'subject' => 'You\'re Invited: {{event_name}}',
			'html_content' => '<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Event Invitation</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; background-color: #f5f5f5;">
	<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f5f5f5; padding: 40px 20px;">
		<tr>
			<td align="center">
				<table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
					<tr>
						<td style="background: linear-gradient(135deg, #503AA8 0%, #6B52C3 100%); padding: 50px 40px; text-align: center;">
							<h1 style="margin: 0; color: #ffffff; font-size: 32px; font-weight: 700;">You\'re Invited!</h1>
							<p style="margin: 15px 0 0 0; color: rgba(255, 255, 255, 0.95); font-size: 18px;">{{host_name}} has invited you to an event</p>
						</td>
					</tr>
					{{#custom_image}}
					<tr>
						<td style="padding: 0;">
							<img src="{{custom_image}}" alt="Event Image" style="width: 100%; height: auto; display: block; max-height: 400px; object-fit: cover;">
						</td>
					</tr>
					{{/custom_image}}
					<tr>
						<td style="padding: 40px;">
							<h2 style="margin: 0 0 20px 0; font-size: 28px; color: #503AA8; font-weight: 700; text-align: center;">{{event_name}}</h2>
							
							<div style="background-color: #f8f9fa; border-left: 4px solid #503AA8; padding: 25px; margin: 30px 0; border-radius: 8px;">
								<p style="margin: 0 0 10px 0; color: #555555; font-size: 15px; line-height: 1.6;">
									<strong>📅 Date:</strong> {{event_date}}
								</p>
								<p style="margin: 0 0 10px 0; color: #555555; font-size: 15px; line-height: 1.6;">
									<strong>🕐 Time:</strong> {{event_time}}
								</p>
								<p style="margin: 0; color: #555555; font-size: 15px; line-height: 1.6;">
									<strong>📍 Location:</strong> {{event_location}}
								</p>
							</div>
							
							{{#event_description}}
							<div style="margin: 30px 0;">
								<p style="margin: 0; font-size: 15px; line-height: 1.7; color: #555555; text-align: center;">{{event_description}}</p>
							</div>
							{{/event_description}}
							
							<div style="text-align: center; margin: 40px 0;">
								<a href="{{tracking_url}}" style="display: inline-block; padding: 16px 40px; background-color: #503AA8; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(80, 58, 168, 0.3);">View Event & RSVP</a>
							</div>
							
							<p style="margin: 30px 0 0 0; font-size: 14px; line-height: 1.6; color: #666666; text-align: center;">
								We hope to see you there!<br>
								<strong>{{host_name}}</strong>
							</p>
						</td>
					</tr>
					<tr>
						<td style="background-color: #f8f9fa; padding: 25px 40px; text-align: center; border-top: 1px solid #e0e0e0;">
							<p style="margin: 0; font-size: 12px; color: #999999; line-height: 1.6;">
								This invitation was sent by {{host_name}}<br>
								<a href="{{unsubscribe_url}}" style="color: #999999; text-decoration: underline;">Unsubscribe</a>
							</p>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>',
			'is_default' => 1
		),
		array('%s', '%s', '%s', '%s', '%d')
	);
	
	if ($wpdb->insert_id) {
		error_log('✓ Image Upload Template added successfully');
	}
}

// Run on init to ensure template is added
add_action('init', 'event_rsvp_add_image_upload_template', 20);

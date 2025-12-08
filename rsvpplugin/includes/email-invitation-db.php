<?php
/**
 * Email Invitation Database Schema
 * 
 * @package EventRSVPPlugin
 */

if (!defined('ABSPATH')) {
	exit;
}

function event_rsvp_create_email_invitation_tables() {
	global $wpdb;
	
	$charset_collate = $wpdb->get_charset_collate();
	
	$campaigns_table = $wpdb->prefix . 'event_email_campaigns';
	$recipients_table = $wpdb->prefix . 'event_email_recipients';
	$templates_table = $wpdb->prefix . 'event_email_templates';
	$tracking_table = $wpdb->prefix . 'event_email_tracking';
	
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	
	$sql_campaigns = "CREATE TABLE $campaigns_table (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		event_id bigint(20) NOT NULL,
		host_id bigint(20) NOT NULL,
		campaign_name varchar(255) NOT NULL,
		template_id bigint(20) DEFAULT NULL,
		subject varchar(255) NOT NULL,
		status varchar(50) DEFAULT 'draft',
		scheduled_time datetime DEFAULT NULL,
		sent_time datetime DEFAULT NULL,
		total_recipients int(11) DEFAULT 0,
		total_sent int(11) DEFAULT 0,
		total_delivered int(11) DEFAULT 0,
		total_clicked int(11) DEFAULT 0,
		total_yes int(11) DEFAULT 0,
		total_no int(11) DEFAULT 0,
		custom_data text DEFAULT NULL,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY event_id (event_id),
		KEY host_id (host_id),
		KEY status (status)
	) $charset_collate;";
	
	$sql_recipients = "CREATE TABLE $recipients_table (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		campaign_id bigint(20) NOT NULL,
		email varchar(255) NOT NULL,
		name varchar(255) DEFAULT NULL,
		tracking_token varchar(100) NOT NULL UNIQUE,
		sent_status varchar(50) DEFAULT 'pending',
		sent_time datetime DEFAULT NULL,
		clicked_status tinyint(1) DEFAULT 0,
		clicked_time datetime DEFAULT NULL,
		response varchar(20) DEFAULT NULL,
		response_time datetime DEFAULT NULL,
		attendee_id bigint(20) DEFAULT NULL,
		ip_address varchar(100) DEFAULT NULL,
		user_agent text DEFAULT NULL,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY campaign_id (campaign_id),
		KEY email (email),
		KEY tracking_token (tracking_token),
		KEY sent_status (sent_status),
		KEY response (response)
	) $charset_collate;";
	
	$sql_templates = "CREATE TABLE $templates_table (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		name varchar(255) NOT NULL,
		description text DEFAULT NULL,
		subject varchar(255) NOT NULL,
		html_content longtext NOT NULL,
		preview_image varchar(500) DEFAULT NULL,
		is_default tinyint(1) DEFAULT 0,
		created_by bigint(20) DEFAULT NULL,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY  (id)
	) $charset_collate;";
	
	$sql_tracking = "CREATE TABLE $tracking_table (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		recipient_id bigint(20) NOT NULL,
		campaign_id bigint(20) NOT NULL,
		event_type varchar(50) NOT NULL,
		ip_address varchar(100) DEFAULT NULL,
		user_agent text DEFAULT NULL,
		metadata text DEFAULT NULL,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY recipient_id (recipient_id),
		KEY campaign_id (campaign_id),
		KEY event_type (event_type),
		KEY created_at (created_at)
	) $charset_collate;";
	
	dbDelta($sql_campaigns);
	dbDelta($sql_recipients);
	dbDelta($sql_templates);
	dbDelta($sql_tracking);
	
	event_rsvp_insert_default_email_templates();
	
	update_option('event_rsvp_email_db_version', '1.0');
}

function event_rsvp_insert_default_email_templates() {
	global $wpdb;
	$templates_table = $wpdb->prefix . 'event_email_templates';
	
	$existing = $wpdb->get_var("SELECT COUNT(*) FROM $templates_table");
	if ($existing > 0) {
		return;
	}
	
	$default_templates = array(
		array(
			'name' => 'Modern Invitation',
			'description' => 'Clean and modern event invitation with gradient header',
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
							<p style="margin: 15px 0 0 0; color: rgba(255, 255, 255, 0.95); font-size: 18px;">Join us for an amazing event</p>
						</td>
					</tr>
					<tr>
						<td style="padding: 20px;">
							<p style="margin: 0 0 20px 0; font-size: 16px; line-height: 1.6; color: #333333;">Hello!</p>
							<p style="margin: 0 0 30px 0; font-size: 16px; line-height: 1.6; color: #333333;">{{host_name}} has invited you to:</p>
							
							<div style="background-color: #f8f9fa; border-left: 4px solid #503AA8; padding: 25px; margin: 30px 0; border-radius: 8px;">
								<h2 style="margin: 0 0 15px 0; font-size: 24px; color: #503AA8; font-weight: 700;">{{event_name}}</h2>
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
								<h3 style="margin: 0 0 15px 0; font-size: 18px; color: #333333;">About This Event</h3>
								<p style="margin: 0; font-size: 15px; line-height: 1.7; color: #555555;">{{event_description}}</p>
							</div>
							{{/event_description}}
							
							<div style="text-align: center; margin: 40px 0;">
								<a href="{{tracking_url}}" style="display: inline-block; padding: 16px 40px; background-color: #503AA8; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(80, 58, 168, 0.3);">View Event Details & RSVP</a>
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
		array(
			'name' => 'Simple & Clean',
			'description' => 'Minimalist design with clean typography',
			'subject' => 'Join us: {{event_name}}',
			'html_content' => '<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Event Invitation</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; background-color: #ffffff;">
	<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; padding: 40px 20px;">
		<tr>
			<td align="center">
				<table width="600" cellpadding="0" cellspacing="0" border="0">
					<tr>
						<td style="padding: 40px; border-bottom: 3px solid #000000;">
							<h1 style="margin: 0; color: #000000; font-size: 36px; font-weight: 700; letter-spacing: -1px;">You\'re Invited</h1>
						</td>
					</tr>
					<tr>
						<td style="padding: 20px;">
							<p style="margin: 0 0 30px 0; font-size: 16px; line-height: 1.6; color: #333333;">Hi there,</p>
							<p style="margin: 0 0 30px 0; font-size: 16px; line-height: 1.6; color: #333333;">{{host_name}} would love for you to join:</p>
							
							<h2 style="margin: 0 0 25px 0; font-size: 28px; color: #000000; font-weight: 700; letter-spacing: -0.5px;">{{event_name}}</h2>
							
							<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 30px 0;">
								<tr>
									<td style="padding: 15px 0; border-bottom: 1px solid #e0e0e0;">
										<strong style="color: #000000; font-size: 14px; font-weight: 600;">DATE</strong><br>
										<span style="color: #555555; font-size: 15px;">{{event_date}}</span>
									</td>
								</tr>
								<tr>
									<td style="padding: 15px 0; border-bottom: 1px solid #e0e0e0;">
										<strong style="color: #000000; font-size: 14px; font-weight: 600;">TIME</strong><br>
										<span style="color: #555555; font-size: 15px;">{{event_time}}</span>
									</td>
								</tr>
								<tr>
									<td style="padding: 15px 0; border-bottom: 1px solid #e0e0e0;">
										<strong style="color: #000000; font-size: 14px; font-weight: 600;">LOCATION</strong><br>
										<span style="color: #555555; font-size: 15px;">{{event_location}}</span>
									</td>
								</tr>
							</table>
							
							{{#event_description}}
							<p style="margin: 30px 0; font-size: 15px; line-height: 1.7; color: #555555;">{{event_description}}</p>
							{{/event_description}}
							
							<div style="margin: 40px 0;">
								<a href="{{tracking_url}}" style="display: inline-block; padding: 14px 36px; background-color: #000000; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 15px; letter-spacing: 0.5px;">RSVP NOW</a>
							</div>
							
							<p style="margin: 30px 0 0 0; font-size: 14px; line-height: 1.6; color: #666666;">
								Best regards,<br>
								<strong>{{host_name}}</strong>
							</p>
						</td>
					</tr>
					<tr>
						<td style="padding: 30px 40px; text-align: center; border-top: 1px solid #e0e0e0;">
							<p style="margin: 0; font-size: 11px; color: #999999; line-height: 1.6;">
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
		array(
			'name' => 'Colorful & Fun',
			'description' => 'Vibrant and energetic design perfect for social events',
			'subject' => '🎉 You\'re Invited to {{event_name}}!',
			'html_content' => '<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Event Invitation</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
	<table width="100%" cellpadding="0" cellspacing="0" border="0" style="padding: 40px 20px;">
		<tr>
			<td align="center">
				<table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);">
					<tr>
						<td style="padding: 50px 40px; text-align: center; background: linear-gradient(135deg, #FF6B6B 0%, #FFE66D 100%);">
							<div style="font-size: 60px; margin-bottom: 15px;">🎉</div>
							<h1 style="margin: 0; color: #ffffff; font-size: 34px; font-weight: 800; text-shadow: 2px 2px 4px rgba(0,0,0,0.2);">You\'re Invited!</h1>
						</td>
					</tr>
					<tr>
						<td style="padding: 20px;">
							<p style="margin: 0 0 20px 0; font-size: 17px; line-height: 1.6; color: #333333;">Hey there! 👋</p>
							<p style="margin: 0 0 30px 0; font-size: 17px; line-height: 1.6; color: #333333;"><strong>{{host_name}}</strong> is throwing an event and you\'re on the guest list!</p>
							
							<div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 30px; margin: 30px 0; border-radius: 12px; box-shadow: 0 4px 15px rgba(240, 147, 251, 0.3);">
								<h2 style="margin: 0 0 20px 0; font-size: 26px; color: #ffffff; font-weight: 700; text-shadow: 1px 1px 2px rgba(0,0,0,0.2);">{{event_name}}</h2>
								<div style="background-color: rgba(255,255,255,0.2); padding: 20px; border-radius: 8px; backdrop-filter: blur(10px);">
									<p style="margin: 0 0 12px 0; color: #ffffff; font-size: 15px;">
										<span style="font-size: 20px;">📅</span> <strong>{{event_date}}</strong>
									</p>
									<p style="margin: 0 0 12px 0; color: #ffffff; font-size: 15px;">
										<span style="font-size: 20px;">🕐</span> <strong>{{event_time}}</strong>
									</p>
									<p style="margin: 0; color: #ffffff; font-size: 15px;">
										<span style="font-size: 20px;">📍</span> <strong>{{event_location}}</strong>
									</p>
								</div>
							</div>
							
							{{#event_description}}
							<div style="background-color: #f8f9fa; padding: 25px; margin: 30px 0; border-radius: 10px; border-left: 4px solid #FF6B6B;">
								<p style="margin: 0; font-size: 15px; line-height: 1.7; color: #555555;">{{event_description}}</p>
							</div>
							{{/event_description}}
							
							<div style="text-align: center; margin: 40px 0;">
								<a href="{{tracking_url}}" style="display: inline-block; padding: 18px 45px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; border-radius: 50px; font-weight: 700; font-size: 17px; box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4); text-transform: uppercase; letter-spacing: 1px;">Let\'s Go! 🚀</a>
							</div>
							
							<p style="margin: 30px 0 0 0; font-size: 15px; line-height: 1.6; color: #666666; text-align: center;">
								Can\'t wait to see you there! 🎊<br>
								<strong style="color: #764ba2;">{{host_name}}</strong>
							</p>
						</td>
					</tr>
					<tr>
						<td style="background-color: #f8f9fa; padding: 25px 40px; text-align: center;">
							<p style="margin: 0; font-size: 12px; color: #999999; line-height: 1.6;">
								Sent with ❤️ by {{host_name}}<br>
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
		array(
			'name' => 'Professional Event',
			'description' => 'Corporate and professional event invitation',
			'subject' => 'Invitation: {{event_name}}',
			'html_content' => '<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Event Invitation</title>
</head>
<body style="margin: 0; padding: 0; font-family: Georgia, \'Times New Roman\', serif; background-color: #f5f5f5;">
	<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f5f5f5; padding: 40px 20px;">
		<tr>
			<td align="center">
				<table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
					<tr>
						<td style="padding: 50px 50px 30px 50px; border-bottom: 2px solid #2C3E50;">
							<h1 style="margin: 0; color: #2C3E50; font-size: 32px; font-weight: 400; text-align: center;">Event Invitation</h1>
						</td>
					</tr>
					<tr>
						<td style="padding: 40px 50px;">
							<p style="margin: 0 0 25px 0; font-size: 16px; line-height: 1.7; color: #333333;">Dear Guest,</p>
							<p style="margin: 0 0 30px 0; font-size: 16px; line-height: 1.7; color: #333333;">You are cordially invited to attend:</p>
							
							<div style="border: 2px solid #2C3E50; padding: 30px; margin: 30px 0; text-align: center;">
								<h2 style="margin: 0 0 25px 0; font-size: 26px; color: #2C3E50; font-weight: 400; font-style: italic;">{{event_name}}</h2>
								<div style="border-top: 1px solid #BDC3C7; border-bottom: 1px solid #BDC3C7; padding: 20px 0; margin: 20px 0;">
									<p style="margin: 0 0 10px 0; color: #555555; font-size: 15px;">
										<strong style="color: #2C3E50;">Date:</strong> {{event_date}}
									</p>
									<p style="margin: 0 0 10px 0; color: #555555; font-size: 15px;">
										<strong style="color: #2C3E50;">Time:</strong> {{event_time}}
									</p>
									<p style="margin: 0; color: #555555; font-size: 15px;">
										<strong style="color: #2C3E50;">Venue:</strong> {{event_location}}
									</p>
								</div>
							</div>
							
							{{#event_description}}
							<p style="margin: 30px 0; font-size: 15px; line-height: 1.7; color: #555555; text-align: justify;">{{event_description}}</p>
							{{/event_description}}
							
							<div style="text-align: center; margin: 40px 0;">
								<a href="{{tracking_url}}" style="display: inline-block; padding: 14px 40px; background-color: #2C3E50; color: #ffffff; text-decoration: none; font-size: 15px; letter-spacing: 1px;">CONFIRM ATTENDANCE</a>
							</div>
							
							<p style="margin: 35px 0 0 0; font-size: 15px; line-height: 1.7; color: #555555;">
								Sincerely,<br><br>
								<strong style="color: #2C3E50;">{{host_name}}</strong>
							</p>
						</td>
					</tr>
					<tr>
						<td style="background-color: #ECF0F1; padding: 25px 50px; border-top: 1px solid #BDC3C7;">
							<p style="margin: 0; font-size: 12px; color: #7F8C8D; line-height: 1.6; text-align: center;">
								This is a formal invitation from {{host_name}}<br>
								<a href="{{unsubscribe_url}}" style="color: #7F8C8D; text-decoration: underline;">Manage preferences</a>
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
		)
	);
	
	foreach ($default_templates as $template) {
		$wpdb->insert(
			$templates_table,
			$template,
			array('%s', '%s', '%s', '%s', '%s', '%d')
		);
	}
}

add_action('after_switch_theme', 'event_rsvp_create_email_invitation_tables');
add_action('init', 'event_rsvp_maybe_create_email_tables');

function event_rsvp_maybe_create_email_tables() {
	$db_version = get_option('event_rsvp_email_db_version');
	if (!$db_version) {
		event_rsvp_create_email_invitation_tables();
	} else {
		event_rsvp_upgrade_email_tables($db_version);
	}
}

function event_rsvp_upgrade_email_tables($current_version) {
	global $wpdb;
	$campaigns_table = $wpdb->prefix . 'event_email_campaigns';

	$column_exists = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM INFORMATION_SCHEMA.COLUMNS
			WHERE TABLE_SCHEMA = %s
			AND TABLE_NAME = %s
			AND COLUMN_NAME = 'custom_data'",
			DB_NAME,
			$campaigns_table
		)
	);

	if (empty($column_exists)) {
		$wpdb->query("ALTER TABLE {$campaigns_table} ADD COLUMN custom_data text DEFAULT NULL AFTER total_no");
		error_log("Added custom_data column to {$campaigns_table}");
	}

	update_option('event_rsvp_email_db_version', '1.1');
}

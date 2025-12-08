<?php
/**
 * Email Functions
 *
 * @package EventRSVPPlugin
 *
 * SMTP Configuration Notes:
 * ========================
 *
 * Hostinger SMTP Settings:
 * - SMTP Host: smtp.hostinger.com
 * - SMTP Port: 465 (SSL) or 587 (TLS)
 * - Encryption: SSL for port 465, TLS for port 587
 * - Username: Your full email address (e.g., ceo@aqbrandingstudio.com)
 * - From Email: MUST match SMTP username to avoid "Sender address rejected" errors
 *
 * CNAME Records for Email Autodiscovery:
 * ======================================
 * Add these CNAME records in your domain's DNS settings:
 *
 * 1. Autodiscover (Outlook/Exchange):
 *    Type: CNAME
 *    Host: autodiscover
 *    Value: autodiscover.mail.hostinger.com
 *    TTL: 300
 *
 * 2. Autoconfig (Thunderbird/other clients):
 *    Type: CNAME
 *    Host: autoconfig
 *    Value: autoconfig.mail.hostinger.com
 *    TTL: 300
 *
 * These records help email clients automatically configure settings.
 */

if (!defined('ABSPATH')) {
	exit;
}

function event_rsvp_get_confirmation_email_template($attendee_id) {
	$attendee_email = get_post_meta($attendee_id, 'attendee_email', true);
	$attendee_name = get_the_title($attendee_id);
	$event_id = get_post_meta($attendee_id, 'linked_event', true);
	$event_title = get_the_title($event_id);
	$event_date = get_post_meta($event_id, 'event_date', true);
	$venue_address = get_post_meta($event_id, 'venue_address', true);
	$qr_data = get_post_meta($attendee_id, 'qr_data', true);
	$qr_code_url = event_rsvp_generate_qr_code($qr_data);

	$event_url = get_permalink($event_id);
	$qr_viewer_url = home_url('/qr-view/?qr=' . urlencode($qr_data));
	$formatted_date = date('F j, Y \a\t g:i A', strtotime($event_date));

	ob_start();
	?>
	<!DOCTYPE html>
	<html>
	<head>
		<meta charset="UTF-8">
		<title>RSVP Confirmation</title>
	</head>
	<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; background-color: #f5f5f5;">
		<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f5f5f5; padding: 40px 20px;">
			<tr>
				<td align="center">
					<table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
						<tr>
							<td style="background: linear-gradient(135deg, #503AA8 0%, #6B52C3 100%); padding: 40px; text-align: center;">
								<h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;">✓ RSVP Confirmed!</h1>
								<p style="margin: 10px 0 0 0; color: rgba(255, 255, 255, 0.9); font-size: 16px;">You're all set for the event</p>
							</td>
						</tr>
						<tr>
							<td style="padding: 40px;">
								<p style="margin: 0 0 20px 0; font-size: 16px; line-height: 1.6; color: #333333;">Hello <strong><?php echo esc_html($attendee_name); ?></strong>,</p>
								<p style="margin: 0 0 20px 0; font-size: 16px; line-height: 1.6; color: #333333;">Thank you for your RSVP! We're excited to have you join us for:</p>

								<div style="background-color: #f8f9fa; border-left: 4px solid #503AA8; padding: 20px; margin: 30px 0; border-radius: 4px;">
									<h2 style="margin: 0 0 15px 0; font-size: 20px; color: #503AA8;"><?php echo esc_html($event_title); ?></h2>
									<p style="margin: 0 0 8px 0; color: #555555; font-size: 14px;">
										<strong>📅 When:</strong> <?php echo esc_html($formatted_date); ?>
									</p>
									<p style="margin: 0; color: #555555; font-size: 14px;">
										<strong>📍 Where:</strong> <?php echo esc_html($venue_address); ?>
									</p>
								</div>

								<h3 style="margin: 30px 0 15px 0; font-size: 18px; color: #333333;">Your Check-In QR Code</h3>
								<p style="margin: 0 0 20px 0; font-size: 14px; color: #666666;">Please save this QR code and present it at the event entrance for quick check-in:</p>

								<div style="text-align: center; padding: 30px; background-color: #f8f9fa; border-radius: 8px; margin: 20px 0;">
									<img src="<?php echo esc_url($qr_code_url); ?>" alt="Check-in QR Code" style="width: 250px; height: 250px; border: 3px solid #503AA8; border-radius: 8px;">
								</div>

								<div style="background-color: #fff3cd; border: 1px solid #ffecb5; border-radius: 6px; padding: 15px; margin: 30px 0;">
									<p style="margin: 0; font-size: 14px; color: #856404;">
										💡 <strong>Pro Tip:</strong> Save this email or take a screenshot of your QR code to have it ready on event day!
									</p>
								</div>

								<div style="text-align: center; margin: 30px 0;">
									<a href="<?php echo esc_url($event_url); ?>" style="display: inline-block; padding: 14px 32px; background-color: #503AA8; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px;">View Event Details</a>
								</div>

								<p style="margin: 30px 0 0 0; font-size: 14px; line-height: 1.6; color: #666666;">
									See you at the event!<br>
									<strong>The Event Team</strong>
								</p>
							</td>
						</tr>
						<tr>
							<td style="background-color: #f8f9fa; padding: 30px; text-align: center; border-top: 1px solid #e0e0e0;">
								<p style="margin: 0; font-size: 12px; color: #999999; line-height: 1.6;">
									This is an automated message. Please do not reply to this email.<br>
									If you have questions, please contact the event organizer.
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

function event_rsvp_configure_smtp($phpmailer) {
	$smtp_enabled = get_option('event_rsvp_smtp_enabled', false);

	if (!$smtp_enabled) {
		return;
	}

	$smtp_host = get_option('event_rsvp_smtp_host', '');
	$smtp_port = get_option('event_rsvp_smtp_port', 587);
	$smtp_username = get_option('event_rsvp_smtp_username', '');
	$smtp_password = get_option('event_rsvp_smtp_password', '');
	$smtp_from_email = get_option('event_rsvp_smtp_from_email', '');
	$smtp_from_name = get_option('event_rsvp_smtp_from_name', 'Event RSVP');
	$smtp_secure = get_option('event_rsvp_smtp_secure', 'tls');

	if (empty($smtp_host)) {
		error_log('Event RSVP: SMTP host not configured');
		return;
	}

	try {
		$phpmailer->isSMTP();
		$phpmailer->Host = $smtp_host;
		$phpmailer->Port = intval($smtp_port);

		if (!empty($smtp_username) && !empty($smtp_password)) {
			$phpmailer->SMTPAuth = true;
			$phpmailer->Username = $smtp_username;
			$phpmailer->Password = $smtp_password;
		} else {
			$phpmailer->SMTPAuth = false;
		}

		// Auto-detect encryption based on port if not explicitly set
		// Port 465 = SSL, Port 587 = TLS, Port 25 = None
		$port = intval($smtp_port);

		if (!empty($smtp_secure) && in_array(strtolower($smtp_secure), array('tls', 'ssl'))) {
			$phpmailer->SMTPSecure = strtolower($smtp_secure);
		} elseif ($port === 465) {
			// Port 465 requires SSL encryption
			$phpmailer->SMTPSecure = 'ssl';
			error_log('Event RSVP: Auto-detected SSL encryption for port 465');
		} elseif ($port === 587) {
			// Port 587 requires TLS encryption
			$phpmailer->SMTPSecure = 'tls';
			error_log('Event RSVP: Auto-detected TLS encryption for port 587');
		} else {
			// Port 25 or custom port - no encryption
			$phpmailer->SMTPSecure = false;
			$phpmailer->SMTPAutoTLS = false;
		}

		// CRITICAL: FROM address MUST match SMTP username for most providers
		// This prevents "Sender address rejected" errors

		// Check if this is a known provider that requires matching FROM and username
		$requires_match = false;
		$known_providers = array('hostinger.com', 'gmail.com', 'outlook.com', 'yahoo.com', 'office365.com', 'mail.yahoo.com');
		foreach ($known_providers as $provider) {
			if (strpos($smtp_host, $provider) !== false) {
				$requires_match = true;
				break;
			}
		}

		// FORCE FROM email to match SMTP username for known providers
		// This is MANDATORY and CANNOT be overridden
		if ($requires_match && !empty($smtp_username) && is_email($smtp_username)) {
			$phpmailer->setFrom($smtp_username, $smtp_from_name);
			$phpmailer->From = $smtp_username; // Force override
			$phpmailer->Sender = $smtp_username; // Set sender path
			error_log('Event RSVP: ENFORCING FROM address to SMTP username (' . $smtp_username . ') for ' . $smtp_host);
		} elseif (!empty($smtp_from_email) && is_email($smtp_from_email)) {
			// Use configured FROM email for other providers
			$phpmailer->setFrom($smtp_from_email, $smtp_from_name);
			$phpmailer->From = $smtp_from_email;
			error_log('Event RSVP: Using configured FROM address (' . $smtp_from_email . ')');
		} elseif (!empty($smtp_username) && is_email($smtp_username)) {
			// Fallback to SMTP username if FROM email not set
			$phpmailer->setFrom($smtp_username, $smtp_from_name);
			$phpmailer->From = $smtp_username;
			error_log('Event RSVP: Using SMTP username as FROM address');
		}

		$phpmailer->CharSet = 'UTF-8';

		// SSL/TLS options for Hostinger and other providers
		$phpmailer->SMTPOptions = array(
			'ssl' => array(
				'verify_peer' => false,
				'verify_peer_name' => false,
				'allow_self_signed' => true
			)
		);

		$phpmailer->Timeout = 30;
		$phpmailer->SMTPKeepAlive = false;

		// Enhanced debugging for troubleshooting
		if (defined('WP_DEBUG') && WP_DEBUG) {
			$phpmailer->SMTPDebug = 2;
			$phpmailer->Debugoutput = function($str, $level) {
				error_log("SMTP Debug [{$level}]: {$str}");
			};
		}

		error_log('Event RSVP SMTP Configured: Host=' . $smtp_host . ', Port=' . $smtp_port . ', Encryption=' . ($phpmailer->SMTPSecure ?: 'none') . ', Auth=' . ($phpmailer->SMTPAuth ? 'yes' : 'no'));

	} catch (Exception $e) {
		error_log('Event RSVP SMTP Configuration Error: ' . $e->getMessage());
	}
}
// Use priority 20 to ensure this runs after other plugins
add_action('phpmailer_init', 'event_rsvp_configure_smtp', 20);

/**
 * Override wp_mail FROM address to use SMTP username
 * This prevents WordPress from using the admin email
 * CRITICAL: This must run to avoid "Sender address rejected" errors
 * PRIORITY 1 ensures this runs BEFORE WordPress defaults
 */
function event_rsvp_mail_from($original_email_address) {
	$smtp_enabled = get_option('event_rsvp_smtp_enabled', false);

	if (!$smtp_enabled) {
		return $original_email_address;
	}

	$smtp_username = get_option('event_rsvp_smtp_username', '');
	$smtp_from_email = get_option('event_rsvp_smtp_from_email', '');
	$smtp_host = get_option('event_rsvp_smtp_host', '');
	$admin_email = get_option('admin_email', '');

	// NEVER allow admin email to be used as FROM
	// This is the most common cause of "Sender address rejected" errors
	if ($original_email_address === $admin_email) {
		error_log('Event RSVP: BLOCKING admin email from being used as FROM address: ' . $admin_email);
		$original_email_address = '';
	}

	// Check if this is a known provider that requires matching FROM and username
	$requires_match = false;
	$known_providers = array('hostinger.com', 'gmail.com', 'outlook.com', 'yahoo.com', 'office365.com', 'mail.yahoo.com');
	foreach ($known_providers as $provider) {
		if (strpos($smtp_host, $provider) !== false) {
			$requires_match = true;
			break;
		}
	}

	// ALWAYS use SMTP username as FROM for known providers
	// This is MANDATORY to avoid "Sender address rejected" errors
	if ($requires_match && !empty($smtp_username) && is_email($smtp_username)) {
		if ($original_email_address !== $smtp_username) {
			error_log('Event RSVP: wp_mail_from filter FORCING FROM to SMTP username: ' . $smtp_username . ' (was: ' . $original_email_address . ')');
		}
		return $smtp_username;
	}

	// Use configured FROM email if set and valid (for non-known providers)
	if (!empty($smtp_from_email) && is_email($smtp_from_email)) {
		return $smtp_from_email;
	}

	// Fallback to SMTP username
	if (!empty($smtp_username) && is_email($smtp_username)) {
		error_log('Event RSVP: wp_mail_from using SMTP username as fallback: ' . $smtp_username);
		return $smtp_username;
	}

	// Last resort: if we get here, log a warning
	if (empty($smtp_username)) {
		error_log('Event RSVP: WARNING - No SMTP username configured, cannot set FROM address properly');
	}

	return $original_email_address;
}
// PRIORITY 1 to run BEFORE WordPress core and other plugins
add_filter('wp_mail_from', 'event_rsvp_mail_from', 1);

/**
 * Override wp_mail FROM name
 */
function event_rsvp_mail_from_name($original_email_from) {
	$smtp_enabled = get_option('event_rsvp_smtp_enabled', false);

	if (!$smtp_enabled) {
		return $original_email_from;
	}

	$smtp_from_name = get_option('event_rsvp_smtp_from_name', 'Event RSVP');

	return !empty($smtp_from_name) ? $smtp_from_name : $original_email_from;
}
add_filter('wp_mail_from_name', 'event_rsvp_mail_from_name', 999);

/**
 * Pre-send validation to ensure FROM email is never the admin email
 * This is a final failsafe to prevent "Sender address rejected" errors
 */
function event_rsvp_validate_mail_before_send($args) {
	$smtp_enabled = get_option('event_rsvp_smtp_enabled', false);

	if (!$smtp_enabled) {
		return $args;
	}

	$admin_email = get_option('admin_email', '');
	$smtp_username = get_option('event_rsvp_smtp_username', '');

	// Check if FROM header contains admin email
	if (!empty($args['headers'])) {
		$headers = $args['headers'];
		if (!is_array($headers)) {
			$headers = explode("\n", str_replace("\r\n", "\n", $headers));
		}

		$filtered_headers = array();
		foreach ($headers as $header) {
			// Remove any FROM headers that use admin email
			if (stripos($header, 'From:') === 0 && !empty($admin_email)) {
				if (stripos($header, $admin_email) !== false) {
					error_log('Event RSVP: BLOCKED admin email in FROM header: ' . $header);
					// Skip this header - let our filter handle it
					continue;
				}
			}
			$filtered_headers[] = $header;
		}

		$args['headers'] = $filtered_headers;
	}

	return $args;
}
add_filter('wp_mail', 'event_rsvp_validate_mail_before_send', 1);

/**
 * Validate SMTP configuration and provide helpful error messages
 *
 * @return array Array with 'valid' (bool) and 'message' (string)
 */
function event_rsvp_validate_smtp_config() {
	$smtp_enabled = get_option('event_rsvp_smtp_enabled', false);

	if (!$smtp_enabled) {
		return array(
			'valid' => false,
			'message' => 'SMTP is not enabled'
		);
	}

	$smtp_host = get_option('event_rsvp_smtp_host', '');
	$smtp_port = get_option('event_rsvp_smtp_port', 587);
	$smtp_username = get_option('event_rsvp_smtp_username', '');
	$smtp_password = get_option('event_rsvp_smtp_password', '');
	$smtp_from_email = get_option('event_rsvp_smtp_from_email', '');
	$smtp_secure = get_option('event_rsvp_smtp_secure', 'tls');

	$errors = array();

	if (empty($smtp_host)) {
		$errors[] = 'SMTP Host is required';
	}

	if (empty($smtp_port)) {
		$errors[] = 'SMTP Port is required';
	}

	if (empty($smtp_username)) {
		$errors[] = 'SMTP Username is required';
	}

	if (empty($smtp_password)) {
		$errors[] = 'SMTP Password is required';
	}

	if (empty($smtp_from_email)) {
		$errors[] = 'FROM Email is required';
	} elseif (!is_email($smtp_from_email)) {
		$errors[] = 'FROM Email is not a valid email address';
	}

	// Check port and encryption compatibility
	$port = intval($smtp_port);
	if ($port === 465 && $smtp_secure !== 'ssl' && $smtp_secure !== '') {
		$errors[] = 'Port 465 requires SSL encryption. Current setting: ' . ($smtp_secure ?: 'None');
	} elseif ($port === 587 && $smtp_secure !== 'tls' && $smtp_secure !== '') {
		$errors[] = 'Port 587 requires TLS encryption. Current setting: ' . ($smtp_secure ?: 'None');
	}

	// Check FROM email matches username for known providers
	if (!empty($smtp_from_email) && !empty($smtp_username) && $smtp_from_email !== $smtp_username) {
		$known_providers = array('hostinger.com', 'gmail.com', 'outlook.com', 'yahoo.com');
		foreach ($known_providers as $provider) {
			if (strpos($smtp_host, $provider) !== false) {
				$errors[] = 'FROM Email must match SMTP Username for ' . $provider . ' to avoid "Sender address rejected" errors';
				break;
			}
		}
	}

	if (!empty($errors)) {
		return array(
			'valid' => false,
			'message' => implode('; ', $errors)
		);
	}

	return array(
		'valid' => true,
		'message' => 'SMTP configuration is valid'
	);
}

function event_rsvp_send_qr_email_now($attendee_id) {
	$attendee_email = get_post_meta($attendee_id, 'attendee_email', true);
	$event_id = get_post_meta($attendee_id, 'linked_event', true);
	$event_title = get_the_title($event_id);
	$attendee_name = get_the_title($attendee_id);

	if (empty($attendee_email)) {
		error_log("RSVP Email Error: No email address for attendee {$attendee_id}");
		return false;
	}

	if (!is_email($attendee_email)) {
		error_log("RSVP Email Error: Invalid email address '{$attendee_email}' for attendee {$attendee_id}");
		return false;
	}

	$subject = sprintf('✓ RSVP Confirmed: %s', $event_title);
	$message = event_rsvp_get_confirmation_email_template($attendee_id);

	// Don't set FROM header - let wp_mail_from filter handle it automatically
	// This prevents conflicts and ensures SMTP username is always used for known providers
	$smtp_username = get_option('event_rsvp_smtp_username', '');

	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		'Reply-To: ' . $smtp_username
	);

	add_action('wp_mail_failed', 'event_rsvp_log_mail_error', 10, 1);

	$result = wp_mail($attendee_email, $subject, $message, $headers);

	remove_action('wp_mail_failed', 'event_rsvp_log_mail_error', 10);

	if ($result) {
		error_log("RSVP Email sent successfully to {$attendee_email} ({$attendee_name}) for event {$event_title}");
		update_post_meta($attendee_id, 'email_sent', true);
		update_post_meta($attendee_id, 'email_sent_time', current_time('mysql'));
	} else {
		error_log("RSVP Email failed to send to {$attendee_email} ({$attendee_name}) for event {$event_title}");
		update_post_meta($attendee_id, 'email_sent', false);
		update_post_meta($attendee_id, 'email_error', 'Failed to send');
	}

	return $result;
}

function event_rsvp_log_mail_error($wp_error) {
	error_log('WP Mail Error: ' . $wp_error->get_error_message());
}

add_action('event_rsvp_send_qr_email', 'event_rsvp_send_qr_email_now');

/**
 * Customize password reset email to use SMTP configuration
 *
 * @param string $message Default message
 * @param string $key The activation key
 * @param string $user_login The username
 * @param WP_User $user_data WP_User object
 * @return string Modified message
 */
function event_rsvp_custom_password_reset_email($message, $key, $user_login, $user_data) {
	$reset_url = network_site_url("wp-login.php?action=rsvp_resetpass&key=$key&login=" . rawurlencode($user_login), 'login');

	$site_name = get_bloginfo('name');
	$user_email = $user_data->user_email;
	$user_display_name = $user_data->display_name;

	$message = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f9fafb; padding: 20px;">';
	$message .= '<div style="background: white; border-radius: 12px; padding: 40px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
	$message .= '<div style="text-align: center; margin-bottom: 30px;">';
	$message .= '<h1 style="color: #667eea; margin: 0; font-size: 28px;">🔐 Password Reset</h1>';
	$message .= '</div>';

	$message .= '<p style="font-size: 16px; color: #1f2937; line-height: 1.6;">Hello <strong>' . esc_html($user_display_name) . '</strong>,</p>';
	$message .= '<p style="font-size: 16px; color: #1f2937; line-height: 1.6;">Someone requested a password reset for the following account on <strong>' . esc_html($site_name) . '</strong>:</p>';

	$message .= '<div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 25px 0;">';
	$message .= '<p style="margin: 5px 0; font-size: 14px; color: #6b7280;"><strong>Username:</strong> ' . esc_html($user_login) . '</p>';
	$message .= '<p style="margin: 5px 0; font-size: 14px; color: #6b7280;"><strong>Email:</strong> ' . esc_html($user_email) . '</p>';
	$message .= '</div>';

	$message .= '<p style="font-size: 16px; color: #1f2937; line-height: 1.6;">If this was a mistake, just ignore this email and nothing will happen.</p>';
	$message .= '<p style="font-size: 16px; color: #1f2937; line-height: 1.6;">To reset your password, click the button below:</p>';

	$message .= '<div style="text-align: center; margin: 30px 0;">';
	$message .= '<a href="' . esc_url($reset_url) . '" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px;">Reset Your Password</a>';
	$message .= '</div>';

	$message .= '<div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 6px; margin: 25px 0;">';
	$message .= '<p style="margin: 0; font-size: 14px; color: #92400e;"><strong>⚠️ Security Notice:</strong> This link will expire in 24 hours for your security. If you didn\'t request this reset, please secure your account immediately.</p>';
	$message .= '</div>';

	$message .= '<p style="font-size: 14px; color: #6b7280; line-height: 1.6; margin-top: 30px;">If the button doesn\'t work, copy and paste this link into your browser:</p>';
	$message .= '<p style="font-size: 12px; color: #9ca3af; word-break: break-all; background: #f9fafb; padding: 10px; border-radius: 4px;">' . esc_url($reset_url) . '</p>';

	$message .= '<div style="border-top: 1px solid #e5e7eb; margin-top: 40px; padding-top: 20px; text-align: center;">';
	$message .= '<p style="font-size: 12px; color: #9ca3af; margin: 0;">This is an automated email from ' . esc_html($site_name) . '</p>';
	$message .= '</div>';

	$message .= '</div>';
	$message .= '</div>';

	return $message;
}
add_filter('retrieve_password_message', 'event_rsvp_custom_password_reset_email', 10, 4);

/**
 * Customize password reset email title/subject
 */
function event_rsvp_custom_password_reset_title($title, $user_login, $user_data) {
	$site_name = get_bloginfo('name');
	return sprintf('[%s] Password Reset Request', $site_name);
}
add_filter('retrieve_password_title', 'event_rsvp_custom_password_reset_title', 10, 3);

/**
 * Customize password changed notification email
 */
function event_rsvp_custom_password_changed_email($message, $key, $user_login, $user_data) {
	$site_name = get_bloginfo('name');
	$user_display_name = $user_data->display_name;

	$message = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f9fafb; padding: 20px;">';
	$message .= '<div style="background: white; border-radius: 12px; padding: 40px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
	$message .= '<div style="text-align: center; margin-bottom: 30px;">';
	$message .= '<h1 style="color: #10b981; margin: 0; font-size: 28px;">✓ Password Changed</h1>';
	$message .= '</div>';

	$message .= '<p style="font-size: 16px; color: #1f2937; line-height: 1.6;">Hello <strong>' . esc_html($user_display_name) . '</strong>,</p>';
	$message .= '<p style="font-size: 16px; color: #1f2937; line-height: 1.6;">This email confirms that your password was successfully changed on <strong>' . esc_html($site_name) . '</strong>.</p>';

	$message .= '<div style="background: #d1fae5; border-left: 4px solid #10b981; padding: 15px; border-radius: 6px; margin: 25px 0;">';
	$message .= '<p style="margin: 0; font-size: 14px; color: #065f46;"><strong>✓ Success:</strong> Your password has been updated. You can now log in with your new password.</p>';
	$message .= '</div>';

	$message .= '<div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 6px; margin: 25px 0;">';
	$message .= '<p style="margin: 0; font-size: 14px; color: #92400e;"><strong>⚠️ Security Alert:</strong> If you didn\'t make this change, please contact our support team immediately and secure your account.</p>';
	$message .= '</div>';

	$message .= '<div style="text-align: center; margin: 30px 0;">';
	$message .= '<a href="' . home_url('/login/') . '" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px;">Log In Now</a>';
	$message .= '</div>';

	$message .= '<div style="border-top: 1px solid #e5e7eb; margin-top: 40px; padding-top: 20px; text-align: center;">';
	$message .= '<p style="font-size: 12px; color: #9ca3af; margin: 0;">This is an automated email from ' . esc_html($site_name) . '</p>';
	$message .= '</div>';

	$message .= '</div>';
	$message .= '</div>';

	return $message;
}

/**
 * Override password change notification
 */
function event_rsvp_send_password_change_email($user) {
	$site_name = get_bloginfo('name');
	$user_display_name = $user->display_name;
	$user_email = $user->user_email;

	$subject = sprintf('[%s] Password Changed Successfully', $site_name);

	$message = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f9fafb; padding: 20px;">';
	$message .= '<div style="background: white; border-radius: 12px; padding: 40px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
	$message .= '<div style="text-align: center; margin-bottom: 30px;">';
	$message .= '<h1 style="color: #10b981; margin: 0; font-size: 28px;">✓ Password Changed</h1>';
	$message .= '</div>';

	$message .= '<p style="font-size: 16px; color: #1f2937; line-height: 1.6;">Hello <strong>' . esc_html($user_display_name) . '</strong>,</p>';
	$message .= '<p style="font-size: 16px; color: #1f2937; line-height: 1.6;">This email confirms that your password was successfully changed on <strong>' . esc_html($site_name) . '</strong>.</p>';

	$message .= '<div style="background: #d1fae5; border-left: 4px solid #10b981; padding: 15px; border-radius: 6px; margin: 25px 0;">';
	$message .= '<p style="margin: 0; font-size: 14px; color: #065f46;"><strong>✓ Success:</strong> Your password has been updated. You can now log in with your new password.</p>';
	$message .= '</div>';

	$message .= '<div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 6px; margin: 25px 0;">';
	$message .= '<p style="margin: 0; font-size: 14px; color: #92400e;"><strong>⚠️ Security Alert:</strong> If you didn\'t make this change, please contact our support team immediately and secure your account.</p>';
	$message .= '</div>';

	$message .= '<div style="text-align: center; margin: 30px 0;">';
	$message .= '<a href="' . home_url('/login/') . '" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px;">Log In Now</a>';
	$message .= '</div>';

	$message .= '<div style="border-top: 1px solid #e5e7eb; margin-top: 40px; padding-top: 20px; text-align: center;">';
	$message .= '<p style="font-size: 12px; color: #9ca3af; margin: 0;">This is an automated email from ' . esc_html($site_name) . '</p>';
	$message .= '</div>';

	$message .= '</div>';
	$message .= '</div>';

	$smtp_username = get_option('event_rsvp_smtp_username', '');
	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		'Reply-To: ' . $smtp_username
	);

	wp_mail($user_email, $subject, $message, $headers);
}
add_action('after_password_reset', 'event_rsvp_send_password_change_email', 10, 1);
add_action('password_reset', 'event_rsvp_send_password_change_email', 10, 1);

/**
 * Get SMTP configuration presets for common email providers
 *
 * @param string $provider Provider name (hostinger, gmail, outlook, yahoo)
 * @return array|false Configuration array or false if provider not found
 */
function event_rsvp_get_smtp_preset($provider) {
	$presets = array(
		'hostinger' => array(
			'name' => 'Hostinger',
			'smtp_host' => 'smtp.hostinger.com',
			'smtp_port' => 465,
			'smtp_secure' => 'ssl',
			'notes' => 'Use your full email address as username. FROM email must match username.'
		),
		'gmail' => array(
			'name' => 'Gmail',
			'smtp_host' => 'smtp.gmail.com',
			'smtp_port' => 587,
			'smtp_secure' => 'tls',
			'notes' => 'Use App Password (not regular password). FROM email must match Gmail address.'
		),
		'outlook' => array(
			'name' => 'Outlook/Office 365',
			'smtp_host' => 'smtp.office365.com',
			'smtp_port' => 587,
			'smtp_secure' => 'tls',
			'notes' => 'FROM email must match Outlook address.'
		),
		'yahoo' => array(
			'name' => 'Yahoo Mail',
			'smtp_host' => 'smtp.mail.yahoo.com',
			'smtp_port' => 587,
			'smtp_secure' => 'tls',
			'notes' => 'Use App Password. FROM email must match Yahoo address.'
		)
	);

	$provider = strtolower($provider);
	return isset($presets[$provider]) ? $presets[$provider] : false;
}

/**
 * Apply SMTP preset configuration
 *
 * @param string $provider Provider name
 * @return bool True if preset applied, false otherwise
 */
function event_rsvp_apply_smtp_preset($provider) {
	$preset = event_rsvp_get_smtp_preset($provider);

	if (!$preset) {
		return false;
	}

	update_option('event_rsvp_smtp_host', $preset['smtp_host']);
	update_option('event_rsvp_smtp_port', $preset['smtp_port']);
	update_option('event_rsvp_smtp_secure', $preset['smtp_secure']);

	error_log('Event RSVP: Applied ' . $preset['name'] . ' SMTP preset');

	return true;
}

function event_rsvp_schedule_qr_email($attendee_id, $event_id) {
	$event_date = get_post_meta($event_id, 'event_date', true);
	$qr_schedule_days = get_post_meta($event_id, 'qr_schedule_days', true);

	if (empty($qr_schedule_days)) {
		$qr_schedule_days = 7;
	}

	if (!empty($event_date)) {
		$event_timestamp = strtotime($event_date);
		$send_timestamp = $event_timestamp - ($qr_schedule_days * DAY_IN_SECONDS);

		if ($send_timestamp > time()) {
			wp_schedule_single_event($send_timestamp, 'event_rsvp_send_qr_email', array($attendee_id));
		} else {
			event_rsvp_send_qr_email_now($attendee_id);
		}
	} else {
		event_rsvp_send_qr_email_now($attendee_id);
	}
}

function event_rsvp_handle_cf7_submission($contact_form) {
	$submission = WPCF7_Submission::get_instance();

	if (!$submission) {
		return;
	}

	$posted_data = $submission->get_posted_data();

	if (isset($posted_data['event-rsvp']) && $posted_data['event-rsvp'] === '1') {
		$attendee_name = sanitize_text_field($posted_data['attendee-name'] ?? '');
		$attendee_email = sanitize_email($posted_data['attendee-email'] ?? '');
		$attendee_phone = sanitize_text_field($posted_data['attendee-phone'] ?? '');
		$rsvp_status = sanitize_text_field($posted_data['rsvp-status'] ?? 'yes');
		$event_id = intval($posted_data['event-id'] ?? 0);

		if (empty($attendee_name) || empty($attendee_email)) {
			return;
		}

		$attendee_id = wp_insert_post(array(
			'post_type' => 'attendee',
			'post_title' => $attendee_name,
			'post_status' => 'publish',
		));

		if (!is_wp_error($attendee_id)) {
			update_post_meta($attendee_id, 'attendee_email', $attendee_email);
			update_post_meta($attendee_id, 'attendee_phone', $attendee_phone);
			update_post_meta($attendee_id, 'rsvp_status', $rsvp_status);
			update_post_meta($attendee_id, 'linked_event', $event_id);
			update_post_meta($attendee_id, 'checkin_status', false);

			$qr_data = base64_encode(json_encode(array(
				'attendee_id' => $attendee_id,
				'event_id' => $event_id,
				'email' => $attendee_email,
				'verification' => wp_hash($attendee_id . $event_id . $attendee_email)
			)));

			update_post_meta($attendee_id, 'qr_data', $qr_data);

			event_rsvp_schedule_qr_email($attendee_id, $event_id);
		}
	}
}
add_action('wpcf7_before_send_mail', 'event_rsvp_handle_cf7_submission');

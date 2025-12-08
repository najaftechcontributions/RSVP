<?php
/**
 * Admin Functions
 *
 * @package EventRSVPPlugin
 */

if (!defined('ABSPATH')) {
	exit;
}

function event_rsvp_capture_test_mail_error($wp_error) {
	if (is_wp_error($wp_error)) {
		set_transient('event_rsvp_test_email_error', $wp_error->get_error_message(), 60);
	}
}

function event_rsvp_add_admin_menu() {
	add_menu_page(
		'Event RSVP Settings',
		'Event RSVP',
		'manage_options',
		'event-rsvp-settings',
		'event_rsvp_settings_page',
		'dashicons-tickets-alt',
		30
	);

	add_submenu_page(
		'event-rsvp-settings',
		'Email Settings',
		'Email Settings',
		'manage_options',
		'event-rsvp-email-settings',
		'event_rsvp_email_settings_page'
	);
}
add_action('admin_menu', 'event_rsvp_add_admin_menu');

function event_rsvp_settings_page() {
	?>
	<div class="wrap">
		<h1>Event RSVP Settings</h1>
		<p>Configure your Event RSVP system settings.</p>
		<div class="card">
			<h2>Quick Links</h2>
			<ul>
				<li><a href="<?php echo admin_url('admin.php?page=event-rsvp-email-settings'); ?>">Email Settings (SMTP Configuration)</a></li>
				<li><a href="<?php echo admin_url('edit.php?post_type=event'); ?>">Manage Events</a></li>
				<li><a href="<?php echo admin_url('edit.php?post_type=attendee'); ?>">Manage Attendees</a></li>
				<li><a href="<?php echo admin_url('edit.php?post_type=vendor_ad'); ?>">Manage Ads</a></li>
			</ul>
		</div>
	</div>
	<?php
}

function event_rsvp_email_settings_page() {
	if (isset($_POST['event_rsvp_save_email_settings'])) {
		check_admin_referer('event_rsvp_email_settings');

		$smtp_host = sanitize_text_field($_POST['smtp_host'] ?? 'smtp.gmail.com');
		$smtp_username = sanitize_email($_POST['smtp_username'] ?? '');
		$smtp_from_email = sanitize_email($_POST['smtp_from_email'] ?? '');

		// Auto-correct FROM email for known providers
		$known_providers = array('hostinger.com', 'gmail.com', 'outlook.com', 'yahoo.com', 'office365.com', 'mail.yahoo.com');
		$requires_match = false;
		$matched_provider = '';
		foreach ($known_providers as $provider) {
			if (strpos($smtp_host, $provider) !== false) {
				$requires_match = true;
				$matched_provider = $provider;
				break;
			}
		}

		// FORCE FROM email to match username for known providers - NO EXCEPTIONS
		if ($requires_match && !empty($smtp_username)) {
			if ($smtp_from_email !== $smtp_username) {
				$old_from = $smtp_from_email;
				$smtp_from_email = $smtp_username;
				echo '<div class="notice notice-warning"><p><strong>⚠️ IMPORTANT:</strong> FROM Email has been automatically changed from <code>' . esc_html($old_from) . '</code> to <code>' . esc_html($smtp_username) . '</code> because ' . esc_html($matched_provider) . ' requires them to match. This prevents "Sender address rejected" errors.</p></div>';
			}
		}

		// If FROM email is empty, default to SMTP username
		if (empty($smtp_from_email) && !empty($smtp_username)) {
			$smtp_from_email = $smtp_username;
		}

		update_option('event_rsvp_smtp_enabled', isset($_POST['smtp_enabled']) ? 1 : 0);
		update_option('event_rsvp_smtp_host', $smtp_host);
		update_option('event_rsvp_smtp_port', intval($_POST['smtp_port'] ?? 587));
		update_option('event_rsvp_smtp_username', $smtp_username);
		update_option('event_rsvp_smtp_password', $_POST['smtp_password'] ?? '');
		update_option('event_rsvp_smtp_from_email', $smtp_from_email);
		update_option('event_rsvp_smtp_from_name', sanitize_text_field($_POST['smtp_from_name'] ?? 'Event RSVP'));
		update_option('event_rsvp_smtp_secure', sanitize_text_field($_POST['smtp_secure'] ?? 'tls'));

		echo '<div class="notice notice-success"><p><strong>✓ Email settings saved successfully!</strong></p></div>';

		// Show confirmation of what was saved
		if ($requires_match) {
			echo '<div class="notice notice-info"><p><strong>Configuration Summary:</strong><br>';
			echo '• SMTP Username: <code>' . esc_html($smtp_username) . '</code><br>';
			echo '• FROM Email: <code>' . esc_html($smtp_from_email) . '</code> (automatically matched)<br>';
			echo '• This prevents "Sender address rejected" errors with ' . esc_html($matched_provider) . '</p></div>';
		}
	}

	if (isset($_POST['event_rsvp_test_email'])) {
		check_admin_referer('event_rsvp_test_email');

		$test_email = sanitize_email($_POST['test_email_address'] ?? '');

		if (!empty($test_email) && is_email($test_email)) {
			$subject = 'Test Email from Event RSVP System';
			$current_time = current_time('mysql');
			$smtp_host = get_option('event_rsvp_smtp_host', '');
			$smtp_port = get_option('event_rsvp_smtp_port', '');
			$smtp_from_email = get_option('event_rsvp_smtp_from_email', '');
			$smtp_from_name = get_option('event_rsvp_smtp_from_name', 'Event RSVP');

			$message = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">';
			$message .= '<h1 style="color: #503AA8; border-bottom: 2px solid #503AA8; padding-bottom: 10px;">✓ SMTP Test Successful!</h1>';
			$message .= '<p style="font-size: 16px; line-height: 1.6;">This is a test email from your Event RSVP system.</p>';
			$message .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">';
			$message .= '<p style="margin: 5px 0;"><strong>Status:</strong> <span style="color: #28a745;">Email sent successfully!</span></p>';
			$message .= '<p style="margin: 5px 0;"><strong>Sent at:</strong> ' . esc_html($current_time) . '</p>';
			$message .= '<p style="margin: 5px 0;"><strong>SMTP Host:</strong> ' . esc_html($smtp_host) . '</p>';
			$message .= '<p style="margin: 5px 0;"><strong>SMTP Port:</strong> ' . esc_html($smtp_port) . '</p>';
			$message .= '<p style="margin: 5px 0;"><strong>From Email:</strong> ' . esc_html($smtp_from_email) . '</p>';
			$message .= '</div>';
			$message .= '<p style="color: #666; font-size: 14px;">If you received this email, your SMTP configuration is working correctly and you can now send emails from your Event RSVP system.</p>';
			$message .= '</div>';

			// Don't set FROM header - let wp_mail_from filter handle it automatically
			// This prevents conflicts and ensures SMTP username is always used for known providers
			$smtp_username = get_option('event_rsvp_smtp_username', '');

			$headers = array(
				'Content-Type: text/html; charset=UTF-8',
				'Reply-To: ' . $smtp_username
			);

			add_action('wp_mail_failed', 'event_rsvp_capture_test_mail_error', 10, 1);
			$result = wp_mail($test_email, $subject, $message, $headers);
			remove_action('wp_mail_failed', 'event_rsvp_capture_test_mail_error', 10);

			if ($result) {
				echo '<div class="notice notice-success"><p><strong>✓ Success!</strong> Test email sent successfully to <strong>' . esc_html($test_email) . '</strong>. Please check your inbox (and spam folder).</p></div>';
			} else {
				$error_msg = get_transient('event_rsvp_test_email_error');
				delete_transient('event_rsvp_test_email_error');

				echo '<div class="notice notice-error">';
				echo '<p><strong>✗ Error:</strong> Failed to send test email. Please check the following:</p>';
				echo '<ul style="margin-left: 20px;">';
				echo '<li>Verify your SMTP host, port, and encryption settings are correct</li>';
				echo '<li>Ensure your username and password are correct</li>';
				echo '<li>Check that "Enable SMTP" is checked</li>';
				echo '<li>Make sure your email provider allows SMTP connections</li>';
				if ($error_msg) {
					echo '<li><strong>Error details:</strong> ' . esc_html($error_msg) . '</li>';
				}
				echo '</ul>';
				echo '</div>';
			}
		} else {
			echo '<div class="notice notice-error"><p>Please enter a valid email address for testing.</p></div>';
		}
	}

	$smtp_enabled = get_option('event_rsvp_smtp_enabled', false);
	$smtp_host = get_option('event_rsvp_smtp_host', 'smtp.gmail.com');
	$smtp_port = get_option('event_rsvp_smtp_port', 587);
	$smtp_username = get_option('event_rsvp_smtp_username', '');
	$smtp_password = get_option('event_rsvp_smtp_password', '');
	$smtp_from_email = get_option('event_rsvp_smtp_from_email', '');
	$smtp_from_name = get_option('event_rsvp_smtp_from_name', 'Event RSVP');
	$smtp_secure = get_option('event_rsvp_smtp_secure', 'tls');
	?>
	<div class="wrap">
		<h1>Email Settings (SMTP Configuration)</h1>

		<?php
		$smtp_configured = !empty($smtp_host) && (!empty($smtp_username) || !empty($smtp_password));
		if ($smtp_enabled && $smtp_configured) : ?>
			<div class="notice notice-info" style="display: flex; align-items: center; padding: 12px 15px;">
				<span style="font-size: 20px; margin-right: 10px;">✓</span>
				<div>
					<p style="margin: 0;"><strong>SMTP is configured and enabled</strong></p>
					<p style="margin: 5px 0 0 0; font-size: 13px;">
						Using <strong><?php echo esc_html($smtp_host); ?></strong> on port <strong><?php echo esc_html($smtp_port); ?></strong> with <strong><?php echo esc_html($smtp_secure ?: 'no'); ?></strong> encryption
					</p>
				</div>
			</div>
		<?php elseif ($smtp_enabled && !$smtp_configured) : ?>
			<div class="notice notice-warning" style="display: flex; align-items: center; padding: 12px 15px;">
				<span style="font-size: 20px; margin-right: 10px;">⚠</span>
				<div>
					<p style="margin: 0;"><strong>SMTP is enabled but not fully configured</strong></p>
					<p style="margin: 5px 0 0 0; font-size: 13px;">Please complete the configuration below and save your settings.</p>
				</div>
			</div>
		<?php else : ?>
			<div class="notice notice-warning" style="display: flex; align-items: center; padding: 12px 15px;">
				<span style="font-size: 20px; margin-right: 10px;">ℹ</span>
				<div>
					<p style="margin: 0;"><strong>SMTP is not enabled</strong></p>
					<p style="margin: 5px 0 0 0; font-size: 13px;">WordPress will use the default mail function. For better email delivery, enable SMTP below.</p>
				</div>
			</div>
		<?php endif; ?>

		<div class="card" style="max-width: 900px; margin-top: 20px;">
			<h2>SMTP Setup Instructions</h2>
			<p>Configure your SMTP settings below. Choose your email provider and enter the required information:</p>

			<details style="margin: 15px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #2271b1;">
				<summary style="cursor: pointer; font-weight: 600; margin-bottom: 10px;">Gmail SMTP Settings</summary>
				<ol style="margin: 10px 0;">
					<li>Go to your <a href="https://myaccount.google.com/security" target="_blank">Google Account Security settings</a></li>
					<li>Enable 2-Step Verification if not already enabled</li>
					<li>Go to <a href="https://myaccount.google.com/apppasswords" target="_blank">App passwords</a></li>
					<li>Create a new App password for "Mail"</li>
					<li>Copy the 16-character password and paste it in the "SMTP Password" field below</li>
				</ol>
				<p><strong>Settings:</strong></p>
				<ul>
					<li><strong>SMTP Host:</strong> smtp.gmail.com</li>
					<li><strong>SMTP Port:</strong> 587</li>
					<li><strong>Encryption:</strong> TLS</li>
					<li><strong>Username:</strong> Your full Gmail address (e.g., yourname@gmail.com)</li>
				</ul>
			</details>

			<details style="margin: 15px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #2271b1;">
				<summary style="cursor: pointer; font-weight: 600; margin-bottom: 10px;">Hostinger SMTP Settings</summary>
				<p><strong>Settings:</strong></p>
				<ul>
					<li><strong>SMTP Host:</strong> smtp.hostinger.com</li>
					<li><strong>SMTP Port:</strong> 465 (SSL) or 587 (TLS)</li>
					<li><strong>Encryption:</strong> SSL (port 465) or TLS (port 587)</li>
					<li><strong>Username:</strong> Your full email address</li>
					<li><strong>Password:</strong> Your email account password</li>
				</ul>
			</details>

			<details style="margin: 15px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #2271b1;">
				<summary style="cursor: pointer; font-weight: 600; margin-bottom: 10px;">Other Common SMTP Providers</summary>
				<ul style="margin: 10px 0;">
					<li><strong>Outlook/Office365:</strong> smtp.office365.com | Port: 587 | TLS</li>
					<li><strong>Yahoo:</strong> smtp.mail.yahoo.com | Port: 465 | SSL</li>
					<li><strong>SendGrid:</strong> smtp.sendgrid.net | Port: 587 | TLS</li>
					<li><strong>Mailgun:</strong> smtp.mailgun.org | Port: 587 | TLS</li>
					<li><strong>Amazon SES:</strong> Check AWS SES console for your region's SMTP endpoint</li>
				</ul>
				<p><em>Note: Contact your email provider for specific SMTP settings if not listed above.</em></p>
			</details>
		</div>

		<form method="post" action="">
			<?php wp_nonce_field('event_rsvp_email_settings'); ?>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="smtp_enabled">Enable SMTP</label>
					</th>
					<td>
						<input type="checkbox" id="smtp_enabled" name="smtp_enabled" value="1" <?php checked($smtp_enabled, 1); ?>>
						<p class="description">Enable custom SMTP configuration for sending emails</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="smtp_host">SMTP Host</label>
					</th>
					<td>
						<input type="text" id="smtp_host" name="smtp_host" value="<?php echo esc_attr($smtp_host); ?>" class="regular-text" placeholder="smtp.example.com">
						<p class="description">Your email provider's SMTP server address (e.g., smtp.gmail.com, smtp.hostinger.com)</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="smtp_port">SMTP Port</label>
					</th>
					<td>
						<input type="number" id="smtp_port" name="smtp_port" value="<?php echo esc_attr($smtp_port); ?>" class="small-text">
						<p class="description">Common ports: 587 (TLS), 465 (SSL), or 25 (No encryption)</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="smtp_secure">Encryption</label>
					</th>
					<td>
						<select id="smtp_secure" name="smtp_secure">
							<option value="tls" <?php selected($smtp_secure, 'tls'); ?>>TLS</option>
							<option value="ssl" <?php selected($smtp_secure, 'ssl'); ?>>SSL</option>
							<option value="" <?php selected($smtp_secure, ''); ?>>None</option>
						</select>
						<p class="description">TLS (port 587) or SSL (port 465) recommended. Use "None" only if your provider doesn't support encryption</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="smtp_username">SMTP Username</label>
					</th>
					<td>
						<input type="text" id="smtp_username" name="smtp_username" value="<?php echo esc_attr($smtp_username); ?>" class="regular-text">
						<p class="description">Usually your full email address (e.g., yourname@example.com). Leave empty if authentication not required</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="smtp_password">SMTP Password</label>
					</th>
					<td>
						<input type="password" id="smtp_password" name="smtp_password" value="<?php echo esc_attr($smtp_password); ?>" class="regular-text" autocomplete="new-password">
						<p class="description">Your email password or app-specific password. For Gmail, use App Password (16-character code). Leave empty if authentication not required</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="smtp_from_email">From Email</label>
					</th>
					<td>
						<input type="email" id="smtp_from_email" name="smtp_from_email" value="<?php echo esc_attr($smtp_from_email); ?>" class="regular-text" placeholder="noreply@example.com">
						<p class="description"><strong>⚠️ Important:</strong> For most providers (Gmail, Hostinger, Outlook), this MUST match your SMTP username. <em>The system will automatically enforce this for known providers when you save.</em></p>
						<?php if (!empty($smtp_username) && !empty($smtp_from_email) && $smtp_username !== $smtp_from_email) : ?>
							<div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px; margin-top: 10px;">
								<strong>⚠️ Warning:</strong> Your From Email (<?php echo esc_html($smtp_from_email); ?>) doesn't match your SMTP Username (<?php echo esc_html($smtp_username); ?>). This may cause email sending to fail with some providers.
							</div>
						<?php endif; ?>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="smtp_from_name">From Name</label>
					</th>
					<td>
						<input type="text" id="smtp_from_name" name="smtp_from_name" value="<?php echo esc_attr($smtp_from_name); ?>" class="regular-text">
						<p class="description">The name that appears in the "From" field of emails</p>
					</td>
				</tr>
			</table>

			<p class="submit">
				<input type="submit" name="event_rsvp_save_email_settings" class="button button-primary" value="Save Email Settings">
			</p>
		</form>

		<hr>

		<h2>Test Email Configuration</h2>
		<form method="post" action="">
			<?php wp_nonce_field('event_rsvp_test_email'); ?>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="test_email_address">Send Test Email To:</label>
					</th>
					<td>
						<input type="email" id="test_email_address" name="test_email_address" class="regular-text" placeholder="test@example.com" required>
						<p class="description">Send a test email to verify your SMTP configuration is working correctly</p>
					</td>
				</tr>
			</table>

			<p class="submit">
				<input type="submit" name="event_rsvp_test_email" class="button button-secondary" value="Send Test Email">
			</p>
		</form>

		<hr>

		<div class="card" style="max-width: 900px; margin-top: 20px;">
			<h2>Troubleshooting Tips</h2>
			<div style="line-height: 1.8;">
				<p><strong>If you're having trouble sending emails:</strong></p>
				<ol style="margin-left: 20px;">
					<li><strong>Check your credentials:</strong> Make sure your SMTP host, port, username, and password are correct</li>
					<li><strong>Verify encryption settings:</strong> Most modern SMTP servers require TLS (port 587) or SSL (port 465)</li>
					<li><strong>Check firewall/hosting restrictions:</strong> Some hosting providers block certain ports. Contact your host if needed</li>
					<li><strong>Use App Passwords:</strong> For Gmail, Outlook, and Yahoo, you must use app-specific passwords instead of your regular password</li>
					<li><strong>Enable less secure apps (if applicable):</strong> Some providers require you to enable "less secure app access" in account settings</li>
					<li><strong>Check error logs:</strong> If WP_DEBUG is enabled, check your WordPress debug log for detailed error messages</li>
					<li><strong>Test with different settings:</strong> Try switching between TLS and SSL, or different ports to see what works</li>
				</ol>

				<p style="margin-top: 20px;"><strong>Common Issues:</strong></p>
				<ul style="margin-left: 20px;">
					<li><strong>Authentication failed:</strong> Double-check your username and password. Use app-specific passwords for Gmail/Outlook</li>
					<li><strong>Sender address rejected / not owned by user:</strong> Make sure your "From Email" matches your "SMTP Username". Most email providers (Hostinger, Gmail, Outlook) reject emails when the FROM address doesn't match the authenticated account</li>
					<li><strong>Connection timeout:</strong> Your hosting provider may be blocking the SMTP port. Try a different port or contact your host</li>
					<li><strong>SSL certificate errors:</strong> The SSL options in the configuration help bypass certificate issues with self-signed certificates</li>
					<li><strong>Emails not arriving:</strong> Check spam/junk folders. Some providers have strict spam filters</li>
				</ul>

				<p style="margin-top: 20px; padding: 15px; background: #f0f6fc; border-left: 4px solid #2271b1;">
					<strong>💡 Tip:</strong> If you're still having issues, consider using a dedicated email service like SendGrid, Mailgun, or Amazon SES for better deliverability.
				</p>
			</div>
		</div>
	</div>

	<script>
	jQuery(document).ready(function($) {
		var $smtpHost = $('#smtp_host');
		var $smtpUsername = $('#smtp_username');
		var $fromEmail = $('#smtp_from_email');

		// Known providers that require matching
		var knownProviders = ['hostinger.com', 'gmail.com', 'outlook.com', 'yahoo.com', 'office365.com', 'mail.yahoo.com'];

		// Check if current host is a known provider
		function isKnownProvider() {
			var host = $smtpHost.val().toLowerCase();
			for (var i = 0; i < knownProviders.length; i++) {
				if (host.indexOf(knownProviders[i]) !== -1) {
					return true;
				}
			}
			return false;
		}

		// Auto-sync FROM email with SMTP username for known providers
		function autoSyncFromEmail() {
			var username = $smtpUsername.val().trim();

			if (isKnownProvider() && username) {
				// Automatically set FROM email to match username
				$fromEmail.val(username);
				$fromEmail.prop('readonly', true);
				$fromEmail.css('background-color', '#f0f0f0');

				// Remove any existing notice
				$('.email-match-notice').remove();

				// Add locked notice
				var notice = $('<div class="email-match-notice" style="background: #d1ecf1; border-left: 4px solid #0c5460; padding: 10px; margin-top: 10px;">' +
					'<strong>🔒 Locked:</strong> FROM Email is automatically set to match SMTP Username for ' + $smtpHost.val() + '. This prevents "Sender address rejected" errors.' +
					'</div>');
				$fromEmail.closest('td').append(notice);
			} else {
				// Unlock for non-known providers
				$fromEmail.prop('readonly', false);
				$fromEmail.css('background-color', '');
				checkEmailMatch();
			}
		}

		// Show warning if emails don't match for known providers
		function checkEmailMatch() {
			var username = $smtpUsername.val().trim();
			var fromEmail = $fromEmail.val().trim();

			// Remove any existing notice
			$('.email-match-notice').remove();

			if (!isKnownProvider() && username && fromEmail && username !== fromEmail) {
				var notice = $('<div class="email-match-notice" style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px; margin-top: 10px;">' +
					'<strong>⚠️ Recommendation:</strong> Your FROM Email should match your SMTP Username for best deliverability. ' +
					'<a href="#" class="sync-from-email" style="text-decoration: underline;">Click here to sync them</a>' +
					'</div>');
				$fromEmail.closest('td').append(notice);
			}
		}

		// Sync button handler
		$(document).on('click', '.sync-from-email', function(e) {
			e.preventDefault();
			$fromEmail.val($smtpUsername.val());
			checkEmailMatch();
		});

		// Auto-sync when username or host changes
		$smtpUsername.on('input blur', autoSyncFromEmail);
		$smtpHost.on('input blur', autoSyncFromEmail);
		$fromEmail.on('blur', function() {
			if (!isKnownProvider()) {
				checkEmailMatch();
			}
		});

		// Initial check on page load
		setTimeout(autoSyncFromEmail, 300);
	});
	</script>
	<?php
}

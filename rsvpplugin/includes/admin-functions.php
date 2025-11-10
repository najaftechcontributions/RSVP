<?php
/**
 * Admin Functions
 *
 * @package EventRSVPPlugin
 */

if (!defined('ABSPATH')) {
	exit;
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

		update_option('event_rsvp_smtp_enabled', isset($_POST['smtp_enabled']) ? 1 : 0);
		update_option('event_rsvp_smtp_host', sanitize_text_field($_POST['smtp_host'] ?? 'smtp.gmail.com'));
		update_option('event_rsvp_smtp_port', intval($_POST['smtp_port'] ?? 587));
		update_option('event_rsvp_smtp_username', sanitize_email($_POST['smtp_username'] ?? ''));
		update_option('event_rsvp_smtp_password', $_POST['smtp_password'] ?? '');
		update_option('event_rsvp_smtp_from_email', sanitize_email($_POST['smtp_from_email'] ?? ''));
		update_option('event_rsvp_smtp_from_name', sanitize_text_field($_POST['smtp_from_name'] ?? 'Event RSVP'));
		update_option('event_rsvp_smtp_secure', sanitize_text_field($_POST['smtp_secure'] ?? 'tls'));

		echo '<div class="notice notice-success"><p>Email settings saved successfully!</p></div>';
	}

	if (isset($_POST['event_rsvp_test_email'])) {
		check_admin_referer('event_rsvp_test_email');

		$test_email = sanitize_email($_POST['test_email_address'] ?? '');

		if (!empty($test_email)) {
			$subject = 'Test Email from Event RSVP System';
			$message = '<h1>Test Email</h1><p>This is a test email from your Event RSVP system. If you received this, your email configuration is working correctly!</p>';
			$headers = array('Content-Type: text/html; charset=UTF-8');

			$result = wp_mail($test_email, $subject, $message, $headers);

			if ($result) {
				echo '<div class="notice notice-success"><p>Test email sent successfully to ' . esc_html($test_email) . '!</p></div>';
			} else {
				echo '<div class="notice notice-error"><p>Failed to send test email. Please check your settings and try again.</p></div>';
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

		<div class="card" style="max-width: 800px; margin-top: 20px;">
			<h2>Gmail SMTP Setup Instructions</h2>
			<ol>
				<li>Go to your <a href="https://myaccount.google.com/security" target="_blank">Google Account Security settings</a></li>
				<li>Enable 2-Step Verification if not already enabled</li>
				<li>Go to <a href="https://myaccount.google.com/apppasswords" target="_blank">App passwords</a></li>
				<li>Create a new App password for "Mail"</li>
				<li>Copy the 16-character password and paste it in the "SMTP Password" field below</li>
				<li>Use your full Gmail address (e.g., yourname@gmail.com) as the SMTP Username</li>
			</ol>
			<p><strong>Note:</strong> For Gmail, use these settings:</p>
			<ul>
				<li><strong>SMTP Host:</strong> smtp.gmail.com</li>
				<li><strong>SMTP Port:</strong> 587</li>
				<li><strong>Encryption:</strong> TLS</li>
			</ul>
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
						<input type="text" id="smtp_host" name="smtp_host" value="<?php echo esc_attr($smtp_host); ?>" class="regular-text">
						<p class="description">For Gmail: smtp.gmail.com</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="smtp_port">SMTP Port</label>
					</th>
					<td>
						<input type="number" id="smtp_port" name="smtp_port" value="<?php echo esc_attr($smtp_port); ?>" class="small-text">
						<p class="description">For Gmail: 587 (TLS) or 465 (SSL)</p>
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
						<p class="description">For Gmail: Use TLS with port 587</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="smtp_username">SMTP Username</label>
					</th>
					<td>
						<input type="email" id="smtp_username" name="smtp_username" value="<?php echo esc_attr($smtp_username); ?>" class="regular-text">
						<p class="description">Your Gmail address (e.g., yourname@gmail.com)</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="smtp_password">SMTP Password</label>
					</th>
					<td>
						<input type="password" id="smtp_password" name="smtp_password" value="<?php echo esc_attr($smtp_password); ?>" class="regular-text">
						<p class="description">For Gmail: Use App Password (16-character code)</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="smtp_from_email">From Email</label>
					</th>
					<td>
						<input type="email" id="smtp_from_email" name="smtp_from_email" value="<?php echo esc_attr($smtp_from_email); ?>" class="regular-text">
						<p class="description">Email address to send from (should match SMTP username for Gmail)</p>
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
					</td>
				</tr>
			</table>

			<p class="submit">
				<input type="submit" name="event_rsvp_test_email" class="button button-secondary" value="Send Test Email">
			</p>
		</form>
	</div>
	<?php
}

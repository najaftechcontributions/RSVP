<?php
/**
 * SMTP FROM Email Configuration Fix & Diagnostic Tool
 * 
 * This script fixes the "Sender address rejected: not owned by user" error
 * by ensuring the FROM email ALWAYS matches the SMTP username.
 * 
 * INSTRUCTIONS:
 * 1. Upload this file to your WordPress root directory
 * 2. Visit: https://yourdomain.com/fix-smtp-from-email.php
 * 3. Delete this file after running
 */

// Load WordPress
require_once('wp-load.php');

if (!current_user_can('manage_options')) {
	die('Access denied. You must be logged in as an administrator.');
}

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>SMTP FROM Email Fix</title>
	<style>
		body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f0f0f0; margin: 0; padding: 20px; }
		.container { max-width: 900px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 30px; }
		h1 { color: #333; margin-top: 0; border-bottom: 3px solid #503AA8; padding-bottom: 15px; }
		h2 { color: #503AA8; margin-top: 30px; }
		.status { padding: 15px; border-radius: 6px; margin: 15px 0; border-left: 4px solid; }
		.success { background: #d4edda; border-color: #28a745; color: #155724; }
		.error { background: #f8d7da; border-color: #dc3545; color: #721c24; }
		.warning { background: #fff3cd; border-color: #ffc107; color: #856404; }
		.info { background: #d1ecf1; border-color: #0c5460; color: #0c5460; }
		table { width: 100%; border-collapse: collapse; margin: 20px 0; }
		th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
		th { background: #f8f9fa; font-weight: 600; }
		code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
		.btn { display: inline-block; padding: 12px 24px; background: #503AA8; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 10px 5px 10px 0; }
		.btn:hover { background: #6B52C3; }
		ul { line-height: 1.8; }
		.icon { font-size: 24px; margin-right: 10px; }
	</style>
</head>
<body>
	<div class="container">
		<h1>🔧 SMTP FROM Email Configuration Fix</h1>

		<?php
		// Get current settings
		$smtp_enabled = get_option('event_rsvp_smtp_enabled', false);
		$smtp_host = get_option('event_rsvp_smtp_host', '');
		$smtp_port = get_option('event_rsvp_smtp_port', 587);
		$smtp_username = get_option('event_rsvp_smtp_username', '');
		$smtp_password = get_option('event_rsvp_smtp_password', '');
		$smtp_from_email = get_option('event_rsvp_smtp_from_email', '');
		$smtp_from_name = get_option('event_rsvp_smtp_from_name', 'Event RSVP');
		$smtp_secure = get_option('event_rsvp_smtp_secure', 'tls');
		$admin_email = get_option('admin_email', '');

		echo '<h2>📊 Current Configuration</h2>';
		echo '<table>';
		echo '<tr><th>Setting</th><th>Value</th></tr>';
		echo '<tr><td><strong>SMTP Enabled</strong></td><td>' . ($smtp_enabled ? '✓ Yes' : '✗ No') . '</td></tr>';
		echo '<tr><td><strong>SMTP Host</strong></td><td><code>' . esc_html($smtp_host) . '</code></td></tr>';
		echo '<tr><td><strong>SMTP Port</strong></td><td><code>' . esc_html($smtp_port) . '</code></td></tr>';
		echo '<tr><td><strong>Encryption</strong></td><td><code>' . esc_html($smtp_secure ?: 'none') . '</code></td></tr>';
		echo '<tr><td><strong>SMTP Username</strong></td><td><code>' . esc_html($smtp_username) . '</code></td></tr>';
		echo '<tr><td><strong>FROM Email</strong></td><td><code>' . esc_html($smtp_from_email) . '</code></td></tr>';
		echo '<tr><td><strong>FROM Name</strong></td><td>' . esc_html($smtp_from_name) . '</td></tr>';
		echo '<tr><td><strong>WordPress Admin Email</strong></td><td><code>' . esc_html($admin_email) . '</code></td></tr>';
		echo '</table>';

		// Check if this is a known provider that requires matching
		$requires_match = false;
		$matched_provider = '';
		$known_providers = array(
			'hostinger.com' => 'Hostinger',
			'gmail.com' => 'Gmail',
			'outlook.com' => 'Outlook',
			'office365.com' => 'Office 365',
			'yahoo.com' => 'Yahoo',
			'mail.yahoo.com' => 'Yahoo Mail'
		);
		
		foreach ($known_providers as $domain => $name) {
			if (strpos($smtp_host, $domain) !== false) {
				$requires_match = true;
				$matched_provider = $name;
				break;
			}
		}

		echo '<h2>🔍 Diagnostic Results</h2>';

		$issues_found = false;
		$fixes_applied = false;

		// Check 1: SMTP Enabled
		if (!$smtp_enabled) {
			echo '<div class="status warning"><span class="icon">⚠️</span><strong>Warning:</strong> SMTP is not enabled. Emails will use WordPress default mail function.</div>';
			$issues_found = true;
		}

		// Check 2: Provider detection
		if ($requires_match) {
			echo '<div class="status info"><span class="icon">ℹ️</span><strong>Provider Detected:</strong> ' . esc_html($matched_provider) . ' - This provider REQUIRES FROM email to match SMTP username.</div>';
		}

		// Check 3: FROM email vs SMTP username
		if (!empty($smtp_username) && !empty($smtp_from_email)) {
			if ($smtp_username !== $smtp_from_email) {
				if ($requires_match) {
					echo '<div class="status error"><span class="icon">✗</span><strong>CRITICAL ERROR:</strong> FROM Email (<code>' . esc_html($smtp_from_email) . '</code>) does not match SMTP Username (<code>' . esc_html($smtp_username) . '</code>)<br>';
					echo 'This WILL cause "Sender address rejected" errors with ' . esc_html($matched_provider) . '!</div>';
					
					// Apply fix
					update_option('event_rsvp_smtp_from_email', $smtp_username);
					$smtp_from_email = $smtp_username;
					$fixes_applied = true;
					
					echo '<div class="status success"><span class="icon">✓</span><strong>FIXED:</strong> FROM Email has been updated to <code>' . esc_html($smtp_username) . '</code></div>';
				} else {
					echo '<div class="status warning"><span class="icon">⚠️</span><strong>Warning:</strong> FROM Email (<code>' . esc_html($smtp_from_email) . '</code>) does not match SMTP Username (<code>' . esc_html($smtp_username) . '</code>)<br>';
					echo 'This may cause issues with some email providers.</div>';
					$issues_found = true;
				}
			} else {
				echo '<div class="status success"><span class="icon">✓</span><strong>Perfect:</strong> FROM Email matches SMTP Username (<code>' . esc_html($smtp_username) . '</code>)</div>';
			}
		}

		// Check 4: Admin email in FROM
		if ($smtp_from_email === $admin_email && !empty($admin_email) && !empty($smtp_username)) {
			echo '<div class="status error"><span class="icon">✗</span><strong>CRITICAL ERROR:</strong> FROM Email is using WordPress admin email (<code>' . esc_html($admin_email) . '</code>)<br>';
			echo 'This is the #1 cause of "Sender address rejected" errors!</div>';
			
			// Apply fix
			update_option('event_rsvp_smtp_from_email', $smtp_username);
			$smtp_from_email = $smtp_username;
			$fixes_applied = true;
			
			echo '<div class="status success"><span class="icon">✓</span><strong>FIXED:</strong> FROM Email changed from admin email to SMTP Username (<code>' . esc_html($smtp_username) . '</code>)</div>';
		}

		// Check 5: Empty FROM email
		if (empty($smtp_from_email) && !empty($smtp_username)) {
			echo '<div class="status warning"><span class="icon">⚠️</span><strong>Warning:</strong> FROM Email is empty. Setting to SMTP Username.</div>';
			
			update_option('event_rsvp_smtp_from_email', $smtp_username);
			$smtp_from_email = $smtp_username;
			$fixes_applied = true;
			
			echo '<div class="status success"><span class="icon">✓</span><strong>FIXED:</strong> FROM Email set to <code>' . esc_html($smtp_username) . '</code></div>';
		}

		// Check 6: Port and encryption compatibility
		$port = intval($smtp_port);
		if ($port === 465 && $smtp_secure !== 'ssl') {
			echo '<div class="status warning"><span class="icon">⚠️</span><strong>Warning:</strong> Port 465 should use SSL encryption (currently: ' . esc_html($smtp_secure ?: 'none') . ')</div>';
			$issues_found = true;
		} elseif ($port === 587 && $smtp_secure !== 'tls') {
			echo '<div class="status warning"><span class="icon">⚠️</span><strong>Warning:</strong> Port 587 should use TLS encryption (currently: ' . esc_html($smtp_secure ?: 'none') . ')</div>';
			$issues_found = true;
		}

		// Final status
		echo '<h2>📋 Summary</h2>';
		
		if ($fixes_applied) {
			echo '<div class="status success">';
			echo '<span class="icon">✓</span><strong>Fixes Applied Successfully!</strong><br>';
			echo 'Your SMTP configuration has been corrected. The FROM email now matches your SMTP username.<br>';
			echo '<strong>Updated FROM Email:</strong> <code>' . esc_html($smtp_from_email) . '</code>';
			echo '</div>';
		}

		if (!$fixes_applied && !$issues_found) {
			echo '<div class="status success">';
			echo '<span class="icon">✓</span><strong>Configuration Looks Good!</strong><br>';
			echo 'No issues detected. Your SMTP FROM email is properly configured.';
			echo '</div>';
		}

		if ($issues_found && !$fixes_applied) {
			echo '<div class="status info">';
			echo '<span class="icon">ℹ️</span><strong>Review Recommended</strong><br>';
			echo 'Some potential issues were detected. Please review the warnings above and update your settings accordingly.';
			echo '</div>';
		}
		?>

		<h2>📝 Recommendations</h2>
		<ul>
			<li><strong>Always use SMTP Username as FROM Email</strong> for Hostinger, Gmail, Outlook, and Yahoo</li>
			<li><strong>Never use WordPress admin email</strong> as the FROM address when using SMTP</li>
			<li><strong>Enable SMTP</strong> for better email deliverability and tracking</li>
			<li><strong>Test your configuration</strong> after making any changes</li>
			<li><strong>Check port and encryption compatibility:</strong>
				<ul>
					<li>Port 465 → SSL encryption</li>
					<li>Port 587 → TLS encryption</li>
					<li>Port 25 → No encryption (not recommended)</li>
				</ul>
			</li>
		</ul>

		<h2>🔧 Next Steps</h2>
		<ol>
			<li>Go to <a href="<?php echo admin_url('admin.php?page=event-rsvp-email-settings'); ?>" class="btn">Email Settings</a></li>
			<li>Send a test email to verify everything works</li>
			<li><strong>Delete this file (fix-smtp-from-email.php)</strong> from your server for security</li>
		</ol>

		<div style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 6px; text-align: center;">
			<p style="margin: 0; color: #666; font-size: 14px;">
				<strong>⚠️ SECURITY NOTICE:</strong> Please delete this file after use to prevent unauthorized access.
			</p>
		</div>
	</div>
</body>
</html>

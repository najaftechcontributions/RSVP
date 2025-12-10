<?php
/**
 * Manual Email Campaign Database Initialization
 * 
 * Visit this file directly in your browser to initialize the email campaign database tables.
 * Example: https://yoursite.com/wp-content/themes/yourtheme/rsvpplugin/init-email-db.php
 * 
 * @package EventRSVPPlugin
 */

// Load WordPress
require_once('../../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
	wp_die('Unauthorized access. You must be an administrator to run this script.');
}

// Include the database setup file
require_once(dirname(__FILE__) . '/includes/email-invitation-db.php');

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Email Campaign Database Initialization</title>
	<style>
		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}
		body {
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			min-height: 100vh;
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 20px;
		}
		.container {
			background: white;
			border-radius: 16px;
			padding: 40px;
			max-width: 600px;
			width: 100%;
			box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
		}
		h1 {
			color: #2d3748;
			margin-bottom: 20px;
			font-size: 28px;
		}
		.status {
			padding: 20px;
			border-radius: 8px;
			margin: 20px 0;
		}
		.status.success {
			background: #c6f6d5;
			color: #2f855a;
			border: 2px solid #9ae6b4;
		}
		.status.error {
			background: #fff5f5;
			color: #c53030;
			border: 2px solid #feb2b2;
		}
		.status.info {
			background: #bee3f8;
			color: #2c5282;
			border: 2px solid #90cdf4;
		}
		.btn {
			display: inline-block;
			padding: 14px 28px;
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			color: white;
			text-decoration: none;
			border-radius: 8px;
			font-weight: 600;
			margin-top: 20px;
			transition: transform 0.2s;
		}
		.btn:hover {
			transform: translateY(-2px);
		}
		.table-list {
			list-style: none;
			padding: 20px 0;
		}
		.table-list li {
			padding: 10px;
			background: #f7fafc;
			margin: 8px 0;
			border-radius: 6px;
			border-left: 4px solid #667eea;
		}
		.back-link {
			display: inline-block;
			margin-top: 20px;
			color: #667eea;
			text-decoration: none;
			font-weight: 600;
		}
		.back-link:hover {
			text-decoration: underline;
		}
	</style>
</head>
<body>
	<div class="container">
		<h1>📧 Email Campaign Database Setup</h1>
		
		<?php
		global $wpdb;
		
		// Check if tables exist
		$campaigns_table = $wpdb->prefix . 'event_email_campaigns';
		$recipients_table = $wpdb->prefix . 'event_email_recipients';
		$templates_table = $wpdb->prefix . 'event_email_templates';
		$tracking_table = $wpdb->prefix . 'event_email_tracking';
		
		$tables_exist = array(
			'campaigns' => $wpdb->get_var("SHOW TABLES LIKE '{$campaigns_table}'") === $campaigns_table,
			'recipients' => $wpdb->get_var("SHOW TABLES LIKE '{$recipients_table}'") === $recipients_table,
			'templates' => $wpdb->get_var("SHOW TABLES LIKE '{$templates_table}'") === $templates_table,
			'tracking' => $wpdb->get_var("SHOW TABLES LIKE '{$tracking_table}'") === $tracking_table,
		);
		
		$all_exist = !in_array(false, $tables_exist, true);
		
		if (isset($_GET['action']) && $_GET['action'] === 'create') {
			echo '<div class="status info">🔄 Creating database tables...</div>';
			
			// Run the table creation
			event_rsvp_create_email_invitation_tables();
			
			// Check again
			$tables_exist = array(
				'campaigns' => $wpdb->get_var("SHOW TABLES LIKE '{$campaigns_table}'") === $campaigns_table,
				'recipients' => $wpdb->get_var("SHOW TABLES LIKE '{$recipients_table}'") === $recipients_table,
				'templates' => $wpdb->get_var("SHOW TABLES LIKE '{$templates_table}'") === $templates_table,
				'tracking' => $wpdb->get_var("SHOW TABLES LIKE '{$tracking_table}'") === $tracking_table,
			);
			
			$all_exist = !in_array(false, $tables_exist, true);
			
			if ($all_exist) {
				echo '<div class="status success">✅ <strong>Success!</strong> All email campaign database tables have been created successfully.</div>';
				
				// Count templates
				$template_count = $wpdb->get_var("SELECT COUNT(*) FROM {$templates_table}");
				
				echo '<div class="status info">📝 <strong>' . $template_count . '</strong> default email templates have been installed.</div>';
			} else {
				echo '<div class="status error">❌ <strong>Error:</strong> Some tables could not be created. Please check your database permissions.</div>';
			}
		}
		
		if ($all_exist) {
			echo '<div class="status success">✅ <strong>Database Ready!</strong> All email campaign tables are installed.</div>';
			
			echo '<h3 style="margin-top: 30px; color: #2d3748;">Installed Tables:</h3>';
			echo '<ul class="table-list">';
			echo '<li>✓ ' . $campaigns_table . ' (Email Campaigns)</li>';
			echo '<li>✓ ' . $recipients_table . ' (Campaign Recipients)</li>';
			echo '<li>✓ ' . $templates_table . ' (Email Templates)</li>';
			echo '<li>✓ ' . $tracking_table . ' (Email Tracking)</li>';
			echo '</ul>';
			
			// Show template count
			$template_count = $wpdb->get_var("SELECT COUNT(*) FROM {$templates_table}");
			echo '<p style="margin-top: 20px; color: #555;">You have <strong>' . $template_count . '</strong> email template(s) available.</p>';
			
			echo '<a href="' . home_url('/email-campaigns/') . '" class="btn">Go to Email Campaigns</a>';
		} else {
			echo '<div class="status error">⚠️ <strong>Database Not Ready!</strong> Some tables are missing.</div>';
			
			echo '<h3 style="margin-top: 30px; color: #2d3748;">Table Status:</h3>';
			echo '<ul class="table-list">';
			echo '<li>' . ($tables_exist['campaigns'] ? '✓' : '✗') . ' ' . $campaigns_table . '</li>';
			echo '<li>' . ($tables_exist['recipients'] ? '✓' : '✗') . ' ' . $recipients_table . '</li>';
			echo '<li>' . ($tables_exist['templates'] ? '✓' : '✗') . ' ' . $templates_table . '</li>';
			echo '<li>' . ($tables_exist['tracking'] ? '✓' : '✗') . ' ' . $tracking_table . '</li>';
			echo '</ul>';
			
			echo '<a href="?action=create" class="btn">Create Database Tables</a>';
		}
		?>
		
		<a href="<?php echo admin_url(); ?>" class="back-link">← Back to Dashboard</a>
	</div>
</body>
</html>

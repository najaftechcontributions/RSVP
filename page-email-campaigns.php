<?php

/**
 * Template Name: Email Campaigns
 *
 * @package RSVP
 */

if (!is_user_logged_in()) {
	wp_redirect(add_query_arg('redirect_to', get_permalink(), home_url('/login/')));
	exit;
}

$current_user = wp_get_current_user();
$allowed_roles = array('event_host', 'pro', 'administrator');
$has_access = false;

foreach ($allowed_roles as $role) {
	if (in_array($role, $current_user->roles)) {
		$has_access = true;
		break;
	}
}

if (!$has_access) {
	wp_die('You do not have permission to access this page. This page is for event hosts only.');
}

get_header();

// Ensure required functions are loaded
$plugin_dir = get_template_directory() . '/rsvpplugin/includes/';

if (!function_exists('event_rsvp_create_email_invitation_tables')) {
	require_once $plugin_dir . 'email-invitation-db.php';
}

if (!function_exists('event_rsvp_get_campaigns_by_host')) {
	require_once $plugin_dir . 'email-invitation-functions.php';
}

if (!function_exists('event_rsvp_get_user_events')) {
	require_once $plugin_dir . 'helper-functions.php';
}

// Check if tables exist, if not create them
global $wpdb;
$campaigns_table = $wpdb->prefix . 'event_email_campaigns';
$templates_table = $wpdb->prefix . 'event_email_templates';
$campaigns_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$campaigns_table}'") === $campaigns_table;
$templates_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$templates_table}'") === $templates_table;

if (!$campaigns_table_exists || !$templates_table_exists) {
	event_rsvp_create_email_invitation_tables();
	error_log('Email campaign tables created');
}

// Verify templates exist
$template_count = $wpdb->get_var("SELECT COUNT(*) FROM $templates_table");
if ($template_count == 0) {
	event_rsvp_insert_default_email_templates();
	error_log('Default email templates inserted');
}

$user_id = get_current_user_id();
$campaigns = event_rsvp_get_campaigns_by_host($user_id);
?>

<main class="email-campaigns-page">
	<div class="container">

		<div style="height:40px" aria-hidden="true"></div>

		<?php
		$smtp_enabled = get_option('event_rsvp_smtp_enabled', false);
		$smtp_username = get_option('event_rsvp_smtp_username', '');
		$smtp_password = get_option('event_rsvp_smtp_password', '');
		$smtp_host = get_option('event_rsvp_smtp_host', '');
		$smtp_configured = !empty($smtp_username) && !empty($smtp_password) && !empty($smtp_host);

		if (!$smtp_enabled || !$smtp_configured) : ?>
			<!-- <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 15px 20px; margin-bottom: 20px;">
				<p style="margin: 0; color: #856404;">
					<strong>⚠️ SMTP Not Configured:</strong>
					To send email campaigns, please configure your SMTP settings in
					<a href="<?php echo admin_url('admin.php?page=event-rsvp-email-settings'); ?>" style="color: #856404; text-decoration: underline;">
						Email Settings
					</a>
				</p>
				<div style="margin-top: 10px; font-size: 13px; color: #856404;">
					Status: SMTP Enabled: <?php echo $smtp_enabled ? '✓ Yes' : '✗ No'; ?> |
					Host: <?php echo !empty($smtp_host) ? '✓ Configured' : '✗ Not set'; ?> |
					Username: <?php echo !empty($smtp_username) ? '✓ Set' : '✗ Not set'; ?> |
					Password: <?php echo !empty($smtp_password) ? '✓ Set' : '✗ Not set'; ?>
				</div>
			</div> -->
		<?php elseif ($smtp_enabled && $smtp_configured) : ?>
			<!-- ix -->
		<?php endif; ?>

		<div class="page-header">
			<div class="header-content">
				<div class="header-text">
					<h1 class="page-title">Email Campaigns</h1>
					<p class="page-subtitle">Send event invitations and track responses</p>
				</div>
				<div class="header-actions">
					<button class="create-campaign-btn primary-button">
						+ Create Campaign
					</button>
				</div>
			</div>
		</div>

		<div style="height:40px" aria-hidden="true"></div>

		<?php if (!empty($campaigns)) : ?>
			<div class="campaigns-grid">
				<?php foreach ($campaigns as $campaign) :
					$event = get_post($campaign->event_id);
					$stats = event_rsvp_get_campaign_stats($campaign->id);

					// Initialize stats if empty
					if (empty($stats)) {
						$stats = (object) array(
							'total' => 0,
							'sent' => 0,
							'clicked' => 0,
							'yes_responses' => 0,
							'no_responses' => 0,
							'click_rate' => 0,
							'yes_rate' => 0,
							'pending' => 0
						);
					}
				?>
					<article class="campaign-card" data-campaign-id="<?php echo $campaign->id; ?>">
						<div class="campaign-header">
							<div class="campaign-info">
								<h3 class="campaign-name"><?php echo esc_html($campaign->campaign_name); ?></h3>
								<p class="campaign-event">Event: <?php echo esc_html(get_the_title($campaign->event_id)); ?></p>
							</div>
							<div class="campaign-status">
								<span class="status-badge status-<?php echo esc_attr($campaign->status); ?>">
									<?php echo esc_html(ucfirst($campaign->status)); ?>
								</span>
							</div>
						</div>

						<div class="campaign-stats-grid">
							<div class="stat-box">
								<div class="stat-number"><?php echo $stats->total; ?></div>
								<div class="stat-label">Recipients</div>
							</div>
							<div class="stat-box">
								<div class="stat-number"><?php echo $stats->sent; ?></div>
								<div class="stat-label">Sent</div>
							</div>
							<div class="stat-box">
								<div class="stat-number"><?php echo $stats->clicked; ?></div>
								<div class="stat-label">Clicked</div>
							</div>
							<div class="stat-box">
								<div class="stat-number"><?php echo $stats->yes_responses; ?></div>
								<div class="stat-label">Yes</div>
							</div>
						</div>

						<div class="campaign-metrics">
							<div class="metric-bar">
								<div class="metric-label">Click Rate</div>
								<div class="metric-bar-track">
									<div class="metric-bar-fill" style="width: <?php echo $stats->click_rate; ?>%"></div>
								</div>
								<div class="metric-value"><?php echo $stats->click_rate; ?>%</div>
							</div>
							<div class="metric-bar">
								<div class="metric-label">Yes Rate</div>
								<div class="metric-bar-track">
									<div class="metric-bar-fill" style="width: <?php echo $stats->yes_rate; ?>%"></div>
								</div>
								<div class="metric-value"><?php echo $stats->yes_rate; ?>%</div>
							</div>
						</div>

						<div class="campaign-actions">
							<button class="action-btn view-campaign-btn" data-campaign-id="<?php echo $campaign->id; ?>">
								📊 View Details
							</button>
							<?php if ($campaign->status === 'draft') : ?>
								<button class="action-btn manage-campaign-btn" data-campaign-id="<?php echo $campaign->id; ?>">
									✏️ Manage
								</button>
							<?php endif; ?>
							<button class="action-btn delete-campaign-btn" data-campaign-id="<?php echo $campaign->id; ?>">
							🗑️ Delete
							</button>
						</div>

						<?php if ($campaign->sent_time) : ?>
							<div class="campaign-footer">
								Sent on <?php echo date('M j, Y g:i A', strtotime($campaign->sent_time)); ?>
							</div>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<div class="no-campaigns-state">
				<div class="no-campaigns-icon">📧</div>
				<h3 class="no-campaigns-title">No Email Campaigns Yet</h3>
				<p class="no-campaigns-text">Create your first email campaign to invite guests to your events!</p>
				<button class="create-campaign-btn primary-button">
					+ Create Campaign
				</button>
			</div>
		<?php endif; ?>

		<div style="height:60px" aria-hidden="true"></div>

	</div>
</main>

<div id="createCampaignModal" class="modal-overlay" style="display: none;">
	<div class="modal-container">
		<div class="modal-header">
			<h2 class="modal-title">Create Email Campaign</h2>
			<button class="modal-close" aria-label="Close">&times;</button>
		</div>
		<div class="modal-body">
			<form id="createCampaignForm">
				<div class="form-group">
					<label for="campaignName">Campaign Name</label>
					<input type="text" id="campaignName" name="campaign_name" class="form-input" placeholder="Summer Event Invitations" required>
				</div>

				<div class="form-group">
					<label for="campaignEvent">Select Event</label>
					<select id="campaignEvent" name="event_id" class="form-input" required>
						<option value="">Choose an event...</option>
						<?php
						$user_events = event_rsvp_get_user_events($user_id);
						foreach ($user_events as $event) {
							echo '<option value="' . $event->ID . '">' . esc_html(get_the_title($event->ID)) . '</option>';
						}
						?>
					</select>
				</div>

				<div class="form-group">
					<label for="campaignSubject">Email Subject</label>
					<input type="text" id="campaignSubject" name="subject" class="form-input" placeholder="You're Invited: {{event_name}}" required>
					<small class="form-help">Use {{event_name}}, {{event_date}}, {{host_name}} as placeholders</small>
				</div>

				<div class="form-group">
					<label for="campaignTemplate">Email Template</label>
					<select id="campaignTemplate" name="template_id" class="form-input">
						<option value="">Loading templates...</option>
						<?php
						// Load templates server-side as fallback
						$available_templates = event_rsvp_get_email_templates();
						if (!empty($available_templates)) {
							echo '<option value="0">Use Default HTML Template</option>';
							foreach ($available_templates as $tmpl) {
								echo '<option value="' . $tmpl->id . '">' . esc_html($tmpl->name) . '</option>';
							}
						}
						?>
					</select>
					<button type="button" id="previewTemplateBtn" class="secondary-button" style="margin-top: 10px;">
						👁️ Preview Template
					</button>
				</div>

				<div class="form-actions">
					<button type="button" class="secondary-button modal-close">Cancel</button>
					<button type="submit" class="primary-button">Create Campaign</button>
				</div>
			</form>
		</div>
	</div>
</div>

<div id="manageCampaignModal" class="modal-overlay" style="display: none;">
	<div class="modal-container modal-large">
		<div class="modal-header">
			<h2 class="modal-title">Manage Campaign</h2>
			<button class="modal-close" aria-label="Close">&times;</button>
		</div>
		<div class="modal-body">
			<div class="campaign-tabs">
				<button class="tab-btn active" data-tab="recipients">Recipients</button>
				<button class="tab-btn" data-tab="preview">Preview</button>
			</div>

			<div class="tab-content" id="recipientsTab">
				<div class="recipients-actions">
					<button class="primary-button" id="addManualRecipientsBtn">
						✏️ Add Emails Manually
					</button>
					<button class="secondary-button" id="uploadCsvBtn">
						📁 Upload CSV
					</button>
					<input type="file" id="csvFileInput" accept=".csv" style="display: none;">
				</div>

				<div id="manualRecipientsForm" class="manual-recipients-section" style="display: none;">
					<h4>Add Recipients</h4>
					<p class="form-help" style="margin-bottom: 10px;">Enter emails separated by spaces, commas, or new lines. You can optionally include names.</p>
					<textarea id="manualEmailsList" class="form-input" rows="8" placeholder="john@example.com jane@example.com bob@company.com&#10;or&#10;jane@example.com, Jane Smith&#10;bob@company.com Bob Johnson"></textarea>
					<small class="form-help">✓ Formats: space-separated, one per line, or email@example.com, Name</small>
					<div class="form-actions">
						<button type="button" id="cancelManualRecipientsBtn" class="secondary-button">Cancel</button>
						<button type="button" id="saveManualRecipientsBtn" class="primary-button">Add Recipients</button>
					</div>
				</div>

				<div id="recipientsList" class="recipients-list"></div>

				<div class="campaign-send-section">
					<div class="test-email-section">
						<input type="email" id="testEmailAddress" class="form-input" placeholder="your@email.com" style="width: 300px;">
						<button id="sendTestEmailBtn" class="secondary-button">Send Test Email</button>
					</div>
					<button id="sendCampaignBtn" class="primary-button large-button">
						🚀 Send Campaign
					</button>
				</div>
			</div>

			<div class="tab-content" id="previewTab" style="display: none;">
				<div id="emailPreviewContainer" class="email-preview-container">
					<p>Select a template to preview...</p>
				</div>
			</div>
		</div>
	</div>
</div>

<div id="campaignStatsModal" class="modal-overlay" style="display: none;">
	<div class="modal-container modal-large">
		<div class="modal-header">
			<h2 class="modal-title">Campaign Details</h2>
			<button class="modal-close" aria-label="Close">&times;</button>
		</div>
		<div class="modal-body">
			<div id="campaignStatsContent">
				<div class="loading-spinner">Loading...</div>
			</div>
		</div>
	</div>
</div>

<div id="templatePreviewModal" class="modal-overlay" style="display: none;">
	<div class="modal-container modal-large">
		<div class="modal-header">
			<h2 class="modal-title">Email Template Preview</h2>
			<button class="modal-close" aria-label="Close">&times;</button>
		</div>
		<div class="modal-body">
			<div id="templatePreviewContent" class="email-preview-container">
				<div class="loading-spinner">Loading...</div>
			</div>
		</div>
	</div>
</div>

<script>
	jQuery(document).ready(function($) {
		let currentCampaignId = null;
		let templates = [];

		function loadTemplates() {
			$.ajax({
				url: eventRsvpData.ajax_url,
				type: 'POST',
				data: {
					action: 'event_rsvp_get_email_templates',
					nonce: eventRsvpData.email_campaign_nonce
				},
				success: function(response) {
					console.log('Templates AJAX response:', response);

					if (response.success && response.data.templates) {
						templates = response.data.templates;
						const select = $('#campaignTemplate');

						// Only update if we got templates from AJAX
						if (templates.length > 0) {
							console.log('✓ Loaded ' + templates.length + ' templates via AJAX');
							select.empty();
							select.append($('<option>', {
								value: '0',
								text: 'Use Default HTML Template'
							}));
							templates.forEach(function(template) {
								select.append($('<option>', {
									value: template.id,
									text: template.name + (template.description ? ' - ' + template.description : '')
								}));
							});
						} else {
							console.warn('⚠ No templates returned from AJAX, using server-side templates');
						}
					} else {
						console.error('✗ Failed to load templates via AJAX:', response);
						// Keep server-side loaded templates as fallback
					}
				},
				error: function(xhr, status, error) {
					console.error('✗ AJAX error loading templates:', status, error, xhr.responseText);
					// Keep server-side loaded templates as fallback - don't modify select
					console.log('Using server-side loaded templates as fallback');
				}
			});
		}

		loadTemplates();

		$('.create-campaign-btn').click(function() {
			$('#createCampaignModal').fadeIn();
		});

		$('.modal-close').click(function() {
			$(this).closest('.modal-overlay').fadeOut();
		});

		$('.modal-overlay').click(function(e) {
			if ($(e.target).hasClass('modal-overlay')) {
				$(this).fadeOut();
			}
		});

		$('#createCampaignForm').submit(function(e) {
			e.preventDefault();

			const formData = new FormData(this);
			formData.append('action', 'event_rsvp_create_email_campaign');
			formData.append('nonce', eventRsvpData.email_campaign_nonce);

			$.ajax({
				url: eventRsvpData.ajax_url,
				type: 'POST',
				data: Object.fromEntries(formData),
				success: function(response) {
					if (response.success) {
						alert('Campaign created successfully!');
						location.reload();
					} else {
						alert('Error: ' + response.data);
					}
				}
			});
		});

		$('.manage-campaign-btn').click(function() {
			currentCampaignId = $(this).data('campaign-id');

			if (!currentCampaignId) {
				alert('✗ Invalid campaign ID');
				return;
			}

			$('#manageCampaignModal').fadeIn();
			$('#manualRecipientsForm').hide();
			$('#manualEmailsList').val('');
			loadCampaignRecipients(currentCampaignId);
		});

		$('#addManualRecipientsBtn').click(function() {
			$('#manualRecipientsForm').slideDown();
		});

		$('#cancelManualRecipientsBtn').click(function() {
			$('#manualRecipientsForm').slideUp();
			$('#manualEmailsList').val('');
		});

		$('#saveManualRecipientsBtn').click(function() {
			const emails = $('#manualEmailsList').val();

			if (!emails.trim()) {
				alert('✗ Please enter at least one email address');
				return;
			}

			// Basic client-side validation
			const lines = emails.split('\n');
			let hasValidEmail = false;
			const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

			for (let line of lines) {
				line = line.trim();
				if (!line) continue;

				// Extract email part (before comma if present)
				let emailPart = line.split(',')[0].trim();

				if (emailRegex.test(emailPart)) {
					hasValidEmail = true;
					break;
				}
			}

			if (!hasValidEmail) {
				alert('✗ No valid email addresses found. Please check the format:\nemail@example.com\nor\nemail@example.com, Name');
				return;
			}

			const button = $(this);
			button.prop('disabled', true).text('Adding...');

			$.ajax({
				url: eventRsvpData.ajax_url,
				type: 'POST',
				data: {
					action: 'event_rsvp_add_manual_recipients',
					nonce: eventRsvpData.email_campaign_nonce,
					campaign_id: currentCampaignId,
					emails: emails
				},
				success: function(response) {
					if (response.success) {
						alert('✓ ' + response.data.message);
						$('#manualRecipientsForm').slideUp();
						$('#manualEmailsList').val('');
						loadCampaignRecipients(currentCampaignId);
					} else {
						alert('✗ Error: ' + response.data);
					}
				},
				error: function(xhr, status, error) {
					alert('✗ Failed to add recipients. Please try again or contact support.');
					console.error('AJAX Error:', status, error, xhr.responseText);
				},
				complete: function() {
					button.prop('disabled', false).text('Add Recipients');
				}
			});
		});

		$('#uploadCsvBtn').click(function() {
			$('#csvFileInput').click();
		});

		$('#csvFileInput').change(function() {
			const file = this.files[0];
			if (!file) return;

			const formData = new FormData();
			formData.append('csv_file', file);
			formData.append('action', 'event_rsvp_upload_csv_recipients');
			formData.append('nonce', eventRsvpData.email_campaign_nonce);
			formData.append('campaign_id', currentCampaignId);

			$.ajax({
				url: eventRsvpData.ajax_url,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				success: function(response) {
					if (response.success) {
						alert(response.data.message);
						loadCampaignRecipients(currentCampaignId);
					} else {
						alert('Error: ' + response.data);
					}
				}
			});

			$(this).val('');
		});

		function loadCampaignRecipients(campaignId) {
			$.ajax({
				url: eventRsvpData.ajax_url,
				type: 'POST',
				data: {
					action: 'event_rsvp_get_campaign_recipients',
					nonce: eventRsvpData.email_campaign_nonce,
					campaign_id: campaignId
				},
				success: function(response) {
					if (response.success) {
						const recipients = response.data.recipients;
						const list = $('#recipientsList');
						list.empty();

						if (recipients.length === 0) {
							list.html('<p class="no-recipients">No recipients added yet. Add emails or upload a CSV file.</p>');
							return;
						}

						list.append('<h4>Recipients (' + recipients.length + ')</h4>');
						const table = $('<table class="recipients-table"><thead><tr><th>Email</th><th>Name</th><th>Status</th><th>Response</th></tr></thead><tbody></tbody></table>');

						recipients.forEach(function(recipient) {
							const row = $('<tr>');
							row.append($('<td>').text(recipient.email));
							row.append($('<td>').text(recipient.name || '-'));
							row.append($('<td>').html('<span class="status-badge status-' + recipient.sent_status + '">' + recipient.sent_status + '</span>'));
							row.append($('<td>').text(recipient.response || '-'));
							table.find('tbody').append(row);
						});

						list.append(table);
					}
				}
			});
		}

		$('#sendTestEmailBtn').click(function() {
			const email = $('#testEmailAddress').val();

			if (!email || !isValidEmail(email)) {
				alert('Please enter a valid email address');
				return;
			}

			$.ajax({
				url: eventRsvpData.ajax_url,
				type: 'POST',
				data: {
					action: 'event_rsvp_send_test_email',
					nonce: eventRsvpData.email_campaign_nonce,
					campaign_id: currentCampaignId,
					test_email: email
				},
				success: function(response) {
					if (response.success) {
						alert('Test email sent!');
					} else {
						alert('Error: ' + response.data);
					}
				}
			});
		});

		$('#sendCampaignBtn').click(function() {
			if (!confirm('Are you sure you want to send this campaign? This cannot be undone.')) {
				return;
			}

			$.ajax({
				url: eventRsvpData.ajax_url,
				type: 'POST',
				data: {
					action: 'event_rsvp_send_campaign',
					nonce: eventRsvpData.email_campaign_nonce,
					campaign_id: currentCampaignId
				},
				success: function(response) {
					if (response.success) {
						alert(response.data.message);
						location.reload();
					} else {
						alert('Error: ' + response.data);
					}
				}
			});
		});

		$('.view-campaign-btn').click(function() {
			const campaignId = $(this).data('campaign-id');
			loadCampaignStats(campaignId);
			$('#campaignStatsModal').fadeIn();
		});

		function loadCampaignStats(campaignId) {
			$('#campaignStatsContent').html('<div class="loading-spinner">Loading campaign details...</div>');

			$.ajax({
				url: eventRsvpData.ajax_url,
				type: 'POST',
				data: {
					action: 'event_rsvp_get_campaign_stats',
					nonce: eventRsvpData.email_campaign_nonce,
					campaign_id: campaignId
				},
				success: function(response) {
					if (response.success) {
						const stats = response.data.stats;
						const campaign = response.data.campaign;
						const recipients = response.data.recipients || [];

						let html = '<div class="campaign-stats-header">';
						html += '<h3>' + campaign.campaign_name + '</h3>';
						html += '<p class="campaign-meta">Event: ' + (response.data.event_name || 'Unknown') + '</p>';
						if (campaign.sent_time) {
							html += '<p class="campaign-meta">Sent: ' + campaign.sent_time + '</p>';
						}
						html += '</div>';

						html += '<div class="stats-grid">';
						html += '<div class="stat-card"><div class="stat-number">' + stats.total + '</div><div class="stat-label">Total Recipients</div></div>';
						html += '<div class="stat-card"><div class="stat-number">' + stats.sent + '</div><div class="stat-label">Emails Sent</div></div>';
						html += '<div class="stat-card"><div class="stat-number">' + stats.pending + '</div><div class="stat-label">Pending</div></div>';
						html += '<div class="stat-card"><div class="stat-number">' + stats.failed + '</div><div class="stat-label">Failed</div></div>';
						html += '<div class="stat-card"><div class="stat-number">' + stats.clicked + '</div><div class="stat-label">Clicked</div></div>';
						html += '<div class="stat-card"><div class="stat-number">' + stats.yes_responses + '</div><div class="stat-label">Yes Responses</div></div>';
						html += '<div class="stat-card"><div class="stat-number">' + stats.no_responses + '</div><div class="stat-label">No Responses</div></div>';
						html += '<div class="stat-card"><div class="stat-number">' + stats.click_rate + '%</div><div class="stat-label">Click Rate</div></div>';
						html += '</div>';

						if (recipients.length > 0) {
							html += '<div class="campaign-recipients-section">';
							html += '<h4>Email Status Details (' + recipients.length + ' recipients)</h4>';
							html += '<div class="recipients-table-container">';
							html += '<table class="recipients-table">';
							html += '<thead><tr><th>Email</th><th>Name</th><th>Status</th><th>Sent Time</th><th>Clicked</th><th>Response</th></tr></thead>';
							html += '<tbody>';

							recipients.forEach(function(r) {
								const statusClass = r.sent_status === 'sent' ? 'status-sent' : (r.sent_status === 'failed' ? 'status-failed' : 'status-pending');
								html += '<tr>';
								html += '<td>' + (r.email || 'N/A') + '</td>';
								html += '<td>' + (r.name || '-') + '</td>';
								html += '<td><span class="status-badge ' + statusClass + '">' + (r.sent_status || 'pending') + '</span></td>';
								html += '<td>' + (r.sent_time ? new Date(r.sent_time).toLocaleString() : '-') + '</td>';
								html += '<td>' + (r.clicked_status == 1 ? '✓ Yes' : '-') + '</td>';
								html += '<td>' + (r.response ? '<strong>' + r.response.toUpperCase() + '</strong>' : '-') + '</td>';
								html += '</tr>';
							});

							html += '</tbody></table>';
							html += '</div></div>';
						}

						$('#campaignStatsContent').html(html);
					} else {
						$('#campaignStatsContent').html('<div class="error-message">Failed to load campaign details: ' + (response.data || 'Unknown error') + '</div>');
					}
				},
				error: function() {
					$('#campaignStatsContent').html('<div class="error-message">Failed to load campaign details. Please try again.</div>');
				}
			});
		}

		$('.delete-campaign-btn').click(function() {
			if (!confirm('Are you sure you want to delete this campaign? This cannot be undone.')) {
				return;
			}

			const campaignId = $(this).data('campaign-id');

			$.ajax({
				url: eventRsvpData.ajax_url,
				type: 'POST',
				data: {
					action: 'event_rsvp_delete_campaign',
					nonce: eventRsvpData.email_campaign_nonce,
					campaign_id: campaignId
				},
				success: function(response) {
					if (response.success) {
						alert('Campaign deleted!');
						location.reload();
					} else {
						alert('Error: ' + response.data);
					}
				}
			});
		});

		$('#previewTemplateBtn').click(function() {
			const templateId = $('#campaignTemplate').val();
			const eventId = $('#campaignEvent').val();

			if (!templateId || templateId == '0') {
				alert('Please select a template first');
				return;
			}

			$.ajax({
				url: eventRsvpData.ajax_url,
				type: 'POST',
				data: {
					action: 'event_rsvp_preview_email_template',
					nonce: eventRsvpData.email_campaign_nonce,
					template_id: templateId,
					event_id: eventId || 0
				},
				success: function(response) {
					if (response.success) {
						$('#templatePreviewContent').html(response.data.html);
						$('#templatePreviewModal').fadeIn();
					}
				}
			});
		});

		$('.tab-btn').click(function() {
			$('.tab-btn').removeClass('active');
			$(this).addClass('active');

			$('.tab-content').hide();
			$('#' + $(this).data('tab') + 'Tab').show();
		});

		function isValidEmail(email) {
			return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
		}
	});
</script>

<?php get_footer(); ?>

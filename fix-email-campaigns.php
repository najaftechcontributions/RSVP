<?php
/**
 * Temporary Script to Fix Email Campaigns Feature
 * Run this once, then delete it.
 */

// Fix 1: Update the add_campaign_recipients function
$functions_file = __DIR__ . '/rsvpplugin/includes/email-invitation-functions.php';
$functions_content = file_get_contents($functions_file);

// Replace the function
$old_function = 'function event_rsvp_add_campaign_recipients($campaign_id, $recipients) {
	global $wpdb;
	$table = $wpdb->prefix . \'event_email_recipients\';
	
	$added = 0;
	
	foreach ($recipients as $recipient) {
		if (!is_email($recipient[\'email\'])) {
			continue;
		}
		
		$tracking_token = wp_generate_password(32, false, false);
		
		$wpdb->insert(
			$table,
			array(
				\'campaign_id\' => $campaign_id,
				\'email\' => sanitize_email($recipient[\'email\']),
				\'name\' => isset($recipient[\'name\']) ? sanitize_text_field($recipient[\'name\']) : \'\',
				\'tracking_token\' => $tracking_token,
				\'sent_status\' => \'pending\'
			),
			array(\'%d\', \'%s\', \'%s\', \'%s\', \'%s\')
		);
		
		if ($wpdb->insert_id) {
			$added++;
		}
	}
	
	$wpdb->update(
		$wpdb->prefix . \'event_email_campaigns\',
		array(\'total_recipients\' => $added),
		array(\'id\' => $campaign_id),
		array(\'%d\'),
		array(\'%d\')
	);
	
	return $added;
}';

$new_function = 'function event_rsvp_add_campaign_recipients($campaign_id, $recipients) {
	global $wpdb;
	$table = $wpdb->prefix . \'event_email_recipients\';
	
	$added = 0;
	$skipped = 0;
	$duplicates = 0;
	
	foreach ($recipients as $recipient) {
		if (!is_email($recipient[\'email\'])) {
			$skipped++;
			continue;
		}
		
		// Check for duplicates
		$existing = $wpdb->get_var($wpdb->prepare(
			"SELECT id FROM $table WHERE campaign_id = %d AND email = %s",
			$campaign_id,
			sanitize_email($recipient[\'email\'])
		));
		
		if ($existing) {
			$duplicates++;
			continue;
		}
		
		$tracking_token = wp_generate_password(32, false, false);
		
		$wpdb->insert(
			$table,
			array(
				\'campaign_id\' => $campaign_id,
				\'email\' => sanitize_email($recipient[\'email\']),
				\'name\' => isset($recipient[\'name\']) ? sanitize_text_field($recipient[\'name\']) : \'\',
				\'tracking_token\' => $tracking_token,
				\'sent_status\' => \'pending\'
			),
			array(\'%d\', \'%s\', \'%s\', \'%s\', \'%s\')
		);
		
		if ($wpdb->insert_id) {
			$added++;
		}
	}
	
	// Get total count properly
	$total_recipients = $wpdb->get_var($wpdb->prepare(
		"SELECT COUNT(*) FROM $table WHERE campaign_id = %d",
		$campaign_id
	));
	
	$wpdb->update(
		$wpdb->prefix . \'event_email_campaigns\',
		array(\'total_recipients\' => $total_recipients),
		array(\'id\' => $campaign_id),
		array(\'%d\'),
		array(\'%d\')
	);
	
	return array(
		\'added\' => $added,
		\'skipped\' => $skipped,
		\'duplicates\' => $duplicates,
		\'total\' => $total_recipients
	);
}';

$functions_content = str_replace($old_function, $new_function, $functions_content);
file_put_contents($functions_file, $functions_content);

echo "Fixed: email-invitation-functions.php\n";

// Fix 2: Update AJAX handlers
$ajax_file = __DIR__ . '/rsvpplugin/includes/email-invitation-ajax.php';
$ajax_content = file_get_contents($ajax_file);

// Fix CSV upload handler
$old_csv = '$added = event_rsvp_add_campaign_recipients($campaign_id, $recipients);
		
		@unlink($movefile[\'file\']);
		
		wp_send_json_success(array(
			\'message\' => sprintf(\'%d recipients added successfully!\', $added),
			\'count\' => $added
		));';

$new_csv = '$result = event_rsvp_add_campaign_recipients($campaign_id, $recipients);
		
		@unlink($movefile[\'file\']);
		
		$message = sprintf(\'%d recipient(s) added successfully!\', $result[\'added\']);
		if ($result[\'duplicates\'] > 0) {
			$message .= sprintf(\' (%d duplicate(s) skipped)\', $result[\'duplicates\']);
		}
		if ($result[\'skipped\'] > 0) {
			$message .= sprintf(\' (%d invalid email(s) skipped)\', $result[\'skipped\']);
		}
		
		wp_send_json_success(array(
			\'message\' => $message,
			\'added\' => $result[\'added\'],
			\'total\' => $result[\'total\']
		));';

$ajax_content = str_replace($old_csv, $new_csv, $ajax_content);

// Fix manual recipients handler
$old_manual = '$added = event_rsvp_add_campaign_recipients($campaign_id, $recipients);
	
	wp_send_json_success(array(
		\'message\' => sprintf(\'%d recipients added successfully!\', $added),
		\'count\' => $added
	));';

$new_manual = '$result = event_rsvp_add_campaign_recipients($campaign_id, $recipients);
	
	if ($result[\'added\'] === 0) {
		if ($result[\'duplicates\'] > 0) {
			wp_send_json_error(sprintf(\'All %d email(s) already exist in this campaign\', $result[\'duplicates\']));
		} else {
			wp_send_json_error(\'No valid emails were added\');
		}
		return;
	}
	
	$message = sprintf(\'%d recipient(s) added successfully!\', $result[\'added\']);
	if ($result[\'duplicates\'] > 0) {
		$message .= sprintf(\' (%d duplicate(s) skipped)\', $result[\'duplicates\']);
	}
	if ($result[\'skipped\'] > 0) {
		$message .= sprintf(\' (%d invalid email(s) skipped)\', $result[\'skipped\']);
	}
	
	wp_send_json_success(array(
		\'message\' => $message,
		\'added\' => $result[\'added\'],
		\'total\' => $result[\'total\']
	));';

$ajax_content = str_replace($old_manual, $new_manual, $ajax_content);
file_put_contents($ajax_file, $ajax_content);

echo "Fixed: email-invitation-ajax.php\n";

// Fix 3: Update JavaScript in page template
$page_file = __DIR__ . '/page-email-campaigns.php';
$page_content = file_get_contents($page_file);

// Fix loadTemplates function
$old_load_templates = 'function loadTemplates() {
		$.ajax({
			url: eventRsvpData.ajax_url,
			type: \'POST\',
			data: {
				action: \'event_rsvp_get_email_templates\',
				nonce: eventRsvpData.email_campaign_nonce
			},
			success: function(response) {
				if (response.success) {
					templates = response.data.templates;
					const select = $(\'#campaignTemplate\');
					select.find(\'option:not(:first)\').remove();
					
					templates.forEach(function(template) {
						select.append($(\'<option>\', {
							value: template.id,
							text: template.name + (template.description ? \' - \' + template.description : \'\')
						}));
					});
				}
			}
		});
	}';

$new_load_templates = 'function loadTemplates() {
		$.ajax({
			url: eventRsvpData.ajax_url,
			type: \'POST\',
			data: {
				action: \'event_rsvp_get_email_templates\',
				nonce: eventRsvpData.email_campaign_nonce
			},
			success: function(response) {
				if (response.success && response.data.templates) {
					templates = response.data.templates;
					const select = $(\'#campaignTemplate\');
					select.find(\'option:not(:first)\').remove();
					
					if (templates.length === 0) {
						select.append($(\'<option>\', {
							value: \'\',
							text: \'No templates available\',
							disabled: true
						}));
						console.warn(\'No email templates found in database\');
					} else {
						templates.forEach(function(template) {
							select.append($(\'<option>\', {
								value: template.id,
								text: template.name + (template.description ? \' - \' + template.description : \'\')
							}));
						});
					}
				} else {
					console.error(\'Failed to load templates:\', response);
				}
			},
			error: function(xhr, status, error) {
				console.error(\'Template loading error:\', error);
			}
		});
	}';

$page_content = str_replace($old_load_templates, $new_load_templates, $page_content);

// Fix manual recipients button handler  
$old_manual_btn = '$(\'#saveManualRecipientsBtn\').click(function() {
		const emails = $(\'#manualEmailsList\').val();
		
		if (!emails.trim()) {
			alert(\'Please enter at least one email address\');
			return;
		}
		
		$.ajax({
			url: eventRsvpData.ajax_url,
			type: \'POST\',
			data: {
				action: \'event_rsvp_add_manual_recipients\',
				nonce: eventRsvpData.email_campaign_nonce,
				campaign_id: currentCampaignId,
				emails: emails
			},
			success: function(response) {
				if (response.success) {
					alert(response.data.message);
					$(\'#manualRecipientsForm\').slideUp();
					$(\'#manualEmailsList\').val(\'\');
					loadCampaignRecipients(currentCampaignId);
				} else {
					alert(\'Error: \' + response.data);
				}
			}
		});
	});';

$new_manual_btn = '$(\'#saveManualRecipientsBtn\').click(function() {
		const emails = $(\'#manualEmailsList\').val();
		
		if (!emails.trim()) {
			alert(\'✗ Please enter at least one email address\');
			return;
		}
		
		const button = $(this);
		const originalText = button.text();
		button.prop(\'disabled\', true).text(\'Adding...\');
		
		$.ajax({
			url: eventRsvpData.ajax_url,
			type: \'POST\',
			data: {
				action: \'event_rsvp_add_manual_recipients\',
				nonce: eventRsvpData.email_campaign_nonce,
				campaign_id: currentCampaignId,
				emails: emails
			},
			success: function(response) {
				if (response.success) {
					alert(\'✓ \' + response.data.message);
					$(\'#manualRecipientsForm\').slideUp();
					$(\'#manualEmailsList\').val(\'\');
					loadCampaignRecipients(currentCampaignId);
				} else {
					alert(\'✗ Error: \' + response.data);
				}
			},
			error: function(xhr, status, error) {
				alert(\'✗ Failed to add recipients. Please try again.\');
				console.error(\'Error:\', error);
			},
			complete: function() {
				button.prop(\'disabled\', false).text(originalText);
			}
		});
	});';

$page_content = str_replace($old_manual_btn, $new_manual_btn, $page_content);

//  Fix CSV upload handler
$old_csv_change = '$(\'#csvFileInput\').change(function() {
		const file = this.files[0];
		if (!file) return;
		
		const formData = new FormData();
		formData.append(\'csv_file\', file);
		formData.append(\'action\', \'event_rsvp_upload_csv_recipients\');
		formData.append(\'nonce\', eventRsvpData.email_campaign_nonce);
		formData.append(\'campaign_id\', currentCampaignId);
		
		$.ajax({
			url: eventRsvpData.ajax_url,
			type: \'POST\',
			data: formData,
			processData: false,
			contentType: false,
			success: function(response) {
				if (response.success) {
					alert(response.data.message);
					loadCampaignRecipients(currentCampaignId);
				} else {
					alert(\'Error: \' + response.data);
				}
			}
		});
		
		$(this).val(\'\');
	});';

$new_csv_change = '$(\'#csvFileInput\').change(function() {
		const file = this.files[0];
		if (!file) return;
		
		if (!file.name.endsWith(\'.csv\')) {
			alert(\'✗ Please upload a CSV file\');
			$(this).val(\'\');
			return;
		}
		
		$(\'#uploadCsvBtn\').prop(\'disabled\', true).text(\'Uploading...\');
		
		const formData = new FormData();
		formData.append(\'csv_file\', file);
		formData.append(\'action\', \'event_rsvp_upload_csv_recipients\');
		formData.append(\'nonce\', eventRsvpData.email_campaign_nonce);
		formData.append(\'campaign_id\', currentCampaignId);
		
		$.ajax({
			url: eventRsvpData.ajax_url,
			type: \'POST\',
			data: formData,
			processData: false,
			contentType: false,
			success: function(response) {
				if (response.success) {
					alert(\'✓ \' + response.data.message);
					loadCampaignRecipients(currentCampaignId);
				} else {
					alert(\'✗ Error: \' + response.data);
				}
			},
			error: function(xhr, status, error) {
				alert(\'✗ Failed to upload CSV. Please try again.\');
				console.error(\'Error:\', error);
			},
			complete: function() {
				$(\'#uploadCsvBtn\').prop(\'disabled\', false).text(\'📁 Upload CSV\');
			}
		});
		
		$(this).val(\'\');
	});';

$page_content = str_replace($old_csv_change, $new_csv_change, $page_content);

file_put_contents($page_file, $page_content);

echo "Fixed: page-email-campaigns.php\n";
echo "\n✓ All fixes applied successfully!\n";
echo "Please test the Email Campaigns feature now.\n";
echo "You can delete this fix script after verifying everything works.\n";
?>

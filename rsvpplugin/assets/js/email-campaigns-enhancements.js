/**
 * Email Campaigns Enhancements
 * Adds image upload functionality and preview support
 */

jQuery(document).ready(function($) {
	
	// Template selection change handler
	$('#campaignTemplate').on('change', function() {
		const selectedOption = $(this).find('option:selected');
		const templateName = selectedOption.text();
		
		// Show/hide custom image upload field for Image Upload Template
		if (templateName.includes('Image Upload Template')) {
			$('#customImageUploadGroup').slideDown();
		} else {
			$('#customImageUploadGroup').slideUp();
			$('#customImageUrl').val('');
			$('#customImagePreview').hide();
		}
	});
	
	// Image upload button handler  
	$('#uploadCustomImageBtn').on('click', function(e) {
		e.preventDefault();
		
		if (typeof wp !== 'undefined' && wp.media) {
			const frame = wp.media({
				title: 'Select Event Image',
				button: { text: 'Use this image' },
				multiple: false,
				library: { type: 'image' }
			});
			
			frame.on('select', function() {
				const attachment = frame.state().get('selection').first().toJSON();
				$('#customImageUrl').val(attachment.url);
				$('#customImagePreview img').attr('src', attachment.url);
				$('#customImagePreview').slideDown();
			});
			
			frame.open();
		} else {
			alert('WordPress media library is not available. Please enter image URL manually.');
		}
	});
	
	// Remove custom image
	$('#removeCustomImageBtn').on('click', function(e) {
		e.preventDefault();
		$('#customImageUrl').val('');
		$('#customImagePreview').slideUp();
	});
	
	// Show preview when image URL is entered
	$('#customImageUrl').on('input', function() {
		const url = $(this).val();
		if (url && url.match(/\.(jpeg|jpg|gif|png|webp)$/i)) {
			$('#customImagePreview img').attr('src', url);
			$('#customImagePreview').slideDown();
		} else if (!url) {
			$('#customImagePreview').slideUp();
		}
	});
	
	// Override campaign form submission to include custom image
	$('#createCampaignForm').off('submit').on('submit', function(e) {
		e.preventDefault();

		const formData = new FormData(this);
		formData.append('action', 'event_rsvp_create_email_campaign');
		formData.append('nonce', eventRsvpData.email_campaign_nonce);
		
		// Add custom image if present
		const customImage = $('#customImageUrl').val();
		if (customImage) {
			formData.set('custom_image', customImage);
		}

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
			},
			error: function() {
				alert('Error creating campaign. Please try again.');
			}
		});
	});
	
	// Override preview template button to include custom image
	$('#previewTemplateBtn').off('click').on('click', function() {
		const templateId = $('#campaignTemplate').val();
		const eventId = $('#campaignEvent').val();
		const customImage = $('#customImageUrl').val();

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
				event_id: eventId || 0,
				custom_image: customImage
			},
			success: function(response) {
				if (response.success) {
					$('#templatePreviewContent').html(response.data.html);
					$('#templatePreviewModal').fadeIn();
				}
			}
		});
	});
	
	// Campaign preview refresh button - handled by manage script when it's loaded
	$('#refreshPreviewBtn').on('click', function() {
		const currentCampaignId = $(this).closest('.modal-container').data('campaign-id') || $('#manageCampaignModal').data('current-campaign-id');
		if (currentCampaignId && typeof window.loadCampaignPreview === 'function') {
			window.loadCampaignPreview(currentCampaignId);
		}
	});
	
	// Override tab button click to load preview
	$('.tab-btn').off('click').on('click', function() {
		const tabName = $(this).data('tab');
		
		$('.tab-btn').removeClass('active');
		$(this).addClass('active');

		$('.tab-content').hide();
		$('#' + tabName + 'Tab').show();
		
		// Load preview when switching to preview tab
		if (tabName === 'preview') {
			const modalCampaignId = $('#manageCampaignModal').data('current-campaign-id');
			if (modalCampaignId && typeof window.loadCampaignPreview === 'function') {
				window.loadCampaignPreview(modalCampaignId);
			}
		}
	});
	
	// Make loadCampaignRecipients available globally for manage script
	window.loadCampaignRecipients = function(currentCampaignId) {
		$.ajax({
			url: eventRsvpData.ajax_url,
			type: 'POST',
			data: {
				action: 'event_rsvp_get_campaign_recipients',
				nonce: eventRsvpData.email_campaign_nonce,
				campaign_id: currentCampaignId
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
	};
	
	// Make loadCampaignPreview available globally for manage script
	window.loadCampaignPreview = function(campaignId) {
		$('#emailPreviewContainer').html('<div class="loading-spinner">Loading preview...</div>');
		
		$.ajax({
			url: eventRsvpData.ajax_url,
			type: 'POST',
			data: {
				action: 'event_rsvp_get_campaign_preview',
				nonce: eventRsvpData.email_campaign_nonce,
				campaign_id: campaignId
			},
			success: function(response) {
				if (response.success) {
					$('#emailPreviewContainer').html(response.data.html);
				} else {
					$('#emailPreviewContainer').html('<p class="error-message">Failed to load preview: ' + (response.data || 'Unknown error') + '</p>');
				}
			},
			error: function() {
				$('#emailPreviewContainer').html('<p class="error-message">Failed to load preview. Please try again.</p>');
			}
		});
	};
	
	// Fix event pre-selection on page load
	const urlParams = new URLSearchParams(window.location.search);
	const isNewEvent = urlParams.get('new_event');
	const preSelectEventId = urlParams.get('event_id');

	if (isNewEvent === '1' && preSelectEventId) {
		setTimeout(function() {
			// Make sure modal is opened (original handler might have done this)
			if (!$('#createCampaignModal').is(':visible')) {
				$('#createCampaignModal').fadeIn();
			}

			// Pre-select the event
			$('#campaignEvent').val(preSelectEventId).trigger('change');

			// Auto-populate campaign name and subject with event name
			const eventName = $('#campaignEvent option:selected').text();
			if (eventName && eventName !== 'Choose an event...') {
				if (!$('#campaignName').val()) {
					$('#campaignName').val(eventName + ' - Invitation Campaign');
				}
				if (!$('#campaignSubject').val() || $('#campaignSubject').val() === "You're Invited: {{event_name}}") {
					$('#campaignSubject').val("You're Invited: " + eventName);
				}
			}

			// Remove the parameters from URL without reloading
			const newUrl = window.location.origin + window.location.pathname;
			window.history.replaceState({}, document.title, newUrl);
		}, 600);
	}
});

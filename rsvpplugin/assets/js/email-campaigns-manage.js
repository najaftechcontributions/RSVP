/**
 * Email Campaign Management - Settings & Image Editing
 */

jQuery(document).ready(function($) {
	let currentManageCampaignId = null;
	
	// Override manage campaign button to load campaign data
	$(document).on('click', '.manage-campaign-btn', function() {
		currentManageCampaignId = $(this).data('campaign-id');
		
		if (!currentManageCampaignId) {
			alert('✗ Invalid campaign ID');
			return;
		}
		
		// Store campaign ID on modal for later use
		$('#manageCampaignModal').data('current-campaign-id', currentManageCampaignId);
		
		// Load campaign settings
		loadCampaignSettings(currentManageCampaignId);
		
		$('#manageCampaignModal').fadeIn();
		$('#manualRecipientsForm').hide();
		$('#manualEmailsList').val('');
		
		// Switch to recipients tab by default
		$('.tab-btn').removeClass('active');
		$('.tab-btn[data-tab="recipients"]').addClass('active');
		$('.tab-content').hide();
		$('#recipientsTab').show();
		
		// Load recipients using the global function if available
		if (typeof window.loadCampaignRecipients === 'function') {
			window.loadCampaignRecipients(currentManageCampaignId);
		}
	});
	
	// Load campaign settings including all fields
	function loadCampaignSettings(campaignId) {
		$.ajax({
			url: eventRsvpData.ajax_url,
			type: 'POST',
			data: {
				action: 'event_rsvp_get_campaign_settings',
				nonce: eventRsvpData.email_campaign_nonce,
				campaign_id: campaignId
			},
			success: function(response) {
				if (response.success && response.data) {
					const customImage = response.data.custom_image || '';
					const campaignName = response.data.campaign_name || '';
					const subject = response.data.subject || '';
					const eventId = response.data.event_id || '';
					
					// Populate all fields
					if ($('#manageCampaignName').length) {
						$('#manageCampaignName').val(campaignName);
					}
					if ($('#manageCampaignSubject').length) {
						$('#manageCampaignSubject').val(subject);
					}
					if ($('#manageCampaignEvent').length) {
						$('#manageCampaignEvent').val(eventId);
					}
					$('#manageCampaignImage').val(customImage);
					
					// Show preview if image exists
					if (customImage) {
						$('#manageCampaignImagePreview img').attr('src', customImage);
						$('#manageCampaignImagePreview').show();
					} else {
						$('#manageCampaignImagePreview').hide();
					}
				}
			},
			error: function(xhr, status, error) {
				console.error('Failed to load campaign settings:', error);
			}
		});
	}
	
	// Upload image button for manage campaign
	$('#uploadManageCampaignImageBtn').on('click', function(e) {
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
				$('#manageCampaignImage').val(attachment.url);
				$('#manageCampaignImagePreview img').attr('src', attachment.url);
				$('#manageCampaignImagePreview').slideDown();
			});
			
			frame.open();
		} else {
			alert('WordPress media library is not available. Please enter image URL manually.');
		}
	});
	
	// Remove image button for manage campaign
	$('#removeManageCampaignImageBtn').on('click', function(e) {
		e.preventDefault();
		$('#manageCampaignImage').val('');
		$('#manageCampaignImagePreview').slideUp();
	});
	
	// Show preview when image URL is entered
	$('#manageCampaignImage').on('input', function() {
		const url = $(this).val();
		if (url && url.match(/\.(jpeg|jpg|gif|png|webp)$/i)) {
			$('#manageCampaignImagePreview img').attr('src', url);
			$('#manageCampaignImagePreview').slideDown();
		} else if (!url) {
			$('#manageCampaignImagePreview').slideUp();
		}
	});
	
	// Save campaign settings
	$('#saveCampaignSettingsBtn').on('click', function() {
		const campaignId = $('#manageCampaignModal').data('current-campaign-id');
		const customImage = $('#manageCampaignImage').val();
		
		// Get campaign name, subject, and event if fields exist
		const campaignName = $('#manageCampaignName').length ? $('#manageCampaignName').val() : '';
		const subject = $('#manageCampaignSubject').length ? $('#manageCampaignSubject').val() : '';
		const eventId = $('#manageCampaignEvent').length ? $('#manageCampaignEvent').val() : '';
		
		if (!campaignId) {
			alert('✗ No campaign selected');
			return;
		}
		
		// Validate required fields if they exist
		if ($('#manageCampaignName').length && (!campaignName || !subject || !eventId)) {
			alert('✗ Please fill in all required fields (Campaign Name, Event, Subject)');
			return;
		}
		
		const button = $(this);
		button.prop('disabled', true).text('Saving...');
		
		const requestData = {
			action: 'event_rsvp_update_campaign_settings',
			nonce: eventRsvpData.email_campaign_nonce,
			campaign_id: campaignId,
			custom_image: customImage
		};
		
		// Add optional fields if they have values
		if (campaignName) requestData.campaign_name = campaignName;
		if (subject) requestData.subject = subject;
		if (eventId) requestData.event_id = eventId;
		
		$.ajax({
			url: eventRsvpData.ajax_url,
			type: 'POST',
			data: requestData,
			success: function(response) {
				console.log('Save settings response:', response);
				
				if (response.success) {
					alert('✓ Campaign settings saved successfully!');
					
					// Refresh preview if on preview tab
					if ($('.tab-btn[data-tab="preview"]').hasClass('active')) {
						loadCampaignPreview(campaignId);
					}
					
					// Refresh page to show updated campaign name in the list
					if (campaignName) {
						setTimeout(function() {
							location.reload();
						}, 1000);
					}
				} else {
					const errorMsg = response.data || 'Failed to save settings';
					alert('✗ Error: ' + errorMsg);
					console.error('Server error:', errorMsg);
				}
			},
			error: function(xhr, status, error) {
				console.error('AJAX Error:', {
					status: status,
					error: error,
					response: xhr.responseText,
					statusCode: xhr.status
				});
				
				let errorMessage = 'Failed to save settings. Please try again.';
				if (xhr.status === 0) {
					errorMessage = 'Network error. Please check your connection.';
				} else if (xhr.status === 403) {
					errorMessage = 'Permission denied. Please refresh the page and try again.';
				} else if (xhr.status === 500) {
					errorMessage = 'Server error. Please contact support.';
				}
				
				alert('✗ ' + errorMessage);
			},
			complete: function() {
				button.prop('disabled', false).text('Save Settings');
			}
		});
	});
	
	// Load preview when switching to preview tab
	$(document).on('click', '.tab-btn[data-tab="preview"]', function() {
		const campaignId = $('#manageCampaignModal').data('current-campaign-id');
		if (campaignId) {
			loadCampaignPreview(campaignId);
		}
	});
	
	// Refresh preview button
	$('#refreshPreviewBtn').on('click', function() {
		const campaignId = $('#manageCampaignModal').data('current-campaign-id');
		if (campaignId) {
			loadCampaignPreview(campaignId);
		}
	});
	
	// Load campaign preview
	function loadCampaignPreview(campaignId) {
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
	}
	
	// Make loadCampaignPreview available globally
	window.loadCampaignPreview = loadCampaignPreview;
});

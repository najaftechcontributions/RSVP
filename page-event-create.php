<?php
/**
 * Template Name: Create Event
 *
 * @package RSVP
 */

if (!is_user_logged_in()) {
	wp_redirect(add_query_arg('redirect_to', get_permalink(), home_url('/login/')));
	exit;
}

if (!current_user_can('edit_posts') && !current_user_can('edit_events')) {
	wp_die('You do not have permission to create events. Please contact an administrator.');
}

// Check event creation limit
if (!function_exists('event_rsvp_can_create_event')) {
	require_once get_template_directory() . '/rsvpplugin/includes/event-limit-functions.php';
}

$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
$is_edit_mode = false;
$current_featured_image_id = 0;

if ($event_id > 0) {
	$event = get_post($event_id);
	if ($event && $event->post_type === 'event' && (get_current_user_id() == $event->post_author || current_user_can('administrator'))) {
		$is_edit_mode = true;
		$current_featured_image_id = get_post_thumbnail_id($event_id);
	} else {
		wp_die('You do not have permission to edit this event.');
	}
}

if (function_exists('acf_form_head')) {
	acf_form_head();
}

get_header();
?>

<main id="primary" class="site-main event-create-page">
	<div class="container">
		
		<div style="height:40px" aria-hidden="true"></div>

		<div class="page-header">
			<h1 class="create-event-title"><?php echo $is_edit_mode ? 'Edit Event' : 'Create New Event'; ?></h1>
			<p class="create-event-subtitle">Fill out the form below to <?php echo $is_edit_mode ? 'update' : 'create and publish'; ?> your event. All required fields are marked with *</p>

			<?php if (!$is_edit_mode) : ?>
				<?php
				$can_create = event_rsvp_can_create_event();
				if (!$can_create) :
					$limit = event_rsvp_get_event_limit();
					$current_count = event_rsvp_get_user_event_count();
					$plan = Event_RSVP_Simple_Stripe::get_user_plan();
				?>
					<div class="event-limit-error">
						<strong>⚠️ Event Limit Reached</strong>
						<p>
							<?php if ($limit === 0) : ?>
								Your current plan does not allow event creation.
							<?php else : ?>
								You have reached your event limit (<?php echo $current_count; ?> of <?php echo $limit; ?> events created).
							<?php endif; ?>
							<a href="<?php echo home_url('/pricing/'); ?>" style="color: #503AA8; font-weight: bold; text-decoration: underline;">Upgrade your plan</a> to create more events<?php if ($limit > 0) : ?>.<?php endif; ?>.
						</p>
					</div>
					<style>
						.event-create-layout { display: none !important; }
						.event-limit-error {
							background: #fff3cd;
							border: 2px solid #ffc107;
							border-radius: 8px;
							padding: 20px;
							margin: 20px 0;
							text-align: center;
						}
						.event-limit-error strong {
							display: block;
							font-size: 1.2em;
							margin-bottom: 10px;
							color: #856404;
						}
						.event-limit-error p {
							margin: 0;
							color: #856404;
						}
					</style>
				<?php else : ?>
					<?php echo event_rsvp_display_event_limit_notice(); ?>
					<style>
						.event-limit-notice {
							background: #f0f7ff;
							border: 2px solid #3b82f6;
							border-radius: 8px;
							padding: 15px;
							margin: 15px 0;
							text-align: center;
							font-size: 0.95em;
							color: #1e40af;
						}
						.event-limit-notice.unlimited-notice {
							background: #f0fdf4;
							border-color: #10b981;
							color: #065f46;
						}
						.event-limit-notice.low-limit-notice {
							background: #fff3cd;
							border-color: #ffc107;
							color: #856404;
						}
						.event-limit-notice a {
							color: #503AA8;
							font-weight: bold;
							text-decoration: underline;
						}
					</style>
				<?php endif; ?>
			<?php endif; ?>
		</div>

		<div style="height:40px" aria-hidden="true"></div>

		<div class="event-create-layout">
			
			<div class="form-main-column">
				<div class="event-form-card">
					
					<?php if (function_exists('acf_form')) : ?>
						
						<?php
						// Build the featured image section HTML to be included in the form
						$featured_image_html = '
						<div class="featured-image-upload-section">
							<h3>Featured Image</h3>
							<p class="field-description">Upload a high-quality image to make your event stand out</p>
							
							<div class="featured-image-preview">';
						
						if ($is_edit_mode && has_post_thumbnail($event_id)) {
							$featured_image_html .= '
								<div class="current-featured-image">
									' . get_the_post_thumbnail($event_id, 'large') . '
								</div>';
						}
						
						$featured_image_html .= '
							</div>

							<div class="image-upload-controls">
								<button type="button" id="upload-featured-image" class="upload-image-button">
									📷 ' . (($is_edit_mode && has_post_thumbnail($event_id)) ? 'Change Featured Image' : 'Upload Featured Image') . '
								</button>';
						
						if ($is_edit_mode && has_post_thumbnail($event_id)) {
							$featured_image_html .= '
								<button type="button" id="remove-featured-image" class="remove-image-button">
									✕ Remove Image
								</button>';
						}
						
						$featured_image_html .= '
							</div>
						</div>';

						// Redirect to email campaigns after event creation (or host dashboard if editing)
						$return_url = $is_edit_mode
							? home_url('/host-dashboard/?event_updated=success')
							: home_url('/email-campaigns/?new_event=1');

						$form_args = array(
							'post_id' => $is_edit_mode ? $event_id : 'new_post',
							'post_title' => true,
							'post_content' => true,
							'field_groups' => array('group_event_fields'),
							'return' => $return_url,
							'submit_value' => $is_edit_mode ? '✓ Update Event' : '✓ Create Event',
							'updated_message' => $is_edit_mode ? 'Event updated successfully! Redirecting...' : 'Event created successfully! Redirecting to email campaigns...',
							'html_before_fields' => '<div class="acf-event-form"><input type="hidden" id="event_featured_image_id" name="event_featured_image_id" value="' . esc_attr($current_featured_image_id) . '">',
							'html_after_fields' => $featured_image_html . '</div>',
							'uploader' => 'wp',
							'honeypot' => true,
							'label_placement' => 'top',
							'instruction_placement' => 'label',
							'html_submit_button' => '<button type="submit" class="acf-button acf-submit-button" style="margin-top: 15px;">' . ($is_edit_mode ? '✓ Update Event' : '✓ Create Event') . '</button>',
						);

						if (!$is_edit_mode) {
							$form_args['new_post'] = array(
								'post_type' => 'event',
								'post_status' => 'publish',
								'post_author' => get_current_user_id()
							);
						}

						acf_form($form_args);
						?>

					<?php else : ?>
						<div class="acf-not-found">
							<h3>ACF Not Available</h3>
							<p>The Advanced Custom Fields plugin is required to create events. Please contact an administrator.</p>
						</div>
					<?php endif; ?>

				</div>
			</div>

			<div class="form-sidebar-column">
				
				<div class="event-tips-card sticky-sidebar">
					<h3>💡 Tips for Success</h3>
					<hr class="tips-divider"/>
					<ul class="tips-list">
						<li><strong>Clear Title:</strong> Use a descriptive, engaging event title</li>
						<li><strong>Detailed Description:</strong> Include what attendees can expect</li>
						<li><strong>High-Quality Image:</strong> Add an eye-catching featured image</li>
						<li><strong>Complete Details:</strong> Fill in all venue and date information</li>
						<li><strong>Set Capacity:</strong> Define max attendees to manage RSVPs</li>
						<li><strong>QR Timing:</strong> Schedule when to send QR codes</li>
					</ul>
				</div>

				<div style="height:30px" aria-hidden="true"></div>

				<div class="quick-links-card">
					<h4>Quick Links</h4>
					<div class="quick-links-buttons">
						<a href="<?php echo esc_url(home_url('/host-dashboard/')); ?>" class="quick-link-button">
							📊 My Dashboard
						</a>
						<a href="<?php echo esc_url(home_url('/events/')); ?>" class="quick-link-button">
							📅 View All Events
						</a>
						<?php if ($is_edit_mode) : ?>
							<a href="<?php echo esc_url(get_permalink($event_id)); ?>" class="quick-link-button">
								👁️ View Event
							</a>
						<?php endif; ?>
					</div>
				</div>

			</div>

		</div>

		<div style="height:60px" aria-hidden="true"></div>

	</div>
</main>

<script>
jQuery(document).ready(function($) {
	// Featured image upload handler
	$('#upload-featured-image').on('click', function(e) {
		e.preventDefault();
		
		var customUploader = wp.media({
			title: 'Select Featured Image',
			button: {
				text: 'Set Featured Image'
			},
			multiple: false
		});

		customUploader.on('select', function() {
			var attachment = customUploader.state().get('selection').first().toJSON();
			
			// Update hidden field with attachment ID
			$('#event_featured_image_id').val(attachment.id);

			// Update preview
			$('.featured-image-preview').html('<div class="current-featured-image"><img src="' + attachment.url + '" alt="Featured Image Preview" /></div>');
			$('#upload-featured-image').text('📷 Change Featured Image');
			
			// Add remove button if it doesn't exist
			if ($('#remove-featured-image').length === 0) {
				$('.image-upload-controls').append('<button type="button" id="remove-featured-image" class="remove-image-button">✕ Remove Image</button>');
			}
		});

		customUploader.open();
	});

	// Remove featured image handler
	$(document).on('click', '#remove-featured-image', function(e) {
		e.preventDefault();
		
		if (!confirm('Are you sure you want to remove the featured image?')) {
			return;
		}

		// Clear the hidden field
		$('#event_featured_image_id').val('');
		
		// Clear the preview
		$('.featured-image-preview').html('');
		
		// Update button text
		$('#upload-featured-image').text('📷 Upload Featured Image');
		
		// Remove the remove button
		$('#remove-featured-image').remove();
	});

	// Preserve iframe content in venue_map_url field
	// Store the raw value before ACF processes it
	var venueMapField = $('textarea[name="acf[field_venue_map_url]"]');
	if (venueMapField.length) {
		// Before form submission, ensure raw value is preserved
		$('form.acf-form').on('submit', function(e) {
			var rawValue = venueMapField.val();
			// Store in a hidden field to bypass ACF sanitization
			if ($('#raw_venue_map_url').length === 0) {
				$(this).append('<input type="hidden" name="raw_venue_map_url" id="raw_venue_map_url" value="">');
			}
			$('#raw_venue_map_url').val(rawValue);
		});
	}
});
</script>

<?php get_footer(); ?>

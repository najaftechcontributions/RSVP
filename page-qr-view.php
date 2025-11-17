<?php
/**
 * Template Name: QR Code Viewer
 * Display event and attendee details from QR code scan
 *
 * @package RSVP
 */

$qr_data = isset($_GET['qr']) ? sanitize_text_field($_GET['qr']) : '';

if (empty($qr_data)) {
	get_header();
	?>
	<main id="primary" class="site-main qr-viewer-page">
		<div class="container">
			<div style="height:60px" aria-hidden="true"></div>
			<div class="error-state">
				<div class="error-icon">❌</div>
				<h1>Invalid QR Code</h1>
				<p>No QR code data provided. Please scan a valid event QR code.</p>
				<a href="<?php echo esc_url(home_url('/events/')); ?>" class="back-button">Browse Events</a>
			</div>
			<div style="height:60px" aria-hidden="true"></div>
		</div>
	</main>
	<?php
	get_footer();
	exit;
}

$decoded_data = json_decode(base64_decode($qr_data), true);

if (!isset($decoded_data['attendee_id']) || !isset($decoded_data['event_id'])) {
	get_header();
	?>
	<main id="primary" class="site-main qr-viewer-page">
		<div class="container">
			<div style="height:60px" aria-hidden="true"></div>
			<div class="error-state">
				<div class="error-icon">⚠️</div>
				<h1>Invalid QR Code Format</h1>
				<p>The QR code data is not in the correct format. Please use a valid event registration QR code.</p>
				<a href="<?php echo esc_url(home_url('/events/')); ?>" class="back-button">Browse Events</a>
			</div>
			<div style="height:60px" aria-hidden="true"></div>
		</div>
	</main>
	<?php
	get_footer();
	exit;
}

$attendee_id = intval($decoded_data['attendee_id']);
$event_id = intval($decoded_data['event_id']);

$stored_qr_data = get_post_meta($attendee_id, 'qr_data', true);

if ($stored_qr_data !== $qr_data) {
	get_header();
	?>
	<main id="primary" class="site-main qr-viewer-page">
		<div class="container">
			<div style="height:60px" aria-hidden="true"></div>
			<div class="error-state">
				<div class="error-icon">🔒</div>
				<h1>QR Code Verification Failed</h1>
				<p>This QR code could not be verified. It may have been tampered with or is no longer valid.</p>
				<a href="<?php echo esc_url(home_url('/events/')); ?>" class="back-button">Browse Events</a>
			</div>
			<div style="height:60px" aria-hidden="true"></div>
		</div>
	</main>
	<?php
	get_footer();
	exit;
}

$attendee = get_post($attendee_id);
$event = get_post($event_id);

if (!$attendee || !$event) {
	get_header();
	?>
	<main id="primary" class="site-main qr-viewer-page">
		<div class="container">
			<div style="height:60px" aria-hidden="true"></div>
			<div class="error-state">
				<div class="error-icon">🔍</div>
				<h1>Record Not Found</h1>
				<p>The attendee or event information could not be found in our system.</p>
				<a href="<?php echo esc_url(home_url('/events/')); ?>" class="back-button">Browse Events</a>
			</div>
			<div style="height:60px" aria-hidden="true"></div>
		</div>
	</main>
	<?php
	get_footer();
	exit;
}

$attendee_name = get_the_title($attendee_id);
$attendee_email = get_post_meta($attendee_id, 'attendee_email', true);
$attendee_phone = get_post_meta($attendee_id, 'attendee_phone', true);
$rsvp_status = get_post_meta($attendee_id, 'rsvp_status', true);
$checkin_status = get_post_meta($attendee_id, 'checkin_status', true);
$checkin_time = get_post_meta($attendee_id, 'checkin_time', true);

$event_title = get_the_title($event_id);
$event_date = get_post_meta($event_id, 'event_date', true);
$event_end_date = get_post_meta($event_id, 'event_end_date', true);
$venue_address = get_post_meta($event_id, 'venue_address', true);
$event_category = get_post_meta($event_id, 'event_category', true);

$event_author_id = get_post_field('post_author', $event_id);
$current_user_id = get_current_user_id();
$is_event_host = ($current_user_id && ($current_user_id == $event_author_id || current_user_can('administrator')));

$status_badge_class = 'status-confirmed';
$status_text = '✓ Confirmed';
$status_icon = '✓';

if ($rsvp_status === 'maybe') {
	$status_badge_class = 'status-maybe';
	$status_text = '? Maybe';
	$status_icon = '?';
} elseif ($rsvp_status === 'no') {
	$status_badge_class = 'status-declined';
	$status_text = '✗ Declined';
	$status_icon = '✗';
}

get_header();
?>

<main id="primary" class="site-main qr-viewer-page">
	<div class="container">
		
		<div style="height:40px" aria-hidden="true"></div>

		<?php if ($is_event_host && !$checkin_status) : ?>
			<div class="checkin-action-notice">
				<div class="notice-icon">🎟️</div>
				<div class="notice-content">
					<h3>Attendee Ready for Check-In</h3>
					<p>As the event host, you can check in <strong><?php echo esc_html($attendee_name); ?></strong>.</p>
					<button type="button" id="manual-checkin-btn" class="checkin-button" data-attendee-id="<?php echo esc_attr($attendee_id); ?>" data-qr-data="<?php echo esc_attr($qr_data); ?>">
						<span class="button-icon">✓</span>
						<span class="button-text">Check In Attendee</span>
					</button>
				</div>
			</div>
			<div style="height:20px" aria-hidden="true"></div>
		<?php endif; ?>

		<div class="qr-viewer-header">
			<div class="qr-header-icon">🎟️</div>
			<h1 class="qr-viewer-title">Event Registration Details</h1>
			<p class="qr-viewer-subtitle">Scanned QR Code Information</p>
		</div>

		<div style="height:40px" aria-hidden="true"></div>

		<div class="qr-viewer-layout">
			
			<div class="qr-viewer-main">
				
				<div class="attendee-info-card">
					<div class="card-header">
						<div class="header-icon">👤</div>
						<h2 class="card-title">Attendee Information</h2>
					</div>
					
					<div class="card-content">
						<div class="info-grid">
							<div class="info-item">
								<span class="info-label">Full Name</span>
								<span class="info-value"><?php echo esc_html($attendee_name); ?></span>
							</div>
							
							<div class="info-item">
								<span class="info-label">Email Address</span>
								<span class="info-value">
									<a href="mailto:<?php echo esc_attr($attendee_email); ?>" class="email-link">
										<?php echo esc_html($attendee_email); ?>
									</a>
								</span>
							</div>
							
							<?php if ($attendee_phone) : ?>
								<div class="info-item">
									<span class="info-label">Phone Number</span>
									<span class="info-value">
										<a href="tel:<?php echo esc_attr($attendee_phone); ?>" class="phone-link">
											<?php echo esc_html($attendee_phone); ?>
										</a>
									</span>
								</div>
							<?php endif; ?>
							
							<div class="info-item">
								<span class="info-label">RSVP Status</span>
								<span class="info-value">
									<span class="status-badge <?php echo esc_attr($status_badge_class); ?>">
										<?php echo esc_html($status_text); ?>
									</span>
								</span>
							</div>
							
							<div class="info-item" id="checkin-status-display">
								<span class="info-label">Check-In Status</span>
								<span class="info-value">
									<?php if ($checkin_status) : ?>
										<span class="status-badge status-checked-in">✓ Checked In</span>
									<?php else : ?>
										<span class="status-badge status-not-checked-in">⏳ Not Checked In</span>
									<?php endif; ?>
								</span>
							</div>
							
							<?php if ($checkin_status && $checkin_time) : ?>
								<div class="info-item">
									<span class="info-label">Check-In Time</span>
									<span class="info-value"><?php echo esc_html(date('F j, Y \a\t g:i A', strtotime($checkin_time))); ?></span>
								</div>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<div style="height:30px" aria-hidden="true"></div>

				<div class="event-info-card">
					<div class="card-header">
						<div class="header-icon">📅</div>
						<h2 class="card-title">Event Details</h2>
					</div>
					
					<div class="card-content">
						<?php if (has_post_thumbnail($event_id)) : ?>
							<div class="event-featured-image">
								<?php echo get_the_post_thumbnail($event_id, 'large'); ?>
							</div>
						<?php endif; ?>
						
						<h3 class="event-name"><?php echo esc_html($event_title); ?></h3>
						
						<div class="info-grid">
							<?php if ($event_date) : ?>
								<div class="info-item">
									<span class="info-label">📅 Event Date</span>
									<span class="info-value"><?php echo esc_html(date('F j, Y', strtotime($event_date))); ?></span>
								</div>
							<?php endif; ?>
							
							<?php if ($event_end_date && $event_end_date !== $event_date) : ?>
								<div class="info-item">
									<span class="info-label">⏰ End Date</span>
									<span class="info-value"><?php echo esc_html(date('F j, Y', strtotime($event_end_date))); ?></span>
								</div>
							<?php endif; ?>
							
							<?php if ($venue_address) : ?>
								<div class="info-item full-width">
									<span class="info-label">📍 Venue</span>
									<span class="info-value"><?php echo esc_html($venue_address); ?></span>
								</div>
							<?php endif; ?>
							
							<?php if ($event_category) : ?>
								<div class="info-item">
									<span class="info-label">🏷️ Category</span>
									<span class="info-value"><?php echo esc_html($event_category); ?></span>
								</div>
							<?php endif; ?>
						</div>
						
						<?php if ($event->post_excerpt) : ?>
							<div class="event-description">
								<h4>About This Event</h4>
								<?php echo wpautop(esc_html($event->post_excerpt)); ?>
							</div>
						<?php endif; ?>
						
						<div class="event-actions">
							<a href="<?php echo esc_url(get_permalink($event_id)); ?>" class="action-button primary-button">
								<span class="button-icon">🔍</span>
								<span class="button-text">View Full Event Details</span>
							</a>
						</div>
					</div>
				</div>

			</div>

			<div class="qr-viewer-sidebar">
				
				<div class="qr-code-card">
					<h3 class="sidebar-card-title">Your QR Code</h3>
					<div class="qr-code-display">
						<img src="<?php echo esc_url(event_rsvp_generate_qr_code($qr_data)); ?>" alt="QR Code" class="qr-code-image">
					</div>
					<p class="qr-code-note">Present this code at the event entrance for quick check-in</p>
				</div>

				<div style="height:20px" aria-hidden="true"></div>

				<div class="quick-actions-card">
					<h3 class="sidebar-card-title">Quick Actions</h3>
					<div class="quick-actions-list">
						<a href="<?php echo esc_url(get_permalink($event_id)); ?>" class="quick-action-item">
							<span class="action-icon">📅</span>
							<span class="action-text">View Event</span>
						</a>
						
						<?php if (current_user_can('edit_posts')) : ?>
							<a href="<?php echo esc_url(home_url('/check-in/?event_id=' . $event_id)); ?>" class="quick-action-item">
								<span class="action-icon">✓</span>
								<span class="action-text">Go to Check-In</span>
							</a>
						<?php endif; ?>
						
						<a href="mailto:<?php echo esc_attr($attendee_email); ?>" class="quick-action-item">
							<span class="action-icon">📧</span>
							<span class="action-text">Email Attendee</span>
						</a>
						
						<?php if ($attendee_phone) : ?>
							<a href="tel:<?php echo esc_attr($attendee_phone); ?>" class="quick-action-item">
								<span class="action-icon">📱</span>
								<span class="action-text">Call Attendee</span>
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
	$('#manual-checkin-btn').on('click', function() {
		const button = $(this);
		const qrData = button.data('qr-data');
		const attendeeId = button.data('attendee-id');
		const noticeBox = button.closest('.checkin-action-notice');

		// Disable button and show loading state
		button.prop('disabled', true);
		button.html('<span class="button-icon">⏳</span><span class="button-text">Checking In...</span>');

		$.ajax({
			url: '<?php echo admin_url('admin-ajax.php'); ?>',
			type: 'POST',
			data: {
				action: 'event_rsvp_checkin',
				nonce: '<?php echo wp_create_nonce('event_rsvp_checkin'); ?>',
				qr_data: qrData
			},
			success: function(response) {
				if (response.success) {
					// Show success message
					noticeBox.html(
						'<div class="notice-icon">✅</div>' +
						'<div class="notice-content">' +
						'<h3>Attendee Successfully Checked In!</h3>' +
						'<p><strong>' + response.data.attendee_name + '</strong> has been checked in at ' + new Date().toLocaleTimeString('en-US', {hour: 'numeric', minute: '2-digit'}) + '.</p>' +
						'</div>'
					);
					noticeBox.removeClass('checkin-action-notice').addClass('auto-checkin-notice');

					// Update check-in status display
					const statusDisplay = $('#checkin-status-display .info-value');
					statusDisplay.html('<span class="status-badge status-checked-in">✓ Checked In</span>');

					// Add check-in time display if it doesn't exist
					const checkinTimeExists = $('#checkin-time-display').length > 0;
					if (!checkinTimeExists) {
						const now = new Date();
						const formattedDate = now.toLocaleDateString('en-US', {
							year: 'numeric',
							month: 'long',
							day: 'numeric'
						});
						const formattedTime = now.toLocaleTimeString('en-US', {
							hour: 'numeric',
							minute: '2-digit'
						});

						$('#checkin-status-display').after(
							'<div class="info-item" id="checkin-time-display">' +
							'<span class="info-label">Check-In Time</span>' +
							'<span class="info-value">' + formattedDate + ' at ' + formattedTime + '</span>' +
							'</div>'
						);
					}

					// Auto-hide success message after 5 seconds
					setTimeout(function() {
						noticeBox.fadeOut();
					}, 5000);
				} else {
					// Show error message
					noticeBox.html(
						'<div class="notice-icon">❌</div>' +
						'<div class="notice-content">' +
						'<h3>Check-In Failed</h3>' +
						'<p>' + (response.data || 'An error occurred. Please try again.') + '</p>' +
						'<button type="button" id="manual-checkin-btn" class="checkin-button" data-attendee-id="' + attendeeId + '" data-qr-data="' + qrData + '">' +
						'<span class="button-icon">✓</span>' +
						'<span class="button-text">Try Again</span>' +
						'</button>' +
						'</div>'
					);
					noticeBox.removeClass('checkin-action-notice').addClass('error-notice');
				}
			},
			error: function() {
				// Show error message
				noticeBox.html(
					'<div class="notice-icon">❌</div>' +
					'<div class="notice-content">' +
					'<h3>Check-In Failed</h3>' +
					'<p>A network error occurred. Please check your connection and try again.</p>' +
					'<button type="button" id="manual-checkin-btn" class="checkin-button" data-attendee-id="' + attendeeId + '" data-qr-data="' + qrData + '">' +
					'<span class="button-icon">✓</span>' +
					'<span class="button-text">Try Again</span>' +
					'</button>' +
					'</div>'
				);
				noticeBox.removeClass('checkin-action-notice').addClass('error-notice');
			}
		});
	});

	// Handle dynamically added button clicks
	$(document).on('click', '#manual-checkin-btn', function() {
		if ($(this).prop('disabled')) {
			return;
		}
		$(this).trigger('click');
	});
});
</script>

<style>
.checkin-action-notice {
	background: #f0f9ff;
	border: 2px solid #3b82f6;
	border-radius: 12px;
	padding: 24px;
	display: flex;
	align-items: flex-start;
	gap: 16px;
	margin-bottom: 20px;
}

.checkin-action-notice .notice-icon {
	font-size: 32px;
	line-height: 1;
}

.checkin-action-notice .notice-content {
	flex: 1;
}

.checkin-action-notice h3 {
	margin: 0 0 8px 0;
	color: #1e40af;
	font-size: 20px;
}

.checkin-action-notice p {
	margin: 0 0 16px 0;
	color: #1e3a8a;
}

.checkin-button {
	background: #10b981;
	color: white;
	border: none;
	padding: 12px 24px;
	border-radius: 8px;
	font-size: 16px;
	font-weight: 600;
	cursor: pointer;
	display: inline-flex;
	align-items: center;
	gap: 8px;
	transition: all 0.3s ease;
}

.checkin-button:hover:not(:disabled) {
	background: #059669;
	transform: translateY(-2px);
	box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.checkin-button:disabled {
	opacity: 0.6;
	cursor: not-allowed;
}

.checkin-button .button-icon {
	font-size: 18px;
}

.auto-checkin-notice {
	background: #f0fdf4;
	border: 2px solid #10b981;
	border-radius: 12px;
	padding: 24px;
	display: flex;
	align-items: flex-start;
	gap: 16px;
	margin-bottom: 20px;
}

.auto-checkin-notice .notice-icon {
	font-size: 32px;
	line-height: 1;
}

.auto-checkin-notice .notice-content {
	flex: 1;
}

.auto-checkin-notice h3 {
	margin: 0 0 8px 0;
	color: #047857;
	font-size: 20px;
}

.auto-checkin-notice p {
	margin: 0;
	color: #065f46;
}

.error-notice {
	background: #fef2f2;
	border: 2px solid #ef4444;
	border-radius: 12px;
	padding: 24px;
	display: flex;
	align-items: flex-start;
	gap: 16px;
	margin-bottom: 20px;
}

.error-notice .notice-icon {
	font-size: 32px;
	line-height: 1;
}

.error-notice .notice-content {
	flex: 1;
}

.error-notice h3 {
	margin: 0 0 8px 0;
	color: #991b1b;
	font-size: 20px;
}

.error-notice p {
	margin: 0 0 16px 0;
	color: #7f1d1d;
}
</style>

<?php get_footer(); ?>

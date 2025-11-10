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
							
							<div class="info-item">
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
						
						<?php if (!$checkin_status && current_user_can('edit_posts')) : ?>
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

<?php get_footer(); ?>

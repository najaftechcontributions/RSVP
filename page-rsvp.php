<?php
/**
 * Template Name: RSVP Page
 *
 * @package RSVP
 */

get_header();

$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

if (!$event_id) {
	wp_die('Please specify an event ID.');
}

$event = get_post($event_id);
if (!$event || $event->post_type !== 'event') {
	wp_die('Invalid event.');
}

$event_date = get_field('event_date', $event_id);
$venue_address = get_field('venue_address', $event_id);
$is_full = event_rsvp_is_event_full($event_id);
$available_spots = event_rsvp_get_available_spots($event_id);

$rsvp_status = '';
$rsvp_message = '';
if (isset($_GET['rsvp'])) {
	switch ($_GET['rsvp']) {
		case 'success':
			$rsvp_status = 'success';
			$rsvp_message = 'RSVP submitted successfully! Check your email for confirmation.';
			break;
		case 'full':
			$rsvp_status = 'error';
			$rsvp_message = 'Sorry, this event is at full capacity.';
			break;
		case 'error':
			$rsvp_status = 'error';
			$rsvp_message = 'An error occurred. Please try again.';
			break;
	}
}
?>

<main id="primary" class="site-main rsvp-page">
	
	<div class="container">
		
		<div style="height:40px" aria-hidden="true"></div>

		<h1 class="rsvp-page-title">RSVP for <?php echo esc_html($event->post_title); ?></h1>

		<?php if (has_post_thumbnail($event_id)) : ?>
			<div class="rsvp-featured-image">
				<?php echo get_the_post_thumbnail($event_id, 'large', array('class' => 'rsvp-event-image')); ?>
			</div>
		<?php endif; ?>

		<div style="height:40px" aria-hidden="true"></div>

		<?php if ($rsvp_message) : ?>
			<div class="rsvp-notification rsvp-<?php echo esc_attr($rsvp_status); ?>">
				<?php echo esc_html($rsvp_message); ?>
			</div>
			<div style="height:20px" aria-hidden="true"></div>
		<?php endif; ?>

		<div class="rsvp-content-layout">
			
			<div class="rsvp-form-column">
				
				<div class="event-info-section">
					<h2>Event Information</h2>
					<div class="event-summary">
						<?php if ($event_date) : ?>
							<p class="event-info-item">
								<strong>📅 Date:</strong> <?php echo esc_html(date('F j, Y', strtotime($event_date))); ?>
							</p>
						<?php endif; ?>
						
						<?php if ($venue_address) : ?>
							<p class="event-info-item">
								<strong>📍 Location:</strong> <?php echo esc_html($venue_address); ?>
							</p>
						<?php endif; ?>
						
						<?php if ($available_spots >= 0) : ?>
							<p class="event-info-item">
								<strong>👥 Available Spots:</strong> <?php echo esc_html($available_spots); ?>
							</p>
						<?php endif; ?>
					</div>
					
					<?php if ($event->post_excerpt) : ?>
						<div class="event-excerpt">
							<?php echo wpautop($event->post_excerpt); ?>
						</div>
					<?php endif; ?>
				</div>

				<div style="height:40px" aria-hidden="true"></div>

				<hr class="content-divider"/>

				<div style="height:40px" aria-hidden="true"></div>

				<h2 class="form-section-heading">Confirm Your Attendance</h2>
				<p>Fill out the form below to RSVP for this event. You'll receive a confirmation email with your unique QR code for check-in at the event.</p>

				<div style="height:30px" aria-hidden="true"></div>

				<?php if (!$is_full) : ?>
					<div class="rsvp-form-container">
						<div class="rsvp-form-wrapper">
							<form class="event-rsvp-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
								<div class="form-row">
									<div class="form-field">
										<label for="attendee-first-name">First Name <span class="required">*</span></label>
										<div class="input-with-icon">
											<span class="input-icon">👤</span>
											<input type="text" id="attendee-first-name" name="attendee-first-name" placeholder="Enter your first name" required>
										</div>
									</div>
									
									<div class="form-field">
										<label for="attendee-last-name">Last Name <span class="required">*</span></label>
										<div class="input-with-icon">
											<span class="input-icon">👤</span>
											<input type="text" id="attendee-last-name" name="attendee-last-name" placeholder="Enter your last name" required>
										</div>
									</div>
								</div>
								
								<div class="form-row">
									<div class="form-field">
										<label for="attendee-email">Email Address <span class="required">*</span></label>
										<div class="input-with-icon">
											<span class="input-icon">📧</span>
											<input type="email" id="attendee-email" name="attendee-email" placeholder="your.email@example.com" required>
										</div>
									</div>
									
									<div class="form-field">
										<label for="attendee-phone">Phone Number</label>
										<div class="input-with-icon">
											<span class="input-icon">📱</span>
											<input type="tel" id="attendee-phone" name="attendee-phone" placeholder="+1 (555) 123-4567">
										</div>
									</div>
								</div>
								
								<div class="form-row">
									<div class="form-field" style="grid-column: 1 / -1;">
										<label for="rsvp-status">RSVP Status <span class="required">*</span></label>
										<select id="rsvp-status" name="rsvp-status" required>
											<option value="">Select your response</option>
											<option value="yes">✓ Yes, I'll attend</option>
											<option value="maybe">? Maybe</option>
											<option value="no">✗ No, I can't attend</option>
										</select>
									</div>
								</div>
								
								<input type="hidden" name="action" value="event_rsvp_submit">
								<input type="hidden" name="event-id" value="<?php echo esc_attr($event_id); ?>">
								<?php wp_nonce_field('event_rsvp_submit', 'event_rsvp_nonce'); ?>
								
								<div class="form-actions">
									<button type="submit" class="rsvp-submit-button">
										<span class="button-icon">✓</span>
										<span class="button-text">Submit RSVP</span>
									</button>
								</div>

								<p class="form-privacy-note">
									<small>🔒 Your information is secure and will only be used for this event.</small>
								</p>
							</form>

							<div class="rsvp-success-message" style="display: none;">
								<div class="success-icon">✓</div>
								<h3>RSVP Confirmed!</h3>
								<p>Thank you for registering. Check your email for your confirmation and QR code.</p>
							</div>
						</div>
					</div>
				<?php else : ?>
					<div class="event-full-notice">
						<div class="full-icon">🚫</div>
						<h3>Event at Full Capacity</h3>
						<p>We're sorry, but this event has reached its maximum capacity. Please check back later for cancellations or contact the event organizer.</p>
					</div>
				<?php endif; ?>

			</div>

			<div class="rsvp-info-column">
				
				<div class="rsvp-info-card sticky-sidebar">
					<h3 class="info-heading">What to Expect</h3>
					
					<div style="height:20px" aria-hidden="true"></div>

					<div class="rsvp-steps">
						<div class="step-item">
							<div class="step-number">1</div>
							<div class="step-content">
								<h4>Fill the Form</h4>
								<p>Provide your contact details and confirm your attendance.</p>
							</div>
						</div>

						<div class="step-item">
							<div class="step-number">2</div>
							<div class="step-content">
								<h4>Get Confirmation</h4>
								<p>You'll receive an email with your RSVP confirmation.</p>
							</div>
						</div>

						<div class="step-item">
							<div class="step-number">3</div>
							<div class="step-content">
								<h4>Receive QR Code</h4>
								<p>Your unique QR code will be emailed closer to the event date.</p>
							</div>
						</div>

						<div class="step-item">
							<div class="step-number">4</div>
							<div class="step-content">
								<h4>Attend Event</h4>
								<p>Present your QR code at check-in for quick entry.</p>
							</div>
						</div>
					</div>
				</div>

				<div style="height:30px" aria-hidden="true"></div>

				<div class="rsvp-help-card">
					<h4>Need Help?</h4>
					<p>If you have any questions about the event or RSVP process, please contact us.</p>
					<a href="<?php echo esc_url(get_permalink($event_id)); ?>" class="help-button">
						View Event Details
					</a>
				</div>

			</div>

		</div>

		<div style="height:60px" aria-hidden="true"></div>

	</div>

</main>

<?php get_footer(); ?>

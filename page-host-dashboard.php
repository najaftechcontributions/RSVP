<?php
/**
 * Template Name: Host Dashboard
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

$current_user = wp_get_current_user();
$user_id = get_current_user_id();
$all_user_events = event_rsvp_get_user_events($user_id);

$today = date('Y-m-d');
$user_events = array();
$past_events = array();

foreach ($all_user_events as $event) {
	$event_date = get_field('event_date', $event->ID);
	if ($event_date && strtotime($event_date) < strtotime($today)) {
		$past_events[] = $event;
	} else {
		$user_events[] = $event;
	}
}
?>

<main id="primary" class="site-main host-dashboard-page">
	<div class="container">
		
		<div style="height:40px" aria-hidden="true"></div>

		<div class="dashboard-header">
			<div class="header-content">
				<div class="header-text">
					<h1 class="dashboard-title">Event Host Dashboard</h1>
					<p class="dashboard-subtitle">Welcome back, <strong><?php echo esc_html($current_user->display_name); ?></strong>! Manage your events and track attendees from here.</p>
				</div>
				<div class="header-actions">
					<a href="<?php echo esc_url(home_url('/event-create/')); ?>" class="create-event-button">
						+ Create New Event
					</a>
				</div>
			</div>
		</div>

		<?php if (isset($_GET['event_created']) && $_GET['event_created'] === 'success') : ?>
			<div class="success-notice">
				✓ Event created successfully!
			</div>
		<?php endif; ?>

		<div style="height:40px" aria-hidden="true"></div>

		<h2 class="section-heading">Upcoming Events (<?php echo count($user_events); ?>)</h2>

		<div style="height:20px" aria-hidden="true"></div>

		<?php if (!empty($user_events)) : ?>
			<div class="dashboard-events-grid">
				<?php foreach ($user_events as $event) :
					$event_id = $event->ID;
					$event_date = get_field('event_date', $event_id);
					$event_end_date = get_field('event_end_date', $event_id);
					$venue_address = get_field('venue_address', $event_id);
					$max_attendees = get_field('max_attendees', $event_id);
					$visibility = get_field('visibility', $event_id);
					
					$stats = event_rsvp_get_event_stats($event_id);
					$available_spots = event_rsvp_get_available_spots($event_id);
					$attendees = event_rsvp_get_attendees_by_event($event_id);
				?>
					<article class="dashboard-event-card upcoming-event">
						<div class="event-card-header">
							<?php if (has_post_thumbnail($event_id)) : ?>
								<?php echo get_the_post_thumbnail($event_id, 'medium', array('class' => 'event-card-thumbnail')); ?>
							<?php else : ?>
								<div class="event-card-no-image">
									<span class="no-image-icon">📅</span>
								</div>
							<?php endif; ?>
							
							<?php if ($visibility === 'private') : ?>
								<span class="event-badge event-badge-private">🔒 Private</span>
							<?php else : ?>
								<span class="event-badge event-badge-public">🌐 Public</span>
							<?php endif; ?>
						</div>
						
						<div class="event-card-body">
							<h3 class="event-card-title">
								<a href="<?php echo get_permalink($event_id); ?>"><?php echo get_the_title($event_id); ?></a>
							</h3>
							
							<div class="event-card-details">
								<?php if ($event_date) : ?>
									<p class="event-detail">
										<span class="detail-icon">📅</span>
										<span class="detail-text"><?php echo esc_html(date('F j, Y', strtotime($event_date))); ?></span>
									</p>
								<?php endif; ?>
								
								<?php if ($venue_address) : ?>
									<p class="event-detail">
										<span class="detail-icon">📍</span>
										<span class="detail-text"><?php echo esc_html($venue_address); ?></span>
									</p>
								<?php endif; ?>
							</div>
							
							<div class="event-card-stats">
								<div class="stat-item">
									<span class="stat-number"><?php echo $stats['total']; ?></span>
									<span class="stat-label">Total RSVPs</span>
								</div>
								<div class="stat-item">
									<span class="stat-number"><?php echo $stats['checked_in']; ?></span>
									<span class="stat-label">Checked In</span>
								</div>
								<?php if ($max_attendees && $available_spots >= 0) : ?>
									<div class="stat-item">
										<span class="stat-number"><?php echo $available_spots; ?></span>
										<span class="stat-label">Spots Left</span>
									</div>
								<?php endif; ?>
							</div>

							<?php if (!empty($attendees)) : ?>
								<div class="event-attendees-section">
									<h4 class="attendees-section-title">
										<span class="attendees-icon">👥</span>
										Attendees (<?php echo count($attendees); ?>)
									</h4>
									<div class="attendees-table-wrapper">
										<table class="attendees-table">
											<thead>
												<tr>
													<th>Name</th>
													<th>Email</th>
													<th>Phone</th>
													<th>Status</th>
													<th>QR Code</th>
												</tr>
											</thead>
											<tbody>
												<?php foreach ($attendees as $attendee) : 
													$attendee_email = get_post_meta($attendee->ID, 'attendee_email', true);
													$attendee_phone = get_post_meta($attendee->ID, 'attendee_phone', true);
													$checkin_status = get_post_meta($attendee->ID, 'checkin_status', true);
													$qr_data = get_post_meta($attendee->ID, 'qr_data', true);
													$qr_viewer_url = home_url('/qr-view/?qr=' . urlencode($qr_data));
												?>
													<tr>
														<td class="attendee-name"><?php echo esc_html(get_the_title($attendee->ID)); ?></td>
														<td class="attendee-email"><?php echo esc_html($attendee_email); ?></td>
														<td class="attendee-phone"><?php echo esc_html($attendee_phone ?: 'N/A'); ?></td>
														<td class="attendee-status">
															<?php if ($checkin_status) : ?>
																<span class="status-badge checked-in">✓ Checked In</span>
															<?php else : ?>
																<span class="status-badge pending">⏳ Pending</span>
															<?php endif; ?>
														</td>
														<td class="attendee-qr-actions">
															<a href="<?php echo esc_url($qr_viewer_url); ?>" target="_blank" class="qr-action-button qr-view" title="View QR Code">
																<span>👁️</span>
															</a>
															<a href="<?php echo esc_url($qr_viewer_url); ?>" download class="qr-action-button qr-download" title="Download QR Code">
																<span>📥</span>
															</a>
															<button type="button" class="qr-action-button qr-email" data-attendee-id="<?php echo $attendee->ID; ?>" title="Send QR Code Email">
																<span>📧</span>
															</button>
														</td>
													</tr>
												<?php endforeach; ?>
											</tbody>
										</table>
									</div>
								</div>
							<?php endif; ?>
						</div>
						
						<div class="event-card-actions">
							<a href="<?php echo get_permalink($event_id); ?>" class="action-button action-view">
								<span>👁️</span> View
							</a>
							<a href="<?php echo esc_url(home_url('/event-create/?event_id=' . $event_id)); ?>" class="action-button action-edit">
								<span>✏️</span> Edit
							</a>
							<a href="<?php echo admin_url('?action=export_attendees&event_id=' . $event_id); ?>" class="action-button action-export">
								<span>📥</span> Export
							</a>
							<a href="<?php echo esc_url(home_url('/check-in/?event_id=' . $event_id)); ?>" class="action-button action-checkin">
								<span>✓</span> Check-In
							</a>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<div class="no-events-state">
				<div class="no-events-icon">📅</div>
				<h3 class="no-events-title">No Upcoming Events</h3>
				<p class="no-events-text">You don't have any upcoming events. Create a new event to get started!</p>
				<a href="<?php echo esc_url(home_url('/event-create/')); ?>" class="create-first-event-button">
					+ Create New Event
				</a>
			</div>
		<?php endif; ?>

		<?php if (!empty($past_events)) : ?>
			<div style="height:60px" aria-hidden="true"></div>

			<h2 class="section-heading">Past Events (<?php echo count($past_events); ?>)</h2>

			<div style="height:20px" aria-hidden="true"></div>

			<div class="dashboard-events-grid past-events-grid">
				<?php foreach ($past_events as $event) :
					$event_id = $event->ID;
					$event_date = get_field('event_date', $event_id);
					$event_end_date = get_field('event_end_date', $event_id);
					$venue_address = get_field('venue_address', $event_id);
					$max_attendees = get_field('max_attendees', $event_id);
					$visibility = get_field('visibility', $event_id);
					
					$stats = event_rsvp_get_event_stats($event_id);
					$available_spots = event_rsvp_get_available_spots($event_id);
				?>
					<article class="dashboard-event-card past-event">
						<div class="event-card-header">
							<?php if (has_post_thumbnail($event_id)) : ?>
								<?php echo get_the_post_thumbnail($event_id, 'medium', array('class' => 'event-card-thumbnail')); ?>
							<?php else : ?>
								<div class="event-card-no-image">
									<span class="no-image-icon">📅</span>
								</div>
							<?php endif; ?>
							
							<span class="event-badge event-badge-past">Past Event</span>
						</div>
						
						<div class="event-card-body">
							<h3 class="event-card-title">
								<a href="<?php echo get_permalink($event_id); ?>"><?php echo get_the_title($event_id); ?></a>
							</h3>
							
							<div class="event-card-details">
								<?php if ($event_date) : ?>
									<p class="event-detail">
										<span class="detail-icon">📅</span>
										<span class="detail-text"><?php echo esc_html(date('F j, Y', strtotime($event_date))); ?></span>
									</p>
								<?php endif; ?>
								
								<?php if ($venue_address) : ?>
									<p class="event-detail">
										<span class="detail-icon">📍</span>
										<span class="detail-text"><?php echo esc_html($venue_address); ?></span>
									</p>
								<?php endif; ?>
							</div>
							
							<div class="event-card-stats">
								<div class="stat-item">
									<span class="stat-number"><?php echo $stats['total']; ?></span>
									<span class="stat-label">Total RSVPs</span>
								</div>
								<div class="stat-item">
									<span class="stat-number"><?php echo $stats['checked_in']; ?></span>
									<span class="stat-label">Checked In</span>
								</div>
								<?php if ($stats['total'] > 0) : ?>
									<div class="stat-item">
										<span class="stat-number"><?php echo $stats['percentage']; ?>%</span>
										<span class="stat-label">Attendance</span>
									</div>
								<?php endif; ?>
							</div>
						</div>
						
						<div class="event-card-actions">
							<a href="<?php echo get_permalink($event_id); ?>" class="action-button action-view">
								👁️ View
							</a>
							<a href="<?php echo admin_url('?action=export_attendees&event_id=' . $event_id); ?>" class="action-button action-export">
								📥 Export
							</a>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div style="height:60px" aria-hidden="true"></div>

	</div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const emailButtons = document.querySelectorAll('.qr-email');
	
	emailButtons.forEach(button => {
		button.addEventListener('click', function() {
			const attendeeId = this.getAttribute('data-attendee-id');
			
			if (!attendeeId) {
				alert('Error: Attendee ID not found');
				return;
			}

			if (!confirm('Send QR code email to this attendee?')) {
				return;
			}

			this.disabled = true;
			this.innerHTML = '<span>⏳</span>';

			fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams({
					action: 'event_rsvp_resend_qr_email',
					attendee_id: attendeeId,
					nonce: '<?php echo wp_create_nonce('event_rsvp_resend_qr'); ?>'
				})
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					alert('QR code email sent successfully!');
					this.innerHTML = '<span>✓</span>';
					setTimeout(() => {
						this.innerHTML = '<span>📧</span>';
						this.disabled = false;
					}, 2000);
				} else {
					alert('Error: ' + (data.data || 'Failed to send email'));
					this.innerHTML = '<span>📧</span>';
					this.disabled = false;
				}
			})
			.catch(error => {
				alert('Error sending email: ' + error);
				this.innerHTML = '<span>📧</span>';
				this.disabled = false;
			});
		});
	});
});
</script>

<?php get_footer(); ?>

<?php
/**
 * Template for displaying single events
 *
 * @package RSVP
 */

get_header();

while ( have_posts() ) :
	the_post();
	
	$event_id = get_the_ID();
	$event_date = get_field('event_date');
	$event_end_date = get_field('event_end_date');
	$venue_address = get_field('venue_address');
	$venue_map_url = get_field('venue_map_url');
	$event_hashtag = get_field('event_hashtag');
	$social_links = get_field('social_links');
	$visibility = get_field('visibility');
	$max_attendees = get_field('max_attendees');
	$qr_schedule_days = get_field('qr_schedule_days');
	
	$stats = event_rsvp_get_event_stats($event_id);
	$available_spots = event_rsvp_get_available_spots($event_id);
	$is_full = event_rsvp_is_event_full($event_id);
	
	$formatted_date = $event_date ? date('F j, Y', strtotime($event_date)) : '';
	$formatted_time = $event_date ? date('g:i A', strtotime($event_date)) : '';
	$formatted_end_date = $event_end_date ? date('F j, Y', strtotime($event_end_date)) : '';
	
	$author_id = get_the_author_meta('ID');
	$author_name = get_the_author_meta('display_name');
	?>

	<main id="primary" class="site-main single-event-page">
		<div class="container">
			
			<div style="height:40px" aria-hidden="true"></div>

			<?php if (isset($_GET['rsvp']) && $_GET['rsvp'] === 'success') : ?>
				<div class="success-notice">
					✓ RSVP submitted successfully! Check your email for confirmation.
				</div>
				<div style="height:20px" aria-hidden="true"></div>
			<?php endif; ?>

			<?php if (isset($_GET['rsvp']) && $_GET['rsvp'] === 'full') : ?>
				<div class="error-notice">
					⚠ Sorry, this event is at full capacity.
				</div>
				<div style="height:20px" aria-hidden="true"></div>
			<?php endif; ?>

			<div class="event-single-layout">
				
				<div class="event-main-content">
					
					<?php if (has_post_thumbnail()) : ?>
						<div class="event-featured-image">
							<?php the_post_thumbnail('large'); ?>
						</div>
					<?php endif; ?>

					<div class="event-header">
						<h1 class="event-title"><?php the_title(); ?></h1>
						
						<div class="event-meta">
							<span class="event-meta-item">
								📅 <?php echo esc_html($formatted_date); ?>
								<?php if ($formatted_time) : ?>
									at <?php echo esc_html($formatted_time); ?>
								<?php endif; ?>
							</span>
							
							<?php if ($venue_address) : ?>
								<span class="event-meta-item">
									📍 <?php echo esc_html($venue_address); ?>
								</span>
							<?php endif; ?>
							
							<span class="event-meta-item">
								👤 Hosted by <?php echo esc_html($author_name); ?>
							</span>
						</div>

						<?php if ($event_hashtag) : ?>
							<div class="event-hashtag">
								#<?php echo esc_html($event_hashtag); ?>
							</div>
						<?php endif; ?>
					</div>

					<div class="event-content">
						<?php the_content(); ?>
					</div>

					<?php if ($social_links && is_array($social_links)) : ?>
						<div class="event-social-links">
							<h3>Share This Event</h3>
							<div class="social-links-list">
								<?php foreach ($social_links as $link) : ?>
									<?php if (!empty($link['platform']) && !empty($link['url'])) : ?>
										<a href="<?php echo esc_url($link['url']); ?>" target="_blank" rel="noopener" class="social-link">
											<?php echo esc_html($link['platform']); ?>
										</a>
									<?php endif; ?>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>

					<?php if ($venue_map_url) : ?>
						<div class="event-map-section">
							<h3>Event Location</h3>
							<div class="map-embed">
								<iframe src="<?php echo esc_url($venue_map_url); ?>" width="100%" height="400" style="border:0; border-radius: var(--event-radius);" allowfullscreen="" loading="lazy"></iframe>
							</div>
						</div>
					<?php endif; ?>

				</div>

				<aside class="event-sidebar">
					
					<div class="event-stats-card">
						<h3>Event Stats</h3>
						<div class="stat-item">
							<span class="stat-label">Total RSVPs</span>
							<span class="stat-value"><?php echo esc_html($stats['total']); ?></span>
						</div>
						<?php if ($max_attendees) : ?>
							<div class="stat-item">
								<span class="stat-label">Capacity</span>
								<span class="stat-value"><?php echo esc_html($max_attendees); ?></span>
							</div>
							<div class="stat-item">
								<span class="stat-label">Available Spots</span>
								<span class="stat-value <?php echo $is_full ? 'stat-full' : ''; ?>">
									<?php echo $is_full ? 'FULL' : esc_html($available_spots); ?>
								</span>
							</div>
						<?php endif; ?>
						<div class="stat-item">
							<span class="stat-label">Checked In</span>
							<span class="stat-value"><?php echo esc_html($stats['checked_in']); ?></span>
						</div>
					</div>

					<div class="rsvp-card">
						<h3>RSVP for This Event</h3>
						
						<?php if ($is_full) : ?>
							<div class="rsvp-full-message">
								⚠ This event is at full capacity. Please check back later for cancellations.
							</div>
						<?php elseif (!is_user_logged_in()) : ?>
							<p class="rsvp-subtitle">Join us for this amazing event!</p>
							
							<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="event-rsvp-form">
								<?php wp_nonce_field('event_rsvp_submit', 'event_rsvp_nonce'); ?>
								<input type="hidden" name="action" value="event_rsvp_submit">
								<input type="hidden" name="event-id" value="<?php echo esc_attr($event_id); ?>">
								
								<div class="form-group">
									<label for="attendee-name">Full Name *</label>
									<input type="text" id="attendee-name" name="attendee-name" required>
								</div>
								
								<div class="form-group">
									<label for="attendee-email">Email Address *</label>
									<input type="email" id="attendee-email" name="attendee-email" required>
								</div>
								
								<div class="form-group">
									<label for="attendee-phone">Phone Number</label>
									<input type="tel" id="attendee-phone" name="attendee-phone">
								</div>
								
								<div class="form-group">
									<label for="rsvp-status">RSVP Status *</label>
									<select id="rsvp-status" name="rsvp-status" required>
										<option value="yes">Yes, I'll attend</option>
										<option value="maybe">Maybe</option>
										<option value="no">No, I can't make it</option>
									</select>
								</div>
								
								<button type="submit" class="rsvp-submit-button">
									Submit RSVP
								</button>
								
								<p class="form-note">You'll receive a confirmation email with your QR code for check-in.</p>
							</form>
						<?php else : ?>
							<p class="rsvp-subtitle">You're logged in! Submit your RSVP below.</p>
							
							<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="event-rsvp-form">
								<?php wp_nonce_field('event_rsvp_submit', 'event_rsvp_nonce'); ?>
								<input type="hidden" name="action" value="event_rsvp_submit">
								<input type="hidden" name="event-id" value="<?php echo esc_attr($event_id); ?>">
								
								<?php
								$current_user = wp_get_current_user();
								$user_name = $current_user->display_name ?: $current_user->user_login;
								$user_email = $current_user->user_email;
								?>
								
								<div class="form-group">
									<label for="attendee-name">Full Name *</label>
									<input type="text" id="attendee-name" name="attendee-name" value="<?php echo esc_attr($user_name); ?>" required>
								</div>
								
								<div class="form-group">
									<label for="attendee-email">Email Address *</label>
									<input type="email" id="attendee-email" name="attendee-email" value="<?php echo esc_attr($user_email); ?>" required readonly>
								</div>
								
								<div class="form-group">
									<label for="attendee-phone">Phone Number</label>
									<input type="tel" id="attendee-phone" name="attendee-phone">
								</div>
								
								<div class="form-group">
									<label for="rsvp-status">RSVP Status *</label>
									<select id="rsvp-status" name="rsvp-status" required>
										<option value="yes">Yes, I'll attend</option>
										<option value="maybe">Maybe</option>
										<option value="no">No, I can't make it</option>
									</select>
								</div>
								
								<button type="submit" class="rsvp-submit-button">
									Submit RSVP
								</button>
								
								<p class="form-note">You'll receive a confirmation email with your QR code for check-in.</p>
							</form>
						<?php endif; ?>
					</div>

					<?php if (is_user_logged_in() && (get_current_user_id() == $author_id || current_user_can('administrator'))) : ?>
						<div class="event-admin-actions">
							<h4>Event Management</h4>
							<a href="<?php echo esc_url(home_url('/event-create/?event_id=' . $event_id)); ?>" class="admin-action-button">
								✏️ Edit Event
							</a>
							<a href="<?php echo esc_url(home_url('/check-in/?event_id=' . $event_id)); ?>" class="admin-action-button">
								✓ Check-In Page
							</a>
							<a href="<?php echo esc_url(add_query_arg('action', 'export_attendees', add_query_arg('event_id', $event_id, home_url()))); ?>" class="admin-action-button">
								📥 Export Attendees
							</a>
						</div>

						<div class="event-checked-in-list">
							<div class="checked-in-header">
								<h4>Checked-In Attendees</h4>
								<button type="button" id="refresh-checked-in" class="refresh-mini-button" title="Refresh list">🔄</button>
							</div>
							<div id="event-checked-in-attendees" class="checked-in-attendees-mini" data-event-id="<?php echo esc_attr($event_id); ?>">
								<div class="loading-mini">Loading...</div>
							</div>
						</div>
					<?php endif; ?>

				</aside>

			</div>

			<div style="height:60px" aria-hidden="true"></div>

		</div>
	</main>

<?php
endwhile;

get_footer();

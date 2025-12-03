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

	if (!function_exists('get_field')) {
		$event_host = get_post_meta($event_id, 'event_host', true);
		$event_date = get_post_meta($event_id, 'event_date', true);
		$event_end_date = get_post_meta($event_id, 'event_end_date', true);
		$venue_address = get_post_meta($event_id, 'venue_address', true);
		$venue_map_url = get_post_meta($event_id, 'venue_map_url', true);
		$event_hashtag = get_post_meta($event_id, 'event_hashtag', true);
		$event_hashtags = get_post_meta($event_id, 'event_hashtags', true);
		$social_facebook = get_post_meta($event_id, 'social_facebook', true);
		$social_twitter = get_post_meta($event_id, 'social_twitter', true);
		$social_instagram = get_post_meta($event_id, 'social_instagram', true);
		$social_linkedin = get_post_meta($event_id, 'social_linkedin', true);
		$social_youtube = get_post_meta($event_id, 'social_youtube', true);
		$social_website = get_post_meta($event_id, 'social_website', true);
		$visibility = get_post_meta($event_id, 'visibility', true);
		$max_attendees = get_post_meta($event_id, 'max_attendees', true);
		$qr_schedule_days = get_post_meta($event_id, 'qr_schedule_days', true);
	} else {
		$event_host = get_field('event_host');
		$event_date = get_field('event_date');
		$event_end_date = get_field('event_end_date');
		$venue_address = get_field('venue_address');
		$venue_map_url = get_field('venue_map_url');
		$event_hashtag = get_field('event_hashtag');
		$event_hashtags = get_field('event_hashtags');
		$social_facebook = get_field('social_facebook');
		$social_twitter = get_field('social_twitter');
		$social_instagram = get_field('social_instagram');
		$social_linkedin = get_field('social_linkedin');
		$social_youtube = get_field('social_youtube');
		$social_website = get_field('social_website');
		$visibility = get_field('visibility');
		$max_attendees = get_field('max_attendees');
		$qr_schedule_days = get_field('qr_schedule_days');
	}
	
	$stats = event_rsvp_get_event_stats($event_id);
	$available_spots = event_rsvp_get_available_spots($event_id);
	$is_full = event_rsvp_is_event_full($event_id);
	$is_past = event_rsvp_is_event_past($event_id);
	
	$formatted_date = $event_date ? date('F j, Y', strtotime($event_date)) : '';
	$formatted_time = $event_date ? date('g:i A', strtotime($event_date)) : '';
	$formatted_end_date = $event_end_date ? date('F j, Y', strtotime($event_end_date)) : '';
	
	$author_id = get_the_author_meta('ID');
	$author_name = get_the_author_meta('display_name');

	$display_host = $event_host;
	$is_event_host = is_user_logged_in() && (get_current_user_id() == $author_id || current_user_can('administrator'));
	?>

	<main id="primary" class="site-main single-event-page">
		<div class="container">
			
			<div style="height:40px" aria-hidden="true"></div>

			<?php if (isset($_GET['rsvp']) && $_GET['rsvp'] === 'success') : ?>
				<div class="success-notice">
					✓ RSVP submitted successfully!
					<?php if (isset($_GET['email']) && $_GET['email'] === 'sent') : ?>
						<br>📧 Confirmation email with QR code sent successfully. Please check your inbox.
					<?php elseif (isset($_GET['email']) && $_GET['email'] === 'failed') : ?>
						<br>⚠️ RSVP recorded but email failed to send. Please contact the event organizer.
					<?php else : ?>
						<br>Check your email for confirmation.
					<?php endif; ?>
				</div>
				<div style="height:20px" aria-hidden="true"></div>
			<?php endif; ?>

			<?php if (isset($_GET['rsvp']) && $_GET['rsvp'] === 'past') : ?>
				<div class="error-notice">
					⚠ Sorry, this event has already passed. RSVPs are no longer accepted.
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
							
							<?php if (!empty($display_host)) : ?>
								<span class="event-meta-item">
									👤 Hosted by <?php echo esc_html($display_host); ?>
								</span>
							<?php endif; ?>
						</div>

						<?php
						// Process hashtags from textarea (comma or newline separated)
						$hashtags_array = array();
						if (!empty($event_hashtags)) {
							// Split by newlines or commas
							$hashtags_raw = preg_split('/[\r\n,]+/', $event_hashtags);
							foreach ($hashtags_raw as $tag) {
								$tag = trim($tag);
								$tag = ltrim($tag, '#'); // Remove # if user added it
								if (!empty($tag)) {
									$hashtags_array[] = $tag;
								}
							}
						}
						// Fallback to legacy single hashtag field
						 if (empty($hashtags_array) && !empty($event_hashtag)) {
							$hashtags_array[] = $event_hashtag;
						}

						if (!empty($hashtags_array)) : ?>
							<div class="event-hashtags">
								<?php foreach ($hashtags_array as $tag) : ?>
									<span class="event-hashtag-item">
										#<?php echo esc_html($tag); ?>
									</span>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>

					<div class="event-content">
						<?php the_content(); ?>
					</div>

					<?php
					// Build social links array from individual fields
					$social_links_data = array();
					if (!empty($social_facebook)) {
						$social_links_data[] = array('platform' => 'Facebook', 'url' => $social_facebook, 'icon' => '📘');
					}
					if (!empty($social_twitter)) {
						$social_links_data[] = array('platform' => 'Twitter', 'url' => $social_twitter, 'icon' => '🐦');
					}
					if (!empty($social_instagram)) {
						$social_links_data[] = array('platform' => 'Instagram', 'url' => $social_instagram, 'icon' => '📸');
					}
					if (!empty($social_linkedin)) {
						$social_links_data[] = array('platform' => 'LinkedIn', 'url' => $social_linkedin, 'icon' => '💼');
					}
					if (!empty($social_youtube)) {
						$social_links_data[] = array('platform' => 'YouTube', 'url' => $social_youtube, 'icon' => '📺');
					}
					if (!empty($social_website)) {
						$social_links_data[] = array('platform' => 'Website', 'url' => $social_website, 'icon' => '🌐');
					}

					if (!empty($social_links_data)) : ?>
						<div class="event-social-links">
							<h3>Share This Event</h3>
							<div class="social-links-list">
								<?php foreach ($social_links_data as $link) : ?>
									<a href="<?php echo esc_url($link['url']); ?>" target="_blank" rel="noopener" class="social-link social-link-<?php echo esc_attr(strtolower($link['platform'])); ?>">
										<?php echo $link['icon'] . ' '; ?>
										<?php echo esc_html($link['platform']); ?>
									</a>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>

					<?php if ($venue_map_url) :
						$map_src = $venue_map_url;
						if (strpos($venue_map_url, '<iframe') !== false) {
							preg_match('/src=["\']([^"\']+)["\']/', $venue_map_url, $matches);
							if (!empty($matches[1])) {
								$map_src = $matches[1];
							}
						}
					?>
						<div class="event-map-section">
							<h3>Event Location</h3>
							<div class="map-embed">
								<iframe src="<?php echo esc_url($map_src); ?>" width="100%" height="400" style="border:0; border-radius: var(--event-radius);" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
							</div>
						</div>
					<?php endif; ?>

					<?php if (is_user_logged_in() && (get_current_user_id() == $author_id || current_user_can('administrator'))) : ?>
						<div class="event-attendees-section">
							<div class="attendees-header">
								<h3>Event Attendees</h3>
								<p class="attendees-subtitle">View and manage all attendees for this event</p>
							</div>

							<?php
							$attendees = event_rsvp_get_attendees_by_event($event_id);
							$attendees_yes = array_filter($attendees, function($att) {
								return get_post_meta($att->ID, 'rsvp_status', true) === 'yes';
							});
							$attendees_maybe = array_filter($attendees, function($att) {
								return get_post_meta($att->ID, 'rsvp_status', true) === 'maybe';
							});
							$attendees_no = array_filter($attendees, function($att) {
								return get_post_meta($att->ID, 'rsvp_status', true) === 'no';
							});
							?>

							<div class="attendees-tabs">
								<button class="attendee-tab-btn active" data-tab="all">All (<?php echo count($attendees); ?>)</button>
								<button class="attendee-tab-btn" data-tab="yes">Attending (<?php echo count($attendees_yes); ?>)</button>
								<button class="attendee-tab-btn" data-tab="maybe">Maybe (<?php echo count($attendees_maybe); ?>)</button>
								<button class="attendee-tab-btn" data-tab="no">Not Attending (<?php echo count($attendees_no); ?>)</button>
							</div>

							<div class="attendees-list-container">
								<div class="attendee-tab-content active" id="tab-all">
									<?php if (!empty($attendees)) : ?>
										<div class="attendees-grid">
											<?php foreach ($attendees as $attendee) :
												$email = get_post_meta($attendee->ID, 'attendee_email', true);
												$phone = get_post_meta($attendee->ID, 'attendee_phone', true);
												$rsvp_status = get_post_meta($attendee->ID, 'rsvp_status', true);
												$checked_in = get_post_meta($attendee->ID, 'checkin_status', true);
												$checkin_time = get_post_meta($attendee->ID, 'checkin_time', true);
												$qr_data = get_post_meta($attendee->ID, 'qr_data', true);
												$email_sent = get_post_meta($attendee->ID, 'email_sent', true);
												$email_sent_time = get_post_meta($attendee->ID, 'email_sent_time', true);
												?>
												<div class="attendee-card" data-attendee-id="<?php echo esc_attr($attendee->ID); ?>">
													<div class="attendee-info">
														<h4 class="attendee-name"><?php echo esc_html(get_the_title($attendee->ID)); ?></h4>
														<p class="attendee-email">📧 <?php echo esc_html($email); ?></p>
														<?php if (!empty($phone)) : ?>
															<p class="attendee-phone">📱 <?php echo esc_html($phone); ?></p>
														<?php endif; ?>
													</div>
													<div class="attendee-meta">
														<span class="attendee-status status-<?php echo esc_attr($rsvp_status); ?>">
															<?php
															if ($rsvp_status === 'yes') {
																echo 'Attending';
															} elseif ($rsvp_status === 'maybe') {
																echo '? Maybe';
															} else {
																echo '✗ Not Attending';
															}
															?>
														</span>
														<?php if ($checked_in) : ?>
															<span class="attendee-checked-in">
															✓ Checked In
																<?php if ($checkin_time) : ?>
																	<small>(<?php echo date('M j, g:i A', strtotime($checkin_time)); ?>)</small>
																<?php endif; ?>
															</span>
														<?php endif; ?>
													</div>
													<div class="attendee-email-status">
														<?php if ($email_sent) : ?>
															<span class="email-status-sent">
																✓ Email Sent
																<?php if ($email_sent_time) : ?>
																	<small>(<?php echo date('M j, g:i A', strtotime($email_sent_time)); ?>)</small>
																<?php endif; ?>
															</span>
														<?php else : ?>
															<span class="email-status-not-sent">⚠️ Email Not Sent</span>
														<?php endif; ?>
													</div>
													<div class="attendee-actions">
														<?php if (!empty($qr_data)) : ?>
															<a href="<?php echo esc_url(home_url('/qr-view/?qr=' . urlencode($qr_data))); ?>" target="_blank" class="attendee-action-btn view-qr-btn" title="View QR Code">
																<span class="btn-icon">🔍</span>
																<span class="btn-text">View QR</span>
															</a>
															<button type="button" class="attendee-action-btn download-qr-btn" data-qr-data="<?php echo esc_attr($qr_data); ?>" data-attendee-name="<?php echo esc_attr(get_the_title($attendee->ID)); ?>" title="Download QR Code">
																<span class="btn-icon">⬇️</span>
																<span class="btn-text">Download QR</span>
															</button>
														<?php endif; ?>
														<button type="button" class="attendee-action-btn send-email-btn" data-attendee-id="<?php echo esc_attr($attendee->ID); ?>" title="Send/Resend Email">
															<span class="btn-icon">📧</span>
															<span class="btn-text"><?php echo $email_sent ? 'Resend Email' : 'Send Email'; ?></span>
														</button>
													</div>
												</div>
											<?php endforeach; ?>
										</div>
									<?php else : ?>
										<div class="no-attendees">
											<p>No attendees yet.</p>
										</div>
									<?php endif; ?>
								</div>

								<div class="attendee-tab-content" id="tab-yes">
									<?php if (!empty($attendees_yes)) : ?>
										<div class="attendees-grid">
											<?php foreach ($attendees_yes as $attendee) :
												$email = get_post_meta($attendee->ID, 'attendee_email', true);
												$phone = get_post_meta($attendee->ID, 'attendee_phone', true);
												$checked_in = get_post_meta($attendee->ID, 'checkin_status', true);
												$checkin_time = get_post_meta($attendee->ID, 'checkin_time', true);
												$qr_data = get_post_meta($attendee->ID, 'qr_data', true);
												$email_sent = get_post_meta($attendee->ID, 'email_sent', true);
												$email_sent_time = get_post_meta($attendee->ID, 'email_sent_time', true);
												?>
												<div class="attendee-card" data-attendee-id="<?php echo esc_attr($attendee->ID); ?>">
													<div class="attendee-info">
														<h4 class="attendee-name"><?php echo esc_html(get_the_title($attendee->ID)); ?></h4>
														<p class="attendee-email">📧 <?php echo esc_html($email); ?></p>
														<?php if (!empty($phone)) : ?>
															<p class="attendee-phone">📱 <?php echo esc_html($phone); ?></p>
														<?php endif; ?>
													</div>
													<div class="attendee-meta">
														<span class="attendee-status status-yes">✓ Attending</span>
														<?php if ($checked_in) : ?>
															<span class="attendee-checked-in">
																✓ Checked In
																<?php if ($checkin_time) : ?>
																	<small>(<?php echo date('M j, g:i A', strtotime($checkin_time)); ?>)</small>
																<?php endif; ?>
															</span>
														<?php endif; ?>
													</div>
													<div class="attendee-email-status">
														<?php if ($email_sent) : ?>
															<span class="email-status-sent">
																✓ Email Sent
																<?php if ($email_sent_time) : ?>
																	<small>(<?php echo date('M j, g:i A', strtotime($email_sent_time)); ?>)</small>
																<?php endif; ?>
															</span>
														<?php else : ?>
															<span class="email-status-not-sent">⚠️ Email Not Sent</span>
														<?php endif; ?>
													</div>
													<div class="attendee-actions">
														<?php if (!empty($qr_data)) : ?>
															<a href="<?php echo esc_url(home_url('/qr-view/?qr=' . urlencode($qr_data))); ?>" target="_blank" class="attendee-action-btn view-qr-btn" title="View QR Code">
																<span class="btn-icon">🔍</span>
																<span class="btn-text">View QR</span>
															</a>
															<button type="button" class="attendee-action-btn download-qr-btn" data-qr-data="<?php echo esc_attr($qr_data); ?>" data-attendee-name="<?php echo esc_attr(get_the_title($attendee->ID)); ?>" title="Download QR Code">
																<span class="btn-icon">⬇️</span>
																<span class="btn-text">Download QR</span>
															</button>
														<?php endif; ?>
														<button type="button" class="attendee-action-btn send-email-btn" data-attendee-id="<?php echo esc_attr($attendee->ID); ?>" title="Send/Resend Email">
															<span class="btn-icon">📧</span>
															<span class="btn-text"><?php echo $email_sent ? 'Resend Email' : 'Send Email'; ?></span>
														</button>
													</div>
												</div>
											<?php endforeach; ?>
										</div>
									<?php else : ?>
										<div class="no-attendees">
											<p>No confirmed attendees yet.</p>
										</div>
									<?php endif; ?>
								</div>

								<div class="attendee-tab-content" id="tab-maybe">
									<?php if (!empty($attendees_maybe)) : ?>
										<div class="attendees-grid">
											<?php foreach ($attendees_maybe as $attendee) :
												$email = get_post_meta($attendee->ID, 'attendee_email', true);
												$phone = get_post_meta($attendee->ID, 'attendee_phone', true);
												$qr_data = get_post_meta($attendee->ID, 'qr_data', true);
												$email_sent = get_post_meta($attendee->ID, 'email_sent', true);
												$email_sent_time = get_post_meta($attendee->ID, 'email_sent_time', true);
												?>
												<div class="attendee-card" data-attendee-id="<?php echo esc_attr($attendee->ID); ?>">
													<div class="attendee-info">
														<h4 class="attendee-name"><?php echo esc_html(get_the_title($attendee->ID)); ?></h4>
														<p class="attendee-email">📧 <?php echo esc_html($email); ?></p>
														<?php if (!empty($phone)) : ?>
															<p class="attendee-phone">📱 <?php echo esc_html($phone); ?></p>
														<?php endif; ?>
													</div>
													<div class="attendee-meta">
														<span class="attendee-status status-maybe">? Maybe</span>
													</div>
													<div class="attendee-email-status">
														<?php if ($email_sent) : ?>
															<span class="email-status-sent">
																✓ Email Sent
																<?php if ($email_sent_time) : ?>
																	<small>(<?php echo date('M j, g:i A', strtotime($email_sent_time)); ?>)</small>
																<?php endif; ?>
															</span>
														<?php else : ?>
															<span class="email-status-not-sent">⚠️ Email Not Sent</span>
														<?php endif; ?>
													</div>
													<div class="attendee-actions">
														<?php if (!empty($qr_data)) : ?>
															<a href="<?php echo esc_url(home_url('/qr-view/?qr=' . urlencode($qr_data))); ?>" target="_blank" class="attendee-action-btn view-qr-btn" title="View QR Code">
																<span class="btn-icon">🔍</span>
																<span class="btn-text">View QR</span>
															</a>
															<button type="button" class="attendee-action-btn download-qr-btn" data-qr-data="<?php echo esc_attr($qr_data); ?>" data-attendee-name="<?php echo esc_attr(get_the_title($attendee->ID)); ?>" title="Download QR Code">
																<span class="btn-icon">⬇️</span>
																<span class="btn-text">Download QR</span>
															</button>
														<?php endif; ?>
														<button type="button" class="attendee-action-btn send-email-btn" data-attendee-id="<?php echo esc_attr($attendee->ID); ?>" title="Send/Resend Email">
															<span class="btn-icon">📧</span>
															<span class="btn-text"><?php echo $email_sent ? 'Resend Email' : 'Send Email'; ?></span>
														</button>
													</div>
												</div>
											<?php endforeach; ?>
										</div>
									<?php else : ?>
										<div class="no-attendees">
											<p>No "maybe" attendees.</p>
										</div>
									<?php endif; ?>
								</div>

								<div class="attendee-tab-content" id="tab-no">
									<?php if (!empty($attendees_no)) : ?>
										<div class="attendees-grid">
											<?php foreach ($attendees_no as $attendee) :
												$email = get_post_meta($attendee->ID, 'attendee_email', true);
												$phone = get_post_meta($attendee->ID, 'attendee_phone', true);
												$qr_data = get_post_meta($attendee->ID, 'qr_data', true);
												$email_sent = get_post_meta($attendee->ID, 'email_sent', true);
												$email_sent_time = get_post_meta($attendee->ID, 'email_sent_time', true);
												?>
												<div class="attendee-card" data-attendee-id="<?php echo esc_attr($attendee->ID); ?>">
													<div class="attendee-info">
														<h4 class="attendee-name"><?php echo esc_html(get_the_title($attendee->ID)); ?></h4>
														<p class="attendee-email">📧 <?php echo esc_html($email); ?></p>
														<?php if (!empty($phone)) : ?>
															<p class="attendee-phone">📱 <?php echo esc_html($phone); ?></p>
														<?php endif; ?>
													</div>
													<div class="attendee-meta">
														<span class="attendee-status status-no">✗ Not Attending</span>
													</div>
													<div class="attendee-email-status">
														<?php if ($email_sent) : ?>
															<span class="email-status-sent">
																✓ Email Sent
																<?php if ($email_sent_time) : ?>
																	<small>(<?php echo date('M j, g:i A', strtotime($email_sent_time)); ?>)</small>
																<?php endif; ?>
															</span>
														<?php else : ?>
															<span class="email-status-not-sent">⚠️ Email Not Sent</span>
														<?php endif; ?>
													</div>
													<div class="attendee-actions">
														<?php if (!empty($qr_data)) : ?>
															<a href="<?php echo esc_url(home_url('/qr-view/?qr=' . urlencode($qr_data))); ?>" target="_blank" class="attendee-action-btn view-qr-btn" title="View QR Code">
																<span class="btn-icon">🔍</span>
																<span class="btn-text">View QR</span>
															</a>
															<button type="button" class="attendee-action-btn download-qr-btn" data-qr-data="<?php echo esc_attr($qr_data); ?>" data-attendee-name="<?php echo esc_attr(get_the_title($attendee->ID)); ?>" title="Download QR Code">
																<span class="btn-icon">⬇️</span>
																<span class="btn-text">Download QR</span>
															</button>
														<?php endif; ?>
														<button type="button" class="attendee-action-btn send-email-btn" data-attendee-id="<?php echo esc_attr($attendee->ID); ?>" title="Send/Resend Email">
															<span class="btn-icon">📧</span>
															<span class="btn-text"><?php echo $email_sent ? 'Resend Email' : 'Send Email'; ?></span>
														</button>
													</div>
												</div>
											<?php endforeach; ?>
										</div>
									<?php else : ?>
										<div class="no-attendees">
											<p>No declined attendees.</p>
										</div>
									<?php endif; ?>
								</div>
							</div>
						</div>
					<?php endif; ?>

				</div>

				<aside class="event-sidebar">
					
					<?php if ($is_event_host) : ?>
					<div class="event-share-card">
						<h3>Share This Event</h3>
						<p class="share-subtitle">Share this event with your friends and colleagues</p>
						<div class="share-link-container">
							<input type="text" id="event-share-link" value="<?php echo esc_url(get_permalink($event_id)); ?>" readonly class="share-link-input">
							<button type="button" id="copy-share-link" class="copy-link-button" title="Copy link">
								📋 Copy Link
							</button>
						</div>
						<p class="share-note">Anyone with this link can <?php echo ($visibility === 'private') ? 'view this private event' : 'view and RSVP to this event'; ?></p>
					</div>

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
					<?php endif; ?>

					<div class="rsvp-card">
						<h3>RSVP for This Event</h3>
						
						<?php if ($is_past) : ?>
						<div class="rsvp-full-message">
							⏰ This event is over. RSVPs are no longer accepted.
						</div>
					<?php elseif ($is_full) : ?>
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

					<?php if ($is_event_host) : ?>
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
	// Handle email tracking from email links
	$track_token = '';
	$show_modal = false;
	$recipient_data = null;

	if (isset($_GET['email_track'])) {
		$token = sanitize_text_field($_GET['email_track']);
		// Track the email open
		if (function_exists('event_rsvp_track_email_open')) {
			event_rsvp_track_email_open($token);
		}
		$track_token = $token;
	} elseif (isset($_GET['track_token'])) {
		$track_token = sanitize_text_field($_GET['track_token']);
	}

	// Check if user already responded or if we should show modal
	if (!empty($track_token)) {
		global $wpdb;
		$recipients_table = $wpdb->prefix . 'event_email_recipients';

		$recipient_data = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM $recipients_table WHERE tracking_token = %s",
			$track_token
		));

		// Check if already responded
		if ($recipient_data && !empty($recipient_data->response) && $recipient_data->response !== 'pending') {
			// Already responded - redirect to clean URL
			if (isset($_GET['track_token']) || isset($_GET['email_track'])) {
				?>
				<script>
					window.location.href = <?php echo json_encode(get_permalink($event_id)); ?>;
				</script>
				<?php
				exit;
			}
		} else {
			// Not responded yet - show modal
			$show_modal = true;
		}
	}

	// Email tracking modal
	if ($show_modal && $recipient_data) :
	?>
	<div id="emailRsvpModal" class="email-rsvp-modal-overlay" style="display: flex;">
		<div class="email-rsvp-modal-container">
			<div class="email-rsvp-modal-header">
				<h2><?php echo esc_html(get_the_title($event_id)); ?></h2>
				<button class="email-rsvp-modal-close" aria-label="Close">&times;</button>
			</div>
			<div class="email-rsvp-modal-body">
				<div class="email-rsvp-question">
					<p class="modal-question-text">Will you attend this event?</p>
				</div>
				<div class="email-rsvp-buttons">
					<button class="email-rsvp-btn email-rsvp-yes" data-response="yes" data-token="<?php echo esc_attr($track_token); ?>" data-event-id="<?php echo esc_attr($event_id); ?>">
						✓ Yes, I'll Attend
					</button>
					<button class="email-rsvp-btn email-rsvp-no" data-response="no" data-token="<?php echo esc_attr($track_token); ?>" data-event-id="<?php echo esc_attr($event_id); ?>">
						✗ No, I Can't Make It
					</button>
				</div>
			</div>
		</div>
	</div>
	<style>
	.email-rsvp-modal-overlay {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background: rgba(0, 0, 0, 0.8);
		align-items: center;
		justify-content: center;
		z-index: 99999;
		padding: 20px;
	}

	.email-rsvp-modal-container {
		background: #ffffff;
		border-radius: 20px;
		width: 100%;
		max-width: 500px;
		box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
		animation: modalSlideIn 0.3s ease;
	}

	@keyframes modalSlideIn {
		from {
			transform: translateY(-50px);
			opacity: 0;
		}
		to {
			transform: translateY(0);
			opacity: 1;
		}
	}

	.email-rsvp-modal-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 30px;
		border-bottom: 2px solid #f0f0f0;
	}

	.email-rsvp-modal-header h2 {
		margin: 0;
		font-size: 24px;
		font-weight: 700;
		color: #2d3748;
	}

	.email-rsvp-modal-close {
		background: none;
		border: none;
		font-size: 32px;
		color: #a0aec0;
		cursor: pointer;
		line-height: 1;
		padding: 0;
		width: 32px;
		height: 32px;
		transition: color 0.2s ease;
	}

	.email-rsvp-modal-close:hover {
		color: #4a5568;
	}

	.email-rsvp-modal-body {
		padding: 40px 30px;
	}

	.modal-question-text {
		margin: 0 0 20px 0;
		font-size: 18px;
		font-weight: 600;
		text-align: center;
		color: #2d3748;
	}

	.email-rsvp-buttons {
		display: flex;
		flex-direction: column;
		gap: 16px;
	}

	.email-rsvp-btn {
		padding: 18px 30px;
		border: none;
		border-radius: 12px;
		font-size: 18px;
		font-weight: 700;
		cursor: pointer;
		transition: all 0.3s ease;
		box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
	}

	.email-rsvp-btn:hover {
		transform: translateY(-2px);
		box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
	}

	.email-rsvp-yes {
		background: linear-gradient(135deg, #10b981 0%, #059669 100%);
		color: #ffffff;
	}

	.email-rsvp-no {
		background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
		color: #ffffff;
	}

	.email-attendee-form-container {
		margin-top: 24px;
		padding-top: 24px;
		border-top: 2px solid #f0f0f0;
	}

	.email-attendee-form-container h3 {
		margin: 0 0 20px 0;
		font-size: 18px;
		color: #2d3748;
	}

	.email-attendee-form-container .form-group {
		margin-bottom: 16px;
	}

	.email-attendee-form-container label {
		display: block;
		margin-bottom: 6px;
		font-weight: 600;
		color: #2d3748;
		font-size: 14px;
	}

	.email-attendee-form-container input {
		width: 100%;
		padding: 12px 16px;
		border: 2px solid #e2e8f0;
		border-radius: 8px;
		font-size: 15px;
		box-sizing: border-box;
	}

	.email-attendee-form-container input:focus {
		outline: none;
		border-color: #667eea;
	}

	.email-attendee-submit-btn {
		width: 100%;
		padding: 14px 30px;
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		color: #ffffff;
		border: none;
		border-radius: 8px;
		font-size: 16px;
		font-weight: 600;
		cursor: pointer;
		margin-top: 20px;
		transition: all 0.3s ease;
	}

	.email-attendee-submit-btn:hover {
		transform: translateY(-2px);
		box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
	}

	.rsvp-success-message {
		padding: 40px;
		text-align: center;
	}

	.success-icon {
		font-size: 64px;
		margin-bottom: 20px;
	}

	.success-title {
		margin: 0 0 16px 0;
		color: #2d3748;
	}

	.success-description {
		margin: 0;
		color: #718096;
	}

	@media (max-width: 600px) {
		.email-rsvp-modal-container {
			border-radius: 0;
			max-width: 100%;
			max-height: 100%;
		}
	}
	</style>
	<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const tabBtns = document.querySelectorAll('.attendee-tab-btn');
	const tabContents = document.querySelectorAll('.attendee-tab-content');

	tabBtns.forEach(btn => {
		btn.addEventListener('click', function() {
			const targetTab = this.getAttribute('data-tab');

			tabBtns.forEach(b => b.classList.remove('active'));
			this.classList.add('active');

			tabContents.forEach(content => {
				content.classList.remove('active');
				if (content.id === 'tab-' + targetTab) {
					content.classList.add('active');
				}
			});
		});
	});

	const refreshBtn = document.getElementById('refresh-checked-in');
	if (refreshBtn) {
		refreshBtn.addEventListener('click', function() {
			loadCheckedInAttendees();
		});
	}

	function loadCheckedInAttendees() {
		const container = document.getElementById('event-checked-in-attendees');
		if (!container) return;

		const eventId = container.getAttribute('data-event-id');
		const currentHtml = container.innerHTML;
		if (!currentHtml.includes('loading-mini')) {
			container.innerHTML = '<div class="loading-mini">Loading...</div>';
		}

		fetch(<?php echo json_encode(admin_url('admin-ajax.php')); ?>, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams({
				action: 'event_rsvp_get_checked_in_attendees',
				event_id: eventId,
				nonce: <?php echo json_encode(wp_create_nonce('event_rsvp_checkin')); ?>
			})
		})
		.then(response => response.json())
		.then(data => {
			if (data.success && data.data.attendees) {
				const attendees = data.data.attendees.filter(a => a.event_id == eventId);

				if (attendees.length === 0) {
					container.innerHTML = '<div class="no-checked-in">No attendees checked in yet.</div>';
					return;
				}

				let html = '<div class="checked-in-mini-list">';
				attendees.forEach(attendee => {
					html += '<div class="checked-in-mini-item">';
					html += '<div class="attendee-mini-name">' + attendee.name + '</div>';
					html += '<div class="attendee-mini-time">' + attendee.checkin_time + '</div>';
					html += '</div>';
				});
				html += '</div>';

				container.innerHTML = html;
			} else {
				container.innerHTML = '<div class="error-mini">Failed to load attendees.</div>';
			}
		})
		.catch(error => {
			container.innerHTML = '<div class="error-mini">Error loading attendees.</div>';
		});
	}

	function refreshAttendeeCounts() {
		const eventId = <?php echo $event_id; ?>;

		fetch(<?php echo json_encode(admin_url('admin-ajax.php')); ?>, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams({
				action: 'event_rsvp_get_attendee_counts',
				event_id: eventId,
				nonce: <?php echo json_encode(wp_create_nonce('event_rsvp_counts')); ?>
			})
		})
		.then(response => response.json())
		.then(data => {
			if (data.success && data.data) {
				const counts = data.data;

				document.querySelectorAll('.attendee-tab-btn').forEach(btn => {
					const tab = btn.getAttribute('data-tab');
					if (tab === 'all') {
						btn.textContent = 'All (' + counts.total + ')';
					} else if (tab === 'yes') {
						btn.textContent = 'Attending (' + counts.yes + ')';
					} else if (tab === 'maybe') {
						btn.textContent = 'Maybe (' + counts.maybe + ')';
					} else if (tab === 'no') {
						btn.textContent = 'Not Attending (' + counts.no + ')';
					}
				});

				const statsCard = document.querySelector('.event-stats-card');
				if (statsCard) {
					const totalValue = statsCard.querySelector('.stat-value');
					if (totalValue) {
						totalValue.textContent = counts.total;
					}
				}
			}
		})
		.catch(error => {
			console.error('Error refreshing counts:', error);
		});
	}

	function startAutoRefresh() {
		if (document.getElementById('event-checked-in-attendees')) {
			loadCheckedInAttendees();
			refreshAttendeeCounts();
			setInterval(function() {
				loadCheckedInAttendees();
				refreshAttendeeCounts();
			}, 30000);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', startAutoRefresh);
	} else {
		startAutoRefresh();
	}

	// Handle Download QR Code
	const downloadQrBtns = document.querySelectorAll('.download-qr-btn');
	downloadQrBtns.forEach(btn => {
		btn.addEventListener('click', function() {
			const qrData = this.getAttribute('data-qr-data');
			const attendeeName = this.getAttribute('data-attendee-name');

			if (!qrData) {
				alert('QR code data not available');
				return;
			}

			// Generate QR code URL
			const qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=' + encodeURIComponent(qrData);

			// Create a temporary link and trigger download
			fetch(qrCodeUrl)
				.then(response => response.blob())
				.then(blob => {
					const url = window.URL.createObjectURL(blob);
					const a = document.createElement('a');
					a.style.display = 'none';
					a.href = url;
					a.download = 'QR-' + attendeeName.replace(/\s+/g, '-') + '.png';
					document.body.appendChild(a);
					a.click();
					window.URL.revokeObjectURL(url);
					document.body.removeChild(a);

					showNotification('QR code downloaded successfully!', 'success');
				})
				.catch(error => {
					console.error('Download error:', error);
					alert('Failed to download QR code. Please try again.');
				});
		});
	});

	// Handle Send/Resend Email
	const sendEmailBtns = document.querySelectorAll('.send-email-btn');
	sendEmailBtns.forEach(btn => {
		btn.addEventListener('click', function() {
			const attendeeId = this.getAttribute('data-attendee-id');
			const btnElement = this;
			const originalText = btnElement.innerHTML;

			if (!attendeeId) {
				alert('Invalid attendee ID');
				return;
			}

			if (!confirm('Are you sure you want to send/resend the email with QR code to this attendee?')) {
				return;
			}

			// Disable button and show loading state
			btnElement.disabled = true;
			btnElement.innerHTML = '<span class="btn-icon">⏳</span><span class="btn-text">Sending...</span>';

			fetch(<?php echo json_encode(admin_url('admin-ajax.php')); ?>, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams({
					action: 'event_rsvp_resend_qr_email',
					attendee_id: attendeeId,
					nonce: <?php echo json_encode(wp_create_nonce('event_rsvp_resend_qr')); ?>
				})
			})
			.then(response => response.json())
			.then(data => {
				btnElement.disabled = false;
				btnElement.innerHTML = originalText;

				if (data.success) {
					showNotification(data.data.message || 'Email sent successfully!', 'success');

					// Update email status in the card
					const card = btnElement.closest('.attendee-card');
					if (card) {
						const emailStatus = card.querySelector('.attendee-email-status');
						if (emailStatus) {
							emailStatus.innerHTML = '<span class="email-status-sent">✓ Email Sent <small>(' + (data.data.sent_time || 'Just now') + ')</small></span>';
						}
					}

					// Update button text
					btnElement.querySelector('.btn-text').textContent = 'Resend Email';
				} else {
					showNotification(data.data || 'Failed to send email. Please check email configuration.', 'error');
				}
			})
			.catch(error => {
				console.error('Email error:', error);
				btnElement.disabled = false;
				btnElement.innerHTML = originalText;
				showNotification('Failed to send email. Please try again.', 'error');
			});
		});
	});

	// Notification helper function
	function showNotification(message, type = 'success') {
		const notification = document.createElement('div');
		notification.className = 'event-notification event-notification-' + type;
		notification.textContent = message;
		notification.style.cssText = 'position:fixed;top:20px;right:20px;padding:15px 25px;background:' + (type === 'success' ? '#10b981' : '#ef4444') + ';color:#fff;border-radius:8px;box-shadow:0 4px 6px rgba(0,0,0,0.1);z-index:10000;animation:slideInRight 0.3s ease;max-width:400px;';

		document.body.appendChild(notification);

		setTimeout(() => {
			notification.style.animation = 'slideOutRight 0.3s ease';
			setTimeout(() => {
				document.body.removeChild(notification);
			}, 300);
		}, 3000);
	}

	// Add CSS animations
	if (!document.getElementById('event-notification-styles')) {
		const style = document.createElement('style');
		style.id = 'event-notification-styles';
		style.textContent = `
			@keyframes slideInRight {
				from { transform: translateX(100%); opacity: 0; }
				to { transform: translateX(0); opacity: 1; }
			}
			@keyframes slideOutRight {
				from { transform: translateX(0); opacity: 1; }
				to { transform: translateX(100%); opacity: 0; }
			}
		`;
		document.head.appendChild(style);
	}

	// Handle email RSVP modal
	try {
		const emailRsvpModal = document.getElementById('emailRsvpModal');
		if (emailRsvpModal) {
			console.log('Email RSVP Modal found, attaching event listeners...');
			const closeBtn = emailRsvpModal.querySelector('.email-rsvp-modal-close');
			const yesBtn = emailRsvpModal.querySelector('.email-rsvp-yes');
			const noBtn = emailRsvpModal.querySelector('.email-rsvp-no');

			if (!yesBtn || !noBtn) {
				console.error('RSVP buttons not found!', {yesBtn: yesBtn, noBtn: noBtn});
				throw new Error('RSVP buttons not found in modal');
			}
			console.log('RSVP buttons found:', {yesBtn: yesBtn, noBtn: noBtn});

		const cleanUrl = function() {
			const url = new URL(window.location.href);
			url.searchParams.delete('track_token');
			url.searchParams.delete('show_rsvp_modal');
			url.searchParams.delete('email_track');
			window.history.replaceState({}, document.title, url.toString());
		};

		closeBtn.addEventListener('click', function() {
			emailRsvpModal.style.display = 'none';
			cleanUrl();
		});

		emailRsvpModal.addEventListener('click', function(e) {
			if (e.target === emailRsvpModal) {
				emailRsvpModal.style.display = 'none';
				cleanUrl();
			}
		});

		noBtn.addEventListener('click', function() {
			console.log('No button clicked');
			const token = this.getAttribute('data-token');
			const eventId = this.getAttribute('data-event-id');
			const originalText = this.innerHTML;

			if (!token || !eventId) {
				console.error('Missing token or event ID', {token: token, eventId: eventId});
				alert('Error: Missing required data. Please try clicking the link in your email again.');
				return;
			}

			this.disabled = true;
			this.innerHTML = '⏳ Processing...';

			// Record "no" response
			fetch(<?php echo json_encode(admin_url('admin-ajax.php')); ?>, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams({
					action: 'event_rsvp_record_email_response',
					token: token,
					response: 'no',
					nonce: <?php echo json_encode(wp_create_nonce('event_rsvp_email_response')); ?>
				})
			})
			.then(response => {
				console.log('Response received:', response);
				return response.json();
			})
			.then(function(data) {
				console.log('Response data:', data);
				if (data.success) {
					emailRsvpModal.innerHTML = '<div class="rsvp-success-message"><div class="success-icon">✓</div><h2 class="success-title">Thank you for your response</h2><p class="success-description">We\'re sorry you can\'t make it!</p></div>';
					setTimeout(function() {
						window.location.href = window.location.pathname;
					}, 2000);
				} else {
					this.disabled = false;
					this.innerHTML = originalText;
					alert('Failed to record response. Please try again.');
				}
			}.bind(this))
			.catch(function(error) {
				console.error('Error recording NO response:', error);
				this.disabled = false;
				this.innerHTML = originalText;
				alert('Failed to record response. Please try again. Error: ' + error.message);
			}.bind(this));
		});

		yesBtn.addEventListener('click', function() {
			console.log('Yes button clicked');
			const token = this.getAttribute('data-token');
			const eventId = this.getAttribute('data-event-id');

			if (!token || !eventId) {
				console.error('Missing token or event ID', {token: token, eventId: eventId});
				alert('Error: Missing required data. Please try clicking the link in your email again.');
				return;
			}

			// Show attendee form
			const modalBody = emailRsvpModal.querySelector('.email-rsvp-modal-body');
			modalBody.innerHTML = `
				<div class="email-attendee-form-container">
					<h3>Great! Please provide your details:</h3>
					<form id="emailAttendeeForm">
						<div class="form-group">
							<label for="email-attendee-name">Full Name *</label>
							<input type="text" id="email-attendee-name" name="attendee_name" required>
						</div>
						<div class="form-group">
							<label for="email-attendee-email">Email Address *</label>
							<input type="email" id="email-attendee-email" name="attendee_email" required>
						</div>
						<div class="form-group">
							<label for="email-attendee-phone">Phone Number</label>
							<input type="tel" id="email-attendee-phone" name="attendee_phone">
						</div>
						<button type="submit" class="email-attendee-submit-btn">Submit RSVP</button>
					</form>
				</div>
			`;

			const form = document.getElementById('emailAttendeeForm');
			form.addEventListener('submit', function(e) {
				e.preventDefault();

				const formData = new FormData(form);
				formData.append('action', 'event_rsvp_record_email_attendance');
				formData.append('token', token);
				formData.append('event_id', eventId);
				formData.append('response', 'yes');
				formData.append('nonce', <?php echo json_encode(wp_create_nonce('event_rsvp_email_response')); ?>);

				const submitBtn = form.querySelector('.email-attendee-submit-btn');
				submitBtn.disabled = true;
				submitBtn.textContent = 'Submitting...';

				fetch(<?php echo json_encode(admin_url('admin-ajax.php')); ?>, {
					method: 'POST',
					body: formData
				})
				.then(response => {
					console.log('Attendance form response received:', response);
					return response.json();
				})
				.then(data => {
					console.log('Attendance form data:', data);
					if (data.success) {
						emailRsvpModal.innerHTML = '<div class="rsvp-success-message"><div class="success-icon">✓</div><h2 class="success-title">Thank you for your RSVP!</h2><p class="success-description">You\'ll receive a confirmation email with your QR code for check-in.</p></div>';
						setTimeout(function() {
							window.location.href = window.location.pathname;
						}, 2000);
					} else {
						submitBtn.disabled = false;
						submitBtn.textContent = 'Submit RSVP';
						alert(data.data || 'Failed to submit RSVP. Please try again.');
					}
				})
				.catch(error => {
					console.error('Error submitting attendance form:', error);
					submitBtn.disabled = false;
					submitBtn.textContent = 'Submit RSVP';
					alert('Failed to submit RSVP. Please try again. Error: ' + error.message);
				});
			});
		});
		} else {
			console.log('Email RSVP Modal not found in DOM');
		}
	} catch (error) {
		console.error('Error initializing email RSVP modal:', error);
		alert('There was an error loading the RSVP form. Please refresh the page and try again.');
	}

	// Copy share link functionality
	const copyShareLinkBtn = document.getElementById('copy-share-link');
	if (copyShareLinkBtn) {
		copyShareLinkBtn.addEventListener('click', function() {
			const shareLinkInput = document.getElementById('event-share-link');
			if (shareLinkInput) {
				shareLinkInput.select();
				shareLinkInput.setSelectionRange(0, 99999); // For mobile devices

				try {
					document.execCommand('copy');
					const originalText = this.innerHTML;
					this.innerHTML = '✓ Copied!';
					this.style.backgroundColor = '#10b981';

					setTimeout(() => {
						this.innerHTML = originalText;
						this.style.backgroundColor = '';
					}, 2000);
				} catch (err) {
					// Fallback to navigator.clipboard if available
					if (navigator.clipboard) {
						navigator.clipboard.writeText(shareLinkInput.value).then(() => {
							const originalText = this.innerHTML;
							this.innerHTML = '✓ Copied!';
							this.style.backgroundColor = '#10b981';

							setTimeout(() => {
								this.innerHTML = originalText;
								this.style.backgroundColor = '';
							}, 2000);
						}).catch(err => {
							alert('Failed to copy link. Please copy it manually.');
						});
					} else {
						alert('Copy failed. Please copy the link manually.');
					}
				}
			}
		});
	}
});
</script>

<style>
.event-share-card {
	background: #ffffff;
	border-radius: 12px;
	padding: 24px;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
	margin-bottom: 24px;
}

.event-share-card h3 {
	margin: 0 0 8px 0;
	font-size: 1.25rem;
	font-weight: 700;
	color: #1f2937;
}

.share-subtitle {
	margin: 0 0 16px 0;
	font-size: 0.9rem;
	color: #6b7280;
}

.share-link-container {
	display: flex;
	gap: 8px;
	margin-bottom: 12px;
}

.share-link-input {
	flex: 1;
	padding: 10px 12px;
	border: 2px solid #e5e7eb;
	border-radius: 8px;
	font-size: 0.875rem;
	color: #4b5563;
	background-color: #f9fafb;
}

.share-link-input:focus {
	outline: none;
	border-color: #667eea;
}

.copy-link-button {
	padding: 10px 16px;
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	color: #ffffff;
	border: none;
	border-radius: 8px;
	font-size: 0.875rem;
	font-weight: 600;
	cursor: pointer;
	transition: all 0.3s ease;
	white-space: nowrap;
}

.copy-link-button:hover {
	transform: translateY(-2px);
	box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.copy-link-button:active {
	transform: translateY(0);
}

.share-note {
	margin: 0;
	font-size: 0.8rem;
	color: #9ca3af;
	font-style: italic;
}

@media (max-width: 768px) {
	.share-link-container {
		flex-direction: column;
	}

	.copy-link-button {
		width: 100%;
	}
}
</style>

<?php
endwhile;

get_footer();

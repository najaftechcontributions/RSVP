<?php
/**
 * Shortcodes
 * 
 * @package EventRSVPPlugin
 */

if (!defined('ABSPATH')) {
	exit;
}

function event_rsvp_form_shortcode($atts) {
	$atts = shortcode_atts(array(
		'event_id' => 0,
	), $atts);

	$event_id = intval($atts['event_id']);

	if (!$event_id) {
		return '<p>Please specify an event ID.</p>';
	}

	$event_title = get_the_title($event_id);

	ob_start();
	?>
	<div class="event-rsvp-form-wrapper">
		<h3><?php echo esc_html('RSVP for ' . $event_title); ?></h3>
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
			
			<button type="submit" class="rsvp-submit-button">Submit RSVP</button>
		</form>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode('event_rsvp_form', 'event_rsvp_form_shortcode');

function event_rsvp_upcoming_events_shortcode($atts) {
	$atts = shortcode_atts(array(
		'limit' => 5,
	), $atts);
	
	$events = event_rsvp_get_upcoming_events($atts['limit']);
	
	if (empty($events)) {
		return '<p>No upcoming events.</p>';
	}
	
	ob_start();
	echo '<div class="upcoming-events-list">';
	
	foreach ($events as $event) {
		$event_date = get_post_meta($event->ID, 'event_date', true);
		$venue = get_post_meta($event->ID, 'venue_address', true);
		
		echo '<div class="event-card">';
		echo get_the_post_thumbnail($event->ID, 'medium');
		echo '<h3><a href="' . get_permalink($event->ID) . '">' . get_the_title($event->ID) . '</a></h3>';
		echo '<p class="event-date">📅 ' . esc_html($event_date) . '</p>';
		echo '<p class="event-venue">📍 ' . esc_html($venue) . '</p>';
		echo '<a href="' . get_permalink($event->ID) . '" class="wp-block-button__link">View Details</a>';
		echo '</div>';
	}
	
	echo '</div>';
	return ob_get_clean();
}
add_shortcode('upcoming_events', 'event_rsvp_upcoming_events_shortcode');

function event_rsvp_vendor_ad_shortcode($atts) {
	$atts = shortcode_atts(array(
		'location' => 'sidebar',
	), $atts);
	
	return event_rsvp_display_vendor_ad($atts['location']);
}
add_shortcode('vendor_ad', 'event_rsvp_vendor_ad_shortcode');

function event_rsvp_single_ad_shortcode($atts) {
	$atts = shortcode_atts(array(
		'id' => 0,
	), $atts);

	$ad_id = intval($atts['id']);

	if (!$ad_id) {
		return '<p class="ad-error">Invalid ad ID</p>';
	}

	$ad = get_post($ad_id);
	if (!$ad || $ad->post_type !== 'vendor_ad') {
		return '<p class="ad-error">Ad not found</p>';
	}

	$ad_status = get_post_meta($ad_id, 'ad_status', true);
	$approval_status = get_post_meta($ad_id, 'ad_approval_status', true);

	if ($ad_status !== 'active' || $approval_status !== 'approved') {
		return '';
	}

	$today = date('Y-m-d');
	$start_date = get_post_meta($ad_id, 'ad_start_date', true);
	$end_date = get_post_meta($ad_id, 'ad_end_date', true);

	if (!empty($start_date) && !empty($end_date)) {
		if ($today < $start_date || $today > $end_date) {
			return '';
		}
	}

	$thumbnail = get_the_post_thumbnail($ad_id, 'large');
	$click_url = get_post_meta($ad_id, 'click_url', true);
	$ad_title = get_the_title($ad_id);

	if (empty($thumbnail)) {
		return '';
	}

	$current_impressions = intval(get_post_meta($ad_id, 'ad_impressions', true));
	update_post_meta($ad_id, 'ad_impressions', $current_impressions + 1);

	ob_start();
	?>
	<div class="vendor-ad-single vendor-ad-wrapper">
		<div class="vendor-ad-container">
			<?php if (!empty($click_url)) : ?>
				<a href="<?php echo esc_url($click_url); ?>" target="_blank" rel="noopener sponsored" class="vendor-ad-link" data-ad-id="<?php echo $ad_id; ?>">
					<div class="vendor-ad-image">
						<?php echo $thumbnail; ?>
					</div>
					<div class="vendor-ad-overlay">
						<span class="vendor-ad-title"><?php echo esc_html($ad_title); ?></span>
						<span class="vendor-ad-cta">Learn More →</span>
					</div>
				</a>
			<?php else : ?>
				<div class="vendor-ad-image">
					<?php echo $thumbnail; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode('ad', 'event_rsvp_single_ad_shortcode');

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
		'preview' => false,
	), $atts);
	
	$is_preview = filter_var($atts['preview'], FILTER_VALIDATE_BOOLEAN);
	
	return event_rsvp_display_vendor_ad($atts['location'], $is_preview);
}
add_shortcode('vendor_ad', 'event_rsvp_vendor_ad_shortcode');

function event_rsvp_single_ad_shortcode($atts) {
	$atts = shortcode_atts(array(
		'id' => 0,
		'preview' => false,
	), $atts);

	$ad_id = intval($atts['id']);
	$is_preview = filter_var($atts['preview'], FILTER_VALIDATE_BOOLEAN);

	if (!$ad_id) {
		if (is_admin() || current_user_can('administrator')) {
			return '<div class="ad-error" style="padding: 20px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; margin: 10px 0; text-align: center;"><strong>⚠️ Invalid ad ID</strong></div>';
		}
		return '';
	}

	$ad = get_post($ad_id);
	if (!$ad || $ad->post_type !== 'vendor_ad') {
		if (is_admin() || current_user_can('administrator')) {
			return '<div class="ad-error" style="padding: 20px; background: #f8d7da; border: 1px solid #dc3545; border-radius: 6px; margin: 10px 0; text-align: center;"><strong>❌ Ad not found (ID: ' . $ad_id . ')</strong></div>';
		}
		return '';
	}

	$ad_status = get_post_meta($ad_id, 'ad_status', true);
	$approval_status = get_post_meta($ad_id, 'ad_approval_status', true);

	if (!$is_preview && ($ad_status !== 'active' || $approval_status !== 'approved')) {
		if (is_admin() || current_user_can('administrator')) {
			$status_msg = $approval_status !== 'approved' ? 'Not Approved' : 'Inactive';
			return '<div class="ad-error" style="padding: 20px; background: #e2e3e5; border: 1px solid #6c757d; border-radius: 6px; margin: 10px 0; text-align: center;"><strong>⏸️ Ad Status: ' . $status_msg . '</strong><br><small>This ad will not be shown to visitors</small></div>';
		}
		return '';
	}

	$today = date('Y-m-d');
	$start_date = get_post_meta($ad_id, 'ad_start_date', true);
	$end_date = get_post_meta($ad_id, 'ad_end_date', true);

	if (!$is_preview && !empty($start_date) && !empty($end_date)) {
		if ($today < $start_date) {
			if (is_admin() || current_user_can('administrator')) {
				return '<div class="ad-error" style="padding: 20px; background: #d1ecf1; border: 1px solid #17a2b8; border-radius: 6px; margin: 10px 0; text-align: center;"><strong>📅 Scheduled</strong><br><small>Ad starts on: ' . date('M j, Y', strtotime($start_date)) . '</small></div>';
			}
			return '';
		}

		if ($today > $end_date) {
			if (is_admin() || current_user_can('administrator')) {
				return '<div class="ad-error" style="padding: 20px; background: #e2e3e5; border: 1px solid #6c757d; border-radius: 6px; margin: 10px 0; text-align: center;"><strong>⏰ Expired</strong><br><small>Ad ended on: ' . date('M j, Y', strtotime($end_date)) . '</small></div>';
			}
			return '';
		}
	}

	$thumbnail_url = get_the_post_thumbnail_url($ad_id, 'large');
	$click_url = get_post_meta($ad_id, 'click_url', true);
	$ad_title = get_the_title($ad_id);

	if (empty($thumbnail_url)) {
		if (is_admin() || current_user_can('administrator')) {
			return '<div class="ad-error" style="padding: 20px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; margin: 10px 0; text-align: center;"><strong>🖼️ No Image</strong><br><small>Please add a featured image to this ad</small></div>';
		}
		return '';
	}

	if (!$is_preview) {
		$current_impressions = intval(get_post_meta($ad_id, 'ad_impressions', true));
		update_post_meta($ad_id, 'ad_impressions', $current_impressions + 1);
	}

	$preview_label = $is_preview ? '<div class="ad-preview-label" style="position: absolute; top: 10px; left: 10px; background: rgba(0,0,0,0.8); color: #fff; padding: 5px 10px; border-radius: 4px; font-size: 0.8rem; z-index: 10;">PREVIEW</div>' : '';

	ob_start();
	?>
	<div class="vendor-ad-single vendor-ad-wrapper" data-ad-id="<?php echo $ad_id; ?>">
		<div class="vendor-ad-container" style="position: relative;">
			<?php echo $preview_label; ?>
			<?php if (!empty($click_url)) : ?>
				<a href="<?php echo esc_url($click_url); ?>" target="_blank" rel="noopener sponsored" class="vendor-ad-link" data-ad-id="<?php echo $ad_id; ?>" <?php echo $is_preview ? 'onclick="return false;"' : ''; ?>>
					<div class="vendor-ad-image">
						<img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($ad_title); ?>" loading="lazy">
					</div>
					<div class="vendor-ad-overlay">
						<span class="vendor-ad-title"><?php echo esc_html($ad_title); ?></span>
						<span class="vendor-ad-cta">Learn More →</span>
					</div>
				</a>
			<?php else : ?>
				<div class="vendor-ad-image">
					<img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($ad_title); ?>" loading="lazy">
				</div>
				<div class="vendor-ad-overlay">
					<span class="vendor-ad-title"><?php echo esc_html($ad_title); ?></span>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode('ad', 'event_rsvp_single_ad_shortcode');

<?php
/**
 * Email Functions
 * 
 * @package EventRSVPPlugin
 */

if (!defined('ABSPATH')) {
	exit;
}

function event_rsvp_get_email_template($attendee_id) {
	$attendee_email = get_post_meta($attendee_id, 'attendee_email', true);
	$attendee_name = get_the_title($attendee_id);
	$event_id = get_post_meta($attendee_id, 'linked_event', true);
	$event_title = get_the_title($event_id);
	$event_date = get_post_meta($event_id, 'event_date', true);
	$venue_address = get_post_meta($event_id, 'venue_address', true);
	$qr_data = get_post_meta($attendee_id, 'qr_data', true);
	$qr_code_url = event_rsvp_generate_qr_code($qr_data);

	$event_url = get_permalink($event_id);
	$qr_viewer_url = home_url('/qr-view/?qr=' . urlencode($qr_data));
	$formatted_date = date('F j, Y \a\t g:i A', strtotime($event_date));

	ob_start();
	?>
	<!DOCTYPE html>
	<html>
	<head>
		<meta charset="UTF-8">
		<title>RSVP Confirmation</title>
	</head>
	<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; background-color: #f5f5f5;">
		<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f5f5f5; padding: 40px 20px;">
			<tr>
				<td align="center">
					<table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
						<tr>
							<td style="background: linear-gradient(135deg, #503AA8 0%, #6B52C3 100%); padding: 40px; text-align: center;">
								<h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;">✓ RSVP Confirmed!</h1>
								<p style="margin: 10px 0 0 0; color: rgba(255, 255, 255, 0.9); font-size: 16px;">You're all set for the event</p>
							</td>
						</tr>
						<tr>
							<td style="padding: 40px;">
								<p style="margin: 0 0 20px 0; font-size: 16px; line-height: 1.6; color: #333333;">Hello <strong><?php echo esc_html($attendee_name); ?></strong>,</p>
								<p style="margin: 0 0 20px 0; font-size: 16px; line-height: 1.6; color: #333333;">Thank you for your RSVP! We're excited to have you join us for:</p>

								<div style="background-color: #f8f9fa; border-left: 4px solid #503AA8; padding: 20px; margin: 30px 0; border-radius: 4px;">
									<h2 style="margin: 0 0 15px 0; font-size: 20px; color: #503AA8;"><?php echo esc_html($event_title); ?></h2>
									<p style="margin: 0 0 8px 0; color: #555555; font-size: 14px;">
										<strong>📅 When:</strong> <?php echo esc_html($formatted_date); ?>
									</p>
									<p style="margin: 0; color: #555555; font-size: 14px;">
										<strong>📍 Where:</strong> <?php echo esc_html($venue_address); ?>
									</p>
								</div>

								<h3 style="margin: 30px 0 15px 0; font-size: 18px; color: #333333;">Your Check-In QR Code</h3>
								<p style="margin: 0 0 20px 0; font-size: 14px; color: #666666;">Please save this QR code and present it at the event entrance for quick check-in:</p>

								<div style="text-align: center; padding: 30px; background-color: #f8f9fa; border-radius: 8px; margin: 20px 0;">
									<img src="<?php echo esc_url($qr_code_url); ?>" alt="Check-in QR Code" style="width: 250px; height: 250px; border: 3px solid #503AA8; border-radius: 8px;">
								</div>

								<div style="background-color: #fff3cd; border: 1px solid #ffecb5; border-radius: 6px; padding: 15px; margin: 30px 0;">
									<p style="margin: 0; font-size: 14px; color: #856404;">
										💡 <strong>Pro Tip:</strong> Save this email or take a screenshot of your QR code to have it ready on event day!
									</p>
								</div>

								<div style="text-align: center; margin: 30px 0;">
									<a href="<?php echo esc_url($event_url); ?>" style="display: inline-block; padding: 14px 32px; background-color: #503AA8; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px;">View Event Details</a>
								</div>

								<p style="margin: 30px 0 0 0; font-size: 14px; line-height: 1.6; color: #666666;">
									See you at the event!<br>
									<strong>The Event Team</strong>
								</p>
							</td>
						</tr>
						<tr>
							<td style="background-color: #f8f9fa; padding: 30px; text-align: center; border-top: 1px solid #e0e0e0;">
								<p style="margin: 0; font-size: 12px; color: #999999; line-height: 1.6;">
									This is an automated message. Please do not reply to this email.<br>
									If you have questions, please contact the event organizer.
								</p>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</body>
	</html>
	<?php
	return ob_get_clean();
}

function event_rsvp_send_qr_email_now($attendee_id) {
	$attendee_email = get_post_meta($attendee_id, 'attendee_email', true);
	$event_id = get_post_meta($attendee_id, 'linked_event', true);
	$event_title = get_the_title($event_id);

	$subject = sprintf('✓ RSVP Confirmed: %s', $event_title);
	$message = event_rsvp_get_email_template($attendee_id);

	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		'From: Event RSVP <noreply@' . $_SERVER['HTTP_HOST'] . '>'
	);

	wp_mail($attendee_email, $subject, $message, $headers);
}
add_action('event_rsvp_send_qr_email', 'event_rsvp_send_qr_email_now');

function event_rsvp_schedule_qr_email($attendee_id, $event_id) {
	$event_date = get_post_meta($event_id, 'event_date', true);
	$qr_schedule_days = get_post_meta($event_id, 'qr_schedule_days', true);

	if (empty($qr_schedule_days)) {
		$qr_schedule_days = 7;
	}

	if (!empty($event_date)) {
		$event_timestamp = strtotime($event_date);
		$send_timestamp = $event_timestamp - ($qr_schedule_days * DAY_IN_SECONDS);

		if ($send_timestamp > time()) {
			wp_schedule_single_event($send_timestamp, 'event_rsvp_send_qr_email', array($attendee_id));
		} else {
			event_rsvp_send_qr_email_now($attendee_id);
		}
	} else {
		event_rsvp_send_qr_email_now($attendee_id);
	}
}

function event_rsvp_handle_cf7_submission($contact_form) {
	$submission = WPCF7_Submission::get_instance();

	if (!$submission) {
		return;
	}

	$posted_data = $submission->get_posted_data();

	if (isset($posted_data['event-rsvp']) && $posted_data['event-rsvp'] === '1') {
		$attendee_name = sanitize_text_field($posted_data['attendee-name'] ?? '');
		$attendee_email = sanitize_email($posted_data['attendee-email'] ?? '');
		$attendee_phone = sanitize_text_field($posted_data['attendee-phone'] ?? '');
		$rsvp_status = sanitize_text_field($posted_data['rsvp-status'] ?? 'yes');
		$event_id = intval($posted_data['event-id'] ?? 0);

		if (empty($attendee_name) || empty($attendee_email)) {
			return;
		}

		$attendee_id = wp_insert_post(array(
			'post_type' => 'attendee',
			'post_title' => $attendee_name,
			'post_status' => 'publish',
		));

		if (!is_wp_error($attendee_id)) {
			update_post_meta($attendee_id, 'attendee_email', $attendee_email);
			update_post_meta($attendee_id, 'attendee_phone', $attendee_phone);
			update_post_meta($attendee_id, 'rsvp_status', $rsvp_status);
			update_post_meta($attendee_id, 'linked_event', $event_id);
			update_post_meta($attendee_id, 'checkin_status', false);

			$qr_data = base64_encode(json_encode(array(
				'attendee_id' => $attendee_id,
				'event_id' => $event_id,
				'email' => $attendee_email,
				'verification' => wp_hash($attendee_id . $event_id . $attendee_email)
			)));

			update_post_meta($attendee_id, 'qr_data', $qr_data);

			event_rsvp_schedule_qr_email($attendee_id, $event_id);
		}
	}
}
add_action('wpcf7_before_send_mail', 'event_rsvp_handle_cf7_submission');

<?php
/**
 * Form Submission Handlers
 * 
 * @package EventRSVPPlugin
 */

if (!defined('ABSPATH')) {
	exit;
}

function event_rsvp_handle_rsvp_submission() {
	if (!isset($_POST['event_rsvp_nonce']) || !wp_verify_nonce($_POST['event_rsvp_nonce'], 'event_rsvp_submit')) {
		wp_die('Security check failed');
	}

	$attendee_name = sanitize_text_field($_POST['attendee-name'] ?? '');
	$attendee_email = sanitize_email($_POST['attendee-email'] ?? '');
	$attendee_phone = sanitize_text_field($_POST['attendee-phone'] ?? '');
	$rsvp_status = sanitize_text_field($_POST['rsvp-status'] ?? 'yes');
	$event_id = intval($_POST['event-id'] ?? 0);

	if (empty($attendee_name) || empty($attendee_email) || !$event_id) {
		wp_redirect(add_query_arg('rsvp', 'error', get_permalink($event_id)));
		exit;
	}

	if (event_rsvp_is_event_past($event_id)) {
		wp_redirect(add_query_arg('rsvp', 'past', get_permalink($event_id)));
		exit;
	}

	if (event_rsvp_is_event_full($event_id)) {
		wp_redirect(add_query_arg('rsvp', 'full', get_permalink($event_id)));
		exit;
	}

	$existing = event_rsvp_get_attendee_by_email($attendee_email, $event_id);

	if ($existing) {
		update_post_meta($existing->ID, 'rsvp_status', $rsvp_status);
		update_post_meta($existing->ID, 'attendee_phone', $attendee_phone);
		$attendee_id = $existing->ID;

		$email_sent = event_rsvp_send_qr_email_now($attendee_id);

		if ($email_sent) {
			wp_redirect(add_query_arg(array('rsvp' => 'success', 'email' => 'sent'), get_permalink($event_id)));
		} else {
			wp_redirect(add_query_arg(array('rsvp' => 'success', 'email' => 'failed'), get_permalink($event_id)));
		}
		exit;
	} else {
		$attendee_id = wp_insert_post(array(
			'post_type' => 'attendee',
			'post_title' => $attendee_name,
			'post_status' => 'publish',
		));

		if (is_wp_error($attendee_id)) {
			wp_redirect(add_query_arg('rsvp', 'error', get_permalink($event_id)));
			exit;
		}

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

		$email_sent = event_rsvp_send_qr_email_now($attendee_id);

		if ($email_sent) {
			wp_redirect(add_query_arg(array('rsvp' => 'success', 'email' => 'sent'), get_permalink($event_id)));
		} else {
			wp_redirect(add_query_arg(array('rsvp' => 'success', 'email' => 'failed'), get_permalink($event_id)));
		}
		exit;
	}

	wp_redirect(add_query_arg('rsvp', 'success', get_permalink($event_id)));
	exit;
}
add_action('admin_post_nopriv_event_rsvp_submit', 'event_rsvp_handle_rsvp_submission');
add_action('admin_post_event_rsvp_submit', 'event_rsvp_handle_rsvp_submission');

function event_rsvp_handle_login() {
	if (!isset($_POST['login_nonce']) || !wp_verify_nonce($_POST['login_nonce'], 'event_rsvp_login')) {
		wp_die('Security check failed');
	}

	$username = sanitize_user($_POST['log'] ?? '');
	$password = $_POST['pwd'] ?? '';
	$remember = isset($_POST['rememberme']);
	$redirect_to = $_POST['redirect_to'] ?? home_url('/my-account/');

	if (empty($username) || empty($password)) {
		wp_redirect(add_query_arg('login', 'empty', home_url('/login/')));
		exit;
	}

	$creds = array(
		'user_login' => $username,
		'user_password' => $password,
		'remember' => $remember
	);

	$user = wp_signon($creds, is_ssl());

	if (is_wp_error($user)) {
		wp_redirect(add_query_arg('login', 'failed', home_url('/login/')));
		exit;
	}

	wp_redirect($redirect_to);
	exit;
}
add_action('admin_post_nopriv_event_rsvp_login', 'event_rsvp_handle_login');
add_action('admin_post_event_rsvp_login', 'event_rsvp_handle_login');

function event_rsvp_logout_redirect() {
	wp_redirect(add_query_arg('logout', 'success', home_url('/login/')));
	exit;
}
add_action('wp_logout', 'event_rsvp_logout_redirect');

function event_rsvp_handle_forgot_password() {
	if (!isset($_POST['forgot_nonce']) || !wp_verify_nonce($_POST['forgot_nonce'], 'event_rsvp_forgot_password')) {
		wp_die('Security check failed');
	}

	$user_email = sanitize_email($_POST['user_email'] ?? '');

	if (empty($user_email)) {
		wp_redirect(add_query_arg('sent', 'failed', home_url('/reset-password/')));
		exit;
	}

	$user = get_user_by('email', $user_email);

	if (!$user) {
		wp_redirect(add_query_arg('sent', 'failed', home_url('/reset-password/')));
		exit;
	}

	$reset_key = get_password_reset_key($user);

	if (is_wp_error($reset_key)) {
		wp_redirect(add_query_arg('sent', 'failed', home_url('/reset-password/')));
		exit;
	}

	$reset_url = home_url('/reset-password/') . '?action=rsvp_resetpass&key=' . $reset_key . '&login=' . rawurlencode($user->user_login);

	$site_name = get_bloginfo('name');
	$user_display_name = $user->display_name;

	$subject = sprintf('[%s] Password Reset Request', $site_name);

	$message = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f9fafb; padding: 20px;">';
	$message .= '<div style="background: white; border-radius: 12px; padding: 40px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
	$message .= '<div style="text-align: center; margin-bottom: 30px;">';
	$message .= '<h1 style="color: #667eea; margin: 0; font-size: 28px;">🔐 Password Reset</h1>';
	$message .= '</div>';

	$message .= '<p style="font-size: 16px; color: #1f2937; line-height: 1.6;">Hello <strong>' . esc_html($user_display_name) . '</strong>,</p>';
	$message .= '<p style="font-size: 16px; color: #1f2937; line-height: 1.6;">Someone requested a password reset for the following account on <strong>' . esc_html($site_name) . '</strong>:</p>';

	$message .= '<div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 25px 0;">';
	$message .= '<p style="margin: 5px 0; font-size: 14px; color: #6b7280;"><strong>Username:</strong> ' . esc_html($user->user_login) . '</p>';
	$message .= '<p style="margin: 5px 0; font-size: 14px; color: #6b7280;"><strong>Email:</strong> ' . esc_html($user_email) . '</p>';
	$message .= '</div>';

	$message .= '<p style="font-size: 16px; color: #1f2937; line-height: 1.6;">If this was a mistake, just ignore this email and nothing will happen.</p>';
	$message .= '<p style="font-size: 16px; color: #1f2937; line-height: 1.6;">To reset your password, click the button below:</p>';

	$message .= '<div style="text-align: center; margin: 30px 0;">';
	$message .= '<a href="' . esc_url($reset_url) . '" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px;">Reset Your Password</a>';
	$message .= '</div>';

	$message .= '<div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 6px; margin: 25px 0;">';
	$message .= '<p style="margin: 0; font-size: 14px; color: #92400e;"><strong>⚠️ Security Notice:</strong> This link will expire in 24 hours for your security. If you didn\'t request this reset, please secure your account immediately.</p>';
	$message .= '</div>';

	$message .= '<p style="font-size: 14px; color: #6b7280; line-height: 1.6; margin-top: 30px;">If the button doesn\'t work, copy and paste this link into your browser:</p>';
	$message .= '<p style="font-size: 12px; color: #9ca3af; word-break: break-all; background: #f9fafb; padding: 10px; border-radius: 4px;">' . esc_url($reset_url) . '</p>';

	$message .= '<div style="border-top: 1px solid #e5e7eb; margin-top: 40px; padding-top: 20px; text-align: center;">';
	$message .= '<p style="font-size: 12px; color: #9ca3af; margin: 0;">This is an automated email from ' . esc_html($site_name) . '</p>';
	$message .= '</div>';

	$message .= '</div>';
	$message .= '</div>';

	$smtp_username = get_option('event_rsvp_smtp_username', '');
	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		'Reply-To: ' . $smtp_username
	);

	$sent = wp_mail($user_email, $subject, $message, $headers);

	if ($sent) {
		wp_redirect(add_query_arg('sent', 'success', home_url('/reset-password/')));
	} else {
		wp_redirect(add_query_arg('sent', 'failed', home_url('/reset-password/')));
	}
	exit;
}
add_action('admin_post_nopriv_event_rsvp_forgot_password', 'event_rsvp_handle_forgot_password');
add_action('admin_post_event_rsvp_forgot_password', 'event_rsvp_handle_forgot_password');

function event_rsvp_handle_reset_password() {
	if (!isset($_POST['reset_nonce']) || !wp_verify_nonce($_POST['reset_nonce'], 'event_rsvp_reset_password')) {
		wp_die('Security check failed');
	}

	$reset_key = sanitize_text_field($_POST['reset_key'] ?? '');
	$reset_login = sanitize_text_field($_POST['reset_login'] ?? '');
	$new_password = $_POST['new_password'] ?? '';
	$confirm_password = $_POST['confirm_password'] ?? '';

	if (empty($new_password) || empty($confirm_password)) {
		wp_redirect(add_query_arg(array('action' => 'rsvp_resetpass', 'key' => $reset_key, 'login' => $reset_login, 'reset' => 'failed'), home_url('/reset-password/')));
		exit;
	}

	if ($new_password !== $confirm_password) {
		wp_redirect(add_query_arg(array('action' => 'rsvp_resetpass', 'key' => $reset_key, 'login' => $reset_login, 'reset' => 'mismatch'), home_url('/reset-password/')));
		exit;
	}

	$user = check_password_reset_key($reset_key, $reset_login);

	if (is_wp_error($user)) {
		wp_redirect(add_query_arg(array('action' => 'rsvp_resetpass', 'key' => $reset_key, 'login' => $reset_login, 'reset' => 'failed'), home_url('/reset-password/')));
		exit;
	}

	reset_password($user, $new_password);

	wp_redirect(add_query_arg('reset', 'success', home_url('/login/')));
	exit;
}
add_action('admin_post_nopriv_event_rsvp_reset_password', 'event_rsvp_handle_reset_password');
add_action('admin_post_event_rsvp_reset_password', 'event_rsvp_handle_reset_password');

function event_rsvp_handle_change_password() {
	if (!is_user_logged_in()) {
		wp_redirect(home_url('/login/'));
		exit;
	}

	if (!isset($_POST['password_nonce']) || !wp_verify_nonce($_POST['password_nonce'], 'event_rsvp_change_password')) {
		wp_die('Security check failed');
	}

	$user_id = get_current_user_id();
	$current_password = $_POST['current_password'] ?? '';
	$new_password = $_POST['new_password'] ?? '';
	$confirm_password = $_POST['confirm_password'] ?? '';

	if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
		wp_redirect(add_query_arg('password_error', 'empty', home_url('/my-account/#security')));
		exit;
	}

	if ($new_password !== $confirm_password) {
		wp_redirect(add_query_arg('password_error', 'mismatch', home_url('/my-account/#security')));
		exit;
	}

	$user = get_userdata($user_id);

	if (!wp_check_password($current_password, $user->user_pass, $user_id)) {
		wp_redirect(add_query_arg('password_error', 'incorrect', home_url('/my-account/#security')));
		exit;
	}

	wp_set_password($new_password, $user_id);

	wp_redirect(add_query_arg('password_changed', 'success', home_url('/my-account/#security')));
	exit;
}
add_action('admin_post_event_rsvp_change_password', 'event_rsvp_handle_change_password');

function event_rsvp_handle_acf_event_submission($post_id) {
	// Skip if not an event post type
	if (get_post_type($post_id) !== 'event') {
		return;
	}

	// Skip if autosave or revision
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	if (wp_is_post_revision($post_id)) {
		return;
	}

	// Handle featured image
	if (isset($_POST['event_featured_image_id'])) {
		$attachment_id = intval($_POST['event_featured_image_id']);

		if ($attachment_id > 0) {
			// Verify attachment exists
			if (get_post($attachment_id) && get_post_type($attachment_id) === 'attachment') {
				set_post_thumbnail($post_id, $attachment_id);
			}
		} else {
			// Remove featured image if field is empty (user clicked remove)
			delete_post_thumbnail($post_id);
		}
	}

	// Handle venue map URL - allow iframe content
	// Prioritize raw_venue_map_url if it exists (preserves iframe content)
	if (isset($_POST['raw_venue_map_url']) && !empty($_POST['raw_venue_map_url'])) {
		$venue_map = $_POST['raw_venue_map_url'];

		// Allow specific iframe attributes for Google Maps embeds
		$allowed_html = array(
			'iframe' => array(
				'src' => true,
				'width' => true,
				'height' => true,
				'frameborder' => true,
				'style' => true,
				'allowfullscreen' => true,
				'loading' => true,
				'referrerpolicy' => true,
				'allow' => true,
			),
		);

		$venue_map = wp_kses($venue_map, $allowed_html);
		update_field('venue_map_url', $venue_map, $post_id);
	} elseif (isset($_POST['acf']['field_venue_map_url'])) {
		$venue_map = $_POST['acf']['field_venue_map_url'];

		// Allow specific iframe attributes for Google Maps embeds
		$allowed_html = array(
			'iframe' => array(
				'src' => true,
				'width' => true,
				'height' => true,
				'frameborder' => true,
				'style' => true,
				'allowfullscreen' => true,
				'loading' => true,
				'referrerpolicy' => true,
				'allow' => true,
			),
		);

		$venue_map = wp_kses($venue_map, $allowed_html);
		update_field('venue_map_url', $venue_map, $post_id);
	}
}
add_action('acf/save_post', 'event_rsvp_handle_acf_event_submission', 20);

/**
 * Allow unfiltered HTML in ACF frontend forms for venue_map_url field
 * This enables users to paste Google Maps iframe code without it being stripped
 */
function event_rsvp_allow_venue_map_html() {
	global $post;

	// Check if we're on a page using the event create template
	if (is_page() && $post) {
		$template = get_page_template_slug($post->ID);
		if ($template === 'page-event-create.php') {
			return true;
		}

		// Also check by page slug or title as fallback
		if (in_array($post->post_name, array('create-event', 'event-create', 'host-event'))) {
			return true;
		}
	}

	// Allow for AJAX requests from event create page
	if (defined('DOING_AJAX') && DOING_AJAX && isset($_POST['_acf_post_id'])) {
		return true;
	}

	return false;
}
add_filter('acf/allow_unfiltered_html', 'event_rsvp_allow_venue_map_html');

<?php
/**
 * Template Name: Email Track Landing
 *
 * @package RSVP
 */

// Handle email tracking
if (isset($_GET['email_track'])) {
	$token = sanitize_text_field($_GET['email_track']);
	$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
	
	// Track the email open
	$recipient = event_rsvp_track_email_open($token);
	
	if ($recipient && $event_id) {
		// Redirect to event page with tracking token
		wp_redirect(add_query_arg(array(
			'track_token' => $token,
			'show_rsvp_modal' => '1'
		), get_permalink($event_id)));
		exit;
	}
}

// Handle unsubscribe
if (isset($_GET['email_unsubscribe'])) {
	$token = sanitize_text_field($_GET['email_unsubscribe']);
	
	global $wpdb;
	$recipients_table = $wpdb->prefix . 'event_email_recipients';
	
	$wpdb->update(
		$recipients_table,
		array('response' => 'unsubscribed'),
		array('tracking_token' => $token),
		array('%s'),
		array('%s')
	);
	
	get_header();
	?>
	<main class="email-track-page">
		<div class="container" style="max-width: 600px; margin: 80px auto; text-align: center; padding: 40px 20px;">
			<div style="background: #ffffff; padding: 60px 40px; border-radius: 16px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);">
				<div style="font-size: 64px; margin-bottom: 20px;">✅</div>
				<h1 style="margin: 0 0 16px 0; font-size: 32px; color: #2d3748;">Unsubscribed Successfully</h1>
				<p style="margin: 0; font-size: 16px; color: #718096;">You won't receive any more emails from this campaign.</p>
			</div>
		</div>
	</main>
	<?php
	get_footer();
	exit;
}

wp_redirect(home_url());
exit;

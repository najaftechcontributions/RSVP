<?php
/**
 * Template Name: Payment Cancelled
 *
 * @package RSVP
 */

get_header();

$plan = isset($_GET['plan']) ? sanitize_text_field($_GET['plan']) : '';
$plan_names = array(
	'pay_as_you_go' => 'Pay As You Go',
	'event_planner' => 'Event Planner',
	'event_host' => 'Event Host',
	'vendor' => 'Vendor',
	'pro' => 'Pro (Host + Vendor)'
);
$plan_name = isset($plan_names[$plan]) ? $plan_names[$plan] : 'premium';
?>

<main id="primary" class="site-main payment-cancelled-page">
	<div class="container payment-cancelled-container">
		<div class="payment-cancelled-content">
			<div class="cancelled-box">
				<div class="cancelled-icon">⚠️</div>
				<h1 class="cancelled-title">Payment Not Completed</h1>
				<p class="cancelled-message">Your payment for the <strong><?php echo esc_html($plan_name); ?></strong> plan was not completed.</p>
				
				<div class="info-section">
					<h2 class="info-heading">What happened?</h2>
					<p>You may have:</p>
					<ul class="reason-list">
						<li>Cancelled the payment</li>
						<li>Closed the payment window</li>
						<li>Experienced a payment error</li>
					</ul>
				</div>

				<div class="good-news-section">
					<h2 class="good-news-heading">✓ Good News!</h2>
					<p>Your account has already been created as a <strong>free Attendee</strong> account. You can:</p>
					<ul class="attendee-features">
						<li>Browse and RSVP to events</li>
						<li>Receive QR codes for check-in</li>
						<li>Get event reminders</li>
					</ul>
					<p class="upgrade-note">You can upgrade to a paid plan anytime from your dashboard or the pricing page.</p>
				</div>

				<div class="action-buttons">
					<a href="<?php echo esc_url(home_url('/pricing/?plan=' . $plan)); ?>" class="action-button action-button-primary">
						Try Payment Again
					</a>
					<a href="<?php echo esc_url(home_url('/login/')); ?>" class="action-button action-button-secondary">
						Login to Free Account
					</a>
					<a href="<?php echo esc_url(home_url('/browse-events/')); ?>" class="action-button action-button-outline">
						Browse Events
					</a>
				</div>

				<div class="help-section">
					<p class="help-text">
						Need help? <a href="<?php echo esc_url(home_url('/contact/')); ?>">Contact our support team</a>
					</p>
				</div>
			</div>
		</div>
	</div>
</main>

<style>
.payment-cancelled-page {
	min-height: 70vh;
	display: flex;
	align-items: center;
	justify-content: center;
	background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
	padding: 40px 20px;
}

.payment-cancelled-container {
	max-width: 700px;
	margin: 0 auto;
}

.cancelled-box {
	background: white;
	border-radius: 12px;
	padding: 60px 40px;
	text-align: center;
	box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.cancelled-icon {
	font-size: 80px;
	margin-bottom: 20px;
}

.cancelled-title {
	font-size: 2rem;
	margin: 0 0 15px 0;
	color: #1f2937;
}

.cancelled-message {
	font-size: 1.1rem;
	color: #6b7280;
	margin-bottom: 30px;
	line-height: 1.6;
}

.info-section,
.good-news-section {
	background-color: #f9fafb;
	border-radius: 8px;
	padding: 20px;
	margin: 20px 0;
	text-align: left;
}

.info-heading,
.good-news-heading {
	font-size: 1.2rem;
	margin: 0 0 15px 0;
	color: #1f2937;
}

.good-news-section {
	background-color: #f0fdf4;
	border: 2px solid #10b981;
}

.good-news-heading {
	color: #065f46;
}

.reason-list,
.attendee-features {
	margin: 15px 0;
	padding-left: 25px;
}

.reason-list li,
.attendee-features li {
	margin: 8px 0;
	color: #4b5563;
}

.attendee-features li {
	color: #047857;
}

.upgrade-note {
	margin-top: 15px;
	font-style: italic;
	color: #047857;
}

.action-buttons {
	margin: 30px 0 20px;
	display: flex;
	flex-direction: column;
	gap: 10px;
}

.action-button {
	display: inline-block;
	padding: 14px 30px;
	border-radius: 6px;
	text-decoration: none;
	font-weight: 600;
	font-size: 1rem;
	transition: all 0.3s ease;
	border: 2px solid transparent;
}

.action-button-primary {
	background-color: #667eea;
	color: white;
}

.action-button-primary:hover {
	background-color: #5568d3;
}

.action-button-secondary {
	background-color: #10b981;
	color: white;
}

.action-button-secondary:hover {
	background-color: #059669;
}

.action-button-outline {
	background-color: white;
	color: #1f2937;
	border-color: #d1d5db;
}

.action-button-outline:hover {
	background-color: #f9fafb;
	border-color: #9ca3af;
}

.help-section {
	margin-top: 30px;
	padding-top: 20px;
	border-top: 1px solid #e5e7eb;
}

.help-text {
	font-size: 0.9rem;
	color: #6b7280;
}

.help-text a {
	color: #667eea;
	text-decoration: underline;
}

@media (max-width: 768px) {
	.cancelled-box {
		padding: 40px 20px;
	}

	.cancelled-title {
		font-size: 1.5rem;
	}

	.cancelled-message {
		font-size: 1rem;
	}
	
	.action-buttons {
		flex-direction: column;
	}
}
</style>

<?php get_footer(); ?>

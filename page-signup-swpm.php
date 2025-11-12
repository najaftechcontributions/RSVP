<?php
/**
 * Template Name: Signup Page (Simple Membership)
 *
 * @package RSVP
 */

if (is_user_logged_in()) {
	wp_redirect(home_url('/host-dashboard/'));
	exit;
}

$selected_plan = isset($_GET['plan']) ? sanitize_text_field($_GET['plan']) : '';
$membership_level_id = 1; // Default to free attendee
$is_paid_plan = false;

switch ($selected_plan) {
	case 'attendee':
		$membership_level_id = 1;
		$is_paid_plan = false;
		break;
	case 'event_host':
		$membership_level_id = 2;
		$is_paid_plan = true;
		break;
	case 'vendor':
		$membership_level_id = 3;
		$is_paid_plan = true;
		break;
	case 'pro':
		$membership_level_id = 4;
		$is_paid_plan = true;
		break;
	default:
		$membership_level_id = 1;
		$is_paid_plan = false;
}

get_header();
?>

<main id="primary" class="site-main signup-page swpm-signup-page">
	<div class="container signup-container">
		<div class="signup-header">
			<h1 class="signup-title">Create Your Account</h1>
			<?php if ($selected_plan) : ?>
				<p class="signup-subtitle">You've selected the <strong><?php echo esc_html(ucwords(str_replace('_', ' ', $selected_plan))); ?></strong> plan</p>
			<?php else : ?>
				<p class="signup-subtitle">Join thousands of event organizers using our platform</p>
			<?php endif; ?>
		</div>

		<div class="signup-content">
			<div class="signup-form-section">
				<div class="signup-card">
					
					<?php if ($is_paid_plan) : ?>
						<div class="paid-plan-notice">
							<h3>📋 Two-Step Registration Process</h3>
							<div class="steps-container">
								<div class="step">
									<div class="step-number">1</div>
									<div class="step-content">
										<strong>Create Account</strong>
										<p>Fill in your details below</p>
									</div>
								</div>
								<div class="step-arrow">→</div>
								<div class="step">
									<div class="step-number">2</div>
									<div class="step-content">
										<strong>Complete Payment</strong>
										<p>Secure checkout with Stripe</p>
									</div>
								</div>
							</div>
							<p class="notice-text">Your subscription will activate immediately after payment.</p>
						</div>
					<?php endif; ?>

					<?php
					// Display Simple Membership registration form
					if (function_exists('swpm_render_registration_form')) {
						// Set the membership level based on selected plan
						echo '<div class="swpm-form-wrapper">';
						echo do_shortcode('[swpm_registration_form id="' . $membership_level_id . '"]');
						echo '</div>';
					} else {
						// Fallback if Simple Membership is not active
						?>
						<div class="swpm-not-active-notice">
							<h3>⚠️ Simple Membership Plugin Required</h3>
							<p>The Simple Membership plugin is required for registration but is not currently active.</p>
							<p>Please contact the site administrator.</p>
						</div>
						<?php
					}
					?>

					<?php if (!$selected_plan || $selected_plan === 'attendee') : ?>
						<div class="pricing-info-box">
							<p><strong>📌 Free Attendee Account</strong></p>
							<p>You're signing up for a free account. You can browse events and RSVP. If you want to create events or post ads, <a href="<?php echo esc_url(home_url('/pricing/')); ?>">check our pricing plans</a>.</p>
						</div>
					<?php elseif ($is_paid_plan) : ?>
						<div class="payment-info-box">
							<p><strong>💳 Secure Payment via Stripe</strong></p>
							<p>After completing the form above, you'll be redirected to our secure Stripe checkout to complete your <strong><?php echo esc_html(ucwords(str_replace('_', ' ', $selected_plan))); ?></strong> subscription.</p>
							<ul class="payment-features">
								<li>✓ Secure SSL encrypted payment</li>
								<li>✓ Account auto-created after payment</li>
								<li>✓ Cancel anytime</li>
								<li>✓ 30-day money-back guarantee</li>
								<li>✓ Email confirmation sent immediately</li>
							</ul>
						</div>
					<?php endif; ?>

					<div class="signup-divider">
						<span class="divider-text">Already have an account?</span>
					</div>

					<div class="signup-links">
						<p class="login-prompt">
							<a href="<?php echo esc_url(home_url('/login/')); ?>" class="login-link">Sign in instead</a>
						</p>
						<?php if ($selected_plan && $selected_plan !== 'attendee') : ?>
							<p class="plan-change-prompt">
								<a href="<?php echo esc_url(home_url('/pricing/')); ?>" class="plan-change-link">Choose a different plan</a>
							</p>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<div class="signup-info-section">
				<div class="info-card">
					<h2 class="info-heading">What's Included</h2>
					
					<div class="features-checklist">
						<div class="feature-check-item">
							<span class="check-icon">✓</span>
							<span class="check-text">Unlimited event browsing</span>
						</div>
						<div class="feature-check-item">
							<span class="check-icon">✓</span>
							<span class="check-text">Automatic QR code generation</span>
						</div>
						<div class="feature-check-item">
							<span class="check-icon">✓</span>
							<span class="check-text">Email confirmations & reminders</span>
						</div>
						<div class="feature-check-item">
							<span class="check-icon">✓</span>
							<span class="check-text">Real-time attendance tracking</span>
						</div>
						<?php if ($selected_plan === 'event_host' || $selected_plan === 'pro') : ?>
						<div class="feature-check-item">
							<span class="check-icon">✓</span>
							<span class="check-text">Create unlimited events</span>
						</div>
						<div class="feature-check-item">
							<span class="check-icon">✓</span>
							<span class="check-text">Event analytics dashboard</span>
						</div>
						<?php endif; ?>
						<?php if ($selected_plan === 'vendor' || $selected_plan === 'pro') : ?>
						<div class="feature-check-item">
							<span class="check-icon">✓</span>
							<span class="check-text">Post advertisements</span>
						</div>
						<div class="feature-check-item">
							<span class="check-icon">✓</span>
							<span class="check-text">Ad analytics & tracking</span>
						</div>
						<?php endif; ?>
						<div class="feature-check-item">
							<span class="check-icon">✓</span>
							<span class="check-text">Mobile-friendly check-in</span>
						</div>
						<div class="feature-check-item">
							<span class="check-icon">✓</span>
							<span class="check-text">Priority email support</span>
						</div>
					</div>
				</div>

				<div class="testimonial-card">
					<div class="testimonial-content">
						<p class="testimonial-text">"This platform transformed how we manage our events. The QR code check-in alone saved us hours of work!"</p>
						<div class="testimonial-author">
							<strong>Sarah Johnson</strong>
							<span>Event Coordinator, Tech Summit</span>
						</div>
					</div>
				</div>

				<?php if ($is_paid_plan) : ?>
				<div class="security-card">
					<h3 class="security-heading">🔒 Secure Payment</h3>
					<p class="security-text">Powered by Stripe, trusted by millions worldwide. Your payment information is encrypted and secure.</p>
					<div class="payment-badges">
						<span class="badge">🔐 SSL Encrypted</span>
						<span class="badge">💳 PCI Compliant</span>
					</div>
				</div>
				<?php else : ?>
				<div class="support-card">
					<h3 class="support-heading">Need Help?</h3>
					<p class="support-text">Our team is here to help you get started.</p>
					<a href="<?php echo esc_url(home_url('/contact/')); ?>" class="support-link">Contact Support →</a>
				</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</main>

<style>
.swpm-form-wrapper {
	margin-bottom: 20px;
}

.swpm-not-active-notice {
	background-color: #fef3c7;
	border: 2px solid #f59e0b;
	border-radius: 8px;
	padding: 20px;
	text-align: center;
	margin-bottom: 20px;
}

.swpm-not-active-notice h3 {
	margin: 0 0 10px 0;
	color: #92400e;
}

.swpm-not-active-notice p {
	margin: 5px 0;
	color: #78350f;
}

.pricing-info-box {
	background-color: var(--event-primary-light, #f0f7ff);
	border: 2px solid var(--event-primary, #3b82f6);
	border-radius: 8px;
	padding: 15px;
	margin-bottom: 20px;
}

.pricing-info-box p {
	margin: 8px 0;
	font-size: 0.95rem;
	color: var(--event-text, #1f2937);
}

.pricing-info-box a {
	color: #503AA8;
	font-weight: 600;
	text-decoration: underline;
}

.payment-info-box {
	background-color: #f0fdf4;
	border: 2px solid #10b981;
	border-radius: 8px;
	padding: 15px;
	margin-bottom: 20px;
}

.payment-info-box p {
	margin: 8px 0;
	font-size: 0.95rem;
	color: #065f46;
}

.payment-features {
	list-style: none;
	padding: 0;
	margin: 10px 0 0 0;
}

.payment-features li {
	padding: 5px 0;
	font-size: 0.9rem;
	color: #047857;
}

.paid-plan-notice {
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	color: white;
	border-radius: 12px;
	padding: 20px;
	margin-bottom: 25px;
	box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.paid-plan-notice h3 {
	margin: 0 0 15px 0;
	font-size: 1.2rem;
	text-align: center;
}

.steps-container {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 15px;
	margin: 20px 0;
}

.step {
	display: flex;
	align-items: center;
	gap: 10px;
	background: rgba(255,255,255,0.2);
	padding: 12px 20px;
	border-radius: 8px;
	flex: 1;
	max-width: 200px;
}

.step-number {
	background: white;
	color: #667eea;
	width: 35px;
	height: 35px;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	font-weight: bold;
	font-size: 1.2rem;
	flex-shrink: 0;
}

.step-content {
	flex: 1;
}

.step-content strong {
	display: block;
	margin-bottom: 3px;
	font-size: 0.95rem;
}

.step-content p {
	margin: 0;
	font-size: 0.8rem;
	opacity: 0.9;
}

.step-arrow {
	font-size: 1.5rem;
	font-weight: bold;
}

.notice-text {
	text-align: center;
	margin: 15px 0 0 0;
	font-size: 0.9rem;
	opacity: 0.95;
}

.plan-change-prompt {
	text-align: center;
	margin-top: 10px;
}

.plan-change-link {
	color: var(--event-text-light, #6b7280);
	text-decoration: none;
	font-size: 0.9rem;
}

.plan-change-link:hover {
	color: var(--event-primary, #3b82f6);
}

.security-card {
	background-color: #f9fafb;
	border: 2px solid #e5e7eb;
	border-radius: 8px;
	padding: 20px;
	text-align: center;
}

.security-heading {
	margin: 0 0 10px 0;
	font-size: 1.1rem;
	color: #1f2937;
}

.security-text {
	font-size: 0.9rem;
	color: #6b7280;
	margin-bottom: 15px;
}

.payment-badges {
	display: flex;
	gap: 10px;
	justify-content: center;
	flex-wrap: wrap;
}

.badge {
	background-color: #10b981;
	color: white;
	padding: 5px 12px;
	border-radius: 20px;
	font-size: 0.8rem;
	font-weight: 500;
}

.swpm-registration-form {
	width: 100%;
}

.swpm-registration-form .swpm-form-field {
	margin-bottom: 20px;
}

.swpm-registration-form label {
	display: block;
	margin-bottom: 8px;
	font-weight: 600;
	color: #374151;
	font-size: 0.95rem;
}

.swpm-registration-form input[type="text"],
.swpm-registration-form input[type="email"],
.swpm-registration-form input[type="password"],
.swpm-registration-form select {
	width: 100%;
	padding: 12px 16px;
	border: 2px solid #e5e7eb;
	border-radius: 8px;
	font-size: 1rem;
	transition: all 0.3s ease;
	box-sizing: border-box;
}

.swpm-registration-form input:focus,
.swpm-registration-form select:focus {
	outline: none;
	border-color: #667eea;
	box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.swpm-registration-form .swpm-submit {
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	color: white;
	padding: 14px 32px;
	border: none;
	border-radius: 8px;
	font-size: 1.1rem;
	font-weight: 600;
	cursor: pointer;
	width: 100%;
	transition: all 0.3s ease;
	margin-top: 10px;
}

.swpm-registration-form .swpm-submit:hover {
	transform: translateY(-2px);
	box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.swpm-error,
.swpm-validation-error {
	background-color: #fee2e2;
	border: 2px solid #ef4444;
	color: #991b1b;
	padding: 12px 16px;
	border-radius: 8px;
	margin-bottom: 20px;
}

.swpm-success {
	background-color: #d1fae5;
	border: 2px solid #10b981;
	color: #065f46;
	padding: 12px 16px;
	border-radius: 8px;
	margin-bottom: 20px;
}

.swpm-registration-form .swpm-required {
	color: #ef4444;
	margin-left: 2px;
}

@media (max-width: 768px) {
	.steps-container {
		flex-direction: column;
	}
	
	.step {
		max-width: 100%;
	}
	
	.step-arrow {
		transform: rotate(90deg);
	}
}
</style>

<?php get_footer(); ?>

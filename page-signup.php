<?php
/**
 * Template Name: Signup Page
 *
 * @package RSVP
 */

if (is_user_logged_in()) {
	wp_redirect(home_url('/host-dashboard/'));
	exit;
}

$selected_plan = isset($_GET['plan']) ? sanitize_text_field($_GET['plan']) : '';
$role_from_plan = '';
$is_paid_plan = false;

switch ($selected_plan) {
	case 'attendee':
		$role_from_plan = 'subscriber';
		$is_paid_plan = false;
		break;
	case 'event_host':
		$role_from_plan = 'event_host';
		$is_paid_plan = true;
		break;
	case 'vendor':
		$role_from_plan = 'vendor';
		$is_paid_plan = true;
		break;
	case 'pro':
		$role_from_plan = 'pro';
		$is_paid_plan = true;
		break;
	default:
		$role_from_plan = 'subscriber';
		$is_paid_plan = false;
}

get_header();
?>

<main id="primary" class="site-main signup-page">
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
					
					<div id="signup-message" class="form-message" style="display: none;"></div>

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

					<form class="event-signup-form" id="event-signup-form">
						<?php wp_nonce_field('event_rsvp_register', 'register_nonce'); ?>
						<input type="hidden" name="pricing_plan" id="pricing-plan" value="<?php echo esc_attr($selected_plan); ?>">
						<input type="hidden" name="is_paid_plan" value="<?php echo $is_paid_plan ? '1' : '0'; ?>">
						
						<div class="form-row">
							<div class="form-field">
								<label for="first-name">First Name <span class="required">*</span></label>
								<input type="text" id="first-name" name="first_name" placeholder="John" required>
							</div>
							
							<div class="form-field">
								<label for="last-name">Last Name <span class="required">*</span></label>
								<input type="text" id="last-name" name="last_name" placeholder="Doe" required>
							</div>
						</div>
						
						<div class="form-field">
							<label for="username">Username <span class="required">*</span></label>
							<input type="text" id="username" name="username" placeholder="Choose a unique username" required>
							<span class="field-hint">This will be your unique identifier on the platform</span>
						</div>
						
						<div class="form-field">
							<label for="email">Email Address <span class="required">*</span></label>
							<input type="email" id="email" name="email" placeholder="your.email@example.com" required>
							<span class="field-hint">We'll send your confirmation and QR codes here</span>
						</div>
						
						<div class="form-field">
							<label for="password">Password <span class="required">*</span></label>
							<input type="password" id="password" name="password" placeholder="Create a strong password" required minlength="8">
							<span class="field-hint">Minimum 8 characters</span>
						</div>
						
						<div class="form-field">
							<label for="password-confirm">Confirm Password <span class="required">*</span></label>
							<input type="password" id="password-confirm" name="password_confirm" placeholder="Re-enter your password" required>
						</div>
						
						<?php if ($selected_plan && $selected_plan !== 'attendee') : ?>
							<div class="form-field">
								<label for="user-role">Account Type <span class="required">*</span></label>
								<select id="user-role" name="user_role" required disabled>
									<option value="event_host" <?php selected($role_from_plan, 'event_host'); ?>>Host Events - Create and manage my own events</option>
									<option value="vendor" <?php selected($role_from_plan, 'vendor'); ?>>Become a Vendor - Advertise my services</option>
									<option value="pro" <?php selected($role_from_plan, 'pro'); ?>>Pro (Both) - Host events & advertise</option>
								</select>
								<input type="hidden" name="user_role" value="<?php echo esc_attr($role_from_plan); ?>">
								<span class="field-hint">Role selected based on your pricing plan</span>
							</div>
						<?php else : ?>
							<input type="hidden" name="user_role" value="subscriber">
						<?php endif; ?>

						<?php if (!$selected_plan || $selected_plan === 'attendee') : ?>
							<div class="pricing-info-box">
								<p><strong>📌 Free Attendee Account</strong></p>
								<p>You're signing up for a free account. You can browse events and RSVP. If you want to create events or post ads, <a href="<?php echo esc_url(home_url('/pricing/')); ?>">check our pricing plans</a>.</p>
							</div>
						<?php elseif ($is_paid_plan) : ?>
							<div class="payment-info-box">
								<p><strong>💳 Secure Payment via Stripe</strong></p>
								<p>You'll be redirected to our secure Stripe checkout to complete your <strong><?php echo esc_html(ucwords(str_replace('_', ' ', $selected_plan))); ?></strong> subscription. Your account will be created automatically after successful payment.</p>
								<ul class="payment-features">
									<li>✓ Secure SSL encrypted payment</li>
									<li>✓ Account auto-created after payment</li>
									<li>✓ Cancel anytime</li>
									<li>✓ 30-day money-back guarantee</li>
									<li>✓ Credentials emailed to you</li>
								</ul>
							</div>
						<?php endif; ?>
						
						<div class="form-checkbox">
							<input type="checkbox" id="terms" name="terms" required>
							<label for="terms">
								I agree to the <a href="<?php echo esc_url(home_url('/terms/')); ?>" target="_blank">Terms of Service</a> 
								and <a href="<?php echo esc_url(home_url('/privacy/')); ?>" target="_blank">Privacy Policy</a> 
								<span class="required">*</span>
							</label>
						</div>
						
						<div class="form-checkbox">
							<input type="checkbox" id="newsletter" name="newsletter">
							<label for="newsletter">
								Send me updates about new events and platform features
							</label>
						</div>
						
						<div class="form-actions">
							<button type="submit" class="signup-submit-button">
								<span class="button-icon">🚀</span>
								<span class="button-text"><?php echo $is_paid_plan ? 'Create Account & Proceed to Payment' : 'Create Free Account'; ?></span>
							</button>
						</div>
					</form>

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

<script>
document.addEventListener('DOMContentLoaded', function() {
	const form = document.getElementById('event-signup-form');
	const messageEl = document.getElementById('signup-message');
	const submitButton = form.querySelector('.signup-submit-button');
	const isPaidPlan = form.querySelector('input[name="is_paid_plan"]').value === '1';

	form.addEventListener('submit', function(e) {
		e.preventDefault();

		const password = document.getElementById('password').value;
		const passwordConfirm = document.getElementById('password-confirm').value;

		if (password !== passwordConfirm) {
			showMessage('Passwords do not match. Please try again.', 'error');
			return;
		}

		submitButton.disabled = true;
		const buttonText = submitButton.querySelector('.button-text');
		const originalText = buttonText.textContent;
		buttonText.textContent = isPaidPlan ? 'Creating Account...' : 'Creating Account...';
		submitButton.querySelector('.button-icon').textContent = '⏳';

		const formData = new FormData(form);
		formData.append('action', 'event_rsvp_register_user');
		formData.append('nonce', '<?php echo wp_create_nonce('event_rsvp_register'); ?>');

		fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
			method: 'POST',
			body: formData
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				showMessage(data.data.message, 'success');
				
				if (isPaidPlan && data.data.checkout_url) {
					buttonText.textContent = 'Redirecting to checkout...';
					setTimeout(() => {
						window.location.href = data.data.checkout_url;
					}, 1000);
				} else {
					setTimeout(() => {
						window.location.href = data.data.redirect;
					}, 1500);
				}
			} else {
				showMessage(data.data || 'Registration failed. Please try again.', 'error');
				submitButton.disabled = false;
				buttonText.textContent = originalText;
				submitButton.querySelector('.button-icon').textContent = '🚀';
			}
		})
		.catch(error => {
			console.error('Error:', error);
			showMessage('An error occurred. Please try again.', 'error');
			submitButton.disabled = false;
			buttonText.textContent = originalText;
			submitButton.querySelector('.button-icon').textContent = '🚀';
		});
	});

	function showMessage(message, type) {
		messageEl.textContent = message;
		messageEl.className = 'form-message ' + type + '-message';
		messageEl.style.display = 'block';
		messageEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
	}
});
</script>

<?php get_footer(); ?>

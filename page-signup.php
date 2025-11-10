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

switch ($selected_plan) {
	case 'attendee':
		$role_from_plan = 'subscriber';
		break;
	case 'event_host':
		$role_from_plan = 'event_host';
		break;
	case 'vendor':
		$role_from_plan = 'vendor';
		break;
	case 'pro':
		$role_from_plan = 'pro';
		break;
	default:
		// Default to subscriber (attendee) for direct signups
		$role_from_plan = 'subscriber';
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

					<form class="event-signup-form" id="event-signup-form">
						<?php wp_nonce_field('event_rsvp_register', 'register_nonce'); ?>
						<input type="hidden" name="pricing_plan" id="pricing-plan" value="<?php echo esc_attr($selected_plan); ?>">
						
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
							<!-- Direct signup defaults to attendee role -->
							<input type="hidden" name="user_role" value="subscriber">
						<?php endif; ?>

						<?php if (!$selected_plan || $selected_plan === 'attendee') : ?>
							<div class="pricing-info-box">
								<p><strong>📌 Free Attendee Account</strong></p>
								<p>You're signing up for a free account. You can browse events and RSVP. If you want to create events or post ads, <a href="<?php echo esc_url(home_url('/pricing/')); ?>">check our pricing plans</a>.</p>
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
								<span class="button-text">Create Account</span>
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

				<div class="support-card">
					<h3 class="support-heading">Need Help?</h3>
					<p class="support-text">Our team is here to help you get started.</p>
					<a href="<?php echo esc_url(home_url('/contact/')); ?>" class="support-link">Contact Support →</a>
				</div>
			</div>
		</div>
	</div>
</main>

<style>
.pricing-info-box {
	background-color: var(--event-primary-light);
	border: 2px solid var(--event-primary);
	border-radius: 8px;
	padding: 15px;
	margin-bottom: 20px;
}

.pricing-info-box p {
	margin: 8px 0;
	font-size: 0.95rem;
	color: var(--event-text);
}

.pricing-info-box a {
	color: #503AA8;
	font-weight: 600;
	text-decoration: underline;
}

.plan-change-prompt {
	text-align: center;
	margin-top: 10px;
}

.plan-change-link {
	color: var(--event-text-light);
	text-decoration: none;
	font-size: 0.9rem;
}

.plan-change-link:hover {
	color: var(--event-primary);
}

.selected-plan-info {
	background-color: #f8f9fa;
	border: 2px solid #e9ecef;
	border-radius: 8px;
	padding: 12px 15px;
	margin-top: 8px;
}

.selected-plan-info strong {
	color: var(--event-primary);
	font-size: 1rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const form = document.getElementById('event-signup-form');
	const messageEl = document.getElementById('signup-message');
	const submitButton = form.querySelector('.signup-submit-button');

	form.addEventListener('submit', function(e) {
		e.preventDefault();

		const password = document.getElementById('password').value;
		const passwordConfirm = document.getElementById('password-confirm').value;

		if (password !== passwordConfirm) {
			showMessage('Passwords do not match. Please try again.', 'error');
			return;
		}

		submitButton.disabled = true;
		submitButton.innerHTML = '<span class="button-icon">⏳</span><span class="button-text">Creating Account...</span>';

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
				setTimeout(() => {
					window.location.href = data.data.redirect;
				}, 1500);
			} else {
				showMessage(data.data || 'Registration failed. Please try again.', 'error');
				submitButton.disabled = false;
				submitButton.innerHTML = '<span class="button-icon">🚀</span><span class="button-text">Create Account</span>';
			}
		})
		.catch(error => {
			console.error('Error:', error);
			showMessage('An error occurred. Please try again.', 'error');
			submitButton.disabled = false;
			submitButton.innerHTML = '<span class="button-icon">🚀</span><span class="button-text">Create Account</span>';
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

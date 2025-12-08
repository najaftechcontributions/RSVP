<?php
/**
 * Template Name: Reset Password Page
 *
 * @package RSVP
 */

$reset_action = isset($_GET['action']) ? $_GET['action'] : '';
$reset_key = isset($_GET['key']) ? $_GET['key'] : '';
$reset_login = isset($_GET['login']) ? $_GET['login'] : '';

get_header();
?>

<main id="primary" class="site-main reset-password-page">
	<div class="container reset-container">
		<div class="reset-wrapper">
			<div class="reset-form-section">
				<div class="reset-card">
					<?php if ($reset_action === 'rsvp_resetpass' && !empty($reset_key) && !empty($reset_login)) : ?>
						<div class="reset-header">
							<h1 class="reset-title">🔐 Set New Password</h1>
							<p class="reset-subtitle">Enter your new password below</p>
						</div>

						<?php
						$user = check_password_reset_key($reset_key, $reset_login);
						
						if (is_wp_error($user)) :
							?>
							<div class="error-message">
								<strong>Invalid or Expired Link</strong><br>
								This password reset link is invalid or has expired. Please request a new one.
							</div>
							<div class="form-actions">
								<a href="<?php echo home_url('/login/'); ?>" class="reset-button">
									<span class="button-icon">🔙</span>
									<span class="button-text">Back to Login</span>
								</a>
							</div>
						<?php else : ?>
							<?php
							$reset_message = '';
							if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
								$reset_message = '<div class="success-message">Your password has been reset successfully! You can now log in with your new password.</div>';
							} elseif (isset($_GET['reset']) && $_GET['reset'] === 'failed') {
								$reset_message = '<div class="error-message">Failed to reset password. Please try again.</div>';
							} elseif (isset($_GET['reset']) && $_GET['reset'] === 'mismatch') {
								$reset_message = '<div class="error-message">Passwords do not match. Please try again.</div>';
							}
							echo $reset_message;
							?>

							<form class="event-reset-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
								<?php wp_nonce_field('event_rsvp_reset_password', 'reset_nonce'); ?>
								<input type="hidden" name="action" value="event_rsvp_reset_password">
								<input type="hidden" name="reset_key" value="<?php echo esc_attr($reset_key); ?>">
								<input type="hidden" name="reset_login" value="<?php echo esc_attr($reset_login); ?>">
								
								<div class="form-field">
									<label for="new_password">New Password <span class="required">*</span></label>
									<input type="password" id="new_password" name="new_password" placeholder="Enter your new password" required minlength="8" autofocus>
									<p class="field-description">Password must be at least 8 characters long</p>
								</div>
								
								<div class="form-field">
									<label for="confirm_password">Confirm Password <span class="required">*</span></label>
									<input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter your new password" required minlength="8">
								</div>

								<div class="password-strength">
									<div class="strength-meter">
										<div class="strength-meter-fill"></div>
									</div>
									<p class="strength-text">Password strength: <span class="strength-label">Too short</span></p>
								</div>
								
								<div class="form-actions">
									<button type="submit" class="reset-submit-button">
										<span class="button-icon">🔐</span>
										<span class="button-text">Reset Password</span>
									</button>
								</div>
							</form>
						<?php endif; ?>

					<?php else : ?>
						<div class="reset-header">
							<h1 class="reset-title">🔑 Forgot Password?</h1>
							<p class="reset-subtitle">Enter your email to receive a password reset link</p>
						</div>

						<?php
						$forgot_message = '';
						if (isset($_GET['sent']) && $_GET['sent'] === 'success') {
							$forgot_message = '<div class="success-message">Check your email! We\'ve sent you a password reset link.</div>';
						} elseif (isset($_GET['sent']) && $_GET['sent'] === 'failed') {
							$forgot_message = '<div class="error-message">No account found with that email address.</div>';
						}
						echo $forgot_message;
						?>

						<form class="event-forgot-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
							<?php wp_nonce_field('event_rsvp_forgot_password', 'forgot_nonce'); ?>
							<input type="hidden" name="action" value="event_rsvp_forgot_password">
							
							<div class="form-field">
								<label for="user_email">Email Address <span class="required">*</span></label>
								<input type="email" id="user_email" name="user_email" placeholder="Enter your email address" required autofocus>
							</div>
							
							<div class="form-actions">
								<button type="submit" class="reset-submit-button">
									<span class="button-icon">✉️</span>
									<span class="button-text">Send Reset Link</span>
								</button>
							</div>
						</form>
					<?php endif; ?>

					<div class="reset-divider">
						<span class="divider-text">or</span>
					</div>

					<div class="reset-links">
						<p class="login-prompt">
							<a href="<?php echo esc_url(home_url('/login/')); ?>" class="login-link">
								<span class="link-icon">🔙</span> Back to Login
							</a>
						</p>
						<p class="signup-prompt">
							Don't have an account? <a href="<?php echo esc_url(home_url('/signup/')); ?>" class="signup-link">Sign up here</a>
						</p>
					</div>
				</div>
			</div>

			<div class="reset-info-section">
				<div class="info-content">
					<h2 class="info-heading">🔒 Security Tips</h2>
					<p class="info-subtitle">Keep your account safe</p>
					
					<div class="info-list">
						<div class="info-item">
							<div class="info-icon">🔐</div>
							<div class="info-text">
								<h3 class="info-title">Strong Passwords</h3>
								<p class="info-description">Use a mix of letters, numbers, and special characters</p>
							</div>
						</div>
						
						<div class="info-item">
							<div class="info-icon">🔄</div>
							<div class="info-text">
								<h3 class="info-title">Regular Updates</h3>
								<p class="info-description">Change your password every few months</p>
							</div>
						</div>
						
						<div class="info-item">
							<div class="info-icon">🚫</div>
							<div class="info-text">
								<h3 class="info-title">Avoid Common Passwords</h3>
								<p class="info-description">Don't use easy-to-guess passwords like "password123"</p>
							</div>
						</div>
						
						<div class="info-item">
							<div class="info-icon">👁️</div>
							<div class="info-text">
								<h3 class="info-title">Unique for Each Site</h3>
								<p class="info-description">Use different passwords for different accounts</p>
							</div>
						</div>

						<div class="info-item">
							<div class="info-icon">⚠️</div>
							<div class="info-text">
								<h3 class="info-title">Never Share</h3>
								<p class="info-description">Don't share your password with anyone</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</main>

<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/assets/css/reset-password.css">

<script>
document.addEventListener('DOMContentLoaded', function() {
	const newPasswordInput = document.getElementById('new_password');
	const confirmPasswordInput = document.getElementById('confirm_password');
	const strengthMeter = document.querySelector('.strength-meter-fill');
	const strengthLabel = document.querySelector('.strength-label');

	if (newPasswordInput) {
		newPasswordInput.addEventListener('input', function() {
			const password = this.value;
			const strength = calculatePasswordStrength(password);
			
			updateStrengthMeter(strength);
		});
	}

	function calculatePasswordStrength(password) {
		let strength = 0;
		
		if (password.length >= 8) strength += 20;
		if (password.length >= 12) strength += 20;
		if (/[a-z]/.test(password)) strength += 15;
		if (/[A-Z]/.test(password)) strength += 15;
		if (/[0-9]/.test(password)) strength += 15;
		if (/[^a-zA-Z0-9]/.test(password)) strength += 15;
		
		return Math.min(strength, 100);
	}

	function updateStrengthMeter(strength) {
		strengthMeter.style.width = strength + '%';
		
		if (strength < 30) {
			strengthMeter.style.backgroundColor = '#ef4444';
			strengthLabel.textContent = 'Weak';
		} else if (strength < 60) {
			strengthMeter.style.backgroundColor = '#f59e0b';
			strengthLabel.textContent = 'Fair';
		} else if (strength < 80) {
			strengthMeter.style.backgroundColor = '#3b82f6';
			strengthLabel.textContent = 'Good';
		} else {
			strengthMeter.style.backgroundColor = '#10b981';
			strengthLabel.textContent = 'Strong';
		}
	}

	const resetForm = document.querySelector('.event-reset-form');
	if (resetForm) {
		resetForm.addEventListener('submit', function(e) {
			const newPassword = newPasswordInput.value;
			const confirmPassword = confirmPasswordInput.value;
			
			if (newPassword !== confirmPassword) {
				e.preventDefault();
				alert('Passwords do not match. Please try again.');
				confirmPasswordInput.focus();
			}
		});
	}
});
</script>

<?php get_footer(); ?>

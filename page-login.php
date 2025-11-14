<?php
/**
 * Template Name: Login Page
 *
 * @package RSVP
 */

if (is_user_logged_in()) {
	wp_redirect(home_url('/my-account/'));
	exit;
}

get_header();
?>

<main id="primary" class="site-main login-page">
	<div class="container login-container">
		<div class="login-wrapper">
			<div class="login-form-section">
				<div class="login-card">
					<div class="login-header">
						<h1 class="login-title">Welcome Back</h1>
						<p class="login-subtitle">Sign in to access your event dashboard</p>
					</div>

					<?php
					$login_error = '';
					$redirect_to = isset($_GET['redirect_to']) ? $_GET['redirect_to'] : home_url('/my-account/');
					
					if (isset($_GET['login']) && $_GET['login'] === 'failed') {
						$login_error = 'Invalid username or password. Please try again.';
					} elseif (isset($_GET['login']) && $_GET['login'] === 'empty') {
						$login_error = 'Please enter your username and password.';
					}
					?>

					<?php if ($login_error) : ?>
						<div class="error-message">
							<?php echo esc_html($login_error); ?>
						</div>
					<?php endif; ?>

					<?php if (isset($_GET['registered']) && $_GET['registered'] === 'success') : ?>
						<div class="success-message">
							Account created successfully! Please log in.
						</div>
					<?php endif; ?>

					<?php if (isset($_GET['logout']) && $_GET['logout'] === 'success') : ?>
						<div class="success-message">
							You have been logged out successfully.
						</div>
					<?php endif; ?>

					<form class="event-login-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
						<?php wp_nonce_field('event_rsvp_login', 'login_nonce'); ?>
						<input type="hidden" name="action" value="event_rsvp_login">
						<input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">
						
						<div class="form-field">
							<label for="username">Username or Email <span class="required">*</span></label>
							<input type="text" id="username" name="log" placeholder="Enter your username or email" required autofocus>
						</div>
						
						<div class="form-field">
							<label for="password">Password <span class="required">*</span></label>
							<input type="password" id="password" name="pwd" placeholder="Enter your password" required>
						</div>
						
						<div class="form-checkbox">
							<input type="checkbox" id="remember" name="rememberme" value="forever">
							<label for="remember">Remember me</label>
						</div>
						
						<div class="form-actions">
							<button type="submit" class="login-submit-button">
								<span class="button-icon">🔐</span>
								<span class="button-text">Sign In</span>
							</button>
						</div>
					</form>

					<div class="login-divider">
						<span class="divider-text">or</span>
					</div>

					<div class="login-links">
						<p class="signup-prompt">
							Don't have an account? <a href="<?php echo esc_url(home_url('/signup/')); ?>" class="signup-link">Create one now</a>
						</p>
						<p class="forgot-password">
							<a href="<?php echo esc_url(wp_lostpassword_url()); ?>" class="forgot-link">Forgot your password?</a>
						</p>
					</div>
				</div>
			</div>

			<div class="login-benefits-section">
				<div class="benefits-content">
					<h2 class="benefits-heading">Why Choose Us?</h2>
					<p class="benefits-subtitle">Everything you need to manage successful events</p>
					
					<div class="benefits-list">
						<div class="benefit-item">
							<div class="benefit-icon">🎯</div>
							<div class="benefit-content">
								<h3 class="benefit-title">Easy Event Creation</h3>
								<p class="benefit-description">Create and publish events in minutes with our intuitive interface</p>
							</div>
						</div>
						
						<div class="benefit-item">
							<div class="benefit-icon">📱</div>
							<div class="benefit-content">
								<h3 class="benefit-title">QR Code Check-In</h3>
								<p class="benefit-description">Streamline attendee check-in with automatic QR code generation</p>
							</div>
						</div>
						
						<div class="benefit-item">
							<div class="benefit-icon">📊</div>
							<div class="benefit-content">
								<h3 class="benefit-title">Real-Time Analytics</h3>
								<p class="benefit-description">Track RSVPs and attendance with live statistics</p>
							</div>
						</div>
						
						<div class="benefit-item">
							<div class="benefit-icon">✉️</div>
							<div class="benefit-content">
								<h3 class="benefit-title">Automated Emails</h3>
								<p class="benefit-description">Send confirmations and reminders automatically</p>
							</div>
						</div>
						
						<div class="benefit-item">
							<div class="benefit-icon">💎</div>
							<div class="benefit-content">
								<h3 class="benefit-title">Professional Dashboard</h3>
								<p class="benefit-description">Manage all your events from one centralized location</p>
							</div>
						</div>
						
						<div class="benefit-item">
							<div class="benefit-icon">🔒</div>
							<div class="benefit-content">
								<h3 class="benefit-title">Secure & Reliable</h3>
								<p class="benefit-description">Your data is protected with enterprise-grade security</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</main>

<?php get_footer(); ?>

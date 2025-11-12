<?php
/**
 * Template Name: Signup Success Page
 *
 * @package RSVP
 */

$session_id = isset($_GET['session_id']) ? sanitize_text_field($_GET['session_id']) : '';
$token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';

get_header();
?>

<main id="primary" class="site-main signup-success-page">
	<div class="container success-container">
		<div class="success-content">
			<?php if ($session_id && $token) : ?>
				<div id="verification-pending" class="verification-box">
					<div class="spinner-wrapper">
						<div class="spinner"></div>
					</div>
					<h1 class="verification-title">Processing Your Payment...</h1>
					<p class="verification-message">Please wait while we verify your payment and create your account. This should only take a moment.</p>
				</div>

				<div id="verification-success" class="verification-box success-box" style="display: none;">
					<div class="success-icon">✓</div>
					<h1 class="success-title">Payment Successful!</h1>
					<p class="success-message">Your account has been created successfully. Redirecting you to your dashboard...</p>
				</div>

				<div id="verification-error" class="verification-box error-box" style="display: none;">
					<div class="error-icon">✗</div>
					<h1 class="error-title">Verification Issue</h1>
					<p class="error-message" id="error-message-text">We're still processing your payment. This can take a few moments.</p>
					<div class="error-actions">
						<button id="retry-verification" class="retry-button">Check Again</button>
						<p class="help-text">If this issue persists, please check your email for your account details or <a href="<?php echo esc_url(home_url('/contact/')); ?>">contact support</a>.</p>
					</div>
				</div>
			<?php else : ?>
				<div class="verification-box error-box">
					<div class="error-icon">⚠</div>
					<h1 class="error-title">Invalid Access</h1>
					<p class="error-message">This page requires valid payment verification data.</p>
					<div class="error-actions">
						<a href="<?php echo esc_url(home_url('/signup/')); ?>" class="button-primary">Back to Signup</a>
						<a href="<?php echo esc_url(home_url('/login/')); ?>" class="button-secondary">Login</a>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
</main>

<style>
.signup-success-page {
	min-height: 70vh;
	display: flex;
	align-items: center;
	justify-content: center;
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	padding: 40px 20px;
}

.success-container {
	max-width: 600px;
	margin: 0 auto;
}

.verification-box {
	background: white;
	border-radius: 12px;
	padding: 60px 40px;
	text-align: center;
	box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.spinner-wrapper {
	margin-bottom: 30px;
}

.spinner {
	width: 60px;
	height: 60px;
	border: 6px solid #f3f3f3;
	border-top: 6px solid #667eea;
	border-radius: 50%;
	margin: 0 auto;
	animation: spin 1s linear infinite;
}

@keyframes spin {
	0% { transform: rotate(0deg); }
	100% { transform: rotate(360deg); }
}

.success-icon,
.error-icon {
	width: 80px;
	height: 80px;
	margin: 0 auto 30px;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 48px;
	font-weight: bold;
}

.success-icon {
	background-color: #10b981;
	color: white;
}

.error-icon {
	background-color: #ef4444;
	color: white;
}

.verification-title,
.success-title,
.error-title {
	font-size: 2rem;
	margin: 0 0 20px 0;
	color: #1f2937;
}

.verification-message,
.success-message,
.error-message {
	font-size: 1.1rem;
	color: #6b7280;
	margin-bottom: 30px;
	line-height: 1.6;
}

.error-actions {
	margin-top: 30px;
}

.retry-button,
.button-primary,
.button-secondary {
	display: inline-block;
	padding: 12px 30px;
	border-radius: 6px;
	text-decoration: none;
	font-weight: 600;
	margin: 5px;
	border: none;
	cursor: pointer;
	font-size: 1rem;
	transition: all 0.3s ease;
}

.retry-button,
.button-primary {
	background-color: #667eea;
	color: white;
}

.retry-button:hover,
.button-primary:hover {
	background-color: #5568d3;
}

.button-secondary {
	background-color: #e5e7eb;
	color: #1f2937;
}

.button-secondary:hover {
	background-color: #d1d5db;
}

.help-text {
	margin-top: 20px;
	font-size: 0.9rem;
	color: #9ca3af;
}

.help-text a {
	color: #667eea;
	text-decoration: underline;
}

@media (max-width: 768px) {
	.verification-box {
		padding: 40px 20px;
	}

	.verification-title,
	.success-title,
	.error-title {
		font-size: 1.5rem;
	}

	.verification-message,
	.success-message,
	.error-message {
		font-size: 1rem;
	}
}
</style>

<?php if ($session_id && $token) : ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
	const sessionId = '<?php echo esc_js($session_id); ?>';
	const token = '<?php echo esc_js($token); ?>';
	let attemptCount = 0;
	const maxAttempts = 20;
	
	function verifyPayment() {
		attemptCount++;
		
		fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams({
				action: 'event_rsvp_verify_payment_token',
				nonce: '<?php echo wp_create_nonce('event_rsvp_verify_token'); ?>',
				session_id: sessionId,
				token: token
			})
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				document.getElementById('verification-pending').style.display = 'none';
				document.getElementById('verification-success').style.display = 'block';
				
				setTimeout(() => {
					window.location.href = data.data.redirect;
				}, 2000);
			} else {
				if (attemptCount < maxAttempts) {
					setTimeout(verifyPayment, 2000);
				} else {
					showError(data.data || 'Verification timeout. Please check your email.');
				}
			}
		})
		.catch(error => {
			console.error('Verification error:', error);
			if (attemptCount < maxAttempts) {
				setTimeout(verifyPayment, 2000);
			} else {
				showError('Unable to verify payment. Please check your email for account details.');
			}
		});
	}
	
	function showError(message) {
		document.getElementById('verification-pending').style.display = 'none';
		document.getElementById('verification-error').style.display = 'block';
		document.getElementById('error-message-text').textContent = message;
	}
	
	document.getElementById('retry-verification')?.addEventListener('click', function() {
		attemptCount = 0;
		document.getElementById('verification-error').style.display = 'none';
		document.getElementById('verification-pending').style.display = 'block';
		verifyPayment();
	});
	
	verifyPayment();
});
</script>
<?php endif; ?>

<?php get_footer(); ?>

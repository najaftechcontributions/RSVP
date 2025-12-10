<?php
/**
 * Join Event Modal Component
 * Modal for guests to RSVP to events or sign up
 *
 * @package RSVP
 */

if (!isset($event_id)) {
	return;
}

$event_title = get_the_title($event_id);
$event_date = get_field('event_date', $event_id);
$venue_address = get_field('venue_address', $event_id);
$is_full = event_rsvp_is_event_full($event_id);
$available_spots = event_rsvp_get_available_spots($event_id);

$modal_id = 'join-event-modal-' . $event_id;
?>

<div id="<?php echo esc_attr($modal_id); ?>" class="join-event-modal-overlay" style="display: none;">
	<div class="join-event-modal">
		<button class="modal-close-button" data-modal="<?php echo esc_attr($modal_id); ?>" aria-label="Close modal">
			<span class="close-icon">×</span>
		</button>

		<div class="modal-header">
			<h2 class="modal-title">Join This Event</h2>
			<div class="modal-event-info">
				<h3 class="modal-event-title"><?php echo esc_html($event_title); ?></h3>
				<?php if ($event_date) : ?>
					<p class="modal-event-date">
						<span class="info-icon">📅</span>
						<?php echo esc_html(date('F j, Y \a\t g:i A', strtotime($event_date))); ?>
					</p>
				<?php endif; ?>
				<?php if ($venue_address) : ?>
					<p class="modal-event-location">
						<span class="info-icon">📍</span>
						<?php echo esc_html($venue_address); ?>
					</p>
				<?php endif; ?>
				<?php if ($available_spots !== -1) : ?>
					<p class="modal-spots-info <?php echo $is_full ? 'spots-full' : 'spots-available'; ?>">
						<span class="info-icon">🎟️</span>
						<?php echo $is_full ? 'Event is full' : $available_spots . ' spots remaining'; ?>
					</p>
				<?php endif; ?>
			</div>
		</div>

		<div class="modal-body">
			<?php if ($is_full) : ?>
				<div class="modal-full-notice">
					<p class="full-notice-text">
						<span class="notice-icon">⚠️</span>
						Sorry, this event has reached maximum capacity.
					</p>
					<a href="<?php echo esc_url(home_url('/events/')); ?>" class="modal-button modal-button-secondary">
						Browse Other Events
					</a>
				</div>
			<?php elseif (is_user_logged_in()) : ?>
				<div class="modal-rsvp-form">
					<p class="form-intro-text">Complete your RSVP to receive your QR code via email.</p>
					
					<form class="event-rsvp-modal-form" data-event-id="<?php echo esc_attr($event_id); ?>">
						<?php wp_nonce_field('event_rsvp_submit', 'event_rsvp_nonce'); ?>
						<input type="hidden" name="action" value="event_rsvp_submit">
						<input type="hidden" name="event-id" value="<?php echo esc_attr($event_id); ?>">
						
						<?php
						$current_user = wp_get_current_user();
						$user_first_name = $current_user->first_name ?: '';
						$user_last_name = $current_user->last_name ?: '';
						if (empty($user_first_name) && empty($user_last_name)) {
							$user_first_name = $current_user->display_name ?: $current_user->user_login;
						}
						$user_email = $current_user->user_email;
						?>
						
						<div class="form-field">
							<label for="attendee-first-name-<?php echo $event_id; ?>">First Name <span class="required">*</span></label>
							<input type="text" id="attendee-first-name-<?php echo $event_id; ?>" name="attendee-first-name" value="<?php echo esc_attr($user_first_name); ?>" required>
						</div>

						<div class="form-field">
							<label for="attendee-last-name-<?php echo $event_id; ?>">Last Name <span class="required">*</span></label>
							<input type="text" id="attendee-last-name-<?php echo $event_id; ?>" name="attendee-last-name" value="<?php echo esc_attr($user_last_name); ?>" required>
						</div>
						
						<div class="form-field">
							<label for="attendee-email-<?php echo $event_id; ?>">Email Address <span class="required">*</span></label>
							<input type="email" id="attendee-email-<?php echo $event_id; ?>" name="attendee-email" value="<?php echo esc_attr($user_email); ?>" required>
						</div>
						
						<div class="form-field">
							<label for="attendee-phone-<?php echo $event_id; ?>">Phone Number (Optional)</label>
							<input type="tel" id="attendee-phone-<?php echo $event_id; ?>" name="attendee-phone" placeholder="(555) 123-4567">
						</div>
						
						<div class="form-field">
							<label for="rsvp-status-<?php echo $event_id; ?>">RSVP Status <span class="required">*</span></label>
							<select id="rsvp-status-<?php echo $event_id; ?>" name="rsvp-status" required>
								<option value="yes">Yes, I'll attend</option>
								<option value="maybe">Maybe</option>
								<option value="no">No, I can't attend</option>
							</select>
						</div>
						
						<div class="form-message" style="display: none;"></div>
						
						<div class="form-actions">
							<button type="submit" class="modal-button modal-button-primary">
								<span class="button-icon">✓</span>
								<span class="button-text">Confirm RSVP</span>
							</button>
						</div>
					</form>
				</div>
			<?php else : ?>
				<div class="modal-guest-options">
					<div class="guest-option-card">
						<div class="option-icon">✨</div>
						<h4 class="option-title">Quick RSVP (No Account)</h4>
						<p class="option-description">RSVP quickly and get your QR code via email</p>
						<button class="modal-button modal-button-primary show-quick-rsvp" data-event-id="<?php echo esc_attr($event_id); ?>">
							Continue as Guest
						</button>
					</div>
					
					<div class="options-divider">
						<span class="divider-text">OR</span>
					</div>
					
					<div class="guest-option-card">
						<div class="option-icon">🚀</div>
						<h4 class="option-title">Create Free Account</h4>
						<p class="option-description">Track all your events and RSVPs in one place</p>
						<a href="<?php echo esc_url(home_url('/signup/?plan=attendee&redirect=' . urlencode(get_permalink($event_id)))); ?>" class="modal-button modal-button-secondary">
							Sign Up Free
						</a>
					</div>
					
					<div class="login-prompt-link">
						Already have an account? 
						<a href="<?php echo esc_url(home_url('/login/?redirect=' . urlencode(get_permalink($event_id)))); ?>">Sign in</a>
					</div>
				</div>

				<div class="modal-quick-rsvp-form" style="display: none;">
					<button class="back-to-options-button">
						<span class="back-icon">←</span> Back to options
					</button>
					
					<p class="form-intro-text">Fill in your details to RSVP and receive your QR code.</p>
					
					<form class="event-rsvp-modal-form" data-event-id="<?php echo esc_attr($event_id); ?>">
						<?php wp_nonce_field('event_rsvp_submit', 'event_rsvp_nonce'); ?>
						<input type="hidden" name="action" value="event_rsvp_submit">
						<input type="hidden" name="event-id" value="<?php echo esc_attr($event_id); ?>">
						
						<div class="form-field">
							<label for="guest-attendee-first-name-<?php echo $event_id; ?>">First Name <span class="required">*</span></label>
							<input type="text" id="guest-attendee-first-name-<?php echo $event_id; ?>" name="attendee-first-name" required>
						</div>

						<div class="form-field">
							<label for="guest-attendee-last-name-<?php echo $event_id; ?>">Last Name <span class="required">*</span></label>
							<input type="text" id="guest-attendee-last-name-<?php echo $event_id; ?>" name="attendee-last-name" required>
						</div>
						
						<div class="form-field">
							<label for="guest-attendee-email-<?php echo $event_id; ?>">Email Address <span class="required">*</span></label>
							<input type="email" id="guest-attendee-email-<?php echo $event_id; ?>" name="attendee-email" required>
							<span class="field-hint">Your QR code will be sent here</span>
						</div>
						
						<div class="form-field">
							<label for="guest-attendee-phone-<?php echo $event_id; ?>">Phone Number (Optional)</label>
							<input type="tel" id="guest-attendee-phone-<?php echo $event_id; ?>" name="attendee-phone" placeholder="(555) 123-4567">
						</div>
						
						<div class="form-field">
							<label for="guest-rsvp-status-<?php echo $event_id; ?>">RSVP Status <span class="required">*</span></label>
							<select id="guest-rsvp-status-<?php echo $event_id; ?>" name="rsvp-status" required>
								<option value="yes">Yes, I'll attend</option>
								<option value="maybe">Maybe</option>
								<option value="no">No, I can't attend</option>
							</select>
						</div>
						
						<div class="form-message" style="display: none;"></div>
						
						<div class="form-actions">
							<button type="submit" class="modal-button modal-button-primary">
								<span class="button-icon">✓</span>
								<span class="button-text">Confirm RSVP</span>
							</button>
						</div>
					</form>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>

<style>
.join-event-modal-overlay {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background-color: rgba(0, 0, 0, 0.75);
	z-index: 9999;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 20px;
	animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
	from {
		opacity: 0;
	}
	to {
		opacity: 1;
	}
}

.join-event-modal {
	background-color: #ffffff;
	border-radius: 12px;
	max-width: 600px;
	width: 100%;
	max-height: 90vh;
	overflow-y: auto;
	box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
	position: relative;
	animation: slideUp 0.3s ease;
}

@keyframes slideUp {
	from {
		transform: translateY(30px);
		opacity: 0;
	}
	to {
		transform: translateY(0);
		opacity: 1;
	}
}

.modal-close-button {
	position: absolute;
	top: 15px;
	right: 15px;
	background: transparent;
	border: none;
	font-size: 2rem;
	color: var(--event-text-light);
	cursor: pointer;
	width: 40px;
	height: 40px;
	display: flex;
	align-items: center;
	justify-content: center;
	border-radius: 50%;
	transition: var(--event-transition);
	z-index: 10;
}

.modal-close-button:hover {
	background-color: rgba(0, 0, 0, 0.1);
	color: var(--event-text);
}

.close-icon {
	line-height: 1;
}

.modal-header {
	padding: 40px 30px 30px;
	background: linear-gradient(135deg, #503AA8 0%, #6B52C3 100%);
	color: #ffffff;
}

.modal-title {
	margin: 0 0 20px 0;
	font-size: 1.8rem;
	font-weight: 700;
}

.modal-event-info {
	background-color: rgba(255, 255, 255, 0.15);
	padding: 20px;
	border-radius: 8px;
	backdrop-filter: blur(10px);
}

.modal-event-title {
	margin: 0 0 15px 0;
	font-size: 1.3rem;
	font-weight: 600;
}

.modal-event-date,
.modal-event-location,
.modal-spots-info {
	margin: 8px 0;
	font-size: 0.95rem;
	display: flex;
	align-items: center;
	gap: 8px;
	opacity: 0.95;
}

.info-icon {
	font-size: 1.1rem;
}

.modal-spots-info.spots-full {
	color: var(--event-primary);
	font-weight: 600;
}

.modal-body {
	padding: 30px;
}

.modal-full-notice {
	text-align: center;
	padding: 40px 20px;
}

.full-notice-text {
	font-size: 1.1rem;
	color: var(--event-error);
	margin: 0 0 25px 0;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 10px;
}

.notice-icon {
	font-size: 1.5rem;
}

.modal-guest-options {
	display: flex;
	flex-direction: column;
	gap: 20px;
}

.guest-option-card {
	border: 2px solid var(--event-border);
	border-radius: 10px;
	padding: 25px;
	text-align: center;
	transition: var(--event-transition);
}

.guest-option-card:hover {
	border-color: #503AA8;
	box-shadow: 0 4px 12px rgba(80, 58, 168, 0.15);
	transform: translateY(-2px);
}

.option-icon {
	font-size: 3rem;
	margin-bottom: 15px;
}

.option-title {
	margin: 0 0 10px 0;
	font-size: 1.2rem;
	font-weight: 700;
	color: var(--event-text);
}

.option-description {
	margin: 0 0 20px 0;
	font-size: 0.95rem;
	color: var(--event-text-light);
}

.options-divider {
	text-align: center;
	position: relative;
	margin: 10px 0;
}

.options-divider::before,
.options-divider::after {
	content: '';
	position: absolute;
	top: 50%;
	width: 40%;
	height: 1px;
	background-color: var(--event-border);
}

.options-divider::before {
	left: 0;
}

.options-divider::after {
	right: 0;
}

.divider-text {
	background-color: #ffffff;
	padding: 0 15px;
	color: var(--event-text-light);
	font-size: 0.9rem;
	font-weight: 600;
}

.login-prompt-link {
	text-align: center;
	margin-top: 20px;
	padding-top: 20px;
	border-top: 1px solid var(--event-border);
	font-size: 0.9rem;
	color: var(--event-text-light);
}

.login-prompt-link a {
	color: #503AA8;
	font-weight: 600;
	text-decoration: none;
}

.login-prompt-link a:hover {
	text-decoration: underline;
}

.back-to-options-button {
	background: transparent;
	border: none;
	color: #503AA8;
	font-weight: 600;
	cursor: pointer;
	padding: 10px 0;
	margin-bottom: 20px;
	display: flex;
	align-items: center;
	gap: 5px;
	transition: var(--event-transition);
}

.back-to-options-button:hover {
	gap: 8px;
}

.back-icon {
	font-size: 1.2rem;
}

.form-intro-text {
	margin: 0 0 25px 0;
	color: var(--event-text-light);
	font-size: 0.95rem;
}

.event-rsvp-modal-form .form-field {
	margin-bottom: 20px;
}

.event-rsvp-modal-form label {
	display: block;
	margin-bottom: 8px;
	font-weight: 600;
	color: var(--event-text);
	font-size: 0.9rem;
}

.event-rsvp-modal-form .required {
	color: var(--event-error);
}

.event-rsvp-modal-form input,
.event-rsvp-modal-form select {
	width: 100%;
	padding: 12px 15px;
	border: 2px solid var(--event-border);
	border-radius: 8px;
	font-size: 1rem;
	transition: var(--event-transition);
	font-family: inherit;
}

.event-rsvp-modal-form input:focus,
.event-rsvp-modal-form select:focus {
	outline: none;
	border-color: #503AA8;
	box-shadow: 0 0 0 3px rgba(80, 58, 168, 0.1);
}

.field-hint {
	display: block;
	margin-top: 5px;
	font-size: 0.85rem;
	color: var(--event-text-light);
}

.form-message {
	padding: 12px 15px;
	border-radius: 8px;
	margin-bottom: 20px;
	font-size: 0.95rem;
}

.form-message.success-message {
	background-color: #d4edda;
	color: #155724;
	border: 1px solid #c3e6cb;
}

.form-message.error-message {
	background-color: #f8d7da;
	color: #721c24;
	border: 1px solid #f5c6cb;
}

.form-actions {
	margin-top: 25px;
}

.modal-button {
	display: inline-block;
	width: 100%;
	padding: 14px 28px;
	border-radius: 8px;
	font-weight: 700;
	font-size: 1rem;
	text-align: center;
	text-decoration: none;
	border: none;
	cursor: pointer;
	transition: var(--event-transition);
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 8px;
}

.modal-button-primary {
	background: linear-gradient(135deg, #FBC02D 0%, #F9A825 100%);
	color: var(--event-dark);
	box-shadow: 0 2px 8px rgba(251, 192, 45, 0.3);
}

.modal-button-primary:hover {
	background: linear-gradient(135deg, #F9A825 0%, #F57F17 100%);
	transform: translateY(-2px);
	box-shadow: 0 4px 12px rgba(251, 192, 45, 0.4);
}

.modal-button-primary:disabled {
	opacity: 0.6;
	cursor: not-allowed;
	transform: none;
}

.modal-button-secondary {
	background-color: #ffffff;
	color: #503AA8;
	border: 2px solid #503AA8;
}

.modal-button-secondary:hover {
	background-color: #503AA8;
	color: #ffffff;
	transform: translateY(-2px);
	box-shadow: 0 4px 12px rgba(80, 58, 168, 0.3);
}

.button-icon {
	font-size: 1.1rem;
}

@media (max-width: 768px) {
	.join-event-modal {
		max-width: 100%;
		max-height: 95vh;
	}
	
	.modal-header {
		padding: 30px 20px 20px;
	}
	
	.modal-title {
		font-size: 1.5rem;
	}
	
	.modal-event-title {
		font-size: 1.1rem;
	}
	
	.modal-body {
		padding: 20px;
	}
	
	.guest-option-card {
		padding: 20px;
	}
}

@media (max-width: 480px) {
	.modal-header {
		padding: 25px 15px 15px;
	}
	
	.modal-title {
		font-size: 1.3rem;
	}
	
	.modal-body {
		padding: 15px;
	}
	
	.modal-button {
		padding: 12px 24px;
		font-size: 0.95rem;
	}
}
</style>

<script>
(function() {
	const modalId = '<?php echo esc_js($modal_id); ?>';
	const modal = document.getElementById(modalId);
	
	if (!modal) return;
	
	const closeButton = modal.querySelector('.modal-close-button');
	const showQuickRsvpBtn = modal.querySelector('.show-quick-rsvp');
	const backToOptionsBtn = modal.querySelector('.back-to-options-button');
	const guestOptions = modal.querySelector('.modal-guest-options');
	const quickRsvpForm = modal.querySelector('.modal-quick-rsvp-form');
	
	if (closeButton) {
		closeButton.addEventListener('click', function() {
			modal.style.display = 'none';
			document.body.style.overflow = '';
		});
	}
	
	modal.addEventListener('click', function(e) {
		if (e.target === modal) {
			modal.style.display = 'none';
			document.body.style.overflow = '';
		}
	});
	
	if (showQuickRsvpBtn && guestOptions && quickRsvpForm) {
		showQuickRsvpBtn.addEventListener('click', function() {
			guestOptions.style.display = 'none';
			quickRsvpForm.style.display = 'block';
		});
	}
	
	if (backToOptionsBtn && guestOptions && quickRsvpForm) {
		backToOptionsBtn.addEventListener('click', function() {
			quickRsvpForm.style.display = 'none';
			guestOptions.style.display = 'block';
		});
	}
	
	const rsvpForms = modal.querySelectorAll('.event-rsvp-modal-form');
	rsvpForms.forEach(function(form) {
		form.addEventListener('submit', function(e) {
			e.preventDefault();
			
			const submitBtn = form.querySelector('button[type="submit"]');
			const messageEl = form.querySelector('.form-message');
			const formData = new FormData(form);
			
			submitBtn.disabled = true;
			submitBtn.innerHTML = '<span class="button-icon">⏳</span><span class="button-text">Processing...</span>';
			
			fetch('<?php echo admin_url("admin-post.php"); ?>', {
				method: 'POST',
				body: formData
			})
			.then(response => {
				if (response.redirected) {
					window.location.href = response.url;
					return;
				}
				return response.text();
			})
			.then(data => {
				if (messageEl) {
					messageEl.textContent = 'RSVP confirmed! Check your email for your QR code.';
					messageEl.className = 'form-message success-message';
					messageEl.style.display = 'block';
				}
				
				setTimeout(function() {
					modal.style.display = 'none';
					document.body.style.overflow = '';
					window.location.reload();
				}, 2000);
			})
			.catch(error => {
				console.error('Error:', error);
				if (messageEl) {
					messageEl.textContent = 'An error occurred. Please try again.';
					messageEl.className = 'form-message error-message';
					messageEl.style.display = 'block';
				}
				submitBtn.disabled = false;
				submitBtn.innerHTML = '<span class="button-icon">✓</span><span class="button-text">Confirm RSVP</span>';
			});
		});
	});
})();
</script>

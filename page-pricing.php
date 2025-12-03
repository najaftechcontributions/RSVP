<?php
/**
 * Template Name: Pricing Page
 *
 * @package RSVP
 */

get_header();

$is_logged_in = is_user_logged_in();
$current_user = wp_get_current_user();
$user_plan = $is_logged_in ? Event_RSVP_Simple_Stripe::get_user_plan() : '';
?>

<main id="primary" class="site-main pricing-page">
	<div class="pricing-hero">
		<div class="container">
			<h1 class="pricing-title">Simple, Transparent Pricing</h1>
			<p class="pricing-subtitle">Choose the plan that's right for you. No hidden fees, cancel anytime.</p>
			<?php if ($is_logged_in && $user_plan && $user_plan !== 'attendee') : ?>
				<div class="current-plan-notice">
					<p>Your current plan: <strong><?php echo esc_html(ucwords(str_replace('_', ' ', $user_plan))); ?></strong></p>
					<p class="plan-info">To manage your subscription, log in to your <a href="https://billing.stripe.com/p/login" target="_blank" class="manage-subscription-link">Stripe Customer Portal</a></p>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<div class="container pricing-container">
		<div class="pricing-grid">
			
			<!-- Attendee Plan -->
			<div class="pricing-card-wrapper">
				<div class="pricing-card pricing-card-attendee">
					<div class="pricing-card-content">
						<h3 class="plan-name">Attendee</h3>
						<p class="plan-description">Browse and join events for free</p>
						
						<div class="price-container">
							<p class="price">$0</p>
							<p class="price-period">forever free</p>
						</div>
						
						<ul class="features-list">
							<li>✓ Browse all public events</li>
							<li>✓ Free event RSVP</li>
							<li>✓ QR code for check-in</li>
							<li>✓ Event reminders</li>
							<li>✓ Save favorite events</li>
							<li>✗ Create events</li>
							<li>✗ Post advertisements</li>
							<li>✗ Analytics dashboard</li>
							<li>✗ Priority support</li>
						</ul>
						
						<div class="pricing-cta">
							<?php if (!$is_logged_in) : ?>
								<a href="<?php echo esc_url(home_url('/signup/?plan=attendee')); ?>" class="pricing-button pricing-button-outline">
									Sign Up Free
								</a>
							<?php elseif ($user_plan === '' || $user_plan === 'attendee') : ?>
								<button class="pricing-button pricing-button-outline" disabled>Current Plan</button>
							<?php else : ?>
								<span class="plan-note">Downgrade available in account settings</span>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>

			<!-- Pay As You Go Plan -->
			<div class="pricing-card-wrapper">
				<div class="pricing-card pricing-card-host">
					<div class="pricing-card-content">
						<h3 class="plan-name">Pay As You Go</h3>
						<p class="plan-description">Perfect for individual event hosts</p>
						
						<div class="price-container">
							<p class="price">$29.99</p>
							<p class="price-period">per month</p>
						</div>
						
						<ul class="features-list">
							<li>✓ Create up to 1 event</li>
							<li>✓ Custom RSVP forms</li>
							<li>✓ QR code check-in</li>
							<li>✓ Email notifications</li>
							<li>✓ Email campaigns</li>
							<li>✓ CSV exports</li>
							<li>✓ Event analytics</li>
							<li>✓ Email support</li>
						</ul>
						
						<div class="pricing-cta">
							<?php if ($user_plan === 'pay_as_you_go' || $user_plan === 'event_host') : ?>
								<button class="pricing-button pricing-button-primary" disabled>Current Plan</button>
							<?php else : ?>
								<?php if ($is_logged_in) : ?>
									<button class="pricing-button pricing-button-primary upgrade-plan-btn" data-plan="pay_as_you_go">
										Upgrade Now
									</button>
								<?php else : ?>
									<a href="<?php echo esc_url(home_url('/signup/?plan=pay_as_you_go')); ?>" class="pricing-button pricing-button-primary">
										Start Hosting
									</a>
								<?php endif; ?>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>

			<!-- Event Planner Plan -->
			<div class="pricing-card-wrapper">
				<div class="pricing-card pricing-card-host pricing-card-featured">
					<span class="popular-badge">Most Popular</span>
					<div class="pricing-card-content">
						<h3 class="plan-name">Event Planner</h3>
						<p class="plan-description">For professional event planners</p>
						
						<div class="price-container">
							<p class="price">$119.99</p>
							<p class="price-period">per month</p>
						</div>
						
						<ul class="features-list">
							<li>✓ Create up to 5 events</li>
							<li>✓ Custom RSVP forms</li>
							<li>✓ QR code check-in</li>
							<li>✓ Email notifications</li>
							<li>✓ Email campaigns</li>
							<li>✓ CSV exports</li>
							<li>✓ Event analytics</li>
							<li>✓ Priority email support</li>
							<li>✓ Advanced features</li>
						</ul>
						
						<div class="pricing-cta">
							<?php if ($user_plan === 'event_planner') : ?>
								<button class="pricing-button pricing-button-primary" disabled>Current Plan</button>
							<?php else : ?>
								<?php if ($is_logged_in) : ?>
									<button class="pricing-button pricing-button-primary upgrade-plan-btn" data-plan="event_planner">
										Upgrade Now
									</button>
								<?php else : ?>
									<a href="<?php echo esc_url(home_url('/signup/?plan=event_planner')); ?>" class="pricing-button pricing-button-primary">
										Start Planning
									</a>
								<?php endif; ?>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>

			<!-- Verbiage Plan -->
			<div class="pricing-card-wrapper">
				<div class="pricing-card pricing-card-pro">
					<div class="pricing-card-content">
						<h3 class="plan-name">Verbiage</h3>
						<p class="plan-description">For high-volume event organizers</p>
						
						<div class="price-container">
							<p class="price">Contact Us</p>
							<p class="price-period">custom pricing</p>
						</div>
						
						<ul class="features-list">
							<li>✓ More than 5 events</li>
							<li>✓ Custom RSVP forms</li>
							<li>✓ QR code check-in</li>
							<li>✓ Email notifications</li>
							<li>✓ Email campaigns</li>
							<li>✓ CSV exports</li>
							<li>✓ Event analytics</li>
							<li>✓ Priority support</li>
							<li>✓ Custom features</li>
						</ul>
						
						<div class="pricing-cta">
							<a href="mailto:<?php echo esc_attr(get_option('admin_email')); ?>?subject=Verbiage Plan Inquiry" class="pricing-button pricing-button-outline">
								Contact Us
							</a>
						</div>
					</div>
				</div>
			</div>

			<!-- Vendor Plan - Commented Out -->
			<div class="pricing-card-wrapper">
				<div class="pricing-card pricing-card-vendor">
					<div class="pricing-card-content">
						<h3 class="plan-name">Vendor</h3>
						<p class="plan-description">Advertise your business or services</p>
						
						<div class="price-container">
							<p class="price">Coming Soon</p>
							<p class="price-period">per month</p>
						</div>
						
						<ul class="features-list">
							<li>Coming Soon</li>
						</ul>
					</div>
				</div>
			</div>

			<!-- Pro Plan - Commented Out -->
			<div class="pricing-card-wrapper">
				<div class="pricing-card pricing-card-pro">
					<div class="pricing-card-content">
						<h3 class="plan-name">Pro (Both)</h3>
						<p class="plan-description">Host events & advertise - Best value!</p>
						
						<div class="price-container">
							<p class="price">Coming Soon</p>
							<p class="price-period">per month</p>
						</div>
						
						<ul class="features-list">
							<li>Coming Soon</li>
						</ul>
					</div>
				</div>
			</div>

		</div>

		<div class="pricing-comparison-section">
			<h2 class="comparison-heading">Compare All Features</h2>
			
			<div class="comparison-table-wrapper">
				<table class="comparison-table">
					<thead>
						<tr>
							<th class="feature-column">Feature</th>
							<th class="plan-column">Attendee</th>
							<th class="plan-column">Pay As You Go</th>
							<th class="plan-column plan-column-featured">Event Planner</th>
							<th class="plan-column">Verbiage</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td class="feature-name">Browse & RSVP Events</td>
							<td class="feature-value">✓</td>
							<td class="feature-value">✓</td>
							<td class="feature-value">✓</td>
							<td class="feature-value">✓</td>
						</tr>
						<tr>
							<td class="feature-name">Events Per Month</td>
							<td class="feature-value">—</td>
							<td class="feature-value">1</td>
							<td class="feature-value">5</td>
							<td class="feature-value">5+</td>
						</tr>
						<tr>
							<td class="feature-name">Email Campaigns</td>
							<td class="feature-value">—</td>
							<td class="feature-value">✓</td>
							<td class="feature-value">✓</td>
							<td class="feature-value">✓</td>
						</tr>
						<tr>
							<td class="feature-name">Event Analytics</td>
							<td class="feature-value">—</td>
							<td class="feature-value">Basic</td>
							<td class="feature-value">Advanced</td>
							<td class="feature-value">Advanced</td>
						</tr>
						<tr>
							<td class="feature-name">QR Code Check-in</td>
							<td class="feature-value">✓</td>
							<td class="feature-value">✓</td>
							<td class="feature-value">✓</td>
							<td class="feature-value">✓</td>
						</tr>
						<tr>
							<td class="feature-name">Email Notifications</td>
							<td class="feature-value">✓</td>
							<td class="feature-value">✓</td>
							<td class="feature-value">✓</td>
							<td class="feature-value">✓</td>
						</tr>
						<tr>
							<td class="feature-name">Data Export</td>
							<td class="feature-value">—</td>
							<td class="feature-value">CSV</td>
							<td class="feature-value">CSV</td>
							<td class="feature-value">CSV</td>
						</tr>
						<tr>
							<td class="feature-name">Support</td>
							<td class="feature-value">Community</td>
							<td class="feature-value">Email</td>
							<td class="feature-value">Priority</td>
							<td class="feature-value">Priority</td>
						</tr>
						<tr>
							<td class="feature-name">Monthly Cost</td>
							<td class="feature-value">$0</td>
							<td class="feature-value">$29.99</td>
							<td class="feature-value">$119.99</td>
							<td class="feature-value">Custom</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<div class="pricing-faq-section">
			<h2 class="faq-heading">Frequently Asked Questions</h2>
			
			<div class="faq-grid">
				<div class="faq-item">
					<h3 class="faq-question">Can I change plans later?</h3>
					<p class="faq-answer">Yes! You can upgrade or downgrade your plan at any time from your account settings. Changes take effect immediately and we'll prorate the difference.</p>
				</div>
				
				<div class="faq-item">
					<h3 class="faq-question">Is the Attendee plan really free?</h3>
					<p class="faq-answer">Absolutely! The Attendee plan is 100% free forever. You can browse and RSVP to unlimited events at no cost.</p>
				</div>
				
				<div class="faq-item">
					<h3 class="faq-question">What happens when I reach my event limit?</h3>
					<p class="faq-answer">Once you reach your event limit, you'll need to upgrade to a higher plan or delete old events before creating new ones.</p>
				</div>
				
				<div class="faq-item">
					<h3 class="faq-question">What payment methods do you accept?</h3>
					<p class="faq-answer">We accept all major credit cards through Stripe. Payments are secure and encrypted. We also support various payment methods available through Stripe.</p>
				</div>
				
				<div class="faq-item">
					<h3 class="faq-question">Can I cancel anytime?</h3>
					<p class="faq-answer">Yes, you can cancel your subscription at any time from your account settings. You'll retain access until the end of your billing period.</p>
				</div>
				
				<div class="faq-item">
					<h3 class="faq-question">Do you offer refunds?</h3>
					<p class="faq-answer">We offer a 30-day money-back guarantee. If you're not satisfied, contact us for a full refund.</p>
				</div>
			</div>
		</div>

		<div class="pricing-cta-section">
			<div class="cta-card">
				<h2 class="cta-heading">Ready to Get Started?</h2>
				<p class="cta-text">Join thousands who trust our platform for their events and event management needs.</p>
				<div class="cta-buttons">
					<a href="<?php echo esc_url(home_url('/signup/')); ?>" class="cta-button cta-button-primary">
						Create Free Account
					</a>
					<a href="<?php echo esc_url(home_url('/events/')); ?>" class="cta-button cta-button-secondary">
						Browse Events
					</a>
				</div>
			</div>
		</div>
	</div>
</main>

<style>
.current-plan-notice {
	background-color: #f0f7ff;
	border: 2px solid #3b82f6;
	border-radius: 8px;
	padding: 15px 20px;
	margin-top: 20px;
	text-align: center;
}

.current-plan-notice p {
	margin: 0 0 10px 0;
	font-size: 1.1rem;
	color: #1e40af;
}

.manage-subscription-link {
	color: #3b82f6;
	text-decoration: none;
	font-weight: 600;
	border: 2px solid #3b82f6;
	padding: 8px 16px;
	border-radius: 4px;
	display: inline-block;
	transition: all 0.3s ease;
}

.manage-subscription-link:hover {
	background-color: #3b82f6;
	color: white;
}

.plan-note {
	font-size: 0.9rem;
	color: #6b7280;
	font-style: italic;
}

.pricing-button:disabled {
	opacity: 0.6;
	cursor: not-allowed;
}

.popular-badge {
	position: absolute;
	top: 15px;
	right: 15px;
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	color: white;
	padding: 5px 15px;
	border-radius: 20px;
	font-size: 0.75rem;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.pricing-card-wrapper {
	position: relative;
}

.pricing-card-featured {
	border: 2px solid #667eea;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const upgradeBtns = document.querySelectorAll('.upgrade-plan-btn');

	upgradeBtns.forEach(btn => {
		btn.addEventListener('click', function() {
			const plan = this.getAttribute('data-plan');
			const originalText = this.textContent;

			this.disabled = true;
			this.textContent = 'Processing...';

			fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams({
					action: 'event_rsvp_initiate_upgrade',
					plan: plan,
					nonce: '<?php echo wp_create_nonce('event_rsvp_upgrade'); ?>'
				})
			})
			.then(response => response.json())
			.then(data => {
				if (data.success && data.data.checkout_url) {
					this.textContent = 'Redirecting to checkout...';
					window.location.href = data.data.checkout_url;
				} else {
					alert(data.data || 'Failed to initiate upgrade. Please try again.');
					this.disabled = false;
					this.textContent = originalText;
				}
			})
			.catch(error => {
				console.error('Error:', error);
				alert('An error occurred. Please try again.');
				this.disabled = false;
				this.textContent = originalText;
			});
		});
	});
});
</script>

<?php
get_footer();
?>

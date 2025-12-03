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

			<div class="pricing-card-wrapper">
				<div class="pricing-card pricing-card-host">
					<div class="pricing-card-content">
						<h3 class="plan-name">Pay As You Go</h3>
						<p class="plan-description">Create and manage your events</p>
						
						<div class="price-container">
							<p class="price">$29.99</p>
							<p class="price-period">per month</p>
						</div>
						
						<ul class="features-list">
							<li>✓ 1 event</li>
							<!-- <li>✓ 500 attendees per event</li> -->
							<li>✓ Custom RSVP forms</li>
							<li>✓ QR code check-in</li>
							<li>✓ Email notifications</li>
							<li>✓ CSV exports</li>
							<li>✓ Event analytics</li>
							<li>✓ Email support</li>
							<!-- <li>✗ Ad posting</li> -->
						</ul>
						
						<div class="pricing-cta">
							<?php if ($user_plan === 'event_host') : ?>
								<button class="pricing-button pricing-button-primary" disabled>Current Plan</button>
							<?php else : ?>
								<?php if ($is_logged_in) : ?>
									<button class="pricing-button pricing-button-primary upgrade-plan-btn" data-plan="event_host">
										Upgrade Now
									</button>
								<?php else : ?>
									<a href="<?php echo esc_url(home_url('/signup/?plan=event_host')); ?>" class="pricing-button pricing-button-primary">
										Start Hosting
									</a>
								<?php endif; ?>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
			<div class="pricing-card-wrapper">
				<div class="pricing-card pricing-card-host">
					<div class="pricing-card-content">
						<h3 class="plan-name">Event Planner</h3>
						<p class="plan-description">Create and manage your events</p>
						
						<div class="price-container">
							<p class="price">$119.99</p>
							<p class="price-period">per month</p>
						</div>
						
						<ul class="features-list">
							<li>✓ 5 events</li>
							<!-- <li>✓ 500 attendees per event</li> -->
							<li>✓ Custom RSVP forms</li>
							<li>✓ QR code check-in</li>
							<li>✓ Email notifications</li>
							<li>✓ CSV exports</li>
							<li>✓ Event analytics</li>
							<li>✓ Email support</li>
							<!-- <li>✗ Ad posting</li> -->
						</ul>
						
						<div class="pricing-cta">
							<?php if ($user_plan === 'event_host') : ?>
								<button class="pricing-button pricing-button-primary" disabled>Current Plan</button>
							<?php else : ?>
								<?php if ($is_logged_in) : ?>
									<button class="pricing-button pricing-button-primary upgrade-plan-btn" data-plan="event_host">
										Upgrade Now
									</button>
								<?php else : ?>
									<a href="<?php echo esc_url(home_url('/signup/?plan=event_host')); ?>" class="pricing-button pricing-button-primary">
										Start Hosting
									</a>
								<?php endif; ?>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>

			<div class="pricing-card-wrapper">
				<div class="pricing-card pricing-card-vendor">
					<div class="pricing-card-content">
						<h3 class="plan-name">Vendor</h3>
						<p class="plan-description">Advertise your business or services</p>
						
						<div class="price-container">
							<p class="price">$--</p>
							<p class="price-period">per month</p>
						</div>
						
						<ul class="features-list">
							<li>Comming Soon</li>
							<!-- <li>✓ Post vendor advertisements</li>
							<li>✓ Featured ad placements</li>
							<li>✓ Ad analytics & tracking</li>
							<li>✓ Multiple ad slots</li>
							<li>✓ Target event audiences</li>
							<li>✓ Email support</li>
							<li>✓ Browse & attend events</li>
							<li>✗ Create events</li>
							<li>✗ Event management</li> -->
						</ul>
						
						<!-- <div class="pricing-cta">
							<?php if ($user_plan === 'vendor') : ?>
								<button class="pricing-button pricing-button-outline" disabled>Current Plan</button>
							<?php else : ?>
								<?php if ($is_logged_in) : ?>
									<button class="pricing-button pricing-button-outline upgrade-plan-btn" data-plan="vendor">
										Upgrade Now
									</button>
								<?php else : ?>
									<a href="<?php echo esc_url(home_url('/signup/?plan=vendor')); ?>" class="pricing-button pricing-button-outline">
										Start Advertising
									</a>
								<?php endif; ?>
							<?php endif; ?>
						</div> -->
					</div>
				</div>
			</div>

			<div class="pricing-card-wrapper">
				<div class="pricing-card pricing-card-pro pricing-card-featured">
					<!-- <span class="popular-badge">Most Popular</span> -->
					<div class="pricing-card-content">
						<h3 class="plan-name">Pro (Both)</h3>
						<p class="plan-description">Host events & advertise - Best value!</p>
						
						<div class="price-container">
							<p class="price">$--</p>
							<p class="price-period">per month</p>
						</div>
						
						<ul class="features-list">
							<li>Comming Soon</li>
							<!--<li>✓ Everything in Event Host</li>
							<li>✓ Everything in Vendor</li>
							<li>✓ Unlimited events</li>
							<li>✓ Unlimited ad postings</li>
							<li>✓ Priority ad placement</li>
							<li>✓ Advanced analytics</li>
							<li>✓ Custom branding</li>
							<li>✓ Priority support</li>
							<li>✓ Save $9/month!</li> -->
						</ul>
						
						<!-- <div class="pricing-cta">
							<?php if ($user_plan === 'pro') : ?>
								<button class="pricing-button pricing-button-primary" disabled>Current Plan</button>
							<?php else : ?>
								<?php if ($is_logged_in) : ?>
									<button class="pricing-button pricing-button-primary upgrade-plan-btn" data-plan="pro">
										Upgrade to Pro
									</button>
								<?php else : ?>
									<a href="<?php echo esc_url(home_url('/signup/?plan=pro')); ?>" class="pricing-button pricing-button-primary">
										Get Pro Access
									</a>
								<?php endif; ?>
							<?php endif; ?>
						</div> -->
					</div>
				</div>
			</div>
			<div class="pricing-card-wrapper">
				<div class="pricing-card pricing-card-pro pricing-card-featured">
					<!-- <span class="popular-badge">Most Popular</span> -->
					<div class="pricing-card-content">
						<h3 class="plan-name">Verbiage</h3>
						<p class="plan-description">Host events & advertise - Best value!</p>
						
						<!-- <div class="price-container">
							<p class="price">$--</p>
							<p class="price-period">per month</p>
						</div> -->
						
						<ul class="features-list">
						<li>✓ More then 5 events</li>
							<!-- <li>✓ 500 attendees per event</li> -->
							<li>✓ Custom RSVP forms</li>
							<li>✓ QR code check-in</li>
							<li>✓ Email notifications</li>
							<li>✓ CSV exports</li>
							<li>✓ Event analytics</li>
							<li>✓ Email support</li>
							<!-- <li>✗ Ad posting</li> -->
						</ul>
						
						<div class="pricing-cta">
							<?php if ($user_plan === 'pro') : ?>
								<button class="pricing-button pricing-button-primary" disabled>Current Plan</button>
							<?php else : ?>
								<?php if ($is_logged_in) : ?>
									<button class="pricing-button pricing-button-primary upgrade-plan-btn" data-plan="pro">
										Contact Us
									</button>
								<?php else : ?>
									<a href="<?php echo esc_url(home_url('/signup/?plan=pro')); ?>" class="pricing-button pricing-button-primary">
										Contact Us
									</a>
								<?php endif; ?>
							<?php endif; ?>
						</div>
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
							<th class="plan-column">Event Host</th>
							<th class="plan-column">Vendor</th>
							<th class="plan-column plan-column-featured">Pro</th>
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
							<td class="feature-name">Create Events</td>
							<td class="feature-value">—</td>
							<td class="feature-value">✓</td>
							<td class="feature-value">—</td>
							<td class="feature-value">✓</td>
						</tr>
						<tr>
							<td class="feature-name">Post Advertisements</td>
							<td class="feature-value">—</td>
							<td class="feature-value">—</td>
							<td class="feature-value">✓</td>
							<td class="feature-value">✓</td>
						</tr>
						<tr>
							<td class="feature-name">Event Analytics</td>
							<td class="feature-value">—</td>
							<td class="feature-value">Basic</td>
							<td class="feature-value">—</td>
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
							<td class="feature-name">Custom Branding</td>
							<td class="feature-value">—</td>
							<td class="feature-value">—</td>
							<td class="feature-value">—</td>
							<td class="feature-value">✓</td>
						</tr>
						<tr>
							<td class="feature-name">Data Export</td>
							<td class="feature-value">—</td>
							<td class="feature-value">CSV</td>
							<td class="feature-value">CSV</td>
							<td class="feature-value">All Formats</td>
						</tr>
						<tr>
							<td class="feature-name">Support</td>
							<td class="feature-value">Community</td>
							<td class="feature-value">Email</td>
							<td class="feature-value">Email</td>
							<td class="feature-value">Priority</td>
						</tr>
						<tr>
							<td class="feature-name">Monthly Cost</td>
							<td class="feature-value">$0</td>
							<td class="feature-value">$19</td>
							<td class="feature-value">$29</td>
							<td class="feature-value">$39</td>
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
					<h3 class="faq-question">What's the difference between Event Host and Vendor?</h3>
					<p class="faq-answer">Event Host allows you to create and manage events. Vendor allows you to post advertisements. Pro gives you both capabilities!</p>
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
				<p class="cta-text">Join thousands who trust our platform for their events and advertising needs.</p>
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
</style>

<?php
function event_rsvp_get_plan_url($plan_slug, $is_logged_in) {
	if (!$is_logged_in) {
		return home_url('/signup/?plan=' . $plan_slug);
	}
	
	if (!class_exists('WooCommerce')) {
		return home_url('/signup/?plan=' . $plan_slug);
	}
	
	// Simple redirect - no WooCommerce needed
	return home_url('/signup/?plan=' . $plan_slug);
	/*
	$product_id = $wc_integration->get_product_id($plan_slug);
	
	if (!$product_id) {
		return home_url('/signup/?plan=' . $plan_slug);
	}
	
	WC()->cart->empty_cart();
	WC()->cart->add_to_cart($product_id);
	
	return wc_get_checkout_url();
	*/
}

?>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const upgradeBtns = document.querySelectorAll('.upgrade-plan-btn');

	upgradeBtns.forEach(btn => {
		btn.addEventListener('click', function() {
			const plan = this.getAttribute('data-plan');
			const originalText = this.textContent;

			// Disable button
			this.disabled = true;
			this.textContent = 'Processing...';

			// Make AJAX request
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
					// Redirect to Stripe checkout
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

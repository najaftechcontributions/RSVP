<?php
/**
 * Template Name: Pricing Page
 *
 * @package RSVP
 */

get_header();
?>

<main id="primary" class="site-main pricing-page">
	<div class="pricing-hero">
		<div class="container">
			<h1 class="pricing-title">Simple, Transparent Pricing</h1>
			<p class="pricing-subtitle">Choose the plan that's right for you. No hidden fees, cancel anytime.</p>
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
							<a href="<?php echo esc_url(home_url('/signup/?plan=attendee')); ?>" class="pricing-button pricing-button-outline">
								Sign Up Free
							</a>
						</div>
					</div>
				</div>
			</div>

			<div class="pricing-card-wrapper">
				<div class="pricing-card pricing-card-host">
					<div class="pricing-card-content">
						<h3 class="plan-name">Event Host</h3>
						<p class="plan-description">Create and manage your events</p>
						
						<div class="price-container">
							<p class="price">$19</p>
							<p class="price-period">per month</p>
						</div>
						
						<ul class="features-list">
							<li>✓ Unlimited events</li>
							<li>✓ 500 attendees per event</li>
							<li>✓ Custom RSVP forms</li>
							<li>✓ QR code check-in</li>
							<li>✓ Email notifications</li>
							<li>✓ CSV exports</li>
							<li>✓ Event analytics</li>
							<li>✓ Email support</li>
							<li>✗ Ad posting</li>
						</ul>
						
						<div class="pricing-cta">
							<a href="<?php echo esc_url(home_url('/signup/?plan=event_host')); ?>" class="pricing-button pricing-button-primary">
								Start Hosting
							</a>
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
							<p class="price">$29</p>
							<p class="price-period">per month</p>
						</div>
						
						<ul class="features-list">
							<li>✓ Post vendor advertisements</li>
							<li>✓ Featured ad placements</li>
							<li>✓ Ad analytics & tracking</li>
							<li>✓ Multiple ad slots</li>
							<li>✓ Target event audiences</li>
							<li>✓ Email support</li>
							<li>✓ Browse & attend events</li>
							<li>✗ Create events</li>
							<li>✗ Event management</li>
						</ul>
						
						<div class="pricing-cta">
							<a href="<?php echo esc_url(home_url('/signup/?plan=vendor')); ?>" class="pricing-button pricing-button-outline">
								Start Advertising
							</a>
						</div>
					</div>
				</div>
			</div>

			<div class="pricing-card-wrapper">
				<div class="pricing-card pricing-card-pro pricing-card-featured">
					<span class="popular-badge">Most Popular</span>
					<div class="pricing-card-content">
						<h3 class="plan-name">Pro (Both)</h3>
						<p class="plan-description">Host events & advertise - Best value!</p>
						
						<div class="price-container">
							<p class="price">$39</p>
							<p class="price-period">per month</p>
						</div>
						
						<ul class="features-list">
							<li>✓ Everything in Event Host</li>
							<li>✓ Everything in Vendor</li>
							<li>✓ Unlimited events</li>
							<li>✓ Unlimited ad postings</li>
							<li>✓ Priority ad placement</li>
							<li>✓ Advanced analytics</li>
							<li>✓ Custom branding</li>
							<li>✓ Priority support</li>
							<li>✓ Save $9/month!</li>
						</ul>
						
						<div class="pricing-cta">
							<a href="<?php echo esc_url(home_url('/signup/?plan=pro')); ?>" class="pricing-button pricing-button-primary">
								Get Pro Access
							</a>
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
					<p class="faq-answer">Yes! You can upgrade or downgrade your plan at any time. Changes take effect immediately and we'll prorate the difference.</p>
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
					<p class="faq-answer">We accept all major credit cards, PayPal, and can set up invoicing for Enterprise customers.</p>
				</div>
				
				<div class="faq-item">
					<h3 class="faq-question">Can I cancel anytime?</h3>
					<p class="faq-answer">Yes, you can cancel your subscription at any time. You'll retain access until the end of your billing period.</p>
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

<?php get_footer(); ?>

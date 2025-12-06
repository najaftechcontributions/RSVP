<?php
/**
 * Template Name: My Account Page
 *
 * @package RSVP
 */

if (!is_user_logged_in()) {
	wp_redirect(add_query_arg('redirect_to', get_permalink(), home_url('/login/')));
	exit;
}

$current_user = wp_get_current_user();
$user_id = get_current_user_id();
$user_plan = Event_RSVP_Simple_Stripe::get_user_plan($user_id);
$subscription_status = get_user_meta($user_id, 'event_rsvp_subscription_status', true);
$payment_date = get_user_meta($user_id, 'event_rsvp_payment_date', true);

$plan_names = array(
	'attendee' => 'Free Attendee',
	'pay_as_you_go' => 'Pay As You Go',
	'event_planner' => 'Event Planner',
	'event_host' => 'Event Host',
	'vendor' => 'Vendor',
	'pro' => 'Pro (Host + Vendor)'
);

$plan_name = isset($plan_names[$user_plan]) ? $plan_names[$user_plan] : 'Free Attendee';

get_header();
?>

<main id="primary" class="site-main my-account-page">
	<div class="account-container">
		<div class="account-sidebar">
			<div class="sidebar-header">
				<div class="user-avatar">
					<?php echo get_avatar($user_id, 80); ?>
				</div>
				<h3 class="user-name"><?php echo esc_html($current_user->display_name); ?></h3>
				<p class="user-email"><?php echo esc_html($current_user->user_email); ?></p>
				<span class="user-plan-badge <?php echo esc_attr($user_plan); ?>">
					<?php echo esc_html($plan_name); ?>
				</span>
			</div>

			<nav class="account-nav">
				<a href="#overview" class="nav-item active" data-tab="overview">
					<span class="nav-icon">📊</span>
					<span class="nav-label">Overview</span>
				</a>
				<a href="#profile" class="nav-item" data-tab="profile">
					<span class="nav-icon">👤</span>
					<span class="nav-label">Profile</span>
				</a>
				
				<?php if (in_array($user_plan, array('pay_as_you_go', 'event_planner', 'event_host', 'pro'))) : ?>
				<a href="#events" class="nav-item" data-tab="events">
					<span class="nav-icon">📅</span>
					<span class="nav-label">My Events</span>
				</a>
				<?php endif; ?>
				
				<?php if (in_array($user_plan, array('vendor', 'pro'))) : ?>
				<a href="#ads" class="nav-item" data-tab="ads">
					<span class="nav-icon">📢</span>
					<span class="nav-label">My Ads</span>
				</a>
				<?php endif; ?>
				
				<a href="#rsvps" class="nav-item" data-tab="rsvps">
					<span class="nav-icon">🎫</span>
					<span class="nav-label">My RSVPs</span>
				</a>
				<a href="#subscription" class="nav-item" data-tab="subscription">
					<span class="nav-icon">💳</span>
					<span class="nav-label">Subscription</span>
				</a>
				<a href="<?php echo wp_logout_url(home_url('/')); ?>" class="nav-item logout-item">
					<span class="nav-icon">🚪</span>
					<span class="nav-label">Logout</span>
				</a>
			</nav>
		</div>

		<div class="account-content">
			<?php if (isset($_GET['welcome']) && $_GET['welcome'] === '1') : ?>
				<div class="welcome-banner">
					<h2>🎉 Welcome to Your Account!</h2>
					<p>Your payment was successful and your account has been upgraded. Enjoy your new features!</p>
				</div>
			<?php endif; ?>

			<div id="tab-overview" class="tab-content active">
				<div class="content-header">
					<h2 class="content-title">Account Overview</h2>
					<p class="content-subtitle">Welcome back, <?php echo esc_html($current_user->first_name); ?>!</p>
				</div>

				<div class="stats-grid">
					<?php if (in_array($user_plan, array('pay_as_you_go', 'event_planner', 'event_host', 'pro'))) : 
						$user_events = event_rsvp_get_user_events($user_id);
						$event_count = count($user_events);
						$total_rsvps = 0;
						foreach ($user_events as $event) {
							$stats = event_rsvp_get_event_stats($event->ID);
							$total_rsvps += $stats['total'];
						}
					?>
					<div class="stat-card">
						<div class="stat-icon event-icon">📅</div>
						<div class="stat-info">
							<div class="stat-value"><?php echo $event_count; ?></div>
							<div class="stat-label">Total Events</div>
						</div>
					</div>
					<div class="stat-card">
						<div class="stat-icon rsvp-icon">🎫</div>
						<div class="stat-info">
							<div class="stat-value"><?php echo $total_rsvps; ?></div>
							<div class="stat-label">Total RSVPs</div>
						</div>
					</div>
					<?php endif; ?>

					<?php if (in_array($user_plan, array('vendor', 'pro'))) : 
						$vendor_ads = get_posts(array(
							'post_type' => 'vendor_ad',
							'author' => $user_id,
							'posts_per_page' => -1
						));
						$ad_count = count($vendor_ads);
						$today = date('Y-m-d');
						$active_ad_count = 0;
						foreach ($vendor_ads as $ad) {
							$start_date = get_post_meta($ad->ID, 'ad_start_date', true);
							$end_date = get_post_meta($ad->ID, 'ad_end_date', true);
							if ($today >= $start_date && $today <= $end_date) {
								$active_ad_count++;
							}
						}
					?>
					<div class="stat-card">
						<div class="stat-icon ads-icon">📢</div>
						<div class="stat-info">
							<div class="stat-value"><?php echo $ad_count; ?></div>
							<div class="stat-label">Total Ads</div>
						</div>
					</div>
					<div class="stat-card">
						<div class="stat-icon active-icon">🟢</div>
						<div class="stat-info">
							<div class="stat-value"><?php echo $active_ad_count; ?></div>
							<div class="stat-label">Active Ads</div>
						</div>
					</div>
					<?php endif; ?>

					<?php
					$my_rsvps = get_posts(array(
						'post_type' => 'attendee',
						'meta_query' => array(
							array(
								'key' => 'attendee_email',
								'value' => $current_user->user_email,
								'compare' => '='
							)
						),
						'posts_per_page' => -1
					));
					$rsvp_count = count($my_rsvps);
					?>
					<div class="stat-card">
						<div class="stat-icon tickets-icon">🎟️</div>
						<div class="stat-info">
							<div class="stat-value"><?php echo $rsvp_count; ?></div>
							<div class="stat-label">My RSVPs</div>
						</div>
					</div>
				</div>

				<div class="quick-actions">
					<h3 class="section-heading">Quick Actions</h3>
					<div class="action-buttons-grid">
						<?php if (in_array($user_plan, array('pay_as_you_go', 'event_planner', 'event_host', 'pro'))) : ?>
						<a href="<?php echo home_url('/event-create/'); ?>" class="action-button create-event">
							<span class="button-icon">➕</span>
							<span class="button-text">Create Event</span>
						</a>
						<a href="<?php echo home_url('/host-dashboard/'); ?>" class="action-button view-events">
							<span class="button-icon">📅</span>
							<span class="button-text">Manage Events</span>
						</a>
						<?php endif; ?>

						<?php if (in_array($user_plan, array('vendor', 'pro'))) : ?>
						<a href="<?php echo home_url('/ad-create/'); ?>" class="action-button create-ad">
							<span class="button-icon">📢</span>
							<span class="button-text">Create Ad</span>
						</a>
						<a href="<?php echo home_url('/ads-manager/'); ?>" class="action-button view-ads">
							<span class="button-icon">📊</span>
							<span class="button-text">Manage Ads</span>
						</a>
						<?php endif; ?>

						<a href="<?php echo home_url('/browse-events/'); ?>" class="action-button browse-events">
							<span class="button-icon">🔍</span>
							<span class="button-text">Browse Events</span>
						</a>

						<?php if ($user_plan === 'attendee') : ?>
						<a href="<?php echo home_url('/pricing/'); ?>" class="action-button upgrade">
							<span class="button-icon">⬆️</span>
							<span class="button-text">Upgrade Plan</span>
						</a>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<div id="tab-profile" class="tab-content">
				<div class="content-header">
					<h2 class="content-title">Profile Information</h2>
					<p class="content-subtitle">Manage your personal information</p>
				</div>

				<div class="profile-section">
					<div class="profile-info-card">
						<div class="info-row">
							<span class="info-label">Username:</span>
							<span class="info-value"><?php echo esc_html($current_user->user_login); ?></span>
						</div>
						<div class="info-row">
							<span class="info-label">Email:</span>
							<span class="info-value"><?php echo esc_html($current_user->user_email); ?></span>
						</div>
						<div class="info-row">
							<span class="info-label">First Name:</span>
							<span class="info-value"><?php echo esc_html($current_user->first_name); ?></span>
						</div>
						<div class="info-row">
							<span class="info-label">Last Name:</span>
							<span class="info-value"><?php echo esc_html($current_user->last_name); ?></span>
						</div>
						<div class="info-row">
							<span class="info-label">Member Since:</span>
							<span class="info-value"><?php echo date('F j, Y', strtotime($current_user->user_registered)); ?></span>
						</div>
					</div>
				</div>
			</div>

			<?php if (in_array($user_plan, array('pay_as_you_go', 'event_planner', 'event_host', 'pro'))) : ?>
			<div id="tab-events" class="tab-content">
				<div class="content-header">
					<h2 class="content-title">My Events</h2>
					<p class="content-subtitle">Manage your hosted events</p>
					<a href="<?php echo home_url('/event-create/'); ?>" class="header-button">+ Create New Event</a>
				</div>

				<?php
				$user_events = event_rsvp_get_user_events($user_id);
				if (!empty($user_events)) :
				?>
				<div class="events-list">
					<?php foreach (array_slice($user_events, 0, 5) as $event) : 
						$event_date = get_field('event_date', $event->ID);
						$venue_address = get_field('venue_address', $event->ID);
						$stats = event_rsvp_get_event_stats($event->ID);
					?>
					<div class="event-item">
						<div class="event-item-header">
							<h4 class="event-item-title"><?php echo get_the_title($event->ID); ?></h4>
							<span class="event-item-date"><?php echo date('M j, Y', strtotime($event_date)); ?></span>
						</div>
						<div class="event-item-meta">
							<span class="meta-item">📍 <?php echo esc_html($venue_address); ?></span>
							<span class="meta-item">👥 <?php echo $stats['total']; ?> RSVPs</span>
						</div>
						<div class="event-item-actions">
							<a href="<?php echo get_permalink($event->ID); ?>" class="item-action-link">View</a>
							<a href="<?php echo home_url('/event-create/?event_id=' . $event->ID); ?>" class="item-action-link">Edit</a>
						</div>
					</div>
					<?php endforeach; ?>
				</div>
				<div class="view-all-link">
					<a href="<?php echo home_url('/host-dashboard/'); ?>">View All Events →</a>
				</div>
				<?php else : ?>
				<div class="empty-state">
					<div class="empty-icon">📅</div>
					<p class="empty-text">You haven't created any events yet.</p>
					<a href="<?php echo home_url('/event-create/'); ?>" class="empty-action-button">Create Your First Event</a>
				</div>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<?php if (in_array($user_plan, array('vendor', 'pro'))) : ?>
			<div id="tab-ads" class="tab-content">
				<div class="content-header">
					<h2 class="content-title">My Ads</h2>
					<p class="content-subtitle">Manage your advertisements</p>
					<a href="<?php echo home_url('/ad-create/'); ?>" class="header-button">+ Create New Ad</a>
				</div>

				<?php
				$vendor_ads = get_posts(array(
					'post_type' => 'vendor_ad',
					'author' => $user_id,
					'posts_per_page' => 5,
					'orderby' => 'date',
					'order' => 'DESC'
				));
				if (!empty($vendor_ads)) :
				?>
				<div class="ads-list">
					<?php foreach ($vendor_ads as $ad) : 
						$start_date = get_post_meta($ad->ID, 'ad_start_date', true);
						$end_date = get_post_meta($ad->ID, 'ad_end_date', true);
						$slot_location = get_post_meta($ad->ID, 'slot_location', true);
						$clicks = get_post_meta($ad->ID, 'ad_clicks', true) ?: 0;
						$impressions = get_post_meta($ad->ID, 'ad_impressions', true) ?: 0;
						$today = date('Y-m-d');
						
						if ($today >= $start_date && $today <= $end_date) {
							$status = '<span class="status-active">🟢 Active</span>';
						} elseif ($today < $start_date) {
							$status = '<span class="status-upcoming">🔵 Upcoming</span>';
						} else {
							$status = '<span class="status-expired">⚪ Expired</span>';
						}
					?>
					<div class="ad-item">
						<div class="ad-item-header">
							<h4 class="ad-item-title"><?php echo get_the_title($ad->ID); ?></h4>
							<span class="ad-item-status"><?php echo $status; ?></span>
						</div>
						<div class="ad-item-meta">
							<span class="meta-item">📍 <?php echo ucfirst($slot_location); ?></span>
							<span class="meta-item">👁️ <?php echo $impressions; ?> views</span>
							<span class="meta-item">🖱️ <?php echo $clicks; ?> clicks</span>
						</div>
						<div class="ad-item-actions">
							<a href="<?php echo get_edit_post_link($ad->ID); ?>" class="item-action-link">Edit</a>
						</div>
					</div>
					<?php endforeach; ?>
				</div>
				<div class="view-all-link">
					<a href="<?php echo home_url('/ads-manager/'); ?>">View All Ads →</a>
				</div>
				<?php else : ?>
				<div class="empty-state">
					<div class="empty-icon">📢</div>
					<p class="empty-text">You haven't created any ads yet.</p>
					<a href="<?php echo home_url('/ad-create/'); ?>" class="empty-action-button">Create Your First Ad</a>
				</div>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<div id="tab-rsvps" class="tab-content">
				<div class="content-header">
					<h2 class="content-title">My RSVPs</h2>
					<p class="content-subtitle">Events you're attending</p>
				</div>

				<?php
				$my_rsvps = get_posts(array(
					'post_type' => 'attendee',
					'meta_query' => array(
						array(
							'key' => 'attendee_email',
							'value' => $current_user->user_email,
							'compare' => '='
						)
					),
					'posts_per_page' => -1,
					'orderby' => 'date',
					'order' => 'DESC'
				));

				if (!empty($my_rsvps)) :
				?>
				<div class="rsvps-list">
					<?php foreach ($my_rsvps as $rsvp) : 
						$event_id = get_post_meta($rsvp->ID, 'linked_event', true);
						$qr_data = get_post_meta($rsvp->ID, 'qr_data', true);
						$checkin_status = get_post_meta($rsvp->ID, 'checkin_status', true);
						$event_date = get_field('event_date', $event_id);
						$venue_address = get_field('venue_address', $event_id);
						$qr_viewer_url = home_url('/qr-view/?qr=' . urlencode($qr_data));
					?>
					<div class="rsvp-item">
						<div class="rsvp-item-header">
							<h4 class="rsvp-item-title"><?php echo get_the_title($event_id); ?></h4>
							<?php if ($checkin_status) : ?>
								<span class="checkin-badge checked">✓ Checked In</span>
							<?php else : ?>
								<span class="checkin-badge pending">⏳ Pending</span>
							<?php endif; ?>
						</div>
						<div class="rsvp-item-meta">
							<span class="meta-item">📅 <?php echo date('M j, Y', strtotime($event_date)); ?></span>
							<span class="meta-item">📍 <?php echo esc_html($venue_address); ?></span>
						</div>
						<div class="rsvp-item-actions">
							<a href="<?php echo get_permalink($event_id); ?>" class="item-action-link">View Event</a>
							<a href="<?php echo $qr_viewer_url; ?>" class="item-action-link qr-link" target="_blank">QR Code</a>
						</div>
					</div>
					<?php endforeach; ?>
				</div>
				<?php else : ?>
				<div class="empty-state">
					<div class="empty-icon">🎫</div>
					<p class="empty-text">You haven't RSVP'd to any events yet.</p>
					<a href="<?php echo home_url('/browse-events/'); ?>" class="empty-action-button">Browse Events</a>
				</div>
				<?php endif; ?>
			</div>

			<div id="tab-subscription" class="tab-content">
				<div class="content-header">
					<h2 class="content-title">Subscription</h2>
					<p class="content-subtitle">Manage your plan and billing</p>
				</div>

				<div class="subscription-card">
					<div class="plan-header">
						<div class="plan-icon <?php echo esc_attr($user_plan); ?>">
							<?php
							$plan_icons = array(
								'attendee' => '🎫',
								'pay_as_you_go' => '📅',
								'event_planner' => '📅',
								'event_host' => '📅',
								'vendor' => '📢',
								'pro' => '⭐'
							);
							echo $plan_icons[$user_plan] ?? '🎫';
							?>
						</div>
						<div class="plan-info">
							<h3 class="plan-name"><?php echo esc_html($plan_name); ?></h3>
							<?php if ($subscription_status === 'active') : ?>
								<span class="plan-status active">Active</span>
							<?php else : ?>
								<span class="plan-status free">Free</span>
							<?php endif; ?>
						</div>
					</div>

					<div class="plan-details">
						<?php if ($payment_date) : ?>
						<div class="detail-row">
							<span class="detail-label">Member Since:</span>
							<span class="detail-value"><?php echo date('F j, Y', strtotime($payment_date)); ?></span>
						</div>
						<?php endif; ?>

						<div class="detail-row">
							<span class="detail-label">Status:</span>
							<span class="detail-value"><?php echo $subscription_status === 'active' ? 'Active Subscription' : 'Free Plan'; ?></span>
						</div>

						<div class="plan-features">
							<h4 class="features-title">Your Features:</h4>
							<ul class="features-list">
								<li>✓ Browse and RSVP to events</li>
								<li>✓ Receive QR code tickets</li>
								<?php if ($user_plan === 'pay_as_you_go') : ?>
								<li>✓ Create up to 1 event</li>
								<li>✓ Manage attendees</li>
								<li>✓ Event analytics</li>
								<?php elseif ($user_plan === 'event_planner') : ?>
								<li>✓ Create up to 5 events</li>
								<li>✓ Manage attendees</li>
								<li>✓ Event analytics</li>
								<?php elseif (in_array($user_plan, array('event_host', 'pro'))) : ?>
								<li>✓ Create unlimited events</li>
								<li>✓ Manage attendees</li>
								<li>✓ Event analytics</li>
								<?php endif; ?>
								<?php if (in_array($user_plan, array('vendor', 'pro'))) : ?>
								<li>✓ Post advertisements</li>
								<li>✓ Track ad performance</li>
								<?php endif; ?>
							</ul>
						</div>
					</div>

					<?php if ($user_plan === 'attendee') : ?>
					<div class="upgrade-section">
						<h4 class="upgrade-title">Want More Features?</h4>
						<p class="upgrade-text">Upgrade to unlock event hosting, vendor ads, and more!</p>
						<a href="<?php echo home_url('/pricing/'); ?>" class="upgrade-button">View Plans & Upgrade</a>
					</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</main>

<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/assets/css/my-account.css">

<script>
document.addEventListener('DOMContentLoaded', function() {
	const navItems = document.querySelectorAll('.account-nav .nav-item:not(.logout-item)');
	const tabContents = document.querySelectorAll('.tab-content');
	
	navItems.forEach(item => {
		item.addEventListener('click', function(e) {
			e.preventDefault();
			const targetTab = this.getAttribute('data-tab');
			
			navItems.forEach(nav => nav.classList.remove('active'));
			tabContents.forEach(tab => tab.classList.remove('active'));
			
			this.classList.add('active');
			document.getElementById('tab-' + targetTab).classList.add('active');
		});
	});
});
</script>

<?php get_footer(); ?>

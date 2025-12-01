<?php

/**
 * Template Name: Browse Events (Guest)
 *
 * @package RSVP
 */

get_header();

$today = date('Y-m-d');
$this_week = date('Y-m-d', strtotime('+7 days'));
$this_month = date('Y-m-d', strtotime('+30 days'));

$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
$event_host = isset($_GET['event_host']) ? intval($_GET['event_host']) : 0;
$show_past = isset($_GET['show_past']) && $_GET['show_past'] === '1';

$current_user_id = get_current_user_id();
$is_admin = current_user_can('administrator');

$args = array(
	'post_type' => 'event',
	'posts_per_page' => -1,
	'meta_key' => 'event_date',
	'orderby' => 'meta_value',
	'order' => $show_past ? 'DESC' : 'ASC',
	'meta_query' => array()
);

if (!$show_past && !$date_from) {
	$args['meta_query'][] = array(
		'key' => 'event_date',
		'value' => $today,
		'compare' => '>=',
		'type' => 'DATE'
	);
}

if ($date_from) {
	$args['meta_query'][] = array(
		'key' => 'event_date',
		'value' => $date_from,
		'compare' => '>=',
		'type' => 'DATE'
	);
}

if ($date_to) {
	$args['meta_query'][] = array(
		'key' => 'event_date',
		'value' => $date_to,
		'compare' => '<=',
		'type' => 'DATE'
	);
}

// Handle private events visibility - only show to creator and admins
if (!$is_admin) {
	// Get allowed event IDs based on visibility
	$allowed_event_ids = array();

	// Build args for getting public events
	$public_events_args = array(
		'post_type' => 'event',
		'posts_per_page' => -1,
		'fields' => 'ids',
		'meta_query' => array(
			'relation' => 'OR',
			array(
				'key' => 'visibility',
				'value' => 'public',
				'compare' => '='
			),
			array(
				'key' => 'visibility',
				'compare' => 'NOT EXISTS'
			)
		)
	);

	// Apply host filter to public events query if specified
	if ($event_host > 0) {
		$public_events_args['author'] = $event_host;
	}

	$public_events = get_posts($public_events_args);
	$allowed_event_ids = $public_events;

	// If user is logged in, also include their own private events
	if ($current_user_id) {
		$user_events_args = array(
			'post_type' => 'event',
			'author' => $current_user_id,
			'posts_per_page' => -1,
			'fields' => 'ids'
		);

		// Apply host filter if it matches current user, otherwise don't show user's own events
		if ($event_host > 0 && $event_host != $current_user_id) {
			// Don't include user's events if filtering by a different host
		} else {
			$user_events = get_posts($user_events_args);
			$allowed_event_ids = array_unique(array_merge($allowed_event_ids, $user_events));
		}
	}

	if (!empty($allowed_event_ids)) {
		$args['post__in'] = $allowed_event_ids;
	} else {
		// No events to show
		$args['post__in'] = array(0);
	}
} else {
	// Admins see all events - apply host filter if specified
	if ($event_host > 0) {
		$args['author'] = $event_host;
	}
}

$all_events = new WP_Query($args);

$events_today = array();
$events_this_week = array();
$events_this_month = array();
$events_later = array();

if ($all_events->have_posts()) {
	while ($all_events->have_posts()) {
		$all_events->the_post();
		$event_date = get_field('event_date');

		if ($event_date === $today) {
			$events_today[] = get_post();
		} elseif ($event_date <= $this_week) {
			$events_this_week[] = get_post();
		} elseif ($event_date <= $this_month) {
			$events_this_month[] = get_post();
		} else {
			$events_later[] = get_post();
		}
	}
	wp_reset_postdata();
}

$event_hosts = get_users(array('role__in' => array('event_host', 'pro', 'administrator')));
?>

<main id="primary" class="site-main browse-events-page">
	<div class="container">

		<div class="browse-hero">
			<h1 class="browse-title">Discover Events</h1>
			<p class="browse-subtitle">Find and join exciting events happening in your area</p>

			<?php if (!is_user_logged_in()) : ?>
				<div class="guest-cta-banner">
					<p class="cta-text">💡 Want to RSVP and get QR codes?
						<a href="<?php echo esc_url(home_url('/signup/?plan=attendee')); ?>" class="cta-link">Sign up for free</a>
						or <a href="<?php echo esc_url(home_url('/login/')); ?>" class="cta-link">login</a>
					</p>
				</div>
			<?php endif; ?>
		</div>

		<div class="events-filter-section">
			<form method="get" action="" class="events-filter-form">
				<div class="filter-group">
					<label for="date_from" class="filter-label">From Date:</label>
					<input type="date" id="date_from" name="date_from" value="<?php echo esc_attr($date_from); ?>" class="filter-input">
				</div>

				<div class="filter-group">
					<label for="date_to" class="filter-label">To Date:</label>
					<input type="date" id="date_to" name="date_to" value="<?php echo esc_attr($date_to); ?>" class="filter-input">
				</div>

				<div class="filter-group">
					<label for="event_host" class="filter-label">Event Host:</label>
					<select id="event_host" name="event_host" class="filter-select">
						<option value="">All Hosts</option>
						<?php foreach ($event_hosts as $host) : ?>
							<option value="<?php echo esc_attr($host->ID); ?>" <?php selected($event_host, $host->ID); ?>>
								<?php echo esc_html($host->display_name); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="filter-group filter-checkbox-group">
					<label class="filter-checkbox-label">
						<input type="checkbox" id="show_past" name="show_past" value="1" <?php checked($show_past, true); ?> class="filter-checkbox">
						<span>Show Past Events</span>
					</label>
				</div>

				<div class="filter-actions">
					<button type="submit" class="filter-submit-button">🔍 Filter Events</button>
					<?php if ($date_from || $date_to || $event_host || $show_past) : ?>
						<a href="<?php echo esc_url(get_permalink()); ?>" class="filter-reset-button">✕ Clear Filters</a>
					<?php endif; ?>
				</div>
			</form>
		</div>

		<?php if (!empty($events_today)) : ?>
			<div class="events-category-section">
				<div class="category-header">
					<h2 class="category-title">🔥 Happening Today</h2>
					<span class="category-count"><?php echo count($events_today); ?> events</span>
				</div>

				<div class="events-carousel">
					<?php foreach ($events_today as $event) :
						setup_postdata($event);
						$event_id = $event->ID;
						include get_template_directory() . '/template-parts/event-card.php';
						wp_reset_postdata();
					endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

		<?php if (!empty($events_this_week)) : ?>
			<div class="events-category-section">
				<div class="category-header">
					<h2 class="category-title">📅 This Week</h2>
					<span class="category-count"><?php echo count($events_this_week); ?> events</span>
				</div>

				<div class="events-carousel">
					<?php foreach ($events_this_week as $event) :
						setup_postdata($event);
						$event_id = $event->ID;
						include get_template_directory() . '/template-parts/event-card.php';
						wp_reset_postdata();
					endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

		<?php if (!empty($events_this_month)) : ?>
			<div class="events-category-section">
				<div class="category-header">
					<h2 class="category-title">🗓️ This Month</h2>
					<span class="category-count"><?php echo count($events_this_month); ?> events</span>
				</div>

				<div class="events-carousel">
					<?php foreach ($events_this_month as $event) :
						setup_postdata($event);
						$event_id = $event->ID;
						include get_template_directory() . '/template-parts/event-card.php';
						wp_reset_postdata();
					endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

		<?php if (!empty($events_later)) : ?>
			<div class="events-category-section">
				<div class="category-header">
					<h2 class="category-title">🌟 Coming Soon</h2>
					<span class="category-count"><?php echo count($events_later); ?> events</span>
				</div>

				<div class="events-carousel">
					<?php foreach ($events_later as $event) :
						setup_postdata($event);
						$event_id = $event->ID;
						include get_template_directory() . '/template-parts/event-card.php';
						wp_reset_postdata();
					endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

		<?php if (empty($events_today) && empty($events_this_week) && empty($events_this_month) && empty($events_later)) : ?>
			<div class="no-events-found">
				<div class="no-events-icon">📅</div>
				<h2>No Events Found</h2>
				<p>There are no events matching your filters. Try adjusting your search criteria.</p>
				<?php if ($date_from || $date_to || $event_host || $show_past) : ?>
					<a href="<?php echo esc_url(get_permalink()); ?>" class="cta-button">View All Events</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="browse-footer-cta">
			<div class="footer-cta-card">
				<h3>Want to create your own events?</h3>
				<p>Join as an event host and start organizing amazing events</p>
				<a href="<?php echo esc_url(home_url('/pricing/')); ?>" class="cta-button">View Pricing Plans</a>
			</div>
		</div>

	</div>
</main>

<style>
	.browse-events-page {
		padding: 60px 0;
		background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%);
	}

	.browse-hero {
		text-align: center;
		margin-bottom: 60px;
	}

	.browse-title {
		font-size: 3rem;
		font-weight: 700;
		margin: 0 0 15px 0;
		background: linear-gradient(135deg, #503AA8 0%, #6B52C3 100%);
		-webkit-background-clip: text;
		-webkit-text-fill-color: transparent;
		background-clip: text;
	}

	.browse-subtitle {
		font-size: 1.3rem;
		color: var(--event-text-light);
		margin: 0 0 25px 0;
	}

	.guest-cta-banner {
		background: linear-gradient(135deg, #FBC02D 0%, #F9A825 100%);
		padding: 20px 30px;
		border-radius: var(--event-radius);
		max-width: 700px;
		margin: 0 auto;
		box-shadow: var(--event-shadow-md);
	}

	.guest-cta-banner .cta-text {
		margin: 0;
		font-size: 1.05rem;
		color: var(--event-dark);
		font-weight: 500;
	}

	.guest-cta-banner .cta-link {
		color: #503AA8;
		font-weight: 700;
		text-decoration: underline;
	}

	.events-filter-section {
		background-color: #ffffff;
		padding: 30px;
		border-radius: var(--event-radius);
		box-shadow: var(--event-shadow-md);
		margin-bottom: 50px;
	}

	.events-filter-form {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
		gap: 20px;
		align-items: end;
	}

	.filter-group {
		display: flex;
		flex-direction: column;
		gap: 8px;
	}

	.filter-checkbox-group {
		justify-content: flex-end;
		padding-bottom: 4px;
	}

	.filter-checkbox-label {
		display: flex;
		align-items: center;
		gap: 8px;
		cursor: pointer;
		font-weight: 600;
		color: var(--event-text);
		font-size: 0.9rem;
	}

	.filter-checkbox {
		width: 18px;
		height: 18px;
		cursor: pointer;
		accent-color: var(--event-primary);
	}

	.filter-label {
		font-weight: 600;
		color: var(--event-text);
		font-size: 0.9rem;
	}

	.filter-input,
	.filter-select {
		padding: 12px 16px;
		border: 2px solid #e0e0e0;
		border-radius: 8px;
		font-size: 1rem;
		transition: border-color 0.3s;
	}

	.filter-input:focus,
	.filter-select:focus {
		outline: none;
		border-color: var(--event-primary);
	}

	.filter-actions {
		display: flex;
		flex-direction: column;
		gap: 10px;
	}

	.filter-submit-button {
		padding: 12px 24px;
		background: linear-gradient(135deg, #503AA8 0%, #6B52C3 100%);
		color: #ffffff;
		border: none;
		border-radius: 8px;
		font-weight: 600;
		font-size: 1rem;
		cursor: pointer;
		transition: transform 0.3s, box-shadow 0.3s;
	}

	.filter-submit-button:hover {
		transform: translateY(-2px);
		box-shadow: 0 6px 20px rgba(80, 58, 168, 0.3);
	}

	.filter-reset-button {
		padding: 10px 20px;
		background-color: #f5f5f5;
		color: var(--event-text);
		border: none;
		border-radius: 8px;
		font-weight: 600;
		text-decoration: none;
		text-align: center;
		cursor: pointer;
		transition: background-color 0.3s;
	}

	.filter-reset-button:hover {
		background-color: #e0e0e0;
	}

	.events-category-section {
		margin-bottom: 60px;
	}

	.category-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 30px;
		padding-bottom: 15px;
		border-bottom: 3px solid var(--event-primary);
	}

	.category-title {
		font-size: 2rem;
		font-weight: 700;
		margin: 0;
		color: var(--event-text);
	}

	.category-count {
		background-color: var(--event-primary);
		color: var(--event-dark);
		padding: 6px 16px;
		border-radius: 20px;
		font-weight: 600;
		font-size: 0.9rem;
	}

	.events-carousel {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
		gap: 30px;
	}

	.no-events-found {
		text-align: center;
		padding: 100px 20px;
	}

	.no-events-icon {
		font-size: 5rem;
		margin-bottom: 20px;
	}

	.no-events-found h2 {
		font-size: 2rem;
		margin: 0 0 15px 0;
		color: var(--event-text);
	}

	.no-events-found p {
		font-size: 1.1rem;
		color: var(--event-text-light);
		margin: 0 0 20px 0;
	}

	.browse-footer-cta {
		margin-top: 80px;
		text-align: center;
	}

	.footer-cta-card {
		background: linear-gradient(135deg, #503AA8 0%, #6B52C3 100%);
		color: #ffffff;
		padding: 50px 40px;
		border-radius: var(--event-radius);
		box-shadow: var(--event-shadow-lg);
	}

	.footer-cta-card h3 {
		font-size: 2rem;
		margin: 0 0 15px 0;
	}

	.footer-cta-card p {
		font-size: 1.1rem;
		margin: 0 0 25px 0;
		opacity: 0.9;
	}

	.footer-cta-card .cta-button,
	.no-events-found .cta-button {
		display: inline-block;
		padding: 15px 40px;
		background-color: var(--event-primary);
		color: var(--event-dark);
		text-decoration: none;
		border-radius: 8px;
		font-weight: 700;
		font-size: 1.1rem;
		transition: var(--event-transition);
	}

	.footer-cta-card .cta-button:hover,
	.no-events-found .cta-button:hover {
		background-color: var(--event-primary-hover);
		transform: translateY(-3px);
		box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
	}

	@media (max-width: 768px) {
		.browse-title {
			font-size: 2rem;
		}

		.browse-subtitle {
			font-size: 1.1rem;
		}

		.events-filter-form {
			grid-template-columns: 1fr;
		}

		.category-header {
			flex-direction: column;
			align-items: flex-start;
			gap: 10px;
		}

		.category-title {
			font-size: 1.5rem;
		}

		.events-carousel {
			grid-template-columns: 1fr;
		}

		.footer-cta-card {
			padding: 40px 25px;
		}

		.footer-cta-card h3 {
			font-size: 1.5rem;
		}
	}

	@media (max-width: 480px) {
		.browse-events-page {
			padding: 40px 0;
		}

		.browse-title {
			font-size: 1.75rem;
		}

		.guest-cta-banner {
			padding: 15px 20px;
		}

		.guest-cta-banner .cta-text {
			font-size: 0.95rem;
		}

		.events-filter-section {
			padding: 20px;
		}
	}
</style>

<?php get_footer(); ?>
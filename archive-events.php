<?php
/**
 * Template for displaying events archive
 *
 * @package RSVP
 */

get_header();

$hashtag_filter = isset($_GET['hashtag']) ? sanitize_text_field($_GET['hashtag']) : '';
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

$current_user_id = get_current_user_id();
$is_admin = current_user_can('administrator');

$args = array(
	'post_type' => 'event',
	'posts_per_page' => 12,
	'paged' => $paged,
	'meta_key' => 'event_date',
	'orderby' => 'meta_value',
	'order' => 'ASC'
);

// Filter private events - only show to creator and admins
if (!$is_admin) {
	$meta_query = array(
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
	);

	// If user is logged in, also show their own private events
	if ($current_user_id) {
		$meta_query = array(
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
		);
		// Add author parameter to show user's own events regardless of visibility
		$args['author'] = $current_user_id;
		// But we need to use a complex query to get public events OR user's own events
		unset($args['author']); // Remove author filter and use meta_query + post__in instead

		// Get user's own event IDs
		$user_events = get_posts(array(
			'post_type' => 'event',
			'author' => $current_user_id,
			'posts_per_page' => -1,
			'fields' => 'ids'
		));

		// Get public event IDs
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
		$public_events = get_posts($public_events_args);

		// Merge and get unique event IDs
		$allowed_event_ids = array_unique(array_merge($user_events, $public_events));

		if (!empty($allowed_event_ids)) {
			$args['post__in'] = $allowed_event_ids;
		} else {
			// No events to show
			$args['post__in'] = array(0);
		}
	} else {
		$args['meta_query'] = $meta_query;
	}
} else {
	// Admins see all events
}

if ($hashtag_filter) {
	$args['meta_query'] = array(
		array(
			'key' => 'event_hashtag',
			'value' => $hashtag_filter,
			'compare' => '='
		)
	);
}

query_posts($args);
?>

<main id="primary" class="site-main events-archive-page">
	<div class="container">
		
		<div style="height:40px" aria-hidden="true"></div>

		<div class="archive-header">
			<?php if ($hashtag_filter) : ?>
				<h1 class="archive-title">Events: #<?php echo esc_html($hashtag_filter); ?></h1>
				<p class="archive-subtitle">Browse all events tagged with #<?php echo esc_html($hashtag_filter); ?></p>
				<a href="<?php echo esc_url(home_url('/events/')); ?>" class="clear-filter-link">← View all events</a>
			<?php else : ?>
				<h1 class="archive-title">Upcoming Events</h1>
				<p class="archive-subtitle">Discover and RSVP for exciting events happening soon</p>
			<?php endif; ?>
		</div>

		<div style="height:40px" aria-hidden="true"></div>

		<?php if (is_user_logged_in()) : ?>
			<div class="archive-actions">
				<?php if (current_user_can('edit_posts') || current_user_can('edit_events')) : ?>
					<a href="<?php echo esc_url(home_url('/event-create/')); ?>" class="create-event-button">
						+ Create New Event
					</a>
				<?php endif; ?>
				<?php if (current_user_can('edit_posts') || current_user_can('edit_events')) : ?>
					<a href="<?php echo esc_url(home_url('/host-dashboard/')); ?>" class="dashboard-button">
						📊 My Dashboard
					</a>
				<?php endif; ?>
			</div>
			<div style="height:30px" aria-hidden="true"></div>
		<?php else : ?>
			<div class="guest-actions">
				<div class="guest-info-card">
					<p>💡 <strong>New here?</strong> Browse events as a guest or 
						<a href="<?php echo esc_url(home_url('/signup/?plan=attendee')); ?>">sign up for free</a> 
						to RSVP and get QR codes!
					</p>
				</div>
			</div>
			<div style="height:30px" aria-hidden="true"></div>
		<?php endif; ?>

		<div class="events-filter-bar">
			<div class="filter-options">
				<button class="filter-button active" data-filter="upcoming">
					<span class="filter-icon">📅</span>
					<span class="filter-text">Upcoming</span>
				</button>
				<button class="filter-button" data-filter="all">
					<span class="filter-icon">🎯</span>
					<span class="filter-text">All Events</span>
				</button>
				<button class="filter-button" data-filter="past">
					<span class="filter-icon">🕐</span>
					<span class="filter-text">Past Events</span>
				</button>
			</div>
			<div class="view-toggle">
				<button class="view-button active" data-view="grid" title="Grid View">
					<span class="view-icon">◫</span>
				</button>
				<button class="view-button" data-view="list" title="List View">
					<span class="view-icon">☰</span>
				</button>
			</div>
		</div>

		<div style="height:30px" aria-hidden="true"></div>

		<?php if ( have_posts() ) : ?>

			<div class="events-grid" data-view="grid">

				<?php
				while ( have_posts() ) :
					the_post();
					
					$event_id = get_the_ID();
					$event_date = get_field('event_date');
					
					$is_upcoming = $event_date && strtotime($event_date) >= strtotime('today');
					$is_past = $event_date && strtotime($event_date) < strtotime('today');
					
					echo '<div class="event-grid-item" data-event-type="' . ($is_upcoming ? 'upcoming' : ($is_past ? 'past' : 'all')) . '">';
					include get_template_directory() . '/template-parts/event-card.php';
					echo '</div>';
				endwhile;
				?>

			</div>

			<div style="height:40px" aria-hidden="true"></div>

			<div class="pagination-wrapper">
				<?php
				the_posts_pagination( array(
					'mid_size'  => 2,
					'prev_text' => '← Previous',
					'next_text' => 'Next →',
				) );
				?>
			</div>

		<?php else : ?>

			<div class="no-events-message">
				<div class="no-events-icon">📅</div>
				<?php if ($hashtag_filter) : ?>
					<h2>No Events Found with #<?php echo esc_html($hashtag_filter); ?></h2>
					<p>No events are tagged with this hashtag. Try browsing all events.</p>
					<div style="height:20px" aria-hidden="true"></div>
					<a href="<?php echo esc_url(home_url('/events/')); ?>" class="create-event-button">
						← Back to All Events
					</a>
				<?php else : ?>
					<h2>No Events Found</h2>
					<p>There are currently no events scheduled. Check back soon!</p>
					<?php if (is_user_logged_in() && (current_user_can('edit_posts') || current_user_can('edit_events'))) : ?>
						<div style="height:20px" aria-hidden="true"></div>
						<a href="<?php echo esc_url(home_url('/event-create/')); ?>" class="create-event-button">
							+ Create Your First Event
						</a>
					<?php endif; ?>
				<?php endif; ?>
			</div>

		<?php endif; ?>

		<div style="height:60px" aria-hidden="true"></div>

	</div>
</main>

<style>
.clear-filter-link {
	display: inline-block;
	margin-top: 10px;
	color: #503AA8;
	text-decoration: none;
	font-weight: 600;
	transition: var(--event-transition);
}

.clear-filter-link:hover {
	color: var(--event-primary);
	transform: translateX(-5px);
}

.guest-info-card {
	background: linear-gradient(135deg, #FBC02D 0%, #F9A825 100%);
	padding: 20px 30px;
	border-radius: var(--event-radius);
	text-align: center;
	box-shadow: var(--event-shadow-md);
}

.guest-info-card p {
	margin: 0;
	font-size: 1.05rem;
	color: var(--event-dark);
	font-weight: 500;
}

.guest-info-card a {
	color: #503AA8;
	font-weight: 700;
	text-decoration: underline;
}

.event-grid-item {
	display: block;
	transition: all 0.3s ease;
}

.events-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
	gap: 30px;
	transition: all 0.3s ease;
}

.events-grid[data-view="list"] {
	grid-template-columns: 1fr;
}

.no-filter-results {
	background: #fff;
	padding: 60px 40px;
	border-radius: var(--event-radius);
	text-align: center;
	box-shadow: var(--event-shadow);
}

.no-filter-results .no-events-icon {
	font-size: 5rem;
	margin-bottom: 20px;
	opacity: 0.4;
}

.no-filter-results h3 {
	font-size: 1.8rem;
	font-weight: 700;
	margin: 0 0 12px 0;
	color: var(--event-text);
}

.no-filter-results p {
	font-size: 1.1rem;
	color: var(--event-text-light);
	margin: 0;
}

.filter-count {
	opacity: 0.8;
	font-size: 0.9em;
}

@media (max-width: 1200px) {
	.events-grid {
		grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
	}
}

@media (max-width: 768px) {
	.events-grid,
	.events-grid[data-view="list"] {
		grid-template-columns: 1fr;
		gap: 25px;
	}
	
	.guest-info-card {
		padding: 15px 20px;
	}
	
	.guest-info-card p {
		font-size: 0.95rem;
	}

	.no-filter-results {
		padding: 40px 20px;
	}

	.no-filter-results .no-events-icon {
		font-size: 4rem;
	}

	.no-filter-results h3 {
		font-size: 1.5rem;
	}

	.no-filter-results p {
		font-size: 1rem;
	}
}

@media (max-width: 480px) {
	.events-grid {
		gap: 20px;
	}
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const filterButtons = document.querySelectorAll('.filter-button');
	const viewButtons = document.querySelectorAll('.view-button');
	const eventsGrid = document.querySelector('.events-grid');
	const eventItems = document.querySelectorAll('.event-grid-item');

	if (filterButtons.length > 0) {
		filterButtons.forEach(button => {
			button.addEventListener('click', function() {
				filterButtons.forEach(btn => btn.classList.remove('active'));
				this.classList.add('active');
				
				const filter = this.dataset.filter;
				let visibleCount = 0;
				
				eventItems.forEach(item => {
					if (filter === 'all') {
						item.style.display = '';
						visibleCount++;
					} else {
						const eventType = item.dataset.eventType;
						if (eventType === filter) {
							item.style.display = '';
							visibleCount++;
						} else {
							item.style.display = 'none';
						}
					}
				});

				// Remove existing no results message
				const noResultsMsg = document.getElementById('no-filter-results');
				if (noResultsMsg) {
					noResultsMsg.remove();
				}
				
				// Show message if no events match filter
				if (visibleCount === 0) {
					const msg = document.createElement('div');
					msg.id = 'no-filter-results';
					msg.className = 'no-filter-results';
					msg.innerHTML = '<div class="no-events-icon">🔍</div><h3>No Events Found</h3><p>No events match this filter. Try a different filter.</p>';
					eventsGrid.parentNode.insertBefore(msg, eventsGrid);
					eventsGrid.style.display = 'none';
				} else {
					eventsGrid.style.display = '';
				}
			});
		});
	}

	if (viewButtons.length > 0) {
		viewButtons.forEach(button => {
			button.addEventListener('click', function() {
				viewButtons.forEach(btn => btn.classList.remove('active'));
				this.classList.add('active');
				
				const view = this.dataset.view;
				if (eventsGrid) {
					eventsGrid.setAttribute('data-view', view);
				}
			});
		});
	}

	// Initialize filter counts
	if (filterButtons.length > 0 && eventItems.length > 0) {
		const upcomingCount = document.querySelectorAll('[data-event-type="upcoming"]').length;
		const pastCount = document.querySelectorAll('[data-event-type="past"]').length;
		const allCount = eventItems.length;

		filterButtons.forEach(button => {
			const filter = button.dataset.filter;
			const filterText = button.querySelector('.filter-text');
			if (filterText) {
				const currentText = filterText.textContent.split(' (')[0];
				if (filter === 'upcoming') {
					filterText.textContent = currentText;
					if (upcomingCount > 0) {
						filterText.innerHTML = currentText + ' <span class="filter-count">(' + upcomingCount + ')</span>';
					}
				} else if (filter === 'past') {
					filterText.textContent = currentText;
					if (pastCount > 0) {
						filterText.innerHTML = currentText + ' <span class="filter-count">(' + pastCount + ')</span>';
					}
				} else if (filter === 'all') {
					filterText.textContent = currentText;
					if (allCount > 0) {
						filterText.innerHTML = currentText + ' <span class="filter-count">(' + allCount + ')</span>';
					}
				}
			}
		});
	}
});
</script>

<?php
wp_reset_query();
get_footer();

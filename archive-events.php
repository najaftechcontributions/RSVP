<?php
/**
 * Template for displaying events archive
 *
 * @package RSVP
 */

get_header();

$hashtag_filter = isset($_GET['hashtag']) ? sanitize_text_field($_GET['hashtag']) : '';
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

$args = array(
	'post_type' => 'event',
	'posts_per_page' => 12,
	'paged' => $paged,
	'meta_key' => 'event_date',
	'orderby' => 'meta_value',
	'order' => 'ASC'
);

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
				<button class="filter-button active" data-filter="upcoming">Upcoming</button>
				<button class="filter-button" data-filter="all">All Events</button>
				<button class="filter-button" data-filter="past">Past Events</button>
			</div>
			<div class="view-toggle">
				<button class="view-button active" data-view="grid" title="Grid View">
					<span>Grid</span>
				</button>
				<button class="view-button" data-view="list" title="List View">
					<span>List</span>
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
}

.events-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
	gap: 30px;
}

.events-grid[data-view="list"] {
	grid-template-columns: 1fr;
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
}

@media (max-width: 480px) {
	.events-grid {
		gap: 20px;
	}
}
</style>

<script>
(function() {
	const filterButtons = document.querySelectorAll('.filter-button');
	const viewButtons = document.querySelectorAll('.view-button');
	const eventsGrid = document.querySelector('.events-grid');
	const eventItems = document.querySelectorAll('.event-grid-item');

	filterButtons.forEach(button => {
		button.addEventListener('click', function() {
			filterButtons.forEach(btn => btn.classList.remove('active'));
			this.classList.add('active');
			
			const filter = this.dataset.filter;
			
			eventItems.forEach(item => {
				if (filter === 'all') {
					item.style.display = '';
				} else {
					const eventType = item.dataset.eventType;
					item.style.display = eventType === filter ? '' : 'none';
				}
			});
		});
	});

	viewButtons.forEach(button => {
		button.addEventListener('click', function() {
			viewButtons.forEach(btn => btn.classList.remove('active'));
			this.classList.add('active');
			
			const view = this.dataset.view;
			eventsGrid.setAttribute('data-view', view);
		});
	});
})();
</script>

<?php
wp_reset_query();
get_footer();

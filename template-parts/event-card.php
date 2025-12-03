<?php
/**
 * Event Card Template Part
 *
 * @package RSVP
 */

if (!isset($event_id)) {
	$event_id = get_the_ID();
}

if (!function_exists('get_field')) {
	$event_host = get_post_meta($event_id, 'event_host', true);
	$event_date = get_post_meta($event_id, 'event_date', true);
	$event_end_date = get_post_meta($event_id, 'event_end_date', true);
	$venue_address = get_post_meta($event_id, 'venue_address', true);
	$max_attendees = get_post_meta($event_id, 'max_attendees', true);
	$event_hashtag = get_post_meta($event_id, 'event_hashtag', true);
} else {
	$event_host = get_field('event_host', $event_id);
	$event_date = get_field('event_date', $event_id);
	$event_end_date = get_field('event_end_date', $event_id);
	$venue_address = get_field('venue_address', $event_id);
	$max_attendees = get_field('max_attendees', $event_id);
	$event_hashtag = get_field('event_hashtag', $event_id);
}

$stats = event_rsvp_get_event_stats($event_id);
$available_spots = event_rsvp_get_available_spots($event_id);
$is_full = event_rsvp_is_event_full($event_id);

$formatted_date = $event_date ? date('M j, Y', strtotime($event_date)) : '';
$formatted_time = $event_date ? date('g:i A', strtotime($event_date)) : '';

$is_upcoming = $event_date && strtotime($event_date) >= strtotime('today');
$is_past = $event_date && strtotime($event_date) < strtotime('today');

$display_host = $event_host;
?>

<article class="improved-event-card <?php echo $is_past ? 'event-past' : ''; ?>" data-event-id="<?php echo esc_attr($event_id); ?>">
	
	<div class="event-card-image-wrapper">
		<?php if (has_post_thumbnail($event_id)) : ?>
			<a href="<?php echo get_permalink($event_id); ?>" class="event-image-link">
				<?php echo get_the_post_thumbnail($event_id, 'medium_large', array('class' => 'event-card-img')); ?>
			</a>
		<?php else : ?>
			<a href="<?php echo get_permalink($event_id); ?>" class="event-image-link event-placeholder">
				<div class="placeholder-content">
					<span class="placeholder-icon">📅</span>
				</div>
			</a>
		<?php endif; ?>
		
		<?php if ($is_full) : ?>
			<span class="event-status-badge badge-full">FULL</span>
		<?php elseif ($is_past) : ?>
			<span class="event-status-badge badge-past">PAST</span>
		<?php elseif ($event_date === date('Y-m-d')) : ?>
			<span class="event-status-badge badge-today">TODAY</span>
		<?php endif; ?>
		
		<div class="event-date-badge">
			<span class="badge-day"><?php echo $event_date ? date('d', strtotime($event_date)) : '?'; ?></span>
			<span class="badge-month"><?php echo $event_date ? date('M', strtotime($event_date)) : ''; ?></span>
		</div>
	</div>

	<div class="event-card-body">
		
		<div class="event-card-header">
			<h3 class="event-card-title">
				<a href="<?php echo get_permalink($event_id); ?>"><?php echo get_the_title($event_id); ?></a>
			</h3>
			
			<?php if ($event_hashtag) : ?>
				<div class="event-card-hashtag">
					<a href="<?php echo esc_url(home_url('/events/?hashtag=' . urlencode($event_hashtag))); ?>" class="hashtag-link">
						#<?php echo esc_html($event_hashtag); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>

		<div class="event-card-meta">
			<?php if ($formatted_time) : ?>
				<div class="meta-item meta-time">
					<span class="meta-icon">🕒</span>
					<span class="meta-text"><?php echo esc_html($formatted_time); ?></span>
				</div>
			<?php endif; ?>
			
			<?php if ($venue_address) : ?>
				<div class="meta-item meta-location">
					<span class="meta-icon">📍</span>
					<span class="meta-text"><?php echo esc_html(wp_trim_words($venue_address, 6, '...')); ?></span>
				</div>
			<?php endif; ?>
			
			<?php if (!empty($display_host)) : ?>
				<div class="meta-item meta-host">
					<span class="meta-icon">👤</span>
					<span class="meta-text"><?php echo esc_html($display_host); ?></span>
				</div>
			<?php endif; ?>
		</div>

		<div class="event-card-description">
			<?php echo wp_trim_words(get_the_excerpt($event_id), 15, '...'); ?>
		</div>

		<div class="event-card-stats-row">
			<div class="stat-item stat-rsvps">
				<span class="stat-number"><?php echo esc_html($stats['total']); ?></span>
				<span class="stat-label">RSVPs</span>
			</div>
			<?php if ($max_attendees) : ?>
				<div class="stat-item stat-spots">
					<span class="stat-number <?php echo $is_full ? 'text-full' : ''; ?>">
						<?php echo $is_full ? '0' : esc_html($available_spots); ?>
					</span>
					<span class="stat-label">spots left</span>
				</div>
			<?php endif; ?>
			<div class="stat-item stat-checked">
				<span class="stat-number"><?php echo esc_html($stats['checked_in']); ?></span>
				<span class="stat-label">checked in</span>
			</div>
		</div>

	</div>

	<div class="event-card-footer">
		<?php if ($is_past) : ?>
			<a href="<?php echo get_permalink($event_id); ?>" class="event-action-button event-action-past">
				📄 View Details
			</a>
		<?php elseif ($is_full) : ?>
			<a href="<?php echo get_permalink($event_id); ?>" class="event-action-button event-action-full">
				👁️ View Event
			</a>
		<?php else : ?>
			<button class="event-action-button open-join-modal" data-event-id="<?php echo esc_attr($event_id); ?>" data-modal-id="join-event-modal-<?php echo esc_attr($event_id); ?>">
				✨ RSVP Now
			</button>
		<?php endif; ?>
	</div>

</article>

<?php
if (!$is_past && !$is_full) {
	include get_template_directory() . '/template-parts/join-event-modal.php';
}
?>

<script>
(function() {
	const openModalBtn = document.querySelector('.open-join-modal[data-event-id="<?php echo esc_js($event_id); ?>"]');
	if (openModalBtn) {
		openModalBtn.addEventListener('click', function() {
			const modalId = this.getAttribute('data-modal-id');
			const modal = document.getElementById(modalId);
			if (modal) {
				modal.style.display = 'flex';
				document.body.style.overflow = 'hidden';
			}
		});
	}
})();
</script>

<style>
.improved-event-card {
	background-color: #ffffff;
	border-radius: var(--event-radius);
	overflow: hidden;
	box-shadow: var(--event-shadow);
	transition: var(--event-transition);
	display: flex;
	flex-direction: column;
	height: 100%;
}

.improved-event-card:hover {
	box-shadow: var(--event-shadow-lg);
	transform: translateY(-8px);
}

.improved-event-card.event-past {
	opacity: 0.75;
}

.event-card-image-wrapper {
	position: relative;
	height: 240px;
	overflow: hidden;
	background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
}

.event-image-link {
	display: block;
	width: 100%;
	height: 100%;
}

.event-card-img {
	width: 100%;
	height: 100%;
	object-fit: cover;
	transition: transform 0.4s ease;
}

.improved-event-card:hover .event-card-img {
	transform: scale(1.08);
}

.event-placeholder {
	display: flex;
	align-items: center;
	justify-content: center;
}

.placeholder-content {
	text-align: center;
}

.placeholder-icon {
	font-size: 4rem;
	opacity: 0.3;
}

.event-status-badge {
	position: absolute;
	top: 15px;
	right: 15px;
	padding: 6px 16px;
	border-radius: 25px;
	font-size: 0.75rem;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: 0.8px;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
	z-index: 2;
}

.badge-full {
	background-color: var(--event-error);
	color: #ffffff;
}

.badge-past {
	background-color: var(--event-text-light);
	color: #ffffff;
}

.badge-today {
	background: linear-gradient(135deg, #FBC02D 0%, #F9A825 100%);
	color: var(--event-dark);
	animation: pulse 2s infinite;
}

@keyframes pulse {
	0%, 100% { opacity: 1; }
	50% { opacity: 0.85; }
}

.event-date-badge {
	position: absolute;
	bottom: 15px;
	left: 15px;
	background: linear-gradient(135deg, #503AA8 0%, #6B52C3 100%);
	color: #ffffff;
	border-radius: 10px;
	padding: 10px 14px;
	text-align: center;
	box-shadow: 0 4px 12px rgba(80, 58, 168, 0.3);
	z-index: 2;
}

.badge-day {
	display: block;
	font-size: 1.8rem;
	font-weight: 700;
	line-height: 1;
}

.badge-month {
	display: block;
	font-size: 0.85rem;
	font-weight: 600;
	text-transform: uppercase;
	margin-top: 2px;
	opacity: 0.95;
}

.event-card-body {
	padding: 25px;
	flex: 1;
	display: flex;
	flex-direction: column;
	gap: 15px;
}

.event-card-header {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.event-card-title {
	margin: 0;
	font-size: 1.35rem;
	font-weight: 700;
	line-height: 1.3;
}

.event-card-title a {
	color: var(--event-text);
	text-decoration: none;
	transition: var(--event-transition);
}

.event-card-title a:hover {
	color: #503AA8;
}

.event-card-hashtag {
	display: inline-block;
}

.hashtag-link {
	display: inline-block;
	color: #503AA8;
	background-color: rgba(80, 58, 168, 0.1);
	padding: 4px 12px;
	border-radius: 15px;
	font-size: 0.85rem;
	font-weight: 600;
	text-decoration: none;
	transition: var(--event-transition);
}

.hashtag-link:hover {
	background-color: rgba(80, 58, 168, 0.2);
	transform: translateX(3px);
}

.event-card-meta {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.meta-item {
	display: flex;
	align-items: center;
	gap: 8px;
	font-size: 0.9rem;
	color: var(--event-text-light);
}

.meta-icon {
	font-size: 1rem;
}

.meta-text {
	flex: 1;
}

.event-card-description {
	font-size: 0.95rem;
	line-height: 1.6;
	color: var(--event-text);
	flex: 1;
}

.event-card-stats-row {
	display: flex;
	gap: 15px;
	padding-top: 15px;
	border-top: 1px solid var(--event-border);
}

.stat-item {
	display: flex;
	flex-direction: column;
	align-items: center;
	text-align: center;
	flex: 1;
}

.stat-number {
	font-size: 1.4rem;
	font-weight: 700;
	color: #503AA8;
	line-height: 1;
}

.stat-number.text-full {
	color: var(--event-error);
}

.stat-label {
	font-size: 0.75rem;
	color: var(--event-text-light);
	margin-top: 4px;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.event-card-footer {
	padding: 20px 25px;
	background-color: var(--event-secondary);
	border-top: 1px solid var(--event-border);
}

.event-action-button {
	display: block;
	width: 100%;
	padding: 14px 24px;
	background: linear-gradient(135deg, #FBC02D 0%, #F9A825 100%);
	color: var(--event-dark);
	text-decoration: none;
	border-radius: 8px;
	font-weight: 700;
	text-align: center;
	transition: var(--event-transition);
	font-size: 1rem;
	box-shadow: 0 2px 8px rgba(251, 192, 45, 0.3);
}

.event-action-button:hover {
	background: linear-gradient(135deg, #F9A825 0%, #F57F17 100%);
	transform: translateY(-3px);
	box-shadow: 0 6px 16px rgba(251, 192, 45, 0.4);
}

@media (max-width: 768px) {
	.event-card-image-wrapper {
		height: 200px;
	}
	
	.event-card-body {
		padding: 20px;
	}
	
	.event-card-title {
		font-size: 1.2rem;
	}
	
	.event-card-stats-row {
		gap: 10px;
	}
	
	.stat-number {
		font-size: 1.2rem;
	}
}

@media (max-width: 480px) {
	.event-card-image-wrapper {
		height: 180px;
	}
	
	.event-card-body {
		padding: 15px;
		gap: 12px;
	}
	
	.event-card-title {
		font-size: 1.1rem;
	}
	
	.meta-item {
		font-size: 0.85rem;
	}
	
	.event-action-button {
		padding: 12px 20px;
		font-size: 0.95rem;
	}
}
</style>

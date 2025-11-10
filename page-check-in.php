<?php
/**
 * Template Name: Event Check-In
 *
 * @package RSVP
 */

if (!is_user_logged_in()) {
	wp_redirect(add_query_arg('redirect_to', get_permalink(), home_url('/login/')));
	exit;
}

$current_user = wp_get_current_user();
$allowed_roles = array('event_host', 'pro', 'administrator');
$has_access = false;

foreach ($allowed_roles as $role) {
	if (in_array($role, $current_user->roles)) {
		$has_access = true;
		break;
	}
}

if (!$has_access) {
	wp_die('You do not have permission to access this page. This page is for event hosts and administrators only.');
}

$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
$event_title = $event_id ? get_the_title($event_id) : 'All Events';

get_header();
?>

<main id="primary" class="site-main checkin-page" data-event-id="<?php echo esc_attr($event_id); ?>">
	<div class="container">
		
		<div style="height:40px" aria-hidden="true"></div>

		<div class="checkin-header">
			<h1 class="checkin-title">📱 Event Check-In Station</h1>
			<p class="checkin-subtitle">
				<?php if ($event_id) : ?>
					Scanning for: <strong><?php echo esc_html($event_title); ?></strong>
				<?php else : ?>
					Scan attendee QR codes for quick and seamless check-in
				<?php endif; ?>
			</p>
		</div>

		<div style="height:40px" aria-hidden="true"></div>

		<div class="checkin-container">
			
			<div class="attendee-search-section">
				<div class="search-header">
					<h2 class="search-title">🔍 Search & Check-In Attendees</h2>
				</div>
				
				<div class="search-form-wrapper">
					<div class="search-input-group">
						<div class="search-input-wrapper">
							<span class="search-icon">🔍</span>
							<input 
								type="text" 
								id="attendee-search-input" 
								class="attendee-search-input" 
								placeholder="Search by name or email..." 
								autocomplete="off"
							>
							<div id="search-results-dropdown" class="search-results-dropdown"></div>
						</div>
						<button type="button" id="clear-search" class="search-submit-button" style="display:none;">Clear</button>
					</div>
				</div>
			</div>
			
			<div class="checkin-stats-section">
				<h2 class="stats-title">Live Statistics</h2>
				<div class="stats-grid">
					<div class="stat-card stat-total">
						<div class="stat-icon">👥</div>
						<div class="stat-content">
							<div class="stat-number" id="total-attendees">0</div>
							<div class="stat-label">Total Attendees</div>
						</div>
					</div>
					
					<div class="stat-card stat-checked">
						<div class="stat-icon">✓</div>
						<div class="stat-content">
							<div class="stat-number" id="checked-in">0</div>
							<div class="stat-label">Checked In</div>
						</div>
					</div>
					
					<div class="stat-card stat-pending">
						<div class="stat-icon">⏳</div>
						<div class="stat-content">
							<div class="stat-number" id="not-checked-in">0</div>
							<div class="stat-label">Not Checked In</div>
						</div>
					</div>
					
					<div class="stat-card stat-rate">
						<div class="stat-icon">📊</div>
						<div class="stat-content">
							<div class="stat-number" id="checkin-percentage">0%</div>
							<div class="stat-label">Check-In Rate</div>
						</div>
					</div>
				</div>
			</div>

			<div class="checkin-scanner-section">
				<div class="scanner-card">
					<h2 class="scanner-title">QR Code Scanner</h2>
					
					<div class="scanner-wrapper">
						<div id="qr-reader" class="qr-reader">
							<div class="scanner-placeholder">
								<div class="scanner-icon">📷</div>
								<p>Position QR code within the frame</p>
								<small>Camera will activate automatically</small>
							</div>
						</div>
						<div id="qr-reader-results" class="qr-results"></div>
					</div>
					
					<div class="manual-entry-section">
						<button type="button" class="toggle-manual-entry" id="toggle-manual">
							<span class="toggle-icon">⌨️</span>
							<span class="toggle-text">Switch to Manual Entry</span>
						</button>
						
						<div class="manual-entry-form" id="manual-entry-form" style="display: none;">
							<h3>Manual Entry</h3>
							<form id="manual-checkin-form">
								<div class="form-field">
									<label for="qr-data-input">Enter QR Code Data or Attendee Email:</label>
									<input type="text" id="qr-data-input" name="qr-data" placeholder="Paste QR code data or email address">
								</div>
								<button type="submit" class="checkin-submit-button">
									<span class="button-icon">✓</span>
									<span class="button-text">Check In</span>
								</button>
							</form>
						</div>
					</div>
				</div>
			</div>

			<div class="recent-checkins-section">
				<h2 class="recent-title">Recent Check-Ins</h2>
				<div id="recent-checkins-list" class="checkins-list">
					<div class="empty-state">
						<div class="empty-icon">📋</div>
						<p>No check-ins yet. Scan a QR code to get started!</p>
					</div>
				</div>
			</div>

			<div class="checked-in-attendees-section">
				<div class="section-header-with-refresh">
					<h2 class="attendees-list-title">All Checked-In Attendees</h2>
					<button type="button" id="refresh-attendees" class="refresh-button">
						<span class="refresh-icon">🔄</span>
						<span class="refresh-text">Refresh</span>
					</button>
				</div>
				<div id="checked-in-attendees-list" class="attendees-table-wrapper">
					<div class="loading-state">
						<div class="loading-icon">⏳</div>
						<p>Loading checked-in attendees...</p>
					</div>
				</div>
			</div>
			
			<div class="status-message" id="status-message"></div>
		</div>

		<div style="height:60px" aria-hidden="true"></div>

	</div>
</main>

<?php get_footer(); ?>

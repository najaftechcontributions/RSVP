<?php
/**
 * Template Name: Vendor Dashboard
 * Template for vendors to manage their ads
 *
 * @package RSVP
 */

if (!is_user_logged_in()) {
	wp_redirect(home_url('/login/'));
	exit;
}

$current_user = wp_get_current_user();
$user_roles = $current_user->roles;
$allowed_roles = array('vendor', 'pro', 'administrator');
$has_access = false;

foreach ($allowed_roles as $role) {
	if (in_array($role, $user_roles)) {
		$has_access = true;
		break;
	}
}

if (!$has_access) {
	wp_die('You do not have permission to access this page. This page is for vendors only.');
}

get_header();

$user_id = get_current_user_id();

$args = array(
	'post_type' => 'vendor_ad',
	'author' => $user_id,
	'posts_per_page' => -1,
	'orderby' => 'date',
	'order' => 'DESC'
);

if (current_user_can('administrator')) {
	unset($args['author']);
}

$vendor_ads = get_posts($args);

$today = date('Y-m-d');
$active_ads = array();
$upcoming_ads = array();
$expired_ads = array();

foreach ($vendor_ads as $ad) {
	$start_date = get_post_meta($ad->ID, 'ad_start_date', true);
	$end_date = get_post_meta($ad->ID, 'ad_end_date', true);
	
	if ($today >= $start_date && $today <= $end_date) {
		$active_ads[] = $ad;
	} elseif ($today < $start_date) {
		$upcoming_ads[] = $ad;
	} else {
		$expired_ads[] = $ad;
	}
}
?>

<main id="primary" class="site-main vendor-dashboard-page">
	<div class="container">
		
		<div style="height:40px" aria-hidden="true"></div>

		<div class="dashboard-header">
			<div class="header-content">
				<div class="vendor-info">
					<span class="vendor-name">👤 <?php echo esc_html($current_user->display_name); ?></span>
					<span class="vendor-email"><?php echo esc_html($current_user->user_email); ?></span>
				</div>
				<h1 class="dashboard-title">📢 Vendor Ads Dashboard</h1>
				<p class="dashboard-subtitle">Manage your advertisements and track their performance</p>
			</div>
			<div class="header-actions">
				<a href="<?php echo esc_url(home_url('/ad-create/')); ?>" class="create-ad-button">
					<span class="button-icon">➕</span>
					<span class="button-text">Create New Ad</span>
				</a>
			</div>
		</div>

		<div style="height:40px" aria-hidden="true"></div>

		<?php if (isset($_GET['ad_created']) && $_GET['ad_created'] === 'success') : ?>
			<div class="success-notice">
				✓ Ad created successfully!
			</div>
			<div style="height:20px" aria-hidden="true"></div>
		<?php endif; ?>

		<?php if (isset($_GET['ad_updated']) && $_GET['ad_updated'] === 'success') : ?>
			<div class="success-notice">
				✓ Ad updated successfully!
			</div>
			<div style="height:20px" aria-hidden="true"></div>
		<?php endif; ?>

		<?php if (isset($_GET['ad_deleted']) && $_GET['ad_deleted'] === 'success') : ?>
			<div class="success-notice">
				��� Ad deleted successfully!
			</div>
			<div style="height:20px" aria-hidden="true"></div>
		<?php endif; ?>

		<div class="ads-stats-overview">
			<div class="stat-card stat-active">
				<div class="stat-icon">🟢</div>
				<div class="stat-content">
					<div class="stat-value"><?php echo count($active_ads); ?></div>
					<div class="stat-label">Active Ads</div>
				</div>
			</div>

			<div class="stat-card stat-upcoming">
				<div class="stat-icon">🔵</div>
				<div class="stat-content">
					<div class="stat-value"><?php echo count($upcoming_ads); ?></div>
					<div class="stat-label">Upcoming Ads</div>
				</div>
			</div>

			<div class="stat-card stat-expired">
				<div class="stat-icon">⚪</div>
				<div class="stat-content">
					<div class="stat-value"><?php echo count($expired_ads); ?></div>
					<div class="stat-label">Expired Ads</div>
				</div>
			</div>

			<div class="stat-card stat-total">
				<div class="stat-icon">📊</div>
				<div class="stat-content">
					<div class="stat-value"><?php echo count($vendor_ads); ?></div>
					<div class="stat-label">Total Ads</div>
				</div>
			</div>
		</div>

		<div style="height:40px" aria-hidden="true"></div>

		<div class="ads-tabs">
			<button class="tab-button active" data-tab="active">Active (<?php echo count($active_ads); ?>)</button>
			<button class="tab-button" data-tab="upcoming">Upcoming (<?php echo count($upcoming_ads); ?>)</button>
			<button class="tab-button" data-tab="expired">Expired (<?php echo count($expired_ads); ?>)</button>
			<button class="tab-button" data-tab="all">All Ads (<?php echo count($vendor_ads); ?>)</button>
		</div>

		<div class="tab-content active" id="tab-active">
			<?php if (!empty($active_ads)) : ?>
				<div class="ads-grid">
					<?php foreach ($active_ads as $ad) : 
						$start_date = get_post_meta($ad->ID, 'ad_start_date', true);
						$end_date = get_post_meta($ad->ID, 'ad_end_date', true);
						$slot_location = get_post_meta($ad->ID, 'slot_location', true);
						$click_url = get_post_meta($ad->ID, 'click_url', true);
						$clicks = get_post_meta($ad->ID, 'ad_clicks', true) ?: 0;
						$impressions = get_post_meta($ad->ID, 'ad_impressions', true) ?: 0;
						?>
						<div class="ad-card ad-active">
							<div class="ad-status-badge active">🟢 Active</div>
							
							<?php if (has_post_thumbnail($ad->ID)) : ?>
								<div class="ad-preview">
									<?php echo get_the_post_thumbnail($ad->ID, 'medium'); ?>
								</div>
							<?php else : ?>
								<div class="ad-preview ad-no-image">
									<span class="no-image-text">No Image</span>
								</div>
							<?php endif; ?>

							<div class="ad-details">
								<h3 class="ad-title"><?php echo esc_html(get_the_title($ad->ID)); ?></h3>
								
								<div class="ad-meta">
									<div class="ad-meta-item">
										<span class="meta-label">📍 Location:</span>
										<span class="meta-value"><?php echo esc_html(ucfirst($slot_location)); ?></span>
									</div>
									<div class="ad-meta-item">
										<span class="meta-label">📅 Start:</span>
										<span class="meta-value"><?php echo esc_html(date('M j, Y', strtotime($start_date))); ?></span>
									</div>
									<div class="ad-meta-item">
										<span class="meta-label">⏰ End:</span>
										<span class="meta-value"><?php echo esc_html(date('M j, Y', strtotime($end_date))); ?></span>
									</div>
									<div class="ad-meta-item">
										<span class="meta-label">👁️ Views:</span>
										<span class="meta-value"><?php echo esc_html($impressions); ?></span>
									</div>
									<div class="ad-meta-item">
										<span class="meta-label">🖱️ Clicks:</span>
										<span class="meta-value"><?php echo esc_html($clicks); ?></span>
									</div>
									<?php if ($impressions > 0) : ?>
										<div class="ad-meta-item">
											<span class="meta-label">📊 CTR:</span>
											<span class="meta-value"><?php echo number_format(($clicks / $impressions) * 100, 2); ?>%</span>
										</div>
									<?php endif; ?>
								</div>

								<div class="ad-url">
									<span class="url-label">🔗 URL:</span>
									<a href="<?php echo esc_url($click_url); ?>" target="_blank" rel="noopener" class="url-link">
										<?php echo esc_html(wp_trim_words($click_url, 5, '...')); ?>
									</a>
								</div>

								<div class="ad-actions">
									<button class="ad-action-button preview-button" data-ad-id="<?php echo $ad->ID; ?>" title="Preview Ad">
										👁️ Preview
									</button>
									<a href="<?php echo esc_url(home_url('/ad-create/?ad_id=' . $ad->ID)); ?>" class="ad-action-button edit-button">
										✏️ Edit
									</a>
									<?php if (current_user_can('delete_post', $ad->ID)) : ?>
										<a href="<?php echo esc_url(get_delete_post_link($ad->ID)); ?>" class="ad-action-button delete-button" onclick="return confirm('Are you sure you want to delete this ad?');">
											🗑️ Delete
										</a>
									<?php endif; ?>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<div class="empty-state">
					<div class="empty-icon">📢</div>
					<h3>No Active Ads</h3>
					<p>You don't have any active ads at the moment.</p>
					<a href="<?php echo esc_url(home_url('/ad-create/')); ?>" class="empty-action-button">
						Create Your First Ad
					</a>
				</div>
			<?php endif; ?>
		</div>

		<div class="tab-content" id="tab-upcoming">
			<?php if (!empty($upcoming_ads)) : ?>
				<div class="ads-grid">
					<?php foreach ($upcoming_ads as $ad) : 
						$start_date = get_post_meta($ad->ID, 'ad_start_date', true);
						$end_date = get_post_meta($ad->ID, 'ad_end_date', true);
						$slot_location = get_post_meta($ad->ID, 'slot_location', true);
						$click_url = get_post_meta($ad->ID, 'click_url', true);
						?>
						<div class="ad-card ad-upcoming">
							<div class="ad-status-badge upcoming">🔵 Upcoming</div>
							
							<?php if (has_post_thumbnail($ad->ID)) : ?>
								<div class="ad-preview">
									<?php echo get_the_post_thumbnail($ad->ID, 'medium'); ?>
								</div>
							<?php else : ?>
								<div class="ad-preview ad-no-image">
									<span class="no-image-text">No Image</span>
								</div>
							<?php endif; ?>

							<div class="ad-details">
								<h3 class="ad-title"><?php echo esc_html(get_the_title($ad->ID)); ?></h3>
								
								<div class="ad-meta">
									<div class="ad-meta-item">
										<span class="meta-label">📍 Location:</span>
										<span class="meta-value"><?php echo esc_html(ucfirst($slot_location)); ?></span>
									</div>
									<div class="ad-meta-item">
										<span class="meta-label">📅 Starts:</span>
										<span class="meta-value"><?php echo esc_html(date('M j, Y', strtotime($start_date))); ?></span>
									</div>
									<div class="ad-meta-item">
										<span class="meta-label">⏰ Ends:</span>
										<span class="meta-value"><?php echo esc_html(date('M j, Y', strtotime($end_date))); ?></span>
									</div>
								</div>

								<div class="ad-url">
									<span class="url-label">🔗 URL:</span>
									<a href="<?php echo esc_url($click_url); ?>" target="_blank" rel="noopener" class="url-link">
										<?php echo esc_html(wp_trim_words($click_url, 5, '...')); ?>
									</a>
								</div>

								<div class="ad-actions">
									<button class="ad-action-button preview-button" data-ad-id="<?php echo $ad->ID; ?>">
										👁️ Preview
									</button>
									<a href="<?php echo esc_url(home_url('/ad-create/?ad_id=' . $ad->ID)); ?>" class="ad-action-button edit-button">
										✏️ Edit
									</a>
									<?php if (current_user_can('delete_post', $ad->ID)) : ?>
										<a href="<?php echo esc_url(get_delete_post_link($ad->ID)); ?>" class="ad-action-button delete-button" onclick="return confirm('Are you sure you want to delete this ad?');">
											🗑️ Delete
										</a>
									<?php endif; ?>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<div class="empty-state">
					<div class="empty-icon">🔵</div>
					<h3>No Upcoming Ads</h3>
					<p>You don't have any scheduled ads.</p>
				</div>
			<?php endif; ?>
		</div>

		<div class="tab-content" id="tab-expired">
			<?php if (!empty($expired_ads)) : ?>
				<div class="ads-grid">
					<?php foreach ($expired_ads as $ad) : 
						$start_date = get_post_meta($ad->ID, 'ad_start_date', true);
						$end_date = get_post_meta($ad->ID, 'ad_end_date', true);
						$slot_location = get_post_meta($ad->ID, 'slot_location', true);
						$click_url = get_post_meta($ad->ID, 'click_url', true);
						$clicks = get_post_meta($ad->ID, 'ad_clicks', true) ?: 0;
						$impressions = get_post_meta($ad->ID, 'ad_impressions', true) ?: 0;
						?>
						<div class="ad-card ad-expired">
							<div class="ad-status-badge expired">⚪ Expired</div>
							
							<?php if (has_post_thumbnail($ad->ID)) : ?>
								<div class="ad-preview">
									<?php echo get_the_post_thumbnail($ad->ID, 'medium'); ?>
								</div>
							<?php else : ?>
								<div class="ad-preview ad-no-image">
									<span class="no-image-text">No Image</span>
								</div>
							<?php endif; ?>

							<div class="ad-details">
								<h3 class="ad-title"><?php echo esc_html(get_the_title($ad->ID)); ?></h3>
								
								<div class="ad-meta">
									<div class="ad-meta-item">
										<span class="meta-label">📍 Location:</span>
										<span class="meta-value"><?php echo esc_html(ucfirst($slot_location)); ?></span>
									</div>
									<div class="ad-meta-item">
										<span class="meta-label">📅 Ran:</span>
										<span class="meta-value"><?php echo esc_html(date('M j', strtotime($start_date)) . ' - ' . date('M j, Y', strtotime($end_date))); ?></span>
									</div>
									<div class="ad-meta-item">
										<span class="meta-label">👁️ Views:</span>
										<span class="meta-value"><?php echo esc_html($impressions); ?></span>
									</div>
									<div class="ad-meta-item">
										<span class="meta-label">🖱️ Clicks:</span>
										<span class="meta-value"><?php echo esc_html($clicks); ?></span>
									</div>
								</div>

								<div class="ad-actions">
									<button class="ad-action-button preview-button" data-ad-id="<?php echo $ad->ID; ?>">
										👁️ Preview
									</button>
									<a href="<?php echo esc_url(home_url('/ad-create/?ad_id=' . $ad->ID)); ?>" class="ad-action-button edit-button">
										✏️ Edit
									</a>
									<?php if (current_user_can('delete_post', $ad->ID)) : ?>
										<a href="<?php echo esc_url(get_delete_post_link($ad->ID)); ?>" class="ad-action-button delete-button" onclick="return confirm('Are you sure you want to delete this ad?');">
											🗑️ Delete
										</a>
									<?php endif; ?>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<div class="empty-state">
					<div class="empty-icon">⚪</div>
					<h3>No Expired Ads</h3>
					<p>You don't have any expired ads yet.</p>
				</div>
			<?php endif; ?>
		</div>

		<div class="tab-content" id="tab-all">
			<?php if (!empty($vendor_ads)) : ?>
				<div class="ads-table-wrapper">
					<table class="ads-table">
						<thead>
							<tr>
								<th>Ad Name</th>
								<th>Location</th>
								<th>Status</th>
								<th>Start Date</th>
								<th>End Date</th>
								<th>Views</th>
								<th>Clicks</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($vendor_ads as $ad) : 
								$start_date = get_post_meta($ad->ID, 'ad_start_date', true);
								$end_date = get_post_meta($ad->ID, 'ad_end_date', true);
								$slot_location = get_post_meta($ad->ID, 'slot_location', true);
								$clicks = get_post_meta($ad->ID, 'ad_clicks', true) ?: 0;
								$impressions = get_post_meta($ad->ID, 'ad_impressions', true) ?: 0;
								
								if ($today >= $start_date && $today <= $end_date) {
									$status = '<span class="status-badge active">🟢 Active</span>';
								} elseif ($today < $start_date) {
									$status = '<span class="status-badge upcoming">🔵 Upcoming</span>';
								} else {
									$status = '<span class="status-badge expired">⚪ Expired</span>';
								}
								?>
								<tr>
									<td class="ad-name-cell">
										<div class="ad-name-wrapper">
											<?php if (has_post_thumbnail($ad->ID)) : ?>
												<div class="ad-thumbnail">
													<?php echo get_the_post_thumbnail($ad->ID, 'thumbnail'); ?>
												</div>
											<?php endif; ?>
											<span><?php echo esc_html(get_the_title($ad->ID)); ?></span>
										</div>
									</td>
									<td><?php echo esc_html(ucfirst($slot_location)); ?></td>
									<td><?php echo $status; ?></td>
									<td><?php echo esc_html(date('M j, Y', strtotime($start_date))); ?></td>
									<td><?php echo esc_html(date('M j, Y', strtotime($end_date))); ?></td>
									<td><?php echo esc_html($impressions); ?></td>
									<td><?php echo esc_html($clicks); ?></td>
									<td class="actions-cell">
										<button class="table-action-link preview-button" data-ad-id="<?php echo $ad->ID; ?>" title="Preview" style="background: none; border: none; cursor: pointer;">👁️</button>
										<a href="<?php echo esc_url(home_url('/ad-create/?ad_id=' . $ad->ID)); ?>" class="table-action-link" title="Edit">✏️</a>
										<?php if (current_user_can('delete_post', $ad->ID)) : ?>
											<a href="<?php echo esc_url(get_delete_post_link($ad->ID)); ?>" class="table-action-link delete" title="Delete" onclick="return confirm('Are you sure?');">🗑️</a>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php else : ?>
				<div class="empty-state">
					<div class="empty-icon">📢</div>
					<h3>No Ads Yet</h3>
					<p>You haven't created any ads yet. Get started by creating your first ad!</p>
					<a href="<?php echo esc_url(home_url('/ad-create/')); ?>" class="empty-action-button">
						Create Your First Ad
					</a>
				</div>
			<?php endif; ?>
		</div>

		<div style="height:60px" aria-hidden="true"></div>

	</div>
</main>

<div id="ad-preview-modal" class="modal-overlay" style="display: none;">
	<div class="modal-container">
		<div class="modal-header">
			<h2 class="modal-title">👁️ Ad Preview</h2>
			<button type="button" class="modal-close" id="close-preview-modal">✕</button>
		</div>
		<div class="modal-body">
			<div id="ad-preview-content" class="ad-preview-area">
				<p class="preview-loading">Loading preview...</p>
			</div>
		</div>
		<div class="modal-footer">
			<button type="button" class="modal-btn close-modal-btn" id="close-modal-footer">Close</button>
		</div>
	</div>
</div>

<style>
.modal-overlay {
	position: fixed;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	background: rgba(0, 0, 0, 0.7);
	z-index: 9999;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 20px;
}

.modal-container {
	background: #fff;
	border-radius: 16px;
	max-width: 800px;
	width: 100%;
	max-height: 90vh;
	overflow-y: auto;
	box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 24px 32px;
	border-bottom: 2px solid #f0f0f0;
}

.modal-title {
	font-size: 24px;
	font-weight: 700;
	margin: 0;
	color: #1a1a1a;
}

.modal-close {
	background: #f5f5f5;
	border: none;
	border-radius: 8px;
	width: 40px;
	height: 40px;
	font-size: 20px;
	cursor: pointer;
	transition: all 0.3s ease;
}

.modal-close:hover {
	background: #e8e8e8;
	transform: rotate(90deg);
}

.modal-body {
	padding: 32px;
}

.ad-preview-area {
	background: #f9f9f9;
	padding: 24px;
	border-radius: 8px;
	min-height: 200px;
	display: flex;
	align-items: center;
	justify-content: center;
}

.preview-loading,
.preview-error {
	color: #999;
	font-size: 14px;
	text-align: center;
	font-style: italic;
}

.preview-error {
	color: #f44336;
}

.modal-footer {
	padding: 20px 32px;
	border-top: 2px solid #f0f0f0;
	display: flex;
	justify-content: flex-end;
}

.modal-btn {
	padding: 12px 24px;
	border-radius: 8px;
	font-size: 15px;
	font-weight: 600;
	cursor: pointer;
	transition: all 0.3s ease;
	border: none;
}

.close-modal-btn {
	background: #f5f5f5;
	color: #333;
	border: 2px solid #e0e0e0;
}

.close-modal-btn:hover {
	background: #e8e8e8;
	border-color: #ccc;
}

.preview-button {
	background: #2196f3;
	color: #fff;
	border: none;
}

.preview-button:hover {
	background: #1976d2;
	transform: translateY(-2px);
	box-shadow: 0 4px 12px rgba(33, 150, 243, 0.4);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const tabButtons = document.querySelectorAll('.tab-button');
	const tabContents = document.querySelectorAll('.tab-content');
	const nonce = '<?php echo wp_create_nonce('event_rsvp_ad_management'); ?>';

	tabButtons.forEach(button => {
		button.addEventListener('click', function() {
			const targetTab = this.getAttribute('data-tab');

			tabButtons.forEach(btn => btn.classList.remove('active'));
			tabContents.forEach(content => content.classList.remove('active'));

			this.classList.add('active');
			document.getElementById('tab-' + targetTab).classList.add('active');
		});
	});

	// Preview functionality
	const previewModal = document.getElementById('ad-preview-modal');
	const closeModalBtn = document.getElementById('close-preview-modal');
	const closeModalFooter = document.getElementById('close-modal-footer');
	const previewContent = document.getElementById('ad-preview-content');

	document.querySelectorAll('.preview-button').forEach(btn => {
		btn.addEventListener('click', function(e) {
			e.preventDefault();
			const adId = this.getAttribute('data-ad-id');
			showPreview(adId);
		});
	});

	function showPreview(adId) {
		if (!previewModal || !previewContent) return;

		previewContent.innerHTML = '<p class="preview-loading">Loading preview...</p>';
		previewModal.style.display = 'flex';
		document.body.style.overflow = 'hidden';

		fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams({
				action: 'event_rsvp_get_ad_preview',
				ad_id: adId,
				nonce: nonce
			})
		})
		.then(response => response.json())
		.then(data => {
			if (data.success && data.data.html) {
				previewContent.innerHTML = data.data.html;
			} else {
				previewContent.innerHTML = '<p class="preview-error">Failed to load preview. Make sure the ad has an image.</p>';
			}
		})
		.catch(error => {
			console.error('Preview error:', error);
			previewContent.innerHTML = '<p class="preview-error">Error loading preview.</p>';
		});
	}

	function closePreview() {
		if (previewModal) {
			previewModal.style.display = 'none';
			document.body.style.overflow = '';
		}
	}

	if (closeModalBtn) {
		closeModalBtn.addEventListener('click', closePreview);
	}

	if (closeModalFooter) {
		closeModalFooter.addEventListener('click', closePreview);
	}

	if (previewModal) {
		previewModal.addEventListener('click', function(e) {
			if (e.target === previewModal) {
				closePreview();
			}
		});
	}
});
</script>

<?php get_footer(); ?>

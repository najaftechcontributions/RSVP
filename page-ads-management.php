<?php
/**
 * Template Name: Ads Management (Admin)
 * Complete ad management interface for administrators
 *
 * @package RSVP
 */

if (!is_user_logged_in() || !current_user_can('administrator')) {
	wp_redirect(home_url('/login/'));
	exit;
}

get_header();

$all_ads = get_posts(array(
	'post_type' => 'vendor_ad',
	'posts_per_page' => -1,
	'orderby' => 'date',
	'order' => 'DESC'
));

$today = date('Y-m-d');
$active_ads = array();
$pending_ads = array();
$total_clicks = 0;
$total_impressions = 0;

foreach ($all_ads as $ad) {
	$approval_status = get_post_meta($ad->ID, 'ad_approval_status', true);
	$ad_status = get_post_meta($ad->ID, 'ad_status', true);
	$start_date = get_post_meta($ad->ID, 'ad_start_date', true);
	$end_date = get_post_meta($ad->ID, 'ad_end_date', true);
	
	if ($approval_status === 'pending' || empty($approval_status)) {
		$pending_ads[] = $ad;
	}
	
	if ($ad_status === 'active' && $approval_status === 'approved' && $today >= $start_date && $today <= $end_date) {
		$active_ads[] = $ad;
	}
	
	$total_clicks += intval(get_post_meta($ad->ID, 'ad_clicks', true));
	$total_impressions += intval(get_post_meta($ad->ID, 'ad_impressions', true));
}

$ad_locations = array(
	'sidebar' => 'Sidebar',
	'footer' => 'Footer',
	'homepage' => 'Homepage',
	'header' => 'Header',
	'event_single' => 'Single Event Page',
	'event_archive' => 'Events Archive',
	'between_content' => 'Between Content'
);
?>

<main id="primary" class="site-main ads-management-page">
	<div class="container">
		
		<div style="height:40px" aria-hidden="true"></div>

		<div class="dashboard-header">
			<div class="header-content">
				<h1 class="dashboard-title">🎯 Ads Management Center</h1>
				<p class="dashboard-subtitle">Manage, approve, and monitor all advertising across your platform</p>
			</div>
			<div class="header-actions">
				<a href="<?php echo esc_url(admin_url('edit.php?post_type=vendor_ad')); ?>" class="action-button secondary-button">
					📋 All Ads
				</a>
				<a href="<?php echo esc_url(admin_url('post-new.php?post_type=vendor_ad')); ?>" class="action-button primary-button">
					<span class="button-icon">➕</span>
					<span class="button-text">Create New Ad</span>
				</a>
			</div>
		</div>

		<div style="height:40px" aria-hidden="true"></div>

		<div class="ads-stats-overview">
			<div class="stat-card stat-total">
				<div class="stat-icon">📊</div>
				<div class="stat-content">
					<div class="stat-value"><?php echo count($all_ads); ?></div>
					<div class="stat-label">Total Ads</div>
				</div>
			</div>

			<div class="stat-card stat-active">
				<div class="stat-icon">🟢</div>
				<div class="stat-content">
					<div class="stat-value"><?php echo count($active_ads); ?></div>
					<div class="stat-label">Currently Active</div>
				</div>
			</div>

			<div class="stat-card stat-pending">
				<div class="stat-icon">⏳</div>
				<div class="stat-content">
					<div class="stat-value"><?php echo count($pending_ads); ?></div>
					<div class="stat-label">Pending Approval</div>
				</div>
			</div>

			<div class="stat-card stat-impressions">
				<div class="stat-icon">👁️</div>
				<div class="stat-content">
					<div class="stat-value"><?php echo number_format($total_impressions); ?></div>
					<div class="stat-label">Total Impressions</div>
				</div>
			</div>

			<div class="stat-card stat-clicks">
				<div class="stat-icon">🖱️</div>
				<div class="stat-content">
					<div class="stat-value"><?php echo number_format($total_clicks); ?></div>
					<div class="stat-label">Total Clicks</div>
				</div>
			</div>

			<div class="stat-card stat-ctr">
				<div class="stat-icon">📈</div>
				<div class="stat-content">
					<div class="stat-value">
						<?php echo $total_impressions > 0 ? number_format(($total_clicks / $total_impressions) * 100, 2) . '%' : '0%'; ?>
					</div>
					<div class="stat-label">Overall CTR</div>
				</div>
			</div>
		</div>

		<div style="height:40px" aria-hidden="true"></div>

		<div class="management-tabs">
			<button class="tab-button active" data-tab="all-ads">📋 All Ads</button>
			<button class="tab-button" data-tab="pending">⏳ Pending Approval (<?php echo count($pending_ads); ?>)</button>
			<button class="tab-button" data-tab="placements">📍 Ad Placements</button>
			<button class="tab-button" data-tab="performance">📊 Performance</button>
		</div>

		<div class="tab-content active" id="tab-all-ads">
			<div class="all-ads-section">
				<div class="section-header">
					<h2>All Advertisements</h2>
					<p>Manage all ads with quick actions to activate, deactivate, or delete</p>
				</div>

				<div style="height:30px" aria-hidden="true"></div>

				<?php if (!empty($all_ads)) : ?>
					<div class="ads-table-wrapper">
						<table class="ads-management-table">
							<thead>
								<tr>
									<th>Ad Preview</th>
									<th>Details</th>
									<th>Vendor</th>
									<th>Location</th>
									<th>Schedule</th>
									<th>Status</th>
									<th>Performance</th>
									<th>Shortcode</th>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($all_ads as $ad) : 
									$start_date = get_post_meta($ad->ID, 'ad_start_date', true);
									$end_date = get_post_meta($ad->ID, 'ad_end_date', true);
									$slot_location = get_post_meta($ad->ID, 'slot_location', true);
									$click_url = get_post_meta($ad->ID, 'click_url', true);
									$clicks = get_post_meta($ad->ID, 'ad_clicks', true) ?: 0;
									$impressions = get_post_meta($ad->ID, 'ad_impressions', true) ?: 0;
									$ctr = $impressions > 0 ? ($clicks / $impressions) * 100 : 0;
									$author = get_user_by('id', $ad->post_author);
									$approval_status = get_post_meta($ad->ID, 'ad_approval_status', true);
									$ad_status = get_post_meta($ad->ID, 'ad_status', true);
									
									if (empty($approval_status)) {
										$approval_status = 'pending';
										update_post_meta($ad->ID, 'ad_approval_status', 'pending');
									}
									if (empty($ad_status)) {
										$ad_status = 'inactive';
										update_post_meta($ad->ID, 'ad_status', 'inactive');
									}
									
									$is_active = ($ad_status === 'active' && $approval_status === 'approved');
									$is_date_active = ($today >= $start_date && $today <= $end_date);
									?>
									<tr class="ad-row" data-ad-id="<?php echo $ad->ID; ?>">
										<td class="ad-preview-cell">
											<?php if (has_post_thumbnail($ad->ID)) : ?>
												<div class="ad-preview-image">
													<?php echo get_the_post_thumbnail($ad->ID, 'thumbnail'); ?>
												</div>
											<?php else : ?>
												<div class="ad-no-image">
													<span>📷</span>
												</div>
											<?php endif; ?>
										</td>
										<td class="ad-details-cell">
											<div class="ad-title-wrapper">
												<strong class="ad-title"><?php echo esc_html(get_the_title($ad->ID)); ?></strong>
												<span class="ad-id">ID: <?php echo $ad->ID; ?></span>
												<?php if (!empty($click_url)) : ?>
													<a href="<?php echo esc_url($click_url); ?>" target="_blank" class="ad-url" title="<?php echo esc_attr($click_url); ?>">
														🔗 <?php echo esc_html(wp_trim_words($click_url, 5, '...')); ?>
													</a>
												<?php endif; ?>
											</div>
										</td>
										<td class="ad-vendor-cell">
											<?php echo esc_html($author->display_name); ?>
										</td>
										<td class="ad-location-cell">
											<select class="ad-location-select" data-ad-id="<?php echo $ad->ID; ?>" data-current="<?php echo esc_attr($slot_location); ?>">
												<?php foreach ($ad_locations as $loc_key => $loc_name) : ?>
													<option value="<?php echo esc_attr($loc_key); ?>" <?php selected($slot_location, $loc_key); ?>>
														<?php echo esc_html($loc_name); ?>
													</option>
												<?php endforeach; ?>
											</select>
										</td>
										<td class="ad-schedule-cell">
											<?php if (!empty($start_date) && !empty($end_date)) : ?>
												<div class="schedule-info">
													<span class="schedule-date">From: <?php echo esc_html(date('M j, Y', strtotime($start_date))); ?></span>
													<span class="schedule-date">To: <?php echo esc_html(date('M j, Y', strtotime($end_date))); ?></span>
													<?php if ($is_date_active) : ?>
														<span class="schedule-status active">🟢 Active Period</span>
													<?php else : ?>
														<span class="schedule-status inactive">⏸️ Scheduled</span>
													<?php endif; ?>
												</div>
											<?php else : ?>
												<span class="schedule-none">No schedule</span>
											<?php endif; ?>
										</td>
										<td class="ad-status-cell">
											<div class="status-badges">
												<?php if ($approval_status === 'approved') : ?>
													<span class="status-badge approved">✓ Approved</span>
												<?php elseif ($approval_status === 'rejected') : ?>
													<span class="status-badge rejected">✗ Rejected</span>
												<?php else : ?>
													<span class="status-badge pending">⏳ Pending</span>
												<?php endif; ?>
												
												<?php if ($ad_status === 'active') : ?>
													<span class="status-badge active">🟢 Active</span>
												<?php else : ?>
													<span class="status-badge inactive">⏸️ Inactive</span>
												<?php endif; ?>
											</div>
										</td>
										<td class="ad-performance-cell">
											<div class="performance-stats">
												<div class="stat-item">
													<span class="stat-icon">👁️</span>
													<span class="stat-number"><?php echo number_format($impressions); ?></span>
												</div>
												<div class="stat-item">
													<span class="stat-icon">🖱️</span>
													<span class="stat-number"><?php echo number_format($clicks); ?></span>
												</div>
												<div class="stat-item">
													<span class="stat-label">CTR:</span>
													<span class="stat-number"><?php echo number_format($ctr, 2); ?>%</span>
												</div>
											</div>
										</td>
										<td class="ad-shortcode-cell">
											<div class="shortcode-wrapper">
												<code class="shortcode-display">[ad id="<?php echo $ad->ID; ?>"]</code>
												<button class="copy-shortcode-btn" data-shortcode='[ad id="<?php echo $ad->ID; ?>"]' title="Copy shortcode">
													📋
												</button>
											</div>
										</td>
										<td class="ad-actions-cell">
											<div class="action-buttons">
												<?php if ($approval_status !== 'approved') : ?>
													<button class="action-btn approve-btn" data-ad-id="<?php echo $ad->ID; ?>" title="Approve Ad">
														✓
													</button>
												<?php endif; ?>
												
												<?php if ($approval_status !== 'rejected') : ?>
													<button class="action-btn reject-btn" data-ad-id="<?php echo $ad->ID; ?>" title="Reject Ad">
														✗
													</button>
												<?php endif; ?>
												
												<?php if ($ad_status === 'active') : ?>
													<button class="action-btn deactivate-btn" data-ad-id="<?php echo $ad->ID; ?>" title="Deactivate Ad">
														⏸️
													</button>
												<?php else : ?>
													<button class="action-btn activate-btn" data-ad-id="<?php echo $ad->ID; ?>" title="Activate Ad">
														▶️
													</button>
												<?php endif; ?>
												
												<a href="<?php echo esc_url(get_edit_post_link($ad->ID)); ?>" class="action-btn edit-btn" title="Edit Ad">
													✏️
												</a>
												
												<button class="action-btn delete-btn" data-ad-id="<?php echo $ad->ID; ?>" title="Delete Ad">
													🗑️
												</button>
											</div>
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
						<p>Create your first ad to get started.</p>
						<a href="<?php echo esc_url(admin_url('post-new.php?post_type=vendor_ad')); ?>" class="primary-button">
							Create First Ad
						</a>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<div class="tab-content" id="tab-pending">
			<div class="pending-ads-section">
				<div class="section-header">
					<h2>Pending Approval</h2>
					<p>Review and approve or reject pending advertisements</p>
				</div>

				<div style="height:30px" aria-hidden="true"></div>

				<?php if (!empty($pending_ads)) : ?>
					<div class="pending-ads-grid">
						<?php foreach ($pending_ads as $ad) : 
							$start_date = get_post_meta($ad->ID, 'ad_start_date', true);
							$end_date = get_post_meta($ad->ID, 'ad_end_date', true);
							$slot_location = get_post_meta($ad->ID, 'slot_location', true);
							$click_url = get_post_meta($ad->ID, 'click_url', true);
							$author = get_user_by('id', $ad->post_author);
							?>
							<div class="pending-ad-card" data-ad-id="<?php echo $ad->ID; ?>">
								<div class="pending-ad-preview">
									<?php if (has_post_thumbnail($ad->ID)) : ?>
										<?php echo get_the_post_thumbnail($ad->ID, 'medium'); ?>
									<?php else : ?>
										<div class="no-preview">
											<span>📷 No Image</span>
										</div>
									<?php endif; ?>
								</div>
								<div class="pending-ad-info">
									<h3 class="pending-ad-title"><?php echo esc_html(get_the_title($ad->ID)); ?></h3>
									<div class="pending-ad-meta">
										<span class="meta-item"><strong>Vendor:</strong> <?php echo esc_html($author->display_name); ?></span>
										<span class="meta-item"><strong>Location:</strong> <?php echo esc_html($ad_locations[$slot_location] ?? ucfirst($slot_location)); ?></span>
										<span class="meta-item"><strong>Duration:</strong> <?php echo esc_html(date('M j', strtotime($start_date)) . ' - ' . date('M j', strtotime($end_date))); ?></span>
										<?php if (!empty($click_url)) : ?>
											<span class="meta-item"><strong>URL:</strong> <a href="<?php echo esc_url($click_url); ?>" target="_blank"><?php echo esc_html(wp_trim_words($click_url, 5, '...')); ?></a></span>
										<?php endif; ?>
									</div>
									<div class="pending-ad-shortcode">
										<label>Shortcode:</label>
										<code>[ad id="<?php echo $ad->ID; ?>"]</code>
										<button class="copy-shortcode-btn small" data-shortcode='[ad id="<?php echo $ad->ID; ?>"]'>Copy</button>
									</div>
									<div class="pending-ad-actions">
										<button class="approve-btn-large" data-ad-id="<?php echo $ad->ID; ?>">
											✓ Approve
										</button>
										<button class="reject-btn-large" data-ad-id="<?php echo $ad->ID; ?>">
											✗ Reject
										</button>
										<a href="<?php echo esc_url(get_edit_post_link($ad->ID)); ?>" class="edit-btn-large">
											✏️ Edit
										</a>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<div class="empty-state">
						<div class="empty-icon">✅</div>
						<h3>All Caught Up!</h3>
						<p>There are no ads pending approval.</p>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<div class="tab-content" id="tab-placements">
			<div class="placements-section">
				<div class="section-header">
					<h2>Ad Placement Locations</h2>
					<p>Configure where ads appear on your website using shortcodes</p>
				</div>

				<div style="height:30px" aria-hidden="true"></div>

				<div class="placements-grid">
					<?php foreach ($ad_locations as $location_key => $location_name) : 
						$location_ads = event_rsvp_get_active_vendor_ads($location_key);
						$location_ads_count = count($location_ads);
						?>
						<div class="placement-card">
							<div class="placement-header">
								<h3 class="placement-title"><?php echo esc_html($location_name); ?></h3>
								<span class="placement-badge">
									<?php echo $location_ads_count; ?> active
								</span>
							</div>

							<div class="placement-info">
								<div class="info-item">
									<span class="info-label">Location Shortcode:</span>
									<div class="shortcode-copy-wrapper">
										<code class="info-code">[vendor_ad location="<?php echo esc_attr($location_key); ?>"]</code>
										<button class="copy-shortcode-btn" data-shortcode='[vendor_ad location="<?php echo esc_attr($location_key); ?>"]'>📋</button>
									</div>
								</div>
								<div class="info-item">
									<span class="info-label">PHP Function:</span>
									<code class="info-code">event_rsvp_display_vendor_ad('<?php echo esc_js($location_key); ?>')</code>
								</div>
							</div>

							<?php if ($location_ads_count > 0) : ?>
								<div class="placement-ads">
									<h4 class="placement-ads-title">Active Ads in This Location:</h4>
									<ul class="placement-ads-list">
										<?php foreach ($location_ads as $ad) : ?>
											<li class="placement-ad-item">
												<span class="ad-item-title"><?php echo esc_html(get_the_title($ad->ID)); ?></span>
												<a href="<?php echo esc_url(get_edit_post_link($ad->ID)); ?>" class="ad-item-edit">✏️</a>
											</li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php else : ?>
								<div class="placement-empty">
									<p>No active ads in this location</p>
								</div>
							<?php endif; ?>

							<div class="placement-preview">
								<h4>Live Preview:</h4>
								<?php 
								$preview = event_rsvp_display_vendor_ad($location_key);
								if ($preview) {
									echo $preview;
								} else {
									echo '<div class="preview-empty">No ad to preview</div>';
								}
								?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<div style="height:40px" aria-hidden="true"></div>

				<div class="implementation-guide">
					<h3>📚 How to Use Ad Shortcodes</h3>
					
					<div class="guide-grid">
						<div class="guide-card">
							<h4>🎯 Individual Ad Shortcode</h4>
							<p>Display a specific ad anywhere on your site:</p>
							<code class="guide-code">[ad id="123"]</code>
							<p class="guide-note">Replace "123" with the actual ad ID. Find the shortcode in the table above.</p>
						</div>

						<div class="guide-card">
							<h4>📍 Location-Based Shortcode</h4>
							<p>Display a random ad from a specific location:</p>
							<code class="guide-code">[vendor_ad location="sidebar"]</code>
							<p class="guide-note">Perfect for rotating ads in a specific position.</p>
						</div>

						<div class="guide-card">
							<h4>🧩 Elementor Integration</h4>
							<p>1. Add a "Shortcode" widget to your Elementor page</p>
							<p>2. Paste the shortcode: <code>[ad id="123"]</code></p>
							<p>3. The ad will display with full styling automatically</p>
						</div>

						<div class="guide-card">
							<h4>⚙️ ACF Blocks Integration</h4>
							<p>In your ACF block template, use:</p>
							<code class="guide-code">&lt;?php echo do_shortcode('[ad id="123"]'); ?&gt;</code>
							<p class="guide-note">Or use the location-based shortcode for dynamic ads.</p>
						</div>

						<div class="guide-card">
							<h4>📝 Page/Post Editor</h4>
							<p>Simply paste the shortcode anywhere in your content:</p>
							<code class="guide-code">[ad id="123"]</code>
							<p class="guide-note">Works in Classic Editor, Gutenberg, and text widgets.</p>
						</div>

						<div class="guide-card">
							<h4>🔧 PHP Templates</h4>
							<p>Add this to your theme template files:</p>
							<code class="guide-code">&lt;?php echo do_shortcode('[ad id="123"]'); ?&gt;</code>
							<p class="guide-note">Useful for header.php, footer.php, sidebar.php, etc.</p>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="tab-content" id="tab-performance">
			<div class="performance-section">
				<div class="section-header">
					<h2>Performance Analytics</h2>
					<p>Track and analyze advertising performance across your platform</p>
				</div>

				<div style="height:30px" aria-hidden="true"></div>

				<div class="performance-summary">
					<h3>Top Performing Ads</h3>
					
					<?php
					$ads_by_performance = $all_ads;
					usort($ads_by_performance, function($a, $b) {
						$clicks_a = intval(get_post_meta($a->ID, 'ad_clicks', true));
						$clicks_b = intval(get_post_meta($b->ID, 'ad_clicks', true));
						return $clicks_b - $clicks_a;
					});
					$top_ads = array_slice($ads_by_performance, 0, 10);
					?>

					<div class="performance-list">
						<?php foreach ($top_ads as $index => $ad) : 
							$clicks = get_post_meta($ad->ID, 'ad_clicks', true) ?: 0;
							$impressions = get_post_meta($ad->ID, 'ad_impressions', true) ?: 0;
							$ctr = $impressions > 0 ? ($clicks / $impressions) * 100 : 0;
							$slot_location = get_post_meta($ad->ID, 'slot_location', true);
							?>
							<div class="performance-item">
								<div class="performance-rank">#<?php echo ($index + 1); ?></div>
								<div class="performance-preview">
									<?php if (has_post_thumbnail($ad->ID)) : ?>
										<?php echo get_the_post_thumbnail($ad->ID, 'thumbnail'); ?>
									<?php else : ?>
										<span class="no-thumb">📷</span>
									<?php endif; ?>
								</div>
								<div class="performance-details">
									<h4 class="performance-ad-title"><?php echo esc_html(get_the_title($ad->ID)); ?></h4>
									<span class="performance-location"><?php echo esc_html($ad_locations[$slot_location] ?? ucfirst($slot_location)); ?></span>
								</div>
								<div class="performance-metrics">
									<div class="metric-item">
										<span class="metric-label">Views:</span>
										<span class="metric-value"><?php echo number_format($impressions); ?></span>
									</div>
									<div class="metric-item">
										<span class="metric-label">Clicks:</span>
										<span class="metric-value"><?php echo number_format($clicks); ?></span>
									</div>
									<div class="metric-item">
										<span class="metric-label">CTR:</span>
										<span class="metric-value"><?php echo number_format($ctr, 2); ?>%</span>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>

				<div style="height:40px" aria-hidden="true"></div>

				<div class="performance-by-location">
					<h3>Performance by Location</h3>
					
					<div class="location-performance-grid">
						<?php foreach ($ad_locations as $location_key => $location_name) : 
							$location_ads = get_posts(array(
								'post_type' => 'vendor_ad',
								'posts_per_page' => -1,
								'meta_query' => array(
									array(
										'key' => 'slot_location',
										'value' => $location_key,
										'compare' => '='
									)
								)
							));
							
							$location_clicks = 0;
							$location_impressions = 0;
							
							foreach ($location_ads as $ad) {
								$location_clicks += intval(get_post_meta($ad->ID, 'ad_clicks', true));
								$location_impressions += intval(get_post_meta($ad->ID, 'ad_impressions', true));
							}
							
							$location_ctr = $location_impressions > 0 ? ($location_clicks / $location_impressions) * 100 : 0;
							?>
							<div class="location-performance-card">
								<h4><?php echo esc_html($location_name); ?></h4>
								<div class="location-stats">
									<div class="location-stat">
										<span class="stat-label">Ads:</span>
										<span class="stat-value"><?php echo count($location_ads); ?></span>
									</div>
									<div class="location-stat">
										<span class="stat-label">Views:</span>
										<span class="stat-value"><?php echo number_format($location_impressions); ?></span>
									</div>
									<div class="location-stat">
										<span class="stat-label">Clicks:</span>
										<span class="stat-value"><?php echo number_format($location_clicks); ?></span>
									</div>
									<div class="location-stat">
										<span class="stat-label">CTR:</span>
										<span class="stat-value"><?php echo number_format($location_ctr, 2); ?>%</span>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>

		<div style="height:60px" aria-hidden="true"></div>

	</div>
</main>

<div id="shortcode-preview-modal" class="modal-overlay" style="display: none;">
	<div class="modal-container">
		<div class="modal-header">
			<h2 class="modal-title">📋 Shortcode Preview</h2>
			<button type="button" class="modal-close" id="close-preview-modal">✕</button>
		</div>
		<div class="modal-body">
			<div class="preview-section">
				<h3>Individual Ad Shortcode</h3>
				<div class="shortcode-demo">
					<code id="individual-shortcode">[ad id="123"]</code>
					<button class="copy-btn" data-copy-id="individual-shortcode">📋 Copy</button>
				</div>
				<p class="preview-description">Use this to display a specific ad anywhere on your site.</p>
			</div>

			<div class="preview-section">
				<h3>Live Preview</h3>
				<div id="ad-preview-container" class="ad-preview-area">
					<p class="preview-loading">Select an ad from the table to see preview...</p>
				</div>
			</div>

			<div class="preview-section">
				<h3>Usage Examples</h3>
				<div class="usage-examples">
					<div class="example-item">
						<h4>In WordPress Editor:</h4>
						<p>Paste the shortcode directly into your page or post content:</p>
						<code>[ad id="123"]</code>
					</div>
					<div class="example-item">
						<h4>In PHP Templates:</h4>
						<p>Use the do_shortcode function:</p>
						<code>&lt;?php echo do_shortcode('[ad id="123"]'); ?&gt;</code>
					</div>
					<div class="example-item">
						<h4>In Elementor:</h4>
						<p>Add a Shortcode widget and paste:</p>
						<code>[ad id="123"]</code>
					</div>
				</div>
			</div>
		</div>
		<div class="modal-footer">
			<button type="button" class="modal-btn close-modal-btn" id="close-modal-footer">Close</button>
		</div>
	</div>
</div>

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

	document.querySelectorAll('.copy-shortcode-btn').forEach(btn => {
		btn.addEventListener('click', function(e) {
			e.preventDefault();
			const shortcode = this.getAttribute('data-shortcode');
			navigator.clipboard.writeText(shortcode).then(() => {
				const originalText = this.textContent;
				this.textContent = '✓';
				this.style.backgroundColor = '#4caf50';
				setTimeout(() => {
					this.textContent = originalText;
					this.style.backgroundColor = '';
				}, 2000);
			});
		});
	});

	document.querySelectorAll('.ad-location-select').forEach(select => {
		select.addEventListener('change', function() {
			const adId = this.getAttribute('data-ad-id');
			const newLocation = this.value;
			const currentLocation = this.getAttribute('data-current');

			if (newLocation === currentLocation) return;

			if (confirm('Are you sure you want to change the ad location?')) {
				fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: new URLSearchParams({
						action: 'event_rsvp_change_ad_location',
						ad_id: adId,
						location: newLocation,
						nonce: nonce
					})
				})
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						alert(data.data.message);
						this.setAttribute('data-current', newLocation);
					} else {
						alert('Error: ' + (data.data || 'Unknown error'));
						this.value = currentLocation;
					}
				})
				.catch(error => {
					console.error('Error:', error);
					alert('An error occurred. Please try again.');
					this.value = currentLocation;
				});
			} else {
				this.value = currentLocation;
			}
		});
	});

	function performAdAction(action, adId, button) {
		const row = button.closest('.ad-row') || button.closest('.pending-ad-card');
		
		fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams({
				action: action,
				ad_id: adId,
				nonce: nonce
			})
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				alert(data.data.message);
				location.reload();
			} else {
				alert('Error: ' + (data.data || 'Unknown error'));
			}
		})
		.catch(error => {
			console.error('Error:', error);
			alert('An error occurred. Please try again.');
		});
	}

	document.querySelectorAll('.approve-btn, .approve-btn-large').forEach(btn => {
		btn.addEventListener('click', function(e) {
			e.preventDefault();
			const adId = this.getAttribute('data-ad-id');
			performAdAction('event_rsvp_approve_ad', adId, this);
		});
	});

	document.querySelectorAll('.reject-btn, .reject-btn-large').forEach(btn => {
		btn.addEventListener('click', function(e) {
			e.preventDefault();
			if (confirm('Are you sure you want to reject this ad?')) {
				const adId = this.getAttribute('data-ad-id');
				performAdAction('event_rsvp_reject_ad', adId, this);
			}
		});
	});

	document.querySelectorAll('.activate-btn').forEach(btn => {
		btn.addEventListener('click', function(e) {
			e.preventDefault();
			const adId = this.getAttribute('data-ad-id');
			fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams({
					action: 'event_rsvp_toggle_ad_status',
					ad_id: adId,
					status: 'activate',
					nonce: nonce
				})
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					alert(data.data.message);
					location.reload();
				} else {
					alert('Error: ' + (data.data || 'Unknown error'));
				}
			});
		});
	});

	document.querySelectorAll('.deactivate-btn').forEach(btn => {
		btn.addEventListener('click', function(e) {
			e.preventDefault();
			if (confirm('Are you sure you want to deactivate this ad?')) {
				const adId = this.getAttribute('data-ad-id');
				fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: new URLSearchParams({
						action: 'event_rsvp_toggle_ad_status',
						ad_id: adId,
						status: 'deactivate',
						nonce: nonce
					})
				})
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						alert(data.data.message);
						location.reload();
					} else {
						alert('Error: ' + (data.data || 'Unknown error'));
					}
				});
			}
		});
	});

	document.querySelectorAll('.delete-btn').forEach(btn => {
		btn.addEventListener('click', function(e) {
			e.preventDefault();
			if (confirm('Are you sure you want to permanently delete this ad? This action cannot be undone.')) {
				const adId = this.getAttribute('data-ad-id');
				performAdAction('event_rsvp_delete_ad', adId, this);
			}
		});
	});

	const previewModal = document.getElementById('shortcode-preview-modal');
	const closeModalBtn = document.getElementById('close-preview-modal');
	const closeModalFooter = document.getElementById('close-modal-footer');
	const previewContainer = document.getElementById('ad-preview-container');
	const individualShortcode = document.getElementById('individual-shortcode');

	document.querySelectorAll('.preview-shortcode-btn').forEach(btn => {
		btn.addEventListener('click', function(e) {
			e.preventDefault();
			const adId = this.getAttribute('data-ad-id');
			showPreviewModal(adId);
		});
	});

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

	function showPreviewModal(adId) {
		if (individualShortcode) {
			individualShortcode.textContent = `[ad id="${adId}"]`;
		}

		if (previewContainer) {
			previewContainer.innerHTML = '<p class="preview-loading">Loading preview...</p>';
		}

		if (previewModal) {
			previewModal.style.display = 'flex';
			document.body.style.overflow = 'hidden';
		}

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
				if (previewContainer) {
					previewContainer.innerHTML = data.data.html;
				}
			} else {
				if (previewContainer) {
					previewContainer.innerHTML = '<p class="preview-error">Failed to load preview. Make sure the ad has an image.</p>';
				}
			}
		})
		.catch(error => {
			console.error('Preview error:', error);
			if (previewContainer) {
				previewContainer.innerHTML = '<p class="preview-error">Error loading preview.</p>';
			}
		});
	}

	function closePreview() {
		if (previewModal) {
			previewModal.style.display = 'none';
			document.body.style.overflow = '';
		}
	}

	document.querySelectorAll('.copy-btn').forEach(btn => {
		btn.addEventListener('click', function() {
			const targetId = this.getAttribute('data-copy-id');
			const targetEl = document.getElementById(targetId);
			if (targetEl) {
				const text = targetEl.textContent;
				navigator.clipboard.writeText(text).then(() => {
					const original = this.textContent;
					this.textContent = '✓ Copied!';
					this.style.backgroundColor = '#4caf50';
					setTimeout(() => {
						this.textContent = original;
						this.style.backgroundColor = '';
					}, 2000);
				});
			}
		});
	});
});
</script>

<?php get_footer(); ?>

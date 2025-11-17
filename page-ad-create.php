<?php
/**
 * Template Name: Ad Creation Form
 * Create and edit vendor ads
 *
 * @package RSVP
 */

if (!is_user_logged_in()) {
	wp_redirect(home_url('/login/'));
	exit;
}

$current_user = wp_get_current_user();
$user_roles = $current_user->roles;

if (!in_array('vendor', $user_roles) && !in_array('pro', $user_roles) && !in_array('administrator', $user_roles)) {
	wp_redirect(home_url('/pricing/'));
	exit;
}

$ad_id = isset($_GET['ad_id']) ? intval($_GET['ad_id']) : 0;
$is_edit = false;
$ad = null;

if ($ad_id > 0) {
	$ad = get_post($ad_id);
	if (!$ad || $ad->post_type !== 'vendor_ad') {
		wp_redirect(home_url('/ad-create/'));
		exit;
	}
	
	if (!current_user_can('administrator') && $ad->post_author != get_current_user_id()) {
		wp_redirect(home_url('/ads-manager/'));
		exit;
	}
	
	$is_edit = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ad_form_nonce'])) {
	if (!wp_verify_nonce($_POST['ad_form_nonce'], 'create_ad')) {
		$error_message = 'Security check failed. Please try again.';
	} else {
		$ad_title = sanitize_text_field($_POST['ad_title'] ?? '');
		$click_url = esc_url_raw($_POST['click_url'] ?? '');
		$slot_location = sanitize_text_field($_POST['slot_location'] ?? 'sidebar');
		$ad_start_date = sanitize_text_field($_POST['ad_start_date'] ?? '');
		$ad_end_date = sanitize_text_field($_POST['ad_end_date'] ?? '');
		$rendering_style = sanitize_text_field($_POST['rendering_style'] ?? 'default');
		
		if (empty($ad_title)) {
			$error_message = 'Please provide an ad title.';
		} else {
			$post_data = array(
				'post_title' => $ad_title,
				'post_type' => 'vendor_ad',
				'post_status' => 'publish',
				'post_author' => get_current_user_id()
			);
			
			if ($is_edit) {
				$post_data['ID'] = $ad_id;
				$result = wp_update_post($post_data);
			} else {
				$result = wp_insert_post($post_data);
			}
			
			if ($result && !is_wp_error($result)) {
				$saved_ad_id = $is_edit ? $ad_id : $result;
				
				update_post_meta($saved_ad_id, 'click_url', $click_url);
				update_post_meta($saved_ad_id, 'slot_location', $slot_location);
				update_post_meta($saved_ad_id, 'ad_start_date', $ad_start_date);
				update_post_meta($saved_ad_id, 'ad_end_date', $ad_end_date);
				update_post_meta($saved_ad_id, 'rendering_style', $rendering_style);
				
				if ($is_edit) {
					update_post_meta($saved_ad_id, 'ad_status', get_post_meta($saved_ad_id, 'ad_status', true) ?: 'inactive');
					update_post_meta($saved_ad_id, 'ad_approval_status', get_post_meta($saved_ad_id, 'ad_approval_status', true) ?: 'pending');
				} else {
					update_post_meta($saved_ad_id, 'ad_status', 'inactive');
					update_post_meta($saved_ad_id, 'ad_approval_status', 'pending');
					update_post_meta($saved_ad_id, 'ad_clicks', 0);
					update_post_meta($saved_ad_id, 'ad_impressions', 0);
				}
				
				// Handle featured image upload
				if (!empty($_FILES['ad_image']['name'])) {
					require_once(ABSPATH . 'wp-admin/includes/image.php');
					require_once(ABSPATH . 'wp-admin/includes/file.php');
					require_once(ABSPATH . 'wp-admin/includes/media.php');
					
					$attachment_id = media_handle_upload('ad_image', $saved_ad_id);
					
					if (!is_wp_error($attachment_id)) {
						set_post_thumbnail($saved_ad_id, $attachment_id);
					} else {
						$error_message = 'Ad saved but failed to upload image: ' . $attachment_id->get_error_message();
					}
				}
				
				if (!isset($error_message)) {
					$success_message = $is_edit ? 'Ad updated successfully!' : 'Ad created successfully! Pending admin approval.';
					
					if (!current_user_can('administrator')) {
						wp_redirect(home_url('/ads-manager/?success=ad_' . ($is_edit ? 'updated' : 'created')));
						exit;
					}
				}
			} else {
				$error_message = 'Failed to ' . ($is_edit ? 'update' : 'create') . ' ad. Please try again.';
			}
		}
	}
}

get_header();

$ad_locations = array(
	'home_1' => 'Homepage Slot 1',
	'home_2' => 'Homepage Slot 2',
	'home_3' => 'Homepage Slot 3',
	'sidebar_1' => 'Sidebar Slot 1',
	'sidebar_2' => 'Sidebar Slot 2',
	'sidebar_3' => 'Sidebar Slot 3',
	'sidebar_4' => 'Sidebar Slot 4',
	'events_1' => 'Events Page Slot 1',
	'events_2' => 'Events Page Slot 2',
	'events_3' => 'Events Page Slot 3',
	'events_4' => 'Events Page Slot 4'
);

if ($is_edit && $ad) {
	$current_title = get_the_title($ad_id);
	$current_click_url = get_post_meta($ad_id, 'click_url', true);
	$current_location = get_post_meta($ad_id, 'slot_location', true);
	$current_start_date = get_post_meta($ad_id, 'ad_start_date', true);
	$current_end_date = get_post_meta($ad_id, 'ad_end_date', true);
	$current_image = get_the_post_thumbnail_url($ad_id, 'medium');
	$current_rendering_style = get_post_meta($ad_id, 'rendering_style', true) ?: 'default';
} else {
	$current_title = '';
	$current_click_url = '';
	$current_location = 'home_1';
	$current_start_date = date('Y-m-d');
	$current_end_date = date('Y-m-d', strtotime('+30 days'));
	$current_image = '';
	$current_rendering_style = 'default';
}
?>

<main id="primary" class="site-main ad-create-page">
	<div class="container">
		
		<div style="height:40px" aria-hidden="true"></div>

		<div class="page-header">
			<div class="header-content">
				<h1 class="page-title">
					<?php echo $is_edit ? '✏️ Edit Advertisement' : '➕ Create New Advertisement'; ?>
				</h1>
				<p class="page-subtitle">
					<?php echo $is_edit ? 'Update your ad details and settings' : 'Design your ad and choose where it appears'; ?>
				</p>
			</div>
			<div class="header-actions">
				<a href="<?php echo esc_url(home_url('/ads-manager/')); ?>" class="back-button">
					← Back to Dashboard
				</a>
			</div>
		</div>

		<div style="height:40px" aria-hidden="true"></div>

		<?php if (isset($success_message)) : ?>
			<div class="success-notice">
				✓ <?php echo esc_html($success_message); ?>
			</div>
			<div style="height:20px" aria-hidden="true"></div>
		<?php endif; ?>

		<?php if (isset($error_message)) : ?>
			<div class="error-notice">
				⚠️ <?php echo esc_html($error_message); ?>
			</div>
			<div style="height:20px" aria-hidden="true"></div>
		<?php endif; ?>

		<div class="ad-create-layout">
			
			<div class="ad-form-container">
				<form method="post" action="" class="ad-creation-form" enctype="multipart/form-data">
					<?php wp_nonce_field('create_ad', 'ad_form_nonce'); ?>
					
					<div class="form-section">
						<h3 class="section-title">Ad Details</h3>
						
						<div class="form-group">
							<label for="ad_title" class="form-label">Ad Title <span class="required">*</span></label>
							<input type="text" id="ad_title" name="ad_title" class="form-input" required value="<?php echo esc_attr($current_title); ?>" placeholder="e.g., Summer Sale Banner">
							<p class="form-help">Give your ad a descriptive name for internal reference.</p>
						</div>

						<div class="form-group">
							<label for="click_url" class="form-label">Click URL</label>
							<input type="url" id="click_url" name="click_url" class="form-input" value="<?php echo esc_url($current_click_url); ?>" placeholder="https://example.com/landing-page">
							<p class="form-help">Where users will be redirected when they click your ad.</p>
						</div>
					</div>

					<div class="form-section">
						<h3 class="section-title">Ad Image</h3>
						
						<div class="form-group">
							<label class="form-label">Upload Ad Image <span class="required">*</span></label>
							<div class="image-upload-area" id="image-upload-area">
								<?php if ($current_image) : ?>
									<div class="image-preview" id="image-preview">
										<img src="<?php echo esc_url($current_image); ?>" alt="Ad preview">
										<button type="button" class="remove-image-btn" id="remove-image-btn">✕</button>
									</div>
								<?php else : ?>
									<div class="upload-placeholder" id="upload-placeholder">
										<span class="upload-icon">📸</span>
										<p class="upload-text">Click to upload or drag and drop</p>
										<p class="upload-hint">Recommended: 800x600px, PNG or JPG</p>
									</div>
								<?php endif; ?>
								<input type="file" name="ad_image" id="ad_image_input" accept="image/*" style="display: none;">
							</div>
							<p class="form-help">Upload a high-quality image for your ad. This is required.</p>
						</div>
					</div>

					<div class="form-section">
						<h3 class="section-title">Placement & Schedule</h3>
						
						<div class="form-group">
							<label for="slot_location" class="form-label">Ad Location <span class="required">*</span></label>
							<select id="slot_location" name="slot_location" class="form-select" required>
								<?php foreach ($ad_locations as $key => $label) : ?>
									<option value="<?php echo esc_attr($key); ?>" <?php selected($current_location, $key); ?>>
										<?php echo esc_html($label); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="form-help">Choose where your ad will appear on the website.</p>
						</div>

						<div class="form-group">
							<label for="rendering_style" class="form-label">Rendering Style</label>
							<select id="rendering_style" name="rendering_style" class="form-select">
								<option value="default" <?php selected($current_rendering_style, 'default'); ?>>Default</option>
								<option value="banner" <?php selected($current_rendering_style, 'banner'); ?>>Banner (Full Width)</option>
								<option value="card" <?php selected($current_rendering_style, 'card'); ?>>Card (with Shadow)</option>
								<option value="minimal" <?php selected($current_rendering_style, 'minimal'); ?>>Minimal (Simple)</option>
								<option value="overlay" <?php selected($current_rendering_style, 'overlay'); ?>>Overlay (Hover Effect)</option>
							</select>
							<p class="form-help">Choose how your ad will be displayed. Default uses the standard responsive layout.</p>
						</div>

						<div class="form-row">
							<div class="form-group">
								<label for="ad_start_date" class="form-label">Start Date <span class="required">*</span></label>
								<input type="date" id="ad_start_date" name="ad_start_date" class="form-input" required value="<?php echo esc_attr($current_start_date); ?>" min="<?php echo date('Y-m-d'); ?>">
							</div>

							<div class="form-group">
								<label for="ad_end_date" class="form-label">End Date <span class="required">*</span></label>
								<input type="date" id="ad_end_date" name="ad_end_date" class="form-input" required value="<?php echo esc_attr($current_end_date); ?>" min="<?php echo date('Y-m-d'); ?>">
							</div>
						</div>
					</div>

					<div class="form-actions">
						<button type="submit" class="submit-button">
							<span class="button-icon"><?php echo $is_edit ? '💾' : '✨'; ?></span>
							<span class="button-text"><?php echo $is_edit ? 'Update Ad' : 'Create Ad'; ?></span>
						</button>
						<a href="<?php echo esc_url(home_url('/ads-manager/')); ?>" class="cancel-button">
							Cancel
						</a>
					</div>
				</form>
			</div>

			<aside class="ad-info-sidebar">
				<div class="info-card">
					<h3 class="info-title">💡 Ad Guidelines</h3>
					<ul class="info-list">
						<li><strong>Image Quality:</strong> Use high-resolution images (800x600px or larger)</li>
						<li><strong>File Format:</strong> PNG or JPG formats are accepted</li>
						<li><strong>Content:</strong> Ensure ads comply with our content policy</li>
						<li><strong>Approval:</strong> Ads require admin approval before going live</li>
						<li><strong>Performance:</strong> Track clicks and impressions in your dashboard</li>
					</ul>
				</div>

				<?php if ($is_edit) : ?>
					<div class="info-card">
						<h3 class="info-title">📊 Ad Status</h3>
						<div class="status-info">
							<?php
							$approval_status = get_post_meta($ad_id, 'ad_approval_status', true);
							$ad_status = get_post_meta($ad_id, 'ad_status', true);
							$clicks = get_post_meta($ad_id, 'ad_clicks', true) ?: 0;
							$impressions = get_post_meta($ad_id, 'ad_impressions', true) ?: 0;
							?>
							<div class="status-item">
								<span class="status-label">Approval:</span>
								<span class="status-badge status-<?php echo esc_attr($approval_status); ?>">
									<?php echo ucfirst($approval_status ?: 'pending'); ?>
								</span>
							</div>
							<div class="status-item">
								<span class="status-label">Status:</span>
								<span class="status-badge status-<?php echo esc_attr($ad_status); ?>">
									<?php echo ucfirst($ad_status ?: 'inactive'); ?>
								</span>
							</div>
							<div class="status-item">
								<span class="status-label">Clicks:</span>
								<span class="status-value"><?php echo number_format($clicks); ?></span>
							</div>
							<div class="status-item">
								<span class="status-label">Impressions:</span>
								<span class="status-value"><?php echo number_format($impressions); ?></span>
							</div>
						</div>
					</div>

					<div class="info-card">
						<h3 class="info-title">🔗 Shortcode</h3>
						<div class="shortcode-box">
							<code class="shortcode-text">[ad id="<?php echo $ad_id; ?>"]</code>
							<button type="button" class="copy-shortcode" data-shortcode='[ad id="<?php echo $ad_id; ?>"]'>
								📋 Copy
							</button>
						</div>
						<p class="shortcode-help">Use this shortcode to display your ad anywhere on the site.</p>
					</div>
				<?php endif; ?>

				<div class="info-card help-card">
					<h3 class="info-title">❓ Need Help?</h3>
					<p>Contact our support team if you have questions about creating or managing ads.</p>
					<a href="<?php echo esc_url(home_url('/contact/')); ?>" class="help-button">
						Contact Support
					</a>
				</div>
			</aside>

		</div>

		<div style="height:60px" aria-hidden="true"></div>

	</div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const imageInput = document.getElementById('ad_image_input');
	const uploadArea = document.getElementById('image-upload-area');
	const uploadPlaceholder = document.getElementById('upload-placeholder');

	if (uploadArea && imageInput) {
		uploadArea.addEventListener('click', function(e) {
			if (e.target.id !== 'remove-image-btn' && !e.target.classList.contains('remove-image-btn')) {
				imageInput.click();
			}
		});

		uploadArea.addEventListener('dragover', function(e) {
			e.preventDefault();
			e.stopPropagation();
			this.classList.add('drag-over');
		});

		uploadArea.addEventListener('dragleave', function(e) {
			e.preventDefault();
			e.stopPropagation();
			this.classList.remove('drag-over');
		});

		uploadArea.addEventListener('drop', function(e) {
			e.preventDefault();
			e.stopPropagation();
			this.classList.remove('drag-over');
			
			const files = e.dataTransfer.files;
			if (files.length > 0) {
				processImageFile(files[0]);
			}
		});

		imageInput.addEventListener('change', function(e) {
			if (this.files && this.files[0]) {
				processImageFile(this.files[0]);
			}
		});
	}

	function processImageFile(file) {
		if (!file.type.match('image.*')) {
			alert('Please upload an image file');
			return;
		}

		const reader = new FileReader();
		reader.onload = function(e) {
			showImagePreview(e.target.result);
		};
		reader.readAsDataURL(file);
	}

	function showImagePreview(src) {
		if (uploadPlaceholder) {
			uploadPlaceholder.style.display = 'none';
		}

		let preview = document.getElementById('image-preview');
		if (!preview) {
			preview = document.createElement('div');
			preview.id = 'image-preview';
			preview.className = 'image-preview';
			uploadArea.appendChild(preview);
		}

		preview.innerHTML = `
			<img src="${src}" alt="Ad preview">
			<button type="button" class="remove-image-btn" id="remove-image-btn">✕</button>
		`;
		preview.style.display = 'block';

		const removeBtn = document.getElementById('remove-image-btn');
		if (removeBtn) {
			removeBtn.addEventListener('click', function(e) {
				e.stopPropagation();
				removeImage();
			});
		}
	}

	function removeImage() {
		const preview = document.getElementById('image-preview');
		if (preview) {
			preview.style.display = 'none';
		}
		if (uploadPlaceholder) {
			uploadPlaceholder.style.display = 'flex';
		}
		if (imageInput) {
			imageInput.value = '';
		}
	}

	const removeImageBtn = document.getElementById('remove-image-btn');
	if (removeImageBtn) {
		removeImageBtn.addEventListener('click', function(e) {
			e.stopPropagation();
			removeImage();
		});
	}

	const copyBtns = document.querySelectorAll('.copy-shortcode');
	copyBtns.forEach(btn => {
		btn.addEventListener('click', function() {
			const shortcode = this.dataset.shortcode;
			navigator.clipboard.writeText(shortcode).then(() => {
				const original = this.textContent;
				this.textContent = '✓ Copied!';
				this.style.backgroundColor = '#4caf50';
				setTimeout(() => {
					this.textContent = original;
					this.style.backgroundColor = '';
				}, 2000);
			});
		});
	});

	const startDate = document.getElementById('ad_start_date');
	const endDate = document.getElementById('ad_end_date');

	if (startDate && endDate) {
		startDate.addEventListener('change', function() {
			endDate.min = this.value;
			if (endDate.value && endDate.value < this.value) {
				endDate.value = this.value;
			}
		});
	}
});

// Fix for drag-and-drop file upload - ensure file is assigned to input
document.addEventListener('DOMContentLoaded', function() {
	const imageInput = document.getElementById('ad_image_input');
	const uploadArea = document.getElementById('image-upload-area');

	if (uploadArea && imageInput) {
		// Override the drop event to properly assign files
		const newDropHandler = function(e) {
			e.preventDefault();
			e.stopPropagation();
			uploadArea.classList.remove('drag-over');

			const files = e.dataTransfer.files;
			if (files.length > 0 && files[0].type.match('image.*')) {
				// Assign the file to the input element
				const dataTransfer = new DataTransfer();
				dataTransfer.items.add(files[0]);
				imageInput.files = dataTransfer.files;

				// Trigger change event to show preview
				const event = new Event('change', { bubbles: true });
				imageInput.dispatchEvent(event);
			}
		};

		// Remove old listeners and add new one
		uploadArea.removeEventListener('drop', newDropHandler);
		uploadArea.addEventListener('drop', newDropHandler);
	}
});
</script>

<style>
.ad-create-page {
	background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
	min-height: 100vh;
	padding-bottom: 60px;
}

.page-header {
	background: #fff;
	padding: 30px 40px;
	border-radius: 16px;
	box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: 30px;
}

.page-title {
	font-size: 32px;
	font-weight: 700;
	margin: 0 0 8px 0;
	color: #1a1a1a;
}

.page-subtitle {
	margin: 0;
	color: #666;
	font-size: 16px;
}

.back-button {
	display: inline-flex;
	align-items: center;
	gap: 8px;
	padding: 12px 24px;
	background: var(--event-secondary);
	color: var(--event-text);
	text-decoration: none;
	border-radius: 8px;
	font-weight: 600;
	transition: var(--event-transition);
	border: 2px solid var(--event-border);
}

.back-button:hover {
	background: var(--event-primary-light);
	border-color: var(--event-primary);
	transform: translateY(-2px);
}

.success-notice {
	background: #d4edda;
	border: 2px solid #c3e6cb;
	color: #155724;
	padding: 16px 20px;
	border-radius: 8px;
	font-weight: 600;
}

.error-notice {
	background: #f8d7da;
	border: 2px solid #f5c6cb;
	color: #721c24;
	padding: 16px 20px;
	border-radius: 8px;
	font-weight: 600;
}

.ad-create-layout {
	display: grid;
	grid-template-columns: 1fr 400px;
	gap: 30px;
}

.ad-form-container {
	background: #fff;
	padding: 40px;
	border-radius: 16px;
	box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.ad-creation-form {
	display: flex;
	flex-direction: column;
	gap: 30px;
}

.form-section {
	display: flex;
	flex-direction: column;
	gap: 20px;
	padding-bottom: 30px;
	border-bottom: 2px solid var(--event-border);
}

.form-section:last-of-type {
	border-bottom: none;
	padding-bottom: 0;
}

.section-title {
	font-size: 20px;
	font-weight: 700;
	margin: 0;
	color: #1a1a1a;
}

.form-group {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.form-label {
	font-weight: 600;
	color: var(--event-text);
	font-size: 15px;
}

.required {
	color: var(--event-error);
}

.form-input,
.form-select {
	padding: 14px 16px;
	border: 2px solid var(--event-border);
	border-radius: 8px;
	font-size: 15px;
	font-family: inherit;
	transition: var(--event-transition);
}

.form-input:focus,
.form-select:focus {
	outline: none;
	border-color: var(--event-primary);
	box-shadow: 0 0 0 3px var(--event-primary-light);
}

.form-help {
	font-size: 13px;
	color: var(--event-text-light);
	margin: 0;
}

.form-row {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 20px;
}

.image-upload-area {
	min-height: 300px;
	border: 3px dashed var(--event-border);
	border-radius: 12px;
	display: flex;
	align-items: center;
	justify-content: center;
	cursor: pointer;
	transition: var(--event-transition);
	position: relative;
	background: var(--event-secondary);
}

.image-upload-area:hover {
	border-color: var(--event-primary);
	background: var(--event-primary-light);
}

.image-upload-area.drag-over {
	border-color: var(--event-primary);
	background: var(--event-primary-light);
}

.upload-placeholder {
	text-align: center;
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 10px;
	padding: 40px;
}

.upload-icon {
	font-size: 4rem;
	opacity: 0.5;
}

.upload-text {
	font-weight: 600;
	margin: 0;
	color: var(--event-text);
}

.upload-hint {
	font-size: 13px;
	color: var(--event-text-light);
	margin: 0;
}

.image-preview {
	width: 100%;
	height: 100%;
	position: relative;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 20px;
}

.image-preview img {
	max-width: 100%;
	max-height: 400px;
	object-fit: contain;
	border-radius: 8px;
}

.remove-image-btn {
	position: absolute;
	top: 30px;
	right: 30px;
	background: var(--event-error);
	color: #fff;
	border: none;
	border-radius: 50%;
	width: 36px;
	height: 36px;
	font-size: 18px;
	cursor: pointer;
	transition: var(--event-transition);
	display: flex;
	align-items: center;
	justify-content: center;
}

.remove-image-btn:hover {
	background: #c62828;
	transform: scale(1.1);
}

.form-actions {
	display: flex;
	gap: 16px;
	padding-top: 20px;
}

.submit-button {
	flex: 1;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 10px;
	padding: 16px 32px;
	background: linear-gradient(135deg, #503AA8 0%, #6B52C3 100%);
	color: #fff;
	border: none;
	border-radius: 8px;
	font-size: 16px;
	font-weight: 700;
	cursor: pointer;
	transition: var(--event-transition);
}

.submit-button:hover {
	background: linear-gradient(135deg, #6B52C3 0%, #503AA8 100%);
	transform: translateY(-2px);
	box-shadow: 0 6px 20px rgba(80, 58, 168, 0.4);
}

.button-icon {
	font-size: 20px;
}

.cancel-button {
	padding: 16px 32px;
	background: var(--event-secondary);
	color: var(--event-text);
	border: 2px solid var(--event-border);
	border-radius: 8px;
	font-weight: 600;
	text-decoration: none;
	transition: var(--event-transition);
	display: flex;
	align-items: center;
	justify-content: center;
}

.cancel-button:hover {
	background: var(--event-border);
}

.ad-info-sidebar {
	display: flex;
	flex-direction: column;
	gap: 20px;
}

.info-card {
	background: #fff;
	padding: 24px;
	border-radius: 12px;
	box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
}

.info-title {
	font-size: 18px;
	font-weight: 700;
	margin: 0 0 16px 0;
	color: #1a1a1a;
}

.info-list {
	list-style: none;
	padding: 0;
	margin: 0;
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.info-list li {
	font-size: 14px;
	color: var(--event-text);
	line-height: 1.6;
}

.status-info {
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.status-item {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 10px;
	background: var(--event-secondary);
	border-radius: 8px;
}

.status-label {
	font-size: 14px;
	font-weight: 600;
	color: var(--event-text);
}

.status-badge {
	padding: 4px 12px;
	border-radius: 12px;
	font-size: 12px;
	font-weight: 600;
	text-transform: capitalize;
}

.status-badge.status-approved {
	background: #e8f5e9;
	color: #2e7d32;
}

.status-badge.status-pending {
	background: #fff3e0;
	color: #ef6c00;
}

.status-badge.status-rejected {
	background: #ffebee;
	color: #c62828;
}

.status-badge.status-active {
	background: #e8f5e9;
	color: #2e7d32;
}

.status-badge.status-inactive {
	background: #f5f5f5;
	color: #666;
}

.status-value {
	font-weight: 700;
	color: #503AA8;
}

.shortcode-box {
	background: var(--event-secondary);
	padding: 16px;
	border-radius: 8px;
	display: flex;
	align-items: center;
	gap: 12px;
	margin-bottom: 12px;
}

.shortcode-text {
	flex: 1;
	font-family: monospace;
	font-size: 13px;
	color: #503AA8;
	background: #fff;
	padding: 8px 12px;
	border-radius: 4px;
	overflow-wrap: break-word;
}

.copy-shortcode {
	background: #503AA8;
	color: #fff;
	border: none;
	border-radius: 6px;
	padding: 8px 16px;
	font-size: 13px;
	font-weight: 600;
	cursor: pointer;
	transition: var(--event-transition);
	white-space: nowrap;
}

.copy-shortcode:hover {
	background: #6B52C3;
	transform: scale(1.05);
}

.shortcode-help {
	font-size: 13px;
	color: var(--event-text-light);
	margin: 0;
}

.help-card p {
	font-size: 14px;
	color: var(--event-text-light);
	margin: 0 0 16px 0;
	line-height: 1.6;
}

.help-button {
	display: block;
	width: 100%;
	padding: 12px 20px;
	background: var(--event-primary);
	color: var(--event-dark);
	text-decoration: none;
	text-align: center;
	border-radius: 8px;
	font-weight: 600;
	transition: var(--event-transition);
}

.help-button:hover {
	background: var(--event-primary-hover);
	transform: translateY(-2px);
}

@media (max-width: 1024px) {
	.ad-create-layout {
		grid-template-columns: 1fr;
	}
	
	.ad-info-sidebar {
		order: 2;
	}
}

@media (max-width: 768px) {
	.page-header {
		flex-direction: column;
		align-items: flex-start;
		padding: 24px;
	}
	
	.header-actions {
		width: 100%;
	}
	
	.back-button {
		width: 100%;
		justify-content: center;
	}
	
	.ad-form-container {
		padding: 24px;
	}
	
	.form-row {
		grid-template-columns: 1fr;
	}
	
	.form-actions {
		flex-direction: column;
	}
	
	.submit-button,
	.cancel-button {
		width: 100%;
	}
}

@media (max-width: 480px) {
	.page-title {
		font-size: 24px;
	}
	
	.page-subtitle {
		font-size: 14px;
	}
	
	.ad-form-container {
		padding: 20px;
	}
	
	.section-title {
		font-size: 18px;
	}
	
	.image-upload-area {
		min-height: 250px;
	}
}
</style>

<?php get_footer(); ?>

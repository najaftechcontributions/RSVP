<?php
/**
 * Simple Membership Plugin Integration
 * 
 * Integrates Simple Membership plugin with Event RSVP Platform
 * Handles role assignment, payment verification, and membership management
 * 
 * @package EventRSVPPlugin
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Simple Membership Integration Class
 */
class Event_RSVP_SWPM_Integration {
	
	private static $instance = null;
	
	/**
	 * Membership level to role mapping
	 */
	private $level_role_map = array(
		1 => 'subscriber',      // Free Attendee
		2 => 'event_host',      // Event Host
		3 => 'vendor',          // Vendor
		4 => 'pro',             // Pro (Both)
	);
	
	/**
	 * Role to plan slug mapping
	 */
	private $role_plan_map = array(
		'subscriber' => 'attendee',
		'event_host' => 'event_host',
		'vendor' => 'vendor',
		'pro' => 'pro',
	);
	
	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	private function __construct() {
		// Only initialize if Simple Membership is active
		if (!$this->is_swpm_active()) {
			add_action('admin_notices', array($this, 'swpm_not_active_notice'));
			return;
		}
		
		// Hook into Simple Membership events
		add_action('swpm_membership_changed', array($this, 'handle_membership_changed'), 10, 2);
		add_action('swpm_front_end_registration_complete', array($this, 'handle_registration_complete'), 10, 2);
		add_action('swpm_payment_ipn_processed', array($this, 'handle_payment_processed'), 10, 1);
		add_action('swpm_subscription_canceled', array($this, 'handle_subscription_canceled'), 10, 1);
		add_action('swpm_subscription_expired', array($this, 'handle_subscription_expired'), 10, 1);
		
		// Add custom redirects
		add_filter('swpm_registration_complete_redirect_url', array($this, 'custom_registration_redirect'), 10, 2);
		add_filter('swpm_after_login_redirect_url', array($this, 'custom_login_redirect'), 10, 2);
		
		// Add membership info to user profile
		add_action('show_user_profile', array($this, 'show_membership_badge'));
		add_action('edit_user_profile', array($this, 'show_membership_badge'));
		
		// Protect event creation and ad posting
		add_action('admin_init', array($this, 'check_permissions'));
		
		// Add custom CSS for Simple Membership forms
		add_action('wp_enqueue_scripts', array($this, 'enqueue_swpm_styles'));
		
		// Add admin menu for migration
		add_action('admin_menu', array($this, 'add_migration_menu'));
	}
	
	/**
	 * Check if Simple Membership plugin is active
	 */
	private function is_swpm_active() {
		return class_exists('SwpmMemberUtils');
	}
	
	/**
	 * Show admin notice if Simple Membership is not active
	 */
	public function swpm_not_active_notice() {
		?>
		<div class="notice notice-error">
			<p><strong>Event RSVP Platform:</strong> Simple Membership plugin is required but not active. Please install and activate the <a href="https://wordpress.org/plugins/simple-membership/" target="_blank">Simple Membership plugin</a>.</p>
		</div>
		<?php
	}
	
	/**
	 * Handle membership level change
	 * Assigns WordPress role based on membership level
	 */
	public function handle_membership_changed($member_id, $new_level_id) {
		if (!isset($this->level_role_map[$new_level_id])) {
			error_log('SWPM Integration: Unknown membership level: ' . $new_level_id);
			return;
		}
		
		$new_role = $this->level_role_map[$new_level_id];
		
		// Get user ID from member ID
		$user_id = SwpmMemberUtils::get_user_id_from_member_id($member_id);
		
		if (!$user_id) {
			error_log('SWPM Integration: Could not find user for member: ' . $member_id);
			return;
		}
		
		// Update WordPress role
		$user = new WP_User($user_id);
		$user->set_role($new_role);
		
		// Update user meta
		$plan_slug = $this->role_plan_map[$new_role];
		update_user_meta($user_id, 'event_rsvp_plan', $plan_slug);
		update_user_meta($user_id, 'event_rsvp_membership_level', $new_level_id);
		update_user_meta($user_id, 'event_rsvp_subscription_status', 'active');
		
		// Log the change
		error_log('SWPM Integration: User ' . $user_id . ' assigned role: ' . $new_role . ' (Level: ' . $new_level_id . ')');
		
		// Fire custom action for other integrations
		do_action('event_rsvp_membership_level_changed', $user_id, $new_level_id, $new_role);
	}
	
	/**
	 * Handle front-end registration completion
	 */
	public function handle_registration_complete($user_data, $member_id) {
		$level_id = isset($user_data['membership_level']) ? intval($user_data['membership_level']) : 1;
		$user_id = SwpmMemberUtils::get_user_id_from_member_id($member_id);
		
		if (!$user_id) {
			return;
		}
		
		// Assign role based on membership level
		$this->handle_membership_changed($member_id, $level_id);
		
		// Send custom welcome email
		$this->send_welcome_email($user_id, $level_id);
		
		// Log registration
		error_log('SWPM Integration: New registration - User: ' . $user_id . ', Level: ' . $level_id);
		
		// Fire custom action
		do_action('event_rsvp_registration_complete', $user_id, $level_id);
	}
	
	/**
	 * Handle payment processed (IPN)
	 */
	public function handle_payment_processed($ipn_data) {
		// Extract user info from IPN data
		if (!isset($ipn_data['member_id'])) {
			return;
		}
		
		$member_id = $ipn_data['member_id'];
		$user_id = SwpmMemberUtils::get_user_id_from_member_id($member_id);
		
		if (!$user_id) {
			return;
		}
		
		// Update payment meta
		update_user_meta($user_id, 'event_rsvp_last_payment_date', current_time('mysql'));
		update_user_meta($user_id, 'event_rsvp_subscription_status', 'active');
		
		// Get membership level
		$level_id = SwpmMemberUtils::get_membership_level_id_of_member($member_id);
		
		// Ensure correct role is assigned
		$this->handle_membership_changed($member_id, $level_id);
		
		// Log payment
		error_log('SWPM Integration: Payment processed for user: ' . $user_id);
		
		// Fire custom action
		do_action('event_rsvp_payment_processed', $user_id, $ipn_data);
	}
	
	/**
	 * Handle subscription cancellation
	 */
	public function handle_subscription_canceled($member_id) {
		$user_id = SwpmMemberUtils::get_user_id_from_member_id($member_id);
		
		if (!$user_id) {
			return;
		}
		
		// Downgrade to free subscriber role
		$user = new WP_User($user_id);
		$user->set_role('subscriber');
		
		// Update user meta
		update_user_meta($user_id, 'event_rsvp_plan', 'attendee');
		update_user_meta($user_id, 'event_rsvp_membership_level', 1);
		update_user_meta($user_id, 'event_rsvp_subscription_status', 'cancelled');
		
		// Send cancellation email
		$this->send_cancellation_email($user_id);
		
		// Log cancellation
		error_log('SWPM Integration: Subscription cancelled for user: ' . $user_id);
		
		// Fire custom action
		do_action('event_rsvp_subscription_cancelled', $user_id);
	}
	
	/**
	 * Handle subscription expiration
	 */
	public function handle_subscription_expired($member_id) {
		$user_id = SwpmMemberUtils::get_user_id_from_member_id($member_id);
		
		if (!$user_id) {
			return;
		}
		
		// Downgrade to free subscriber role
		$user = new WP_User($user_id);
		$user->set_role('subscriber');
		
		// Update user meta
		update_user_meta($user_id, 'event_rsvp_plan', 'attendee');
		update_user_meta($user_id, 'event_rsvp_membership_level', 1);
		update_user_meta($user_id, 'event_rsvp_subscription_status', 'expired');
		
		// Send expiration email
		$this->send_expiration_email($user_id);
		
		// Log expiration
		error_log('SWPM Integration: Subscription expired for user: ' . $user_id);
		
		// Fire custom action
		do_action('event_rsvp_subscription_expired', $user_id);
	}
	
	/**
	 * Custom redirect after registration
	 */
	public function custom_registration_redirect($redirect_url, $user_data) {
		$level_id = isset($user_data['membership_level']) ? intval($user_data['membership_level']) : 1;
		
		switch ($level_id) {
			case 1: // Free Attendee
				return home_url('/browse-events/?welcome=1');
				
			case 2: // Event Host
				return home_url('/host-dashboard/?welcome=1');
				
			case 3: // Vendor
				return home_url('/vendor-dashboard/?welcome=1');
				
			case 4: // Pro
				return home_url('/host-dashboard/?welcome=1&pro=1');
				
			default:
				return $redirect_url;
		}
	}
	
	/**
	 * Custom redirect after login
	 */
	public function custom_login_redirect($redirect_url, $user_id) {
		$user = get_user_by('id', $user_id);
		
		if (!$user) {
			return $redirect_url;
		}
		
		$roles = $user->roles;
		
		if (in_array('event_host', $roles) || in_array('pro', $roles)) {
			return home_url('/host-dashboard/');
		} elseif (in_array('vendor', $roles)) {
			return home_url('/vendor-dashboard/');
		} else {
			return home_url('/browse-events/');
		}
	}
	
	/**
	 * Show membership badge on user profile
	 */
	public function show_membership_badge($user) {
		if (!function_exists('SwpmMemberUtils::get_member_id_from_user_id')) {
			return;
		}
		
		$member_id = SwpmMemberUtils::get_member_id_from_user_id($user->ID);
		
		if (!$member_id) {
			echo '<h3>Membership Status</h3>';
			echo '<p>Not a Simple Membership member</p>';
			return;
		}
		
		$level_id = SwpmMemberUtils::get_membership_level_id_of_member($member_id);
		$level_info = SwpmUtils::get_membership_level_rowdata($level_id);
		$account_state = SwpmMemberUtils::get_account_state_by_user_id($user->ID);
		
		echo '<h3>Membership Status</h3>';
		echo '<table class="form-table">';
		echo '<tr><th>Membership Level:</th><td><strong>' . esc_html($level_info->alias) . '</strong></td></tr>';
		echo '<tr><th>Account State:</th><td>' . esc_html($account_state) . '</td></tr>';
		echo '<tr><th>WordPress Role:</th><td>' . esc_html(implode(', ', $user->roles)) . '</td></tr>';
		echo '</table>';
	}
	
	/**
	 * Check permissions for event creation and ad posting
	 */
	public function check_permissions() {
		if (!is_admin()) {
			return;
		}
		
		global $pagenow;
		$user_id = get_current_user_id();
		
		if (!$user_id) {
			return;
		}
		
		// Check event creation
		if ($pagenow === 'post-new.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'event') {
			if (!$this->user_can_create_events($user_id)) {
				wp_die(
					'<h1>Upgrade Required</h1>' .
					'<p>You need an Event Host or Pro membership to create events.</p>' .
					'<p><a href="' . home_url('/pricing/') . '" class="button button-primary">View Pricing Plans</a></p>',
					'Upgrade Required'
				);
			}
		}
		
		// Check ad posting
		if ($pagenow === 'post-new.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'vendor_ad') {
			if (!$this->user_can_post_ads($user_id)) {
				wp_die(
					'<h1>Upgrade Required</h1>' .
					'<p>You need a Vendor or Pro membership to post advertisements.</p>' .
					'<p><a href="' . home_url('/pricing/') . '" class="button button-primary">View Pricing Plans</a></p>',
					'Upgrade Required'
				);
			}
		}
	}
	
	/**
	 * Check if user can create events
	 */
	public function user_can_create_events($user_id = 0) {
		if (!$user_id) {
			$user_id = get_current_user_id();
		}
		
		if (!$user_id) {
			return false;
		}
		
		$user = get_user_by('id', $user_id);
		
		if (!$user) {
			return false;
		}
		
		// Admins can always create
		if (in_array('administrator', $user->roles)) {
			return true;
		}
		
		// Check for event_host or pro role
		return in_array('event_host', $user->roles) || in_array('pro', $user->roles);
	}
	
	/**
	 * Check if user can post ads
	 */
	public function user_can_post_ads($user_id = 0) {
		if (!$user_id) {
			$user_id = get_current_user_id();
		}
		
		if (!$user_id) {
			return false;
		}
		
		$user = get_user_by('id', $user_id);
		
		if (!$user) {
			return false;
		}
		
		// Admins can always post
		if (in_array('administrator', $user->roles)) {
			return true;
		}
		
		// Check for vendor or pro role
		return in_array('vendor', $user->roles) || in_array('pro', $user->roles);
	}
	
	/**
	 * Send welcome email
	 */
	private function send_welcome_email($user_id, $level_id) {
		$user = get_user_by('id', $user_id);
		
		if (!$user) {
			return;
		}
		
		$level_names = array(
			1 => 'Free Attendee',
			2 => 'Event Host',
			3 => 'Vendor',
			4 => 'Pro (Host + Vendor)',
		);
		
		$level_name = isset($level_names[$level_id]) ? $level_names[$level_id] : 'Member';
		
		$subject = 'Welcome to ' . get_bloginfo('name') . '!';
		
		$message = "Hi {$user->first_name},\n\n";
		$message .= "Welcome to " . get_bloginfo('name') . "!\n\n";
		$message .= "Your account has been successfully created with the {$level_name} plan.\n\n";
		$message .= "Account Details:\n";
		$message .= "Username: {$user->user_login}\n";
		$message .= "Email: {$user->user_email}\n";
		$message .= "Membership Level: {$level_name}\n\n";
		
		if ($level_id === 1) {
			$message .= "You can now browse and RSVP to events at: " . home_url('/browse-events/') . "\n\n";
		} elseif ($level_id === 2) {
			$message .= "You can now create and manage events at: " . home_url('/host-dashboard/') . "\n\n";
		} elseif ($level_id === 3) {
			$message .= "You can now post advertisements at: " . home_url('/vendor-dashboard/') . "\n\n";
		} else {
			$message .= "You now have full access to create events and post ads!\n";
			$message .= "Dashboard: " . home_url('/host-dashboard/') . "\n\n";
		}
		
		$message .= "Need help getting started? Visit our help center or contact support.\n\n";
		$message .= "Thank you for choosing " . get_bloginfo('name') . "!\n\n";
		$message .= "Best regards,\n";
		$message .= "The " . get_bloginfo('name') . " Team";
		
		wp_mail($user->user_email, $subject, $message);
	}
	
	/**
	 * Send cancellation email
	 */
	private function send_cancellation_email($user_id) {
		$user = get_user_by('id', $user_id);
		
		if (!$user) {
			return;
		}
		
		$subject = 'Your Subscription Has Been Cancelled';
		
		$message = "Hi {$user->first_name},\n\n";
		$message .= "Your subscription has been cancelled and you've been downgraded to a Free Attendee account.\n\n";
		$message .= "You can still:\n";
		$message .= "- Browse and RSVP to events\n";
		$message .= "- Receive QR codes for check-in\n";
		$message .= "- Manage your profile\n\n";
		$message .= "However, you will no longer be able to:\n";
		$message .= "- Create new events\n";
		$message .= "- Post advertisements\n\n";
		$message .= "Want to reactivate your subscription? Visit: " . home_url('/pricing/') . "\n\n";
		$message .= "Thank you for being a member!\n\n";
		$message .= "Best regards,\n";
		$message .= "The " . get_bloginfo('name') . " Team";
		
		wp_mail($user->user_email, $subject, $message);
	}
	
	/**
	 * Send expiration email
	 */
	private function send_expiration_email($user_id) {
		$user = get_user_by('id', $user_id);
		
		if (!$user) {
			return;
		}
		
		$subject = 'Your Subscription Has Expired';
		
		$message = "Hi {$user->first_name},\n\n";
		$message .= "Your subscription has expired and you've been downgraded to a Free Attendee account.\n\n";
		$message .= "To continue enjoying premium features, please renew your subscription at: " . home_url('/pricing/') . "\n\n";
		$message .= "We'd love to have you back!\n\n";
		$message .= "Best regards,\n";
		$message .= "The " . get_bloginfo('name') . " Team";
		
		wp_mail($user->user_email, $subject, $message);
	}
	
	/**
	 * Enqueue custom styles for Simple Membership forms
	 */
	public function enqueue_swpm_styles() {
		if (is_page_template('page-signup-swpm.php') || is_page_template('page-login.php')) {
			wp_add_inline_style('event-rsvp-main', $this->get_swpm_custom_css());
		}
	}
	
	/**
	 * Get custom CSS for Simple Membership forms
	 */
	private function get_swpm_custom_css() {
		return "
		.swpm-registration-form,
		.swpm-login-form {
			max-width: 100%;
		}
		
		.swpm-registration-form .swpm-form-field,
		.swpm-login-form .swpm-form-field {
			margin-bottom: 20px;
		}
		
		.swpm-registration-form label,
		.swpm-login-form label {
			display: block;
			margin-bottom: 8px;
			font-weight: 600;
			color: #374151;
		}
		
		.swpm-registration-form input[type='text'],
		.swpm-registration-form input[type='email'],
		.swpm-registration-form input[type='password'],
		.swpm-registration-form select,
		.swpm-login-form input[type='text'],
		.swpm-login-form input[type='password'] {
			width: 100%;
			padding: 12px 16px;
			border: 2px solid #e5e7eb;
			border-radius: 8px;
			font-size: 1rem;
			transition: all 0.3s ease;
		}
		
		.swpm-registration-form input:focus,
		.swpm-login-form input:focus {
			outline: none;
			border-color: #667eea;
			box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
		}
		
		.swpm-registration-form .swpm-submit,
		.swpm-login-form .swpm-submit {
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			color: white;
			padding: 14px 32px;
			border: none;
			border-radius: 8px;
			font-size: 1.1rem;
			font-weight: 600;
			cursor: pointer;
			width: 100%;
			transition: all 0.3s ease;
		}
		
		.swpm-registration-form .swpm-submit:hover,
		.swpm-login-form .swpm-submit:hover {
			transform: translateY(-2px);
			box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
		}
		
		.swpm-error,
		.swpm-validation-error {
			background-color: #fee2e2;
			border: 2px solid #ef4444;
			color: #991b1b;
			padding: 12px 16px;
			border-radius: 8px;
			margin-bottom: 20px;
		}
		
		.swpm-success {
			background-color: #d1fae5;
			border: 2px solid #10b981;
			color: #065f46;
			padding: 12px 16px;
			border-radius: 8px;
			margin-bottom: 20px;
		}
		";
	}
	
	/**
	 * Add migration menu
	 */
	public function add_migration_menu() {
		add_submenu_page(
			'options-general.php',
			'SWPM Migration',
			'SWPM Migration',
			'manage_options',
			'event-rsvp-swpm-migration',
			array($this, 'render_migration_page')
		);
	}
	
	/**
	 * Render migration page
	 */
	public function render_migration_page() {
		if (!current_user_can('manage_options')) {
			return;
		}
		
		?>
		<div class="wrap">
			<h1>Simple Membership Migration</h1>
			<p>Migrate existing users from custom Stripe integration to Simple Membership.</p>
			
			<div class="notice notice-warning">
				<p><strong>Warning:</strong> This is a one-time migration. Backup your database before proceeding!</p>
			</div>
			
			<h2>Migration Status</h2>
			<?php
			$users_with_stripe = get_users(array(
				'meta_key' => 'event_rsvp_stripe_customer_id',
				'meta_compare' => 'EXISTS',
				'count_total' => true,
			));
			?>
			<p>Users with custom Stripe subscriptions: <strong><?php echo count($users_with_stripe); ?></strong></p>
			
			<p>For migration instructions, see the <code>SIMPLE-MEMBERSHIP-INTEGRATION-GUIDE.md</code> file.</p>
		</div>
		<?php
	}
	
	/**
	 * Get user's current membership level
	 */
	public static function get_user_membership_level($user_id = 0) {
		if (!$user_id) {
			$user_id = get_current_user_id();
		}
		
		if (!$user_id || !function_exists('SwpmMemberUtils::get_member_id_from_user_id')) {
			return 0;
		}
		
		$member_id = SwpmMemberUtils::get_member_id_from_user_id($user_id);
		
		if (!$member_id) {
			return 0;
		}
		
		return SwpmMemberUtils::get_membership_level_id_of_member($member_id);
	}
	
	/**
	 * Get user's plan slug
	 */
	public static function get_user_plan($user_id = 0) {
		if (!$user_id) {
			$user_id = get_current_user_id();
		}
		
		return get_user_meta($user_id, 'event_rsvp_plan', true);
	}
}

// Initialize the integration
function event_rsvp_swpm_init() {
	return Event_RSVP_SWPM_Integration::get_instance();
}
add_action('plugins_loaded', 'event_rsvp_swpm_init');

<?php
/**
 * Simple Stripe Payment Integration
 * 
 * Uses Stripe Payment Links created in Stripe Dashboard
 * Redirects to Stripe, then back with a token to assign roles
 * If payment fails, creates account as attendee
 * 
 * @package EventRSVPPlugin
 */

if (!defined('ABSPATH')) {
	exit;
}

class Event_RSVP_Simple_Stripe {
	
	private static $instance = null;
	
	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	private function __construct() {
		add_action('admin_menu', array($this, 'add_settings_page'));
		add_action('admin_init', array($this, 'register_settings'));
		$this->maybe_create_tokens_table();
	}
	
	/**
	 * Get Stripe Payment Links from settings
	 */
	public function get_payment_links() {
		return array(
			'pay_as_you_go' => get_option('event_rsvp_stripe_link_pay_as_you_go', ''),
			'event_planner' => get_option('event_rsvp_stripe_link_event_planner', ''),
			'event_host' => get_option('event_rsvp_stripe_link_event_host', ''), // Legacy
			'vendor' => get_option('event_rsvp_stripe_link_vendor', ''), // Commented out
			'pro' => get_option('event_rsvp_stripe_link_pro', ''), // Commented out
		);
	}
	
	/**
	 * Generate payment URL with token
	 */
	public function get_payment_url($plan_slug, $user_data) {
		$links = $this->get_payment_links();
		
		if (empty($links[$plan_slug])) {
			return false;
		}
		
		// Create account immediately as attendee (will be upgraded after payment)
		$user_id = $this->create_attendee_account($user_data);
		
		if (is_wp_error($user_id)) {
			return $user_id;
		}
		
		// Generate secure token and save it
		$token = wp_generate_password(32, false);
		$this->save_payment_token($user_id, $token, $plan_slug);
		
		// Store token in user meta for easy lookup when user returns from Stripe
		update_user_meta($user_id, 'event_rsvp_pending_token', $token);
		update_user_meta($user_id, 'event_rsvp_pending_plan', $plan_slug);
		
		// Return the Stripe payment link
		// Success and Cancel URLs must be configured in Stripe Dashboard
		$payment_link = $links[$plan_slug];
		
		// Add customer email prefill if supported
		$payment_url = add_query_arg(array(
			'prefilled_email' => urlencode($user_data['email'])
		), $payment_link);
		
		return $payment_url;
	}
	
	/**
	 * Create account as attendee (subscriber)
	 */
	private function create_attendee_account($user_data) {
		// Check if username exists
		if (username_exists($user_data['username'])) {
			return new WP_Error('username_exists', 'Username already exists.');
		}
		
		// Check if email exists
		if (email_exists($user_data['email'])) {
			return new WP_Error('email_exists', 'Email already exists.');
		}
		
		// Create user
		$user_id = wp_create_user(
			$user_data['username'],
			$user_data['password'],
			$user_data['email']
		);
		
		if (is_wp_error($user_id)) {
			return $user_id;
		}
		
		// Update user details
		wp_update_user(array(
			'ID' => $user_id,
			'first_name' => $user_data['first_name'],
			'last_name' => $user_data['last_name'],
			'display_name' => $user_data['first_name'] . ' ' . $user_data['last_name'],
			'role' => 'subscriber' // Start as attendee
		));
		
		// Store initial plan as attendee
		update_user_meta($user_id, 'event_rsvp_plan', 'attendee');
		update_user_meta($user_id, 'event_rsvp_payment_pending', '1');
		
		// Auto-login the user
		$user = get_user_by('id', $user_id);
		if ($user) {
			wp_set_current_user($user_id, $user->user_login);
			wp_set_auth_cookie($user_id, true);
			do_action('wp_login', $user->user_login, $user);
		}
		
		// Send welcome email for free account
		$this->send_account_created_email($user_id);
		
		return $user_id;
	}
	
	/**
	 * Save payment token
	 */
	private function save_payment_token($user_id, $token, $plan_slug) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'event_rsvp_payment_tokens';
		
		$wpdb->insert(
			$table_name,
			array(
				'user_id' => $user_id,
				'token' => wp_hash_password($token),
				'plan_slug' => $plan_slug,
				'status' => 'pending',
				'created_at' => current_time('mysql')
			),
			array('%d', '%s', '%s', '%s', '%s')
		);
	}
	
	/**
	 * Get user's current plan
	 */
	public static function get_user_plan($user_id = null) {
		if (!$user_id) {
			$user_id = get_current_user_id();
		}

		if (!$user_id) {
			return '';
		}

		$plan = get_user_meta($user_id, 'event_rsvp_plan', true);

		// If no plan meta, check user role
		if (empty($plan)) {
			$user = get_user_by('id', $user_id);
			if ($user) {
				$roles = $user->roles;
				if (in_array('pro', $roles)) {
					return 'pro';
				} elseif (in_array('event_host', $roles)) {
					return 'event_host';
				} elseif (in_array('vendor', $roles)) {
					return 'vendor';
				} else {
					return 'attendee';
				}
			}
			return 'attendee';
		}

		return $plan;
	}

	/**
	 * Verify payment and upgrade account (with token)
	 */
	public function verify_payment_and_upgrade($token, $plan_slug) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'event_rsvp_payment_tokens';
		
		error_log('=== TOKEN VERIFICATION START ===');
		error_log('Token: ' . substr($token, 0, 10) . '...');
		error_log('Plan: ' . $plan_slug);
		
		// Get all pending tokens for this plan
		$tokens = $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM $table_name WHERE plan_slug = %s AND status = 'pending' ORDER BY created_at DESC LIMIT 50",
			$plan_slug
		));
		
		error_log('Found ' . count($tokens) . ' pending tokens for plan ' . $plan_slug);
		
		foreach ($tokens as $token_row) {
			error_log('Checking token ID: ' . $token_row->id . ' for user: ' . $token_row->user_id);
			
			if (wp_check_password($token, $token_row->token)) {
				$user_id = $token_row->user_id;
				error_log('TOKEN MATCH! Upgrading user ' . $user_id . ' to ' . $plan_slug);
				
				// Upgrade user role
				$role_map = array(
					'pay_as_you_go' => 'event_host',
					'event_planner' => 'event_host',
					'event_host' => 'event_host', // Legacy
					'vendor' => 'vendor',
					'pro' => 'pro'
				);
				
				$new_role = isset($role_map[$plan_slug]) ? $role_map[$plan_slug] : 'subscriber';
				
				wp_update_user(array(
					'ID' => $user_id,
					'role' => $new_role
				));
				
				error_log('User role updated to: ' . $new_role);
				
				// Update user meta
				update_user_meta($user_id, 'event_rsvp_plan', $plan_slug);
				update_user_meta($user_id, 'event_rsvp_subscription_status', 'active');
				delete_user_meta($user_id, 'event_rsvp_payment_pending');
				delete_user_meta($user_id, 'event_rsvp_pending_token');
				delete_user_meta($user_id, 'event_rsvp_pending_plan');
				update_user_meta($user_id, 'event_rsvp_payment_date', current_time('mysql'));
				
				error_log('User meta updated');
				
				// Mark token as used
				$wpdb->update(
					$table_name,
					array('status' => 'completed', 'completed_at' => current_time('mysql')),
					array('id' => $token_row->id),
					array('%s', '%s'),
					array('%d')
				);
				
				error_log('Token marked as completed');
				
				// Send upgrade confirmation email
				$this->send_upgrade_email($user_id, $plan_slug);
				
				error_log('=== TOKEN VERIFICATION SUCCESS ===');
				
				return array(
					'success' => true,
					'user_id' => $user_id,
					'plan' => $plan_slug
				);
			} else {
				error_log('Token does not match for token ID: ' . $token_row->id);
			}
		}
		
		error_log('=== TOKEN VERIFICATION FAILED - No matching token ===');
		return array('success' => false, 'message' => 'Invalid or expired token');
	}
	
	/**
	 * Verify payment for logged-in user (alternative method when no token in URL)
	 */
	public function verify_payment_for_user($user_id) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'event_rsvp_payment_tokens';
		
		error_log('=== VERIFYING PAYMENT FOR USER ' . $user_id . ' ===');
		
		// Get pending token for this user
		$token_row = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM $table_name WHERE user_id = %d AND status = 'pending' ORDER BY created_at DESC LIMIT 1",
			$user_id
		));
		
		if (!$token_row) {
			error_log('No pending token found for user ' . $user_id);
			return array('success' => false, 'message' => 'No pending payment found');
		}
		
		$plan_slug = $token_row->plan_slug;
		error_log('Found pending token for plan: ' . $plan_slug);
		
		// Upgrade user role
		$role_map = array(
			'pay_as_you_go' => 'event_host',
			'event_planner' => 'event_host',
			'event_host' => 'event_host', // Legacy
			'vendor' => 'vendor',
			'pro' => 'pro'
		);
		
		$new_role = isset($role_map[$plan_slug]) ? $role_map[$plan_slug] : 'subscriber';
		
		wp_update_user(array(
			'ID' => $user_id,
			'role' => $new_role
		));
		
		error_log('User role updated to: ' . $new_role);
		
		// Update user meta
		update_user_meta($user_id, 'event_rsvp_plan', $plan_slug);
		update_user_meta($user_id, 'event_rsvp_subscription_status', 'active');
		delete_user_meta($user_id, 'event_rsvp_payment_pending');
		delete_user_meta($user_id, 'event_rsvp_pending_token');
		delete_user_meta($user_id, 'event_rsvp_pending_plan');
		update_user_meta($user_id, 'event_rsvp_payment_date', current_time('mysql'));
		
		// Mark token as used
		$wpdb->update(
			$table_name,
			array('status' => 'completed', 'completed_at' => current_time('mysql')),
			array('id' => $token_row->id),
			array('%s', '%s'),
			array('%d')
		);
		
		error_log('Token marked as completed');
		
		// Send upgrade confirmation email
		$this->send_upgrade_email($user_id, $plan_slug);
		
		error_log('=== USER VERIFICATION SUCCESS ===');
		
		return array(
			'success' => true,
			'user_id' => $user_id,
			'plan' => $plan_slug
		);
	}
	
	/**
	 * Create tokens table
	 */
	private function maybe_create_tokens_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'event_rsvp_payment_tokens';
		$charset_collate = $wpdb->get_charset_collate();
		
		if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
			$sql = "CREATE TABLE $table_name (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				user_id bigint(20) NOT NULL,
				token varchar(255) NOT NULL,
				plan_slug varchar(20) NOT NULL,
				status varchar(20) DEFAULT 'pending',
				created_at datetime NOT NULL,
				completed_at datetime DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY user_id (user_id),
				KEY status (status)
			) $charset_collate;";
			
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		}
	}
	
	/**
	 * Send account created email
	 */
	private function send_account_created_email($user_id) {
		$user = get_user_by('id', $user_id);
		if (!$user) return;
		
		$subject = 'Account Created - ' . get_bloginfo('name');
		$message = "Hi {$user->first_name},\n\n";
		$message .= "Your account has been created successfully!\n\n";
		$message .= "Username: {$user->user_login}\n";
		$message .= "Email: {$user->user_email}\n\n";
		$message .= "You can log in at: " . home_url('/login/') . "\n\n";
		$message .= "Your account is currently set up as a free Attendee account.\n";
		$message .= "Complete your payment to unlock premium features.\n\n";
		$message .= "Best regards,\n";
		$message .= get_bloginfo('name');
		
		wp_mail($user->user_email, $subject, $message);
	}
	
	/**
	 * Send upgrade confirmation email
	 */
	private function send_upgrade_email($user_id, $plan_slug) {
		$user = get_user_by('id', $user_id);
		if (!$user) return;
		
		$plan_names = array(
			'pay_as_you_go' => 'Pay As You Go (1 Event)',
			'event_planner' => 'Event Planner (5 Events)',
			'event_host' => 'Event Host (Legacy)',
			'vendor' => 'Vendor',
			'pro' => 'Pro (Host + Vendor)'
		);
		
		$plan_name = isset($plan_names[$plan_slug]) ? $plan_names[$plan_slug] : $plan_slug;
		
		$subject = 'Payment Successful - Welcome to ' . $plan_name . '!';
		$message = "Hi {$user->first_name},\n\n";
		$message .= "Great news! Your payment was successful and your account has been upgraded to {$plan_name}.\n\n";
		$message .= "You now have full access to:\n";
		
		if ($plan_slug === 'pay_as_you_go') {
			$message .= "- Create up to 1 event\n";
			$message .= "- Access to event analytics\n";
		} elseif ($plan_slug === 'event_planner') {
			$message .= "- Create up to 5 events\n";
			$message .= "- Access to event analytics\n";
		} elseif ($plan_slug === 'event_host' || $plan_slug === 'pro') {
			$message .= "- Create and manage unlimited events\n";
			$message .= "- Access to event analytics\n";
		}
		if ($plan_slug === 'vendor' || $plan_slug === 'pro') {
			$message .= "- Post vendor advertisements\n";
			$message .= "- Track ad performance\n";
		}
		
		$message .= "\nGet started: " . home_url('/my-account/') . "\n\n";
		$message .= "Thank you for upgrading!\n\n";
		$message .= "Best regards,\n";
		$message .= get_bloginfo('name');
		
		wp_mail($user->user_email, $subject, $message);
	}
	
	/**
	 * Add settings page
	 */
	public function add_settings_page() {
		add_submenu_page(
			'options-general.php',
			'Stripe Payment Links',
			'Stripe Payments',
			'manage_options',
			'event-rsvp-stripe-links',
			array($this, 'render_settings_page')
		);
	}
	
	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting('event_rsvp_stripe_links', 'event_rsvp_stripe_link_pay_as_you_go');
		register_setting('event_rsvp_stripe_links', 'event_rsvp_stripe_link_event_planner');
		// Legacy and future plans
		register_setting('event_rsvp_stripe_links', 'event_rsvp_stripe_link_event_host');
		register_setting('event_rsvp_stripe_links', 'event_rsvp_stripe_link_vendor');
		register_setting('event_rsvp_stripe_links', 'event_rsvp_stripe_link_pro');
	}
	
	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		?>
		<div class="wrap">
			<h1>Stripe Payment Links Configuration</h1>
			<p>Enter the Stripe Payment Links created in your Stripe Dashboard.</p>
			
			<div class="notice notice-info">
				<p><strong>How to create Payment Links in Stripe:</strong></p>
				<ol>
					<li>Log in to your <a href="https://dashboard.stripe.com/" target="_blank">Stripe Dashboard</a></li>
					<li>Go to <strong>Products</strong> → <strong>Payment Links</strong></li>
					<li>Click <strong>New</strong> to create a payment link for each plan</li>
					<li>Set up your product (name, price, recurring)</li>
					<li>Configure success URL to: <code><?php echo esc_url(home_url('/signup-success/?payment_success=1')); ?></code></li>
					<li>Configure cancel URL to: <code><?php echo esc_url(home_url('/payment-cancelled/')); ?></code></li>
					<li>Copy the payment link URL and paste it below</li>
				</ol>
				<p><strong>Important:</strong> The success and cancel URLs MUST be configured in your Stripe Payment Link settings.</p>
			</div>
			
			<form method="post" action="options.php">
				<?php settings_fields('event_rsvp_stripe_links'); ?>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="event_rsvp_stripe_link_pay_as_you_go">Pay As You Go Plan Link</label>
						</th>
						<td>
							<input type="url"
								   id="event_rsvp_stripe_link_pay_as_you_go"
								   name="event_rsvp_stripe_link_pay_as_you_go"
								   value="<?php echo esc_attr(get_option('event_rsvp_stripe_link_pay_as_you_go')); ?>"
								   class="regular-text"
								   placeholder="https://buy.stripe.com/...">
							<p class="description">Stripe payment link for Pay As You Go plan - $29.99/month - 1 event limit - event_host role</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="event_rsvp_stripe_link_event_planner">Event Planner Plan Link</label>
						</th>
						<td>
							<input type="url"
								   id="event_rsvp_stripe_link_event_planner"
								   name="event_rsvp_stripe_link_event_planner"
								   value="<?php echo esc_attr(get_option('event_rsvp_stripe_link_event_planner')); ?>"
								   class="regular-text"
								   placeholder="https://buy.stripe.com/...">
							<p class="description">Stripe payment link for Event Planner plan - $119.99/month - 5 event limit - event_host role</p>
						</td>
					</tr>
					<tr style="opacity: 0.5;">
						<th scope="row">
							<label for="event_rsvp_stripe_link_event_host">Event Host Plan Link (Legacy)</label>
						</th>
						<td>
							<input type="url"
								   id="event_rsvp_stripe_link_event_host"
								   name="event_rsvp_stripe_link_event_host"
								   value="<?php echo esc_attr(get_option('event_rsvp_stripe_link_event_host')); ?>"
								   class="regular-text"
								   placeholder="https://buy.stripe.com/...">
							<p class="description">Legacy plan - Keep for existing subscribers</p>
						</td>
					</tr>
					<!--
					<tr>
						<th scope="row">
							<label for="event_rsvp_stripe_link_vendor">Vendor Plan Link</label>
						</th>
						<td>
							<input type="url"
								   id="event_rsvp_stripe_link_vendor"
								   name="event_rsvp_stripe_link_vendor"
								   value="<?php echo esc_attr(get_option('event_rsvp_stripe_link_vendor')); ?>"
								   class="regular-text"
								   placeholder="https://buy.stripe.com/...">
							<p class="description">Stripe payment link for Vendor plan ($29/month)</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="event_rsvp_stripe_link_pro">Pro Plan Link</label>
						</th>
						<td>
							<input type="url"
								   id="event_rsvp_stripe_link_pro"
								   name="event_rsvp_stripe_link_pro"
								   value="<?php echo esc_attr(get_option('event_rsvp_stripe_link_pro')); ?>"
								   class="regular-text"
								   placeholder="https://buy.stripe.com/...">
							<p class="description">Stripe payment link for Pro plan ($39/month)</p>
						</td>
					</tr>
					-->
				</table>
				<?php submit_button(); ?>
			</form>
			
			<hr>
			
			<h2>Success URL for Stripe Dashboard</h2>
			<p>Use this EXACT URL as the success redirect in your Stripe Payment Link settings:</p>
			<code style="display: block; background: #f5f5f5; padding: 10px; margin: 10px 0;"><?php echo esc_url(home_url('/signup-success/?payment_success=1')); ?></code>
			
			<hr>
			
			<h2>Cancel URL for Stripe Dashboard</h2>
			<p>Use this EXACT URL as the cancel redirect in your Stripe Payment Link settings:</p>
			<code style="display: block; background: #f5f5f5; padding: 10px; margin: 10px 0;"><?php echo esc_url(home_url('/payment-cancelled/')); ?></code>
		</div>
		<?php
	}
	
}

// Initialize immediately (this is loaded from theme, not plugin)
Event_RSVP_Simple_Stripe::get_instance();

<?php
/**
 * Stripe Payment Integration for Event RSVP Platform
 * 
 * Handles Stripe payment links, webhooks, and user account creation after payment.
 * Payment-first flow: User pays -> Webhook creates account -> Email sent with credentials
 * 
 * @package EventRSVPPlugin
 */

if (!defined('ABSPATH')) {
	exit;
}

class Event_RSVP_Stripe_Integration {
	
	private static $instance = null;
	private $stripe_secret_key;
	private $stripe_publishable_key;
	private $webhook_secret;
	
	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	private function __construct() {
		$this->stripe_secret_key = get_option('event_rsvp_stripe_secret_key', '');
		$this->stripe_publishable_key = get_option('event_rsvp_stripe_publishable_key', '');
		$this->webhook_secret = get_option('event_rsvp_stripe_webhook_secret', '');
		
		add_action('rest_api_init', array($this, 'register_webhook_endpoint'));
		add_action('admin_menu', array($this, 'add_settings_page'));
		add_action('admin_init', array($this, 'register_settings'));
	}
	
	/**
	 * Register REST API endpoint for Stripe webhooks
	 */
	public function register_webhook_endpoint() {
		register_rest_route('event-rsvp/v1', '/stripe-webhook', array(
			'methods' => 'POST',
			'callback' => array($this, 'handle_webhook'),
			'permission_callback' => '__return_true'
		));
	}
	
	/**
	 * Get pricing plans configuration
	 */
	private function get_pricing_plans() {
		return array(
			'event_host' => array(
				'name' => 'Event Host Subscription',
				'price' => 1900, // In cents
				'currency' => 'usd',
				'interval' => 'month',
				'description' => 'Create and manage unlimited events',
				'role' => 'event_host'
			),
			'vendor' => array(
				'name' => 'Vendor Subscription',
				'price' => 2900,
				'currency' => 'usd',
				'interval' => 'month',
				'description' => 'Advertise your business or services',
				'role' => 'vendor'
			),
			'pro' => array(
				'name' => 'Pro Subscription (Host + Vendor)',
				'price' => 3900,
				'currency' => 'usd',
				'interval' => 'month',
				'description' => 'Host events AND advertise - Best value!',
				'role' => 'pro'
			)
		);
	}
	
	/**
	 * Create Stripe payment link for a plan
	 */
	public function create_payment_link($plan_slug, $user_data) {
		if (empty($this->stripe_secret_key)) {
			return new WP_Error('stripe_not_configured', 'Stripe is not configured. Please add your API keys.');
		}
		
		$plans = $this->get_pricing_plans();
		
		if (!isset($plans[$plan_slug])) {
			return new WP_Error('invalid_plan', 'Invalid plan selected.');
		}
		
		$plan = $plans[$plan_slug];
		
		// Generate secure token for user registration
		$token = wp_generate_password(32, false);
		$token_hash = wp_hash_password($token);
		
		// Store pending registration data
		$pending_data = array(
			'username' => sanitize_user($user_data['username']),
			'email' => sanitize_email($user_data['email']),
			'password' => $user_data['password'], // Will be hashed when account is created
			'first_name' => sanitize_text_field($user_data['first_name']),
			'last_name' => sanitize_text_field($user_data['last_name']),
			'role' => $plan['role'],
			'plan' => $plan_slug,
			'created_at' => current_time('mysql'),
			'token_hash' => $token_hash
		);
		
		$pending_id = $this->save_pending_registration($pending_data);
		
		if (!$pending_id) {
			return new WP_Error('save_failed', 'Failed to save registration data.');
		}
		
		// Create Stripe checkout session
		try {
			\Stripe\Stripe::setApiKey($this->stripe_secret_key);
			
			$checkout_session = \Stripe\Checkout\Session::create([
				'payment_method_types' => ['card'],
				'mode' => 'subscription',
				'line_items' => [[
					'price_data' => [
						'currency' => $plan['currency'],
						'product_data' => [
							'name' => $plan['name'],
							'description' => $plan['description'],
						],
						'recurring' => [
							'interval' => $plan['interval'],
						],
						'unit_amount' => $plan['price'],
					],
					'quantity' => 1,
				]],
				'metadata' => [
					'pending_registration_id' => $pending_id,
					'plan_slug' => $plan_slug,
					'registration_token' => $token
				],
				'customer_email' => $user_data['email'],
				'success_url' => home_url('/signup/success/?session_id={CHECKOUT_SESSION_ID}&token=' . urlencode($token)),
				'cancel_url' => home_url('/signup/?plan=' . $plan_slug . '&payment=cancelled'),
			]);
			
			return array(
				'checkout_url' => $checkout_session->url,
				'session_id' => $checkout_session->id,
				'pending_id' => $pending_id,
				'token' => $token
			);
			
		} catch (Exception $e) {
			error_log('Stripe Error: ' . $e->getMessage());
			return new WP_Error('stripe_error', $e->getMessage());
		}
	}
	
	/**
	 * Save pending registration to database
	 */
	private function save_pending_registration($data) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'event_rsvp_pending_registrations';
		
		// Create table if not exists
		$this->maybe_create_pending_table();
		
		$inserted = $wpdb->insert(
			$table_name,
			array(
				'username' => $data['username'],
				'email' => $data['email'],
				'password_hash' => wp_hash_password($data['password']),
				'first_name' => $data['first_name'],
				'last_name' => $data['last_name'],
				'role' => $data['role'],
				'plan' => $data['plan'],
				'token_hash' => $data['token_hash'],
				'status' => 'pending',
				'created_at' => $data['created_at']
			),
			array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
		);
		
		if ($inserted) {
			return $wpdb->insert_id;
		}
		
		return false;
	}
	
	/**
	 * Create pending registrations table
	 */
	private function maybe_create_pending_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'event_rsvp_pending_registrations';
		$charset_collate = $wpdb->get_charset_collate();
		
		if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
			$sql = "CREATE TABLE $table_name (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				username varchar(60) NOT NULL,
				email varchar(100) NOT NULL,
				password_hash varchar(255) NOT NULL,
				first_name varchar(50) NOT NULL,
				last_name varchar(50) NOT NULL,
				role varchar(20) NOT NULL,
				plan varchar(20) NOT NULL,
				token_hash varchar(255) NOT NULL,
				stripe_session_id varchar(255) DEFAULT NULL,
				stripe_customer_id varchar(255) DEFAULT NULL,
				stripe_subscription_id varchar(255) DEFAULT NULL,
				status varchar(20) DEFAULT 'pending',
				created_at datetime NOT NULL,
				completed_at datetime DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY email (email),
				KEY token_hash (token_hash),
				KEY status (status)
			) $charset_collate;";
			
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		}
	}
	
	/**
	 * Handle Stripe webhook
	 */
	public function handle_webhook($request) {
		$payload = $request->get_body();
		$sig_header = $request->get_header('stripe_signature');
		
		if (empty($this->webhook_secret)) {
			return new WP_Error('webhook_not_configured', 'Webhook secret not configured', array('status' => 400));
		}
		
		try {
			\Stripe\Stripe::setApiKey($this->stripe_secret_key);
			$event = \Stripe\Webhook::constructEvent($payload, $sig_header, $this->webhook_secret);
		} catch (\UnexpectedValueException $e) {
			return new WP_Error('invalid_payload', 'Invalid payload', array('status' => 400));
		} catch (\Stripe\Exception\SignatureVerificationException $e) {
			return new WP_Error('invalid_signature', 'Invalid signature', array('status' => 400));
		}
		
		// Handle the event
		switch ($event->type) {
			case 'checkout.session.completed':
				$this->handle_checkout_completed($event->data->object);
				break;
			
			case 'customer.subscription.created':
			case 'customer.subscription.updated':
				$this->handle_subscription_updated($event->data->object);
				break;
			
			case 'customer.subscription.deleted':
				$this->handle_subscription_cancelled($event->data->object);
				break;
			
			case 'invoice.payment_succeeded':
				$this->handle_payment_succeeded($event->data->object);
				break;
			
			case 'invoice.payment_failed':
				$this->handle_payment_failed($event->data->object);
				break;
			
			default:
				error_log('Unhandled webhook event type: ' . $event->type);
		}
		
		return rest_ensure_response(array('received' => true));
	}
	
	/**
	 * Handle checkout session completed
	 */
	private function handle_checkout_completed($session) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'event_rsvp_pending_registrations';
		
		$pending_id = isset($session->metadata->pending_registration_id) ? intval($session->metadata->pending_registration_id) : 0;
		$token = isset($session->metadata->registration_token) ? $session->metadata->registration_token : '';
		
		if (!$pending_id || !$token) {
			error_log('Missing pending_id or token in checkout session metadata');
			return;
		}
		
		// Get pending registration
		$pending = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM $table_name WHERE id = %d AND status = 'pending'",
			$pending_id
		));
		
		if (!$pending) {
			error_log('Pending registration not found: ' . $pending_id);
			return;
		}
		
		// Verify token
		if (!wp_check_password($token, $pending->token_hash)) {
			error_log('Token verification failed for pending registration: ' . $pending_id);
			return;
		}
		
		// Check if user already exists
		if (username_exists($pending->username) || email_exists($pending->email)) {
			error_log('User already exists: ' . $pending->username);
			$wpdb->update(
				$table_name,
				array('status' => 'duplicate'),
				array('id' => $pending_id)
			);
			return;
		}
		
		// Create WordPress user account
		$user_id = wp_create_user($pending->username, wp_generate_password(12, true, true), $pending->email);
		
		if (is_wp_error($user_id)) {
			error_log('Failed to create user: ' . $user_id->get_error_message());
			return;
		}
		
		// Update user details
		wp_update_user(array(
			'ID' => $user_id,
			'first_name' => $pending->first_name,
			'last_name' => $pending->last_name,
			'display_name' => $pending->first_name . ' ' . $pending->last_name,
			'role' => $pending->role
		));
		
		// Store subscription data
		update_user_meta($user_id, 'event_rsvp_plan', $pending->plan);
		update_user_meta($user_id, 'event_rsvp_subscription_status', 'active');
		update_user_meta($user_id, 'event_rsvp_stripe_customer_id', $session->customer);
		update_user_meta($user_id, 'event_rsvp_stripe_session_id', $session->id);
		
		if (isset($session->subscription)) {
			update_user_meta($user_id, 'event_rsvp_stripe_subscription_id', $session->subscription);
		}
		
		// Update pending registration
		$wpdb->update(
			$table_name,
			array(
				'status' => 'completed',
				'completed_at' => current_time('mysql'),
				'stripe_session_id' => $session->id,
				'stripe_customer_id' => $session->customer,
				'stripe_subscription_id' => isset($session->subscription) ? $session->subscription : null
			),
			array('id' => $pending_id)
		);
		
		// Send welcome email with login credentials
		$this->send_welcome_email($user_id, $pending->email, $pending->username);
		
		do_action('event_rsvp_account_created_after_payment', $user_id, $pending->plan);
	}
	
	/**
	 * Handle subscription updated
	 */
	private function handle_subscription_updated($subscription) {
		$customer_id = $subscription->customer;
		
		$users = get_users(array(
			'meta_key' => 'event_rsvp_stripe_customer_id',
			'meta_value' => $customer_id,
			'number' => 1
		));
		
		if (empty($users)) {
			return;
		}
		
		$user = $users[0];
		
		if ($subscription->status === 'active') {
			update_user_meta($user->ID, 'event_rsvp_subscription_status', 'active');
		} elseif ($subscription->status === 'past_due') {
			update_user_meta($user->ID, 'event_rsvp_subscription_status', 'past_due');
		}
	}
	
	/**
	 * Handle subscription cancelled
	 */
	private function handle_subscription_cancelled($subscription) {
		$customer_id = $subscription->customer;
		
		$users = get_users(array(
			'meta_key' => 'event_rsvp_stripe_customer_id',
			'meta_value' => $customer_id,
			'number' => 1
		));
		
		if (empty($users)) {
			return;
		}
		
		$user = $users[0];
		
		// Downgrade to subscriber role
		wp_update_user(array(
			'ID' => $user->ID,
			'role' => 'subscriber'
		));
		
		update_user_meta($user->ID, 'event_rsvp_subscription_status', 'cancelled');
		delete_user_meta($user->ID, 'event_rsvp_plan');
		
		// Send cancellation email
		$this->send_cancellation_email($user->ID);
	}
	
	/**
	 * Handle successful payment
	 */
	private function handle_payment_succeeded($invoice) {
		$customer_id = $invoice->customer;
		
		$users = get_users(array(
			'meta_key' => 'event_rsvp_stripe_customer_id',
			'meta_value' => $customer_id,
			'number' => 1
		));
		
		if (!empty($users)) {
			$user = $users[0];
			update_user_meta($user->ID, 'event_rsvp_last_payment_date', current_time('mysql'));
			update_user_meta($user->ID, 'event_rsvp_subscription_status', 'active');
		}
	}
	
	/**
	 * Handle failed payment
	 */
	private function handle_payment_failed($invoice) {
		$customer_id = $invoice->customer;
		
		$users = get_users(array(
			'meta_key' => 'event_rsvp_stripe_customer_id',
			'meta_value' => $customer_id,
			'number' => 1
		));
		
		if (!empty($users)) {
			$user = $users[0];
			update_user_meta($user->ID, 'event_rsvp_subscription_status', 'payment_failed');
			
			// Send payment failed email
			$this->send_payment_failed_email($user->ID);
		}
	}
	
	/**
	 * Send welcome email with account details
	 */
	private function send_welcome_email($user_id, $email, $username) {
		$user = get_user_by('id', $user_id);
		$plan = get_user_meta($user_id, 'event_rsvp_plan', true);
		
		$subject = 'Welcome to ' . get_bloginfo('name') . ' - Your Account is Ready!';
		
		$message = "Hi {$user->first_name},\n\n";
		$message .= "Great news! Your payment was successful and your account has been created.\n\n";
		$message .= "Account Details:\n";
		$message .= "Username: {$username}\n";
		$message .= "Email: {$email}\n";
		$message .= "Plan: " . ucwords(str_replace('_', ' ', $plan)) . "\n\n";
		$message .= "You can now log in at: " . home_url('/login/') . "\n\n";
		$message .= "To reset your password, visit: " . wp_lostpassword_url() . "\n\n";
		$message .= "Thank you for choosing us!\n\n";
		$message .= "Best regards,\n";
		$message .= get_bloginfo('name');
		
		wp_mail($email, $subject, $message);
	}
	
	/**
	 * Send cancellation email
	 */
	private function send_cancellation_email($user_id) {
		$user = get_user_by('id', $user_id);
		
		$subject = 'Your Subscription Has Been Cancelled';
		$message = "Hi {$user->first_name},\n\n";
		$message .= "Your subscription has been cancelled. You've been downgraded to a free Attendee account.\n\n";
		$message .= "You can still browse and RSVP to events, but you won't be able to create events or post ads.\n\n";
		$message .= "To reactivate your subscription, visit: " . home_url('/pricing/') . "\n\n";
		$message .= "Best regards,\n";
		$message .= get_bloginfo('name');
		
		wp_mail($user->user_email, $subject, $message);
	}
	
	/**
	 * Send payment failed email
	 */
	private function send_payment_failed_email($user_id) {
		$user = get_user_by('id', $user_id);
		
		$subject = 'Payment Failed - Action Required';
		$message = "Hi {$user->first_name},\n\n";
		$message .= "We were unable to process your recent payment. Please update your payment method to continue your subscription.\n\n";
		$message .= "Manage your subscription: " . home_url('/account/') . "\n\n";
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
			'Stripe Settings',
			'Stripe Settings',
			'manage_options',
			'event-rsvp-stripe',
			array($this, 'render_settings_page')
		);
	}
	
	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting('event_rsvp_stripe_settings', 'event_rsvp_stripe_secret_key');
		register_setting('event_rsvp_stripe_settings', 'event_rsvp_stripe_publishable_key');
		register_setting('event_rsvp_stripe_settings', 'event_rsvp_stripe_webhook_secret');
	}
	
	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		?>
		<div class="wrap">
			<h1>Stripe Payment Settings</h1>
			<p>Configure your Stripe API keys to enable payment processing.</p>
			
			<div class="notice notice-info">
				<p><strong>Webhook URL:</strong> <?php echo esc_url(rest_url('event-rsvp/v1/stripe-webhook')); ?></p>
				<p>Add this URL to your Stripe webhook endpoints. Events to listen for: <code>checkout.session.completed</code>, <code>customer.subscription.created</code>, <code>customer.subscription.updated</code>, <code>customer.subscription.deleted</code>, <code>invoice.payment_succeeded</code>, <code>invoice.payment_failed</code></p>
			</div>
			
			<form method="post" action="options.php">
				<?php settings_fields('event_rsvp_stripe_settings'); ?>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="event_rsvp_stripe_publishable_key">Publishable Key</label>
						</th>
						<td>
							<input type="text" id="event_rsvp_stripe_publishable_key" 
								   name="event_rsvp_stripe_publishable_key" 
								   value="<?php echo esc_attr(get_option('event_rsvp_stripe_publishable_key')); ?>" 
								   class="regular-text" 
								   placeholder="pk_test_...">
							<p class="description">Your Stripe publishable key (starts with pk_)</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="event_rsvp_stripe_secret_key">Secret Key</label>
						</th>
						<td>
							<input type="password" id="event_rsvp_stripe_secret_key" 
								   name="event_rsvp_stripe_secret_key" 
								   value="<?php echo esc_attr(get_option('event_rsvp_stripe_secret_key')); ?>" 
								   class="regular-text" 
								   placeholder="sk_test_...">
							<p class="description">Your Stripe secret key (starts with sk_)</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="event_rsvp_stripe_webhook_secret">Webhook Secret</label>
						</th>
						<td>
							<input type="password" id="event_rsvp_stripe_webhook_secret" 
								   name="event_rsvp_stripe_webhook_secret" 
								   value="<?php echo esc_attr(get_option('event_rsvp_stripe_webhook_secret')); ?>" 
								   class="regular-text" 
								   placeholder="whsec_...">
							<p class="description">Your Stripe webhook signing secret (starts with whsec_)</p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
	
	/**
	 * Get user's subscription status
	 */
	public static function get_user_subscription_status($user_id = 0) {
		if (!$user_id) {
			$user_id = get_current_user_id();
		}
		
		return get_user_meta($user_id, 'event_rsvp_subscription_status', true);
	}
	
	/**
	 * Get user's plan
	 */
	public static function get_user_plan($user_id = 0) {
		if (!$user_id) {
			$user_id = get_current_user_id();
		}
		
		return get_user_meta($user_id, 'event_rsvp_plan', true);
	}
	
	/**
	 * Check if user has active subscription
	 */
	public static function has_active_subscription($user_id = 0) {
		if (!$user_id) {
			$user_id = get_current_user_id();
		}
		
		$status = self::get_user_subscription_status($user_id);
		return $status === 'active';
	}
}

// Initialize
function event_rsvp_stripe_init() {
	// Autoload Stripe library
	if (!class_exists('Stripe\Stripe')) {
		require_once EVENT_RSVP_PLUGIN_DIR . '/includes/stripe-php/init.php';
	}
	
	return Event_RSVP_Stripe_Integration::get_instance();
}
add_action('plugins_loaded', 'event_rsvp_stripe_init');

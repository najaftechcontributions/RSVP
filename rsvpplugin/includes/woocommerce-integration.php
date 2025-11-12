<?php
/**
 * WooCommerce Integration for Event RSVP Platform
 * 
 * Handles WooCommerce subscription products, payment processing, and user role management.
 * Requires WooCommerce and WooCommerce Subscriptions plugins.
 * 
 * @package EventRSVPPlugin
 */

if (!defined('ABSPATH')) {
	exit;
}

class Event_RSVP_WooCommerce_Integration {
	
	private static $instance = null;
	
	private $product_ids = array();
	
	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	private function __construct() {
		add_action('init', array($this, 'check_woocommerce'));
		add_action('admin_init', array($this, 'create_subscription_products'));
		add_action('woocommerce_subscription_status_active', array($this, 'activate_subscription'), 10, 1);
		add_action('woocommerce_subscription_status_cancelled', array($this, 'cancel_subscription'), 10, 1);
		add_action('woocommerce_subscription_status_expired', array($this, 'cancel_subscription'), 10, 1);
		add_action('woocommerce_subscription_status_on-hold', array($this, 'suspend_subscription'), 10, 1);
		add_action('woocommerce_order_status_completed', array($this, 'handle_order_completed'), 10, 1);
		add_filter('woocommerce_add_to_cart_redirect', array($this, 'redirect_to_checkout'));
		add_action('wp_ajax_get_product_ids', array($this, 'ajax_get_product_ids'));
		add_action('wp_ajax_nopriv_get_product_ids', array($this, 'ajax_get_product_ids'));
	}
	
	public function check_woocommerce() {
		if (!class_exists('WooCommerce')) {
			add_action('admin_notices', function() {
				echo '<div class="notice notice-error"><p><strong>Event RSVP Platform:</strong> WooCommerce is required for payment processing. Please install and activate WooCommerce.</p></div>';
			});
		}
	}
	
	public function create_subscription_products() {
		if (!class_exists('WooCommerce') || !current_user_can('administrator')) {
			return;
		}
		
		$option_key = 'event_rsvp_wc_products_created';
		$created = get_option($option_key, false);
		
		if ($created) {
			$this->load_product_ids();
			return;
		}
		
		$plans = array(
			'event_host' => array(
				'name' => 'Event Host Subscription',
				'price' => 19.00,
				'description' => 'Create and manage unlimited events with up to 500 attendees per event. Includes QR code check-in, email notifications, CSV exports, and event analytics.',
				'features' => array(
					'Unlimited events',
					'500 attendees per event',
					'Custom RSVP forms',
					'QR code check-in',
					'Email notifications',
					'CSV exports',
					'Event analytics',
					'Email support'
				)
			),
			'vendor' => array(
				'name' => 'Vendor Subscription',
				'price' => 29.00,
				'description' => 'Advertise your business or services with featured ad placements, analytics tracking, and multiple ad slots.',
				'features' => array(
					'Post vendor advertisements',
					'Featured ad placements',
					'Ad analytics & tracking',
					'Multiple ad slots',
					'Target event audiences',
					'Email support',
					'Browse & attend events'
				)
			),
			'pro' => array(
				'name' => 'Pro Subscription (Host + Vendor)',
				'price' => 39.00,
				'description' => 'Get everything! Host unlimited events AND advertise your business. Best value - save $9/month!',
				'features' => array(
					'Everything in Event Host',
					'Everything in Vendor',
					'Unlimited events',
					'Unlimited ad postings',
					'Priority ad placement',
					'Advanced analytics',
					'Custom branding',
					'Priority support',
					'Save $9/month!'
				)
			)
		);
		
		$product_ids = array();
		
		foreach ($plans as $plan_slug => $plan_data) {
			$existing_id = get_option('event_rsvp_product_' . $plan_slug);
			
			if ($existing_id && get_post($existing_id)) {
				$product_ids[$plan_slug] = $existing_id;
				continue;
			}
			
			if (class_exists('WC_Product_Subscription')) {
				$product = new WC_Product_Subscription();
			} else {
				$product = new WC_Product_Simple();
			}
			
			$product->set_name($plan_data['name']);
			$product->set_status('publish');
			$product->set_catalog_visibility('visible');
			$product->set_description($this->format_product_description($plan_data));
			$product->set_short_description($plan_data['description']);
			$product->set_regular_price($plan_data['price']);
			$product->set_price($plan_data['price']);
			$product->set_virtual(true);
			$product->set_sold_individually(true);
			
			if (class_exists('WC_Product_Subscription') && method_exists($product, 'set_subscription_price')) {
				$product->set_subscription_price($plan_data['price']);
				$product->set_subscription_period('month');
				$product->set_subscription_period_interval(1);
				$product->set_subscription_length(0);
			}
			
			$product_id = $product->save();
			
			if ($product_id) {
				update_post_meta($product_id, '_event_rsvp_plan', $plan_slug);
				update_option('event_rsvp_product_' . $plan_slug, $product_id);
				$product_ids[$plan_slug] = $product_id;
			}
		}
		
		update_option($option_key, true);
		update_option('event_rsvp_product_ids', $product_ids);
		$this->product_ids = $product_ids;
	}
	
	private function format_product_description($plan_data) {
		$description = $plan_data['description'] . "\n\n<strong>Features:</strong>\n<ul>";
		foreach ($plan_data['features'] as $feature) {
			$description .= '<li>' . esc_html($feature) . '</li>';
		}
		$description .= '</ul>';
		return $description;
	}
	
	public function load_product_ids() {
		$this->product_ids = get_option('event_rsvp_product_ids', array());
	}
	
	public function get_product_id($plan_slug) {
		if (empty($this->product_ids)) {
			$this->load_product_ids();
		}
		return isset($this->product_ids[$plan_slug]) ? $this->product_ids[$plan_slug] : 0;
	}
	
	public function activate_subscription($subscription) {
		if (!is_object($subscription)) {
			$subscription = wcs_get_subscription($subscription);
		}
		
		if (!$subscription) {
			return;
		}
		
		$user_id = $subscription->get_user_id();
		$items = $subscription->get_items();
		
		foreach ($items as $item) {
			$product_id = $item->get_product_id();
			$plan_slug = get_post_meta($product_id, '_event_rsvp_plan', true);
			
			if ($plan_slug) {
				$this->assign_user_role($user_id, $plan_slug);
				update_user_meta($user_id, 'event_rsvp_subscription_id', $subscription->get_id());
				update_user_meta($user_id, 'event_rsvp_subscription_status', 'active');
				update_user_meta($user_id, 'event_rsvp_plan', $plan_slug);
			}
		}
	}
	
	public function cancel_subscription($subscription) {
		if (!is_object($subscription)) {
			$subscription = wcs_get_subscription($subscription);
		}
		
		if (!$subscription) {
			return;
		}
		
		$user_id = $subscription->get_user_id();
		
		$user = new WP_User($user_id);
		$user->set_role('subscriber');
		
		update_user_meta($user_id, 'event_rsvp_subscription_status', 'cancelled');
		delete_user_meta($user_id, 'event_rsvp_plan');
	}
	
	public function suspend_subscription($subscription) {
		if (!is_object($subscription)) {
			$subscription = wcs_get_subscription($subscription);
		}
		
		if (!$subscription) {
			return;
		}
		
		$user_id = $subscription->get_user_id();
		update_user_meta($user_id, 'event_rsvp_subscription_status', 'suspended');
	}
	
	public function handle_order_completed($order_id) {
		$order = wc_get_order($order_id);
		
		if (!$order) {
			return;
		}
		
		if (function_exists('wcs_order_contains_subscription') && wcs_order_contains_subscription($order)) {
			return;
		}
		
		$user_id = $order->get_user_id();
		
		if (!$user_id) {
			return;
		}
		
		foreach ($order->get_items() as $item) {
			$product_id = $item->get_product_id();
			$plan_slug = get_post_meta($product_id, '_event_rsvp_plan', true);
			
			if ($plan_slug) {
				$this->assign_user_role($user_id, $plan_slug);
				update_user_meta($user_id, 'event_rsvp_plan', $plan_slug);
				update_user_meta($user_id, 'event_rsvp_subscription_status', 'active');
			}
		}
	}
	
	private function assign_user_role($user_id, $plan_slug) {
		$user = new WP_User($user_id);
		
		$role_map = array(
			'event_host' => 'event_host',
			'vendor' => 'vendor',
			'pro' => 'pro'
		);
		
		$role = isset($role_map[$plan_slug]) ? $role_map[$plan_slug] : 'subscriber';
		$user->set_role($role);
	}
	
	public function redirect_to_checkout($url) {
		return wc_get_checkout_url();
	}
	
	public function ajax_get_product_ids() {
		$this->load_product_ids();
		wp_send_json_success($this->product_ids);
	}
	
	public static function get_checkout_url($plan_slug) {
		$integration = self::get_instance();
		$product_id = $integration->get_product_id($plan_slug);
		
		if (!$product_id) {
			return wc_get_page_permalink('shop');
		}
		
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart($product_id);
		
		return wc_get_checkout_url();
	}
	
	public static function has_active_subscription($user_id = 0) {
		if (!$user_id) {
			$user_id = get_current_user_id();
		}
		
		$status = get_user_meta($user_id, 'event_rsvp_subscription_status', true);
		return $status === 'active';
	}
	
	public static function get_user_plan($user_id = 0) {
		if (!$user_id) {
			$user_id = get_current_user_id();
		}
		
		return get_user_meta($user_id, 'event_rsvp_plan', true);
	}
}

function event_rsvp_wc_init() {
	return Event_RSVP_WooCommerce_Integration::get_instance();
}

add_action('plugins_loaded', 'event_rsvp_wc_init');

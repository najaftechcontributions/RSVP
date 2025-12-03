<?php
/**
 * Event RSVP Platform - Main Plugin File
 * 
 * A complete event management system with RSVP, QR codes, attendee management,
 * check-in system, and vendor advertising capabilities.
 * 
 * Uses simple Stripe Payment Links for subscriptions.
 * 
 * @package EventRSVPPlugin
 * @version 3.0.0
 */

if (!defined('ABSPATH')) {
	exit;
}

define('EVENT_RSVP_VERSION', '3.0.0');
define('EVENT_RSVP_PLUGIN_DIR', dirname(__FILE__));
define('EVENT_RSVP_PLUGIN_URL', get_template_directory_uri() . '/rsvpplugin');

class EventRSVPPlugin {
	
	private static $instance = null;
	
	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	private function __construct() {
		$this->load_dependencies();
		$this->register_hooks();
	}
	
	private function load_dependencies() {
		require_once EVENT_RSVP_PLUGIN_DIR . '/includes/post-types.php';
		require_once EVENT_RSVP_PLUGIN_DIR . '/includes/user-roles.php';
		require_once EVENT_RSVP_PLUGIN_DIR . '/includes/acf-fields.php';
		require_once EVENT_RSVP_PLUGIN_DIR . '/includes/acf-return-url-filter.php';
		
		require_once EVENT_RSVP_PLUGIN_DIR . '/includes/helper-functions.php';
		require_once EVENT_RSVP_PLUGIN_DIR . '/includes/ajax-handlers.php';
		require_once EVENT_RSVP_PLUGIN_DIR . '/includes/event-limit-functions.php';
		require_once EVENT_RSVP_PLUGIN_DIR . '/includes/event-creation-ajax.php';
		require_once EVENT_RSVP_PLUGIN_DIR . '/includes/form-handlers.php';
		require_once EVENT_RSVP_PLUGIN_DIR . '/includes/email-functions.php';
		require_once EVENT_RSVP_PLUGIN_DIR . '/includes/shortcodes.php';
		require_once EVENT_RSVP_PLUGIN_DIR . '/includes/admin-functions.php';
		
		// Email Invitation System
		require_once EVENT_RSVP_PLUGIN_DIR . '/includes/email-invitation-db.php';
		require_once EVENT_RSVP_PLUGIN_DIR . '/includes/email-invitation-functions.php';
		require_once EVENT_RSVP_PLUGIN_DIR . '/includes/email-invitation-ajax.php';
		require_once EVENT_RSVP_PLUGIN_DIR . '/includes/email-template-image-upload.php';
		
		// Simple Stripe Payment Links integration
		require_once EVENT_RSVP_PLUGIN_DIR . '/includes/simple-stripe-payments.php';
		require_once EVENT_RSVP_PLUGIN_DIR . '/includes/simple-stripe-ajax.php';
		require_once EVENT_RSVP_PLUGIN_DIR . '/includes/simple-stripe-payments-upgrade.php';
		require_once EVENT_RSVP_PLUGIN_DIR . '/includes/upgrade-ajax.php';
	}
	
	private function register_hooks() {
		add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'), 20);
		add_action('admin_notices', array($this, 'admin_notices'));
	}
	
	public function enqueue_assets() {
		wp_enqueue_style('event-rsvp-styles', EVENT_RSVP_PLUGIN_URL . '/assets/css/event-rsvp.css', array(), EVENT_RSVP_VERSION);
		wp_enqueue_style('event-templates-styles', EVENT_RSVP_PLUGIN_URL . '/assets/css/event-templates.css', array('event-rsvp-styles'), EVENT_RSVP_VERSION);
		wp_enqueue_style('vendor-ads-display-styles', EVENT_RSVP_PLUGIN_URL . '/assets/css/vendor-ads-display.css', array(), EVENT_RSVP_VERSION);
		
		if (is_page_template('page-vendor-dashboard.php')) {
			wp_enqueue_style('vendor-dashboard-styles', EVENT_RSVP_PLUGIN_URL . '/assets/css/vendor-dashboard.css', array('event-rsvp-styles'), EVENT_RSVP_VERSION);
		}
		
		if (is_page_template('page-ads-management.php')) {
			wp_enqueue_style('ads-management-styles', EVENT_RSVP_PLUGIN_URL . '/assets/css/ads-management.css', array('event-rsvp-styles'), EVENT_RSVP_VERSION);
		}
		
		if (is_page_template('page-email-campaigns.php')) {
			wp_enqueue_style('email-campaigns-styles', EVENT_RSVP_PLUGIN_URL . '/assets/css/email-campaigns.css', array('event-rsvp-styles'), EVENT_RSVP_VERSION);
			wp_enqueue_media();
			wp_enqueue_script('email-campaigns-enhancements', EVENT_RSVP_PLUGIN_URL . '/assets/js/email-campaigns-enhancements.js', array('jquery'), EVENT_RSVP_VERSION, true);
			wp_enqueue_script('email-campaigns-manage', EVENT_RSVP_PLUGIN_URL . '/assets/js/email-campaigns-manage.js', array('jquery', 'email-campaigns-enhancements'), EVENT_RSVP_VERSION, true);
		}
		
		if (is_page_template('page-check-in.php')) {
			wp_enqueue_script('html5-qrcode', 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js', array(), '2.3.8', true);
		}
		
		wp_enqueue_script('event-rsvp-scripts', EVENT_RSVP_PLUGIN_URL . '/assets/js/event-rsvp.js', array('jquery'), EVENT_RSVP_VERSION, true);
		wp_enqueue_script('vendor-ads-scripts', EVENT_RSVP_PLUGIN_URL . '/assets/js/vendor-ads.js', array('jquery'), EVENT_RSVP_VERSION, true);
		
		wp_localize_script('event-rsvp-scripts', 'eventRsvpData', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('event_rsvp_checkin'),
			'ad_management_nonce' => wp_create_nonce('event_rsvp_ad_management'),
			'email_campaign_nonce' => wp_create_nonce('event_rsvp_email_campaign')
		));
	}
	
	public function admin_notices() {
		// Check if Stripe payment links are configured
		$links = Event_RSVP_Simple_Stripe::get_instance()->get_payment_links();
		$has_links = !empty($links['event_host']) || !empty($links['vendor']) || !empty($links['pro']);
		
		if (!$has_links) {
			?>
			<div class="notice notice-warning">
				<p><strong>Event RSVP Platform:</strong> Stripe Payment Links are not configured. Please configure them in <a href="<?php echo admin_url('options-general.php?page=event-rsvp-stripe-links'); ?>">Settings → Stripe Payments</a> to enable paid subscriptions.</p>
			</div>
			<?php
		}
	}
}

function event_rsvp_plugin_init() {
	return EventRSVPPlugin::get_instance();
}

event_rsvp_plugin_init();

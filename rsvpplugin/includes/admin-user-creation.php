<?php
/**
 * Admin User Creation Tool
 * Allows administrators to create users with specific roles and plans
 * 
 * @package EventRSVPPlugin
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Add user creation submenu to Event RSVP menu
 */
function event_rsvp_add_user_creation_menu() {
	add_submenu_page(
		'event-rsvp-settings',
		'Create User',
		'Create User',
		'manage_options',
		'event-rsvp-create-user',
		'event_rsvp_create_user_page'
	);
}
add_action('admin_menu', 'event_rsvp_add_user_creation_menu');

/**
 * User creation page
 */
function event_rsvp_create_user_page() {
	// Handle role change submission
	if (isset($_POST['event_rsvp_change_role_submit'])) {
		check_admin_referer('event_rsvp_change_role');

		$user_id = intval($_POST['user_id']);
		$plan = sanitize_text_field($_POST['plan']);

		// Validate
		if (empty($user_id) || !get_userdata($user_id)) {
			echo '<div class="notice notice-error"><p>Invalid user selected.</p></div>';
		} else {
			// Map plan to role
			$role_map = array(
				'attendee' => 'subscriber',
				'pay_as_you_go' => 'event_host',
				'event_planner' => 'event_host',
				'event_host' => 'event_host',
				'vendor' => 'vendor',
				'pro' => 'pro'
			);

			$role = isset($role_map[$plan]) ? $role_map[$plan] : 'subscriber';
			$user = new WP_User($user_id);

			// Update role
			$user->set_role($role);

			// Set plan meta
			update_user_meta($user_id, 'event_rsvp_plan', $plan);

			// For paid plans, set active subscription
			if (in_array($plan, array('pay_as_you_go', 'event_planner', 'event_host', 'vendor', 'pro'))) {
				update_user_meta($user_id, 'event_rsvp_subscription_status', 'active');
				if (!get_user_meta($user_id, 'event_rsvp_payment_date', true)) {
					update_user_meta($user_id, 'event_rsvp_payment_date', current_time('mysql'));
				}
			} else {
				// For free plan, clear subscription
				delete_user_meta($user_id, 'event_rsvp_subscription_status');
			}

			echo '<div class="notice notice-success"><p><strong>Success!</strong> User <strong>' . esc_html($user->user_login) . '</strong> role changed to <strong>' . esc_html($plan) . '</strong>.</p></div>';
		}
	}

	// Handle form submission
	if (isset($_POST['event_rsvp_create_user_submit'])) {
		check_admin_referer('event_rsvp_create_user');
		
		$username = sanitize_user($_POST['username']);
		$email = sanitize_email($_POST['email']);
		$password = $_POST['password'];
		$first_name = sanitize_text_field($_POST['first_name']);
		$last_name = sanitize_text_field($_POST['last_name']);
		$plan = sanitize_text_field($_POST['plan']);
		$send_email = isset($_POST['send_email']);
		
		// Validate
		if (empty($username) || empty($email) || empty($password)) {
			echo '<div class="notice notice-error"><p>Username, email, and password are required.</p></div>';
		} elseif (username_exists($username)) {
			echo '<div class="notice notice-error"><p>Username already exists.</p></div>';
		} elseif (email_exists($email)) {
			echo '<div class="notice notice-error"><p>Email already exists.</p></div>';
		} else {
			// Create user
			$user_id = wp_create_user($username, $password, $email);
			
			if (is_wp_error($user_id)) {
				echo '<div class="notice notice-error"><p>Error creating user: ' . esc_html($user_id->get_error_message()) . '</p></div>';
			} else {
				// Map plan to role
				$role_map = array(
					'attendee' => 'subscriber',
					'pay_as_you_go' => 'event_host',
					'event_planner' => 'event_host',
					'event_host' => 'event_host',
					'vendor' => 'vendor',
					'pro' => 'pro'
				);
				
				$role = isset($role_map[$plan]) ? $role_map[$plan] : 'subscriber';
				
				// Update user details
				wp_update_user(array(
					'ID' => $user_id,
					'first_name' => $first_name,
					'last_name' => $last_name,
					'display_name' => $first_name . ' ' . $last_name,
					'role' => $role
				));
				
				// Set plan meta
				update_user_meta($user_id, 'event_rsvp_plan', $plan);
				
				// For paid plans, set active subscription
				if (in_array($plan, array('pay_as_you_go', 'event_planner', 'event_host', 'vendor', 'pro'))) {
					update_user_meta($user_id, 'event_rsvp_subscription_status', 'active');
					update_user_meta($user_id, 'event_rsvp_payment_date', current_time('mysql'));
				}
				
				// Send welcome email if requested
				if ($send_email) {
					$plan_names = array(
						'attendee' => 'Free Attendee',
						'pay_as_you_go' => 'Pay As You Go',
						'event_planner' => 'Event Planner',
						'event_host' => 'Event Host',
						'vendor' => 'Vendor',
						'pro' => 'Pro (Event Host + Vendor)'
					);
					
					$plan_name = isset($plan_names[$plan]) ? $plan_names[$plan] : 'Attendee';
					
					$subject = 'Your Account Has Been Created - ' . get_bloginfo('name');
					$message = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">';
					$message .= '<h1 style="color: #503AA8; border-bottom: 2px solid #503AA8; padding-bottom: 10px;">Welcome to ' . get_bloginfo('name') . '!</h1>';
					$message .= '<p>Hi ' . esc_html($first_name) . ',</p>';
					$message .= '<p>Your account has been created by an administrator.</p>';
					$message .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">';
					$message .= '<p style="margin: 5px 0;"><strong>Username:</strong> ' . esc_html($username) . '</p>';
					$message .= '<p style="margin: 5px 0;"><strong>Email:</strong> ' . esc_html($email) . '</p>';
					$message .= '<p style="margin: 5px 0;"><strong>Password:</strong> ' . esc_html($password) . '</p>';
					$message .= '<p style="margin: 5px 0;"><strong>Plan:</strong> ' . esc_html($plan_name) . '</p>';
					$message .= '</div>';
					$message .= '<p>You can log in at: <a href="' . home_url('/login/') . '">' . home_url('/login/') . '</a></p>';
					$message .= '<p><strong>What you can do:</strong></p>';
					$message .= '<ul style="line-height: 1.8;">';
					$message .= '<li>Browse and RSVP to events</li>';
					if (in_array($plan, array('pay_as_you_go', 'event_planner', 'event_host', 'pro'))) {
						$message .= '<li>Create and manage events</li>';
						$message .= '<li>Track attendees and RSVPs</li>';
					}
					if (in_array($plan, array('vendor', 'pro'))) {
						$message .= '<li>Create and manage advertisements</li>';
						$message .= '<li>Track ad performance</li>';
					}
					$message .= '</ul>';
					$message .= '<p style="margin-top: 30px;">Best regards,<br>' . get_bloginfo('name') . '</p>';
					$message .= '</div>';
					
					$headers = array('Content-Type: text/html; charset=UTF-8');
					wp_mail($email, $subject, $message, $headers);
				}
				
				echo '<div class="notice notice-success"><p><strong>Success!</strong> User created with username: <strong>' . esc_html($username) . '</strong> and plan: <strong>' . esc_html($plan) . '</strong>. ' . ($send_email ? 'Welcome email sent.' : '') . '</p></div>';
			}
		}
	}
	?>
	<div class="wrap">
		<h1>User Management Tool</h1>
		<p>Create new users or change existing user roles and plans.</p>

		<div class="card" style="max-width: 800px; margin-top: 20px;">
			<h2>Change Existing User's Role/Plan</h2>
			<p>Update the role and plan of an existing user.</p>
			<form method="post" action="">
				<?php wp_nonce_field('event_rsvp_change_role'); ?>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="user_id">Select User <span class="required" style="color: red;">*</span></label>
						</th>
						<td>
							<select id="user_id" name="user_id" class="regular-text" required>
								<option value="">Select a user...</option>
								<?php
								$users = get_users(array('orderby' => 'display_name'));
								foreach ($users as $user) {
									$current_plan = get_user_meta($user->ID, 'event_rsvp_plan', true);
									$roles = implode(', ', $user->roles);
									echo '<option value="' . esc_attr($user->ID) . '">';
									echo esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')';
									if ($current_plan) {
										echo ' - Current Plan: ' . esc_html($current_plan);
									}
									echo ' - Role: ' . esc_html($roles);
									echo '</option>';
								}
								?>
							</select>
							<p class="description">Select the user whose role/plan you want to change.</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="plan_change">New Plan / Role <span class="required" style="color: red;">*</span></label>
						</th>
						<td>
							<select id="plan_change" name="plan" class="regular-text" required>
								<option value="attendee">Free Attendee (Subscriber Role)</option>
								<option value="pay_as_you_go">Pay As You Go (Event Host Role - 1 Event)</option>
								<option value="event_planner">Event Planner (Event Host Role - 5 Events)</option>
								<option value="event_host">Event Host (Event Host Role - Unlimited Events)</option>
								<option value="vendor">Vendor (Vendor Role)</option>
								<option value="pro">Pro - Event Host + Vendor (Pro Role)</option>
							</select>
							<p class="description">Select the new plan/role for this user. This will update their capabilities immediately.</p>
						</td>
					</tr>
				</table>

				<p class="submit">
					<input type="submit" name="event_rsvp_change_role_submit" class="button button-primary" value="Change User Role">
				</p>
			</form>
		</div>

		<div style="height: 30px;"></div>
		
		<div class="card" style="max-width: 800px; margin-top: 20px;">
			<h2>Create New User</h2>
			<p>Create a new user account with a specific plan and role.</p>
			<form method="post" action="">
				<?php wp_nonce_field('event_rsvp_create_user'); ?>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="first_name">First Name <span class="required" style="color: red;">*</span></label>
						</th>
						<td>
							<input type="text" id="first_name" name="first_name" class="regular-text" required>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="last_name">Last Name <span class="required" style="color: red;">*</span></label>
						</th>
						<td>
							<input type="text" id="last_name" name="last_name" class="regular-text" required>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="username">Username <span class="required" style="color: red;">*</span></label>
						</th>
						<td>
							<input type="text" id="username" name="username" class="regular-text" required>
							<p class="description">Must be unique. No spaces allowed.</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="email">Email <span class="required" style="color: red;">*</span></label>
						</th>
						<td>
							<input type="email" id="email" name="email" class="regular-text" required>
							<p class="description">Must be unique and valid.</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="password">Password <span class="required" style="color: red;">*</span></label>
						</th>
						<td>
							<input type="text" id="password" name="password" class="regular-text" required>
							<button type="button" id="generate_password" class="button">Generate Strong Password</button>
							<p class="description">Minimum 8 characters recommended.</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="plan">Plan / Role <span class="required" style="color: red;">*</span></label>
						</th>
						<td>
							<select id="plan" name="plan" class="regular-text" required>
								<option value="attendee">Free Attendee (Subscriber Role)</option>
								<option value="pay_as_you_go">Pay As You Go (Event Host Role - 1 Event)</option>
								<option value="event_planner">Event Planner (Event Host Role - 5 Events)</option>
								<option value="event_host">Event Host (Event Host Role - Unlimited Events)</option>
								<option value="vendor">Vendor (Vendor Role)</option>
								<option value="pro">Pro - Event Host + Vendor (Pro Role)</option>
							</select>
							<p class="description">Select the plan/role for this user. This determines their capabilities.</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="send_email">Send Welcome Email</label>
						</th>
						<td>
							<input type="checkbox" id="send_email" name="send_email" value="1" checked>
							<label for="send_email">Send the user an email with their login credentials</label>
						</td>
					</tr>
				</table>
				
				<p class="submit">
					<input type="submit" name="event_rsvp_create_user_submit" class="button button-primary" value="Create User">
				</p>
			</form>
		</div>
		
		<div class="card" style="max-width: 800px; margin-top: 20px;">
			<h2>Plan Descriptions</h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th>Plan</th>
						<th>WordPress Role</th>
						<th>Event Limit</th>
						<th>Can Create Ads</th>
						<th>Description</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><strong>Free Attendee</strong></td>
						<td>Subscriber</td>
						<td>0</td>
						<td>No</td>
						<td>Can browse events and RSVP only</td>
					</tr>
					<tr>
						<td><strong>Pay As You Go</strong></td>
						<td>Event Host</td>
						<td>1</td>
						<td>No</td>
						<td>Can create 1 event</td>
					</tr>
					<tr>
						<td><strong>Event Planner</strong></td>
						<td>Event Host</td>
						<td>5</td>
						<td>No</td>
						<td>Can create up to 5 events</td>
					</tr>
					<tr>
						<td><strong>Event Host</strong></td>
						<td>Event Host</td>
						<td>Unlimited</td>
						<td>No</td>
						<td>Can create unlimited events (legacy plan)</td>
					</tr>
					<tr>
						<td><strong>Vendor</strong></td>
						<td>Vendor</td>
						<td>0</td>
						<td>Yes</td>
						<td>Can create and manage advertisements</td>
					</tr>
					<tr>
						<td><strong>Pro</strong></td>
						<td>Pro</td>
						<td>Unlimited</td>
						<td>Yes</td>
						<td>Can create unlimited events AND manage ads</td>
					</tr>
				</tbody>
			</table>
		</div>
		
		<div class="card" style="max-width: 800px; margin-top: 20px;">
			<h2>Important Notes</h2>
			<ul style="line-height: 1.8;">
				<li><strong>Access Control:</strong> Event Hosts, Vendors, and Pro users are automatically redirected from wp-admin to their frontend dashboards.</li>
				<li><strong>Admin Bar:</strong> The WordPress admin bar is hidden for non-administrator users.</li>
				<li><strong>Event Limits:</strong> Event creation limits are enforced based on the plan. Pay As You Go allows 1 event, Event Planner allows 5 events.</li>
				<li><strong>Plan vs Role:</strong> The "plan" determines event limits, while the "role" determines WordPress capabilities.</li>
				<li><strong>Switching Plans:</strong> Use the "Change Existing User's Role/Plan" form above to update a user's plan and role.</li>
				<li><strong>User Permissions:</strong> Changing a user's role will immediately affect their WordPress capabilities and dashboard access.</li>
			</ul>
		</div>
	</div>
	
	<script>
	jQuery(document).ready(function($) {
		$('#generate_password').on('click', function() {
			var length = 16;
			var charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
			var password = "";
			for (var i = 0; i < length; i++) {
				password += charset.charAt(Math.floor(Math.random() * charset.length));
			}
			$('#password').val(password);
		});
	});
	</script>
	
	<style>
	.required {
		color: red;
	}
	</style>
	<?php
}

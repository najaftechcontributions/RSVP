# Complete Stripe Payment Setup & Troubleshooting Guide

## 🔴 ISSUE: Payment Works But User Stays as Subscriber

If you're experiencing this issue, follow the **Troubleshooting** section below.

---

## Table of Contents

1. [How the System Works](#how-the-system-works)
2. [WordPress Configuration](#wordpress-configuration)
3. [Stripe Dashboard Configuration](#stripe-dashboard-configuration)
4. [Testing the Complete Flow](#testing-the-complete-flow)
5. [Troubleshooting](#troubleshooting)
6. [Common Issues & Solutions](#common-issues--solutions)

---

## How the System Works

### Registration Flow for Paid Plans

```
1. User fills signup form
   ↓
2. WordPress creates account as SUBSCRIBER (free attendee)
   ↓
3. System generates secure token & saves to database
   ↓
4. User redirected to Stripe Payment Link
   ↓
5a. Payment SUCCESS → Redirects to /signup-success/?token=XXX&plan=event_host
   ↓
6. JavaScript automatically calls verification AJAX
   ↓
7. Backend verifies token & UPGRADES role to event_host/vendor/pro
   ↓
8. User redirected to dashboard

   OR

5b. Payment CANCELLED → Redirects to /payment-cancelled/?plan=event_host
   ↓
6. User account remains as free subscriber
   ↓
7. User can retry payment or use free account
```

### Key Database Components

**Table: `wp_event_rsvp_payment_tokens`**
- Stores payment verification tokens
- Status: `pending` or `completed`
- Linked to user_id and plan_slug

**User Meta Fields:**
- `event_rsvp_plan` → attendee, event_host, vendor, or pro
- `event_rsvp_subscription_status` → active, cancelled, suspended
- `event_rsvp_payment_pending` → 1 (deleted after upgrade)
- `event_rsvp_payment_date` → timestamp of successful payment

---

## WordPress Configuration

### Step 1: Verify Required Pages Exist

These pages MUST exist with correct templates:

| Page Slug | Template Name | Purpose |
|-----------|--------------|---------|
| `/signup/` | Signup Page | Registration form |
| `/signup-success/` | Signup Success Page | Payment verification |
| `/payment-cancelled/` | Payment Cancelled | Failed payment handler |
| `/login/` | Login Page | User login |
| `/pricing/` | Pricing Page | Plan selection |
| `/host-dashboard/` | Host Dashboard | Event host dashboard |
| `/vendor-dashboard/` | Vendor Dashboard | Vendor dashboard |
| `/browse-events/` | Browse Events | Event listing |

**How to Create:**
1. Go to **Pages** → **Add New**
2. Set page title (e.g., "Signup Success")
3. Set page slug (e.g., `signup-success`)
4. Select template from **Page Attributes** → **Template** dropdown
5. Publish

### Step 2: Configure Stripe Payment Links

1. Go to **WordPress Admin** → **Settings** → **Stripe Payments**
2. You should see three fields for payment links:
   - Event Host Plan Link
   - Vendor Plan Link
   - Pro Plan Link
3. **Leave these EMPTY for now** (we'll fill them after creating Stripe payment links)

### Step 3: Verify Database Table

Run this SQL query in **phpMyAdmin** or **Adminer**:

```sql
SHOW TABLES LIKE 'wp_event_rsvp_payment_tokens';
```

If table doesn't exist, the plugin should create it automatically. If not, run:

```sql
CREATE TABLE wp_event_rsvp_payment_tokens (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    token varchar(255) NOT NULL,
    plan_slug varchar(20) NOT NULL,
    status varchar(20) DEFAULT 'pending',
    created_at datetime NOT NULL,
    completed_at datetime DEFAULT NULL,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Stripe Dashboard Configuration

### Step 1: Create Products in Stripe

1. Log in to [Stripe Dashboard](https://dashboard.stripe.com)
2. Switch to **Test Mode** (toggle in top right) for testing
3. Go to **Product catalog** → Click **Add product**

#### Create Event Host Product

1. **Product information:**
   - Name: `Event Host Subscription`
   - Description: `Create and manage unlimited events`

2. **Pricing:**
   - Pricing model: `Standard pricing`
   - Price: `$19.00 USD`
   - Billing period: `Monthly`
   - Type: `Recurring`

3. Click **Save product**

#### Create Vendor Product

Repeat above with:
- Name: `Vendor Subscription`
- Description: `Advertise your business or services`
- Price: `$29.00 USD`
- Billing period: `Monthly`

#### Create Pro Product

Repeat above with:
- Name: `Pro Subscription (Host + Vendor)`
- Description: `Host events AND advertise - Best value!`
- Price: `$39.00 USD`
- Billing period: `Monthly`

### Step 2: Create Payment Links

For **EACH** product you just created:

1. Go to **Payment links** → Click **New**
2. Select the product (e.g., Event Host Subscription)
3. **Configure the payment link:**

   **CRITICAL SETTINGS:**
   
   - **After payment:** Select `Don't show confirmation page`
   - **Success page:** Click `Redirect to your website`
     - Enter: `https://yoursite.com/signup-success/`
     - ⚠️ **IMPORTANT:** Use your actual domain
   
   - **Cancellation page:** Click `Redirect to your website`
     - Enter: `https://yoursite.com/payment-cancelled/`
     - ⚠️ **IMPORTANT:** Use your actual domain

   - **Allow promotion codes:** (optional) Enable if you want coupon codes
   - **Collect customer's billing address:** (optional) Enable if required
   - **Collect customer's phone number:** (optional)

4. Click **Create link**
5. **COPY THE PAYMENT LINK URL**
   - It will look like: `https://buy.stripe.com/test_xxxxxxxxxxxxx`

6. Repeat for all three products

### Step 3: Configure Payment Links in WordPress

1. Go back to **WordPress Admin** → **Settings** → **Stripe Payments**
2. Paste the payment link URLs:
   - Event Host Plan Link: `https://buy.stripe.com/test_xxxxx` (Event Host link)
   - Vendor Plan Link: `https://buy.stripe.com/test_xxxxx` (Vendor link)
   - Pro Plan Link: `https://buy.stripe.com/test_xxxxx` (Pro link)
3. Click **Save Changes**

---

## Testing the Complete Flow

### Test 1: Payment Success Flow

1. **Start Registration:**
   - Go to `/pricing/` on your site
   - Click "Start Hosting" or choose a paid plan
   - Fill out the signup form completely
   - Click "Create Account & Proceed to Payment"

2. **Verify Account Creation:**
   - Open another browser tab
   - Go to **WordPress Admin** → **Users**
   - Find the newly created user
   - **Verify:** Role should be **Subscriber**
   - **Verify:** User meta should have `event_rsvp_payment_pending = 1`

3. **Complete Payment on Stripe:**
   - Use test card: `4242 4242 4242 4242`
   - Any future expiry date (e.g., 12/34)
   - Any 3-digit CVC (e.g., 123)
   - Any ZIP code (e.g., 12345)
   - Complete the payment

4. **Verify Redirect:**
   - You should be redirected to: `yoursite.com/signup-success/?payment_success=1&token=XXXXX&plan=event_host`
   - You should see a **spinner** with "Processing Your Payment..."

5. **Watch Browser Console:**
   - Open browser DevTools (F12)
   - Go to **Console** tab
   - Watch for AJAX call to `wp-admin/admin-ajax.php`
   - Should show success response

6. **Verify Upgrade:**
   - After 2-3 seconds, page should show green checkmark
   - You should be redirected to `/host-dashboard/`
   - Go to **WordPress Admin** → **Users**
   - Find the user
   - **Verify:** Role should now be **Event Host** (or Vendor/Pro)
   - **Verify:** User meta `event_rsvp_plan` = `event_host`
   - **Verify:** User meta `event_rsvp_subscription_status` = `active`
   - **Verify:** `event_rsvp_payment_pending` should be DELETED

7. **Verify Database:**
   - Go to **phpMyAdmin** → `wp_event_rsvp_payment_tokens` table
   - Find the token record
   - **Verify:** Status should be `completed`
   - **Verify:** `completed_at` should have a timestamp

### Test 2: Payment Cancellation Flow

1. Start registration with a paid plan
2. On Stripe payment page, click **← Back** or close the window
3. You should be redirected to `/payment-cancelled/`
4. User account should exist as **Subscriber**
5. User can log in with created credentials

---

## Troubleshooting

### Issue: Payment Works But User Stays as Subscriber

This is the most common issue. Here's the systematic debug process:

#### Debug Step 1: Check Success URL Configuration

**In Stripe Dashboard:**

1. Go to **Payment links**
2. Click on your Event Host payment link
3. Check **After payment** settings:
   - ✅ Should redirect to: `https://yoursite.com/signup-success/`
   - ❌ Should NOT show Stripe confirmation page

**Why this matters:** If Stripe shows its own confirmation page, the user never reaches your verification page.

#### Debug Step 2: Verify Token is Generated

**Check Database:**

```sql
SELECT * FROM wp_event_rsvp_payment_tokens 
ORDER BY created_at DESC 
LIMIT 10;
```

**What to look for:**
- Token record exists for the user
- `status` is `pending` (not `completed`)
- `plan_slug` matches the plan they selected
- `created_at` is recent

**If no token exists:**
- Problem is in account creation step
- Check PHP error logs
- Verify `simple-stripe-payments.php` is loaded

#### Debug Step 3: Check URL Parameters

**When redirected to /signup-success/, check URL:**

```
https://yoursite.com/signup-success/?payment_success=1&token=XXXXX&plan=event_host
```

**All three parameters must be present:**
- `payment_success=1`
- `token=XXXXX` (long random string)
- `plan=event_host` (or vendor/pro)

**If token parameter is missing:**
- Stripe is not passing it through
- Check Stripe payment link configuration
- **SOLUTION:** Manually configure success URL in Stripe to:
  ```
  https://yoursite.com/signup-success/?payment_success=1
  ```
  The system will append `&token=XXX&plan=XXX` automatically

#### Debug Step 4: Check JavaScript Verification

**Open Browser Console (F12) on /signup-success/ page:**

Look for:
1. AJAX call to `/wp-admin/admin-ajax.php`
2. Request payload should include:
   ```javascript
   action: "event_rsvp_verify_payment_token"
   nonce: "xxxxxxxxxx"
   token: "the-token-from-url"
   plan: "event_host"
   ```

3. Response should be:
   ```json
   {
     "success": true,
     "data": {
       "message": "Payment verified! Your account has been upgraded.",
       "redirect": "https://yoursite.com/host-dashboard/?welcome=1"
     }
   }
   ```

**If AJAX call fails:**
- Check for JavaScript errors in console
- Verify nonce is valid (might be cached)
- Check if user is logged in (should auto-login after account creation)

**If response shows error:**
- Copy the error message
- Check PHP error logs
- Proceed to Debug Step 5

#### Debug Step 5: Check AJAX Handler

**Add debug logging to `rsvpplugin/includes/simple-stripe-ajax.php`:**

Find the `event_rsvp_verify_payment_token()` function and add logging:

```php
function event_rsvp_verify_payment_token() {
    error_log('=== TOKEN VERIFICATION START ===');
    error_log('POST data: ' . print_r($_POST, true));
    
    check_ajax_referer('event_rsvp_verify_token', 'nonce');
    
    $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
    $plan = isset($_POST['plan']) ? sanitize_text_field($_POST['plan']) : '';
    
    error_log('Token: ' . $token);
    error_log('Plan: ' . $plan);
    
    if (empty($token) || empty($plan)) {
        error_log('ERROR: Token or plan is empty');
        wp_send_json_error('Invalid verification data.');
        return;
    }
    
    $stripe = Event_RSVP_Simple_Stripe::get_instance();
    $result = $stripe->verify_payment_and_upgrade($token, $plan);
    
    error_log('Verification result: ' . print_r($result, true));
    error_log('=== TOKEN VERIFICATION END ===');
    
    // ... rest of function
}
```

**Check PHP error log** (usually in `wp-content/debug.log` if `WP_DEBUG_LOG` is enabled)

#### Debug Step 6: Check Token Verification Logic

**Add debug logging to `rsvpplugin/includes/simple-stripe-payments.php`:**

Find the `verify_payment_and_upgrade()` function:

```php
public function verify_payment_and_upgrade($token, $plan_slug) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'event_rsvp_payment_tokens';
    
    error_log('=== VERIFY AND UPGRADE START ===');
    error_log('Looking for plan: ' . $plan_slug);
    
    // Get all pending tokens for this plan
    $tokens = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE plan_slug = %s AND status = 'pending' ORDER BY created_at DESC LIMIT 50",
        $plan_slug
    ));
    
    error_log('Found ' . count($tokens) . ' pending tokens');
    
    foreach ($tokens as $token_row) {
        error_log('Checking token ID: ' . $token_row->id);
        
        if (wp_check_password($token, $token_row->token)) {
            error_log('TOKEN MATCH! User ID: ' . $token_row->user_id);
            
            // ... existing upgrade logic ...
            
            error_log('User upgraded successfully');
            return array(
                'success' => true,
                'user_id' => $user_id,
                'plan' => $plan_slug
            );
        } else {
            error_log('Token does not match token ID: ' . $token_row->id);
        }
    }
    
    error_log('ERROR: No matching token found');
    error_log('=== VERIFY AND UPGRADE END ===');
    
    return array('success' => false, 'message' => 'Invalid or expired token');
}
```

**What to look for in logs:**
- Number of pending tokens found (should be at least 1)
- Whether token matches
- If user is upgraded
- Any SQL errors

#### Debug Step 7: Manual Token Verification

**If automated verification fails, manually upgrade the user:**

```php
// Add this to functions.php temporarily
add_action('init', function() {
    if (isset($_GET['manual_upgrade']) && current_user_can('administrator')) {
        $user_id = intval($_GET['user_id']);
        $plan = sanitize_text_field($_GET['plan']);
        
        if ($user_id && $plan) {
            wp_update_user(array(
                'ID' => $user_id,
                'role' => $plan
            ));
            
            update_user_meta($user_id, 'event_rsvp_plan', $plan);
            update_user_meta($user_id, 'event_rsvp_subscription_status', 'active');
            delete_user_meta($user_id, 'event_rsvp_payment_pending');
            update_user_meta($user_id, 'event_rsvp_payment_date', current_time('mysql'));
            
            echo 'User upgraded to ' . $plan;
            exit;
        }
    }
});
```

**Usage:**
```
https://yoursite.com/?manual_upgrade=1&user_id=123&plan=event_host
```

(Only accessible to administrators)

---

## Common Issues & Solutions

### Issue 1: "Invalid or expired token" Error

**Causes:**
- Token wasn't generated during account creation
- Token was already used (status is 'completed')
- Token hash doesn't match
- Wrong plan selected

**Solutions:**
1. Check database for token record
2. Verify token status is 'pending'
3. Ensure plan_slug matches exactly
4. Try creating a new account and test again

### Issue 2: User Not Auto-Logged In After Registration

**Symptoms:**
- User redirected to Stripe but not logged in
- After payment success, verification fails due to authentication

**Solution:**

Verify in `simple-stripe-payments.php` → `create_attendee_account()`:

```php
// Auto-login the user
$user = get_user_by('id', $user_id);
if ($user) {
    wp_set_current_user($user_id, $user->user_login);
    wp_set_auth_cookie($user_id, true);
    do_action('wp_login', $user->user_login, $user);
}
```

This code **must** execute before redirect to Stripe.

### Issue 3: Stripe Shows Error "Invalid redirect URL"

**Cause:**
- Success URL or Cancel URL contains special characters
- URL is not properly encoded

**Solution:**

In Stripe Payment Link settings:
- Use exact URL: `https://yoursite.com/signup-success/`
- Use exact URL: `https://yoursite.com/payment-cancelled/`
- **Do NOT** add query parameters manually
- The system appends parameters programmatically

### Issue 4: Multiple Accounts Created for Same User

**Cause:**
- User clicks "Submit" multiple times
- Account creation runs before checking for existing accounts

**Solution:**

Add to `simple-stripe-payments.php` → `create_attendee_account()`:

```php
// Check if email ALREADY has an account
if (email_exists($user_data['email'])) {
    // Find existing user
    $user = get_user_by('email', $user_data['email']);
    
    // Check if they have payment pending
    $payment_pending = get_user_meta($user->ID, 'event_rsvp_payment_pending', true);
    
    if ($payment_pending) {
        // Reuse existing account instead of creating new
        return $user->ID;
    }
    
    return new WP_Error('email_exists', 'Email already exists.');
}
```

### Issue 5: Email Not Being Sent

**Causes:**
- WordPress mail function not working
- No SMTP configured
- Email blocked by server

**Solution:**

Install SMTP plugin:
1. Install **WP Mail SMTP** plugin
2. Configure with Gmail/SendGrid/Mailgun credentials
3. Send test email

Or use transactional email service:
- SendGrid
- Mailgun
- Amazon SES

### Issue 6: Token Verification Loops Forever

**Symptoms:**
- Spinner keeps spinning on /signup-success/
- Never shows success or error
- Console shows repeated AJAX calls

**Cause:**
- AJAX returns success: false
- JavaScript retries up to 10 times
- After 10 attempts, shows error

**Solution:**
- Check AJAX response in browser console
- Look for specific error message
- Check PHP error logs
- Verify token exists in database
- Ensure user is logged in

---

## Advanced Configuration

### Enable Webhooks (Optional - For Production)

While the current system uses redirect-based verification, you can add webhooks for better reliability:

#### Create Webhook Endpoint

**Add to `rsvpplugin/includes/stripe-webhook.php`:**

```php
<?php
/**
 * Stripe Webhook Handler
 */

if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function() {
    register_rest_route('event-rsvp/v1', '/stripe-webhook', array(
        'methods' => 'POST',
        'callback' => 'event_rsvp_handle_stripe_webhook',
        'permission_callback' => '__return_true'
    ));
});

function event_rsvp_handle_stripe_webhook($request) {
    $payload = $request->get_body();
    $sig_header = $request->get_header('stripe-signature');
    $endpoint_secret = get_option('stripe_webhook_secret');
    
    try {
        $event = \Stripe\Webhook::constructEvent(
            $payload, $sig_header, $endpoint_secret
        );
    } catch(\UnexpectedValueException $e) {
        return new WP_Error('invalid_payload', $e->getMessage(), array('status' => 400));
    } catch(\Stripe\Exception\SignatureVerificationException $e) {
        return new WP_Error('invalid_signature', $e->getMessage(), array('status' => 400));
    }
    
    // Handle different event types
    switch ($event->type) {
        case 'checkout.session.completed':
            $session = $event->data->object;
            // Get customer email and upgrade account
            $email = $session->customer_email;
            $user = get_user_by('email', $email);
            
            if ($user) {
                // Upgrade user based on product purchased
                // ... upgrade logic here
            }
            break;
            
        case 'customer.subscription.deleted':
            // Downgrade user when subscription cancelled
            break;
    }
    
    return array('received' => true);
}
```

#### Configure Webhook in Stripe

1. Go to **Stripe Dashboard** → **Developers** → **Webhooks**
2. Click **Add endpoint**
3. Endpoint URL: `https://yoursite.com/wp-json/event-rsvp/v1/stripe-webhook`
4. Select events:
   - `checkout.session.completed`
   - `customer.subscription.created`
   - `customer.subscription.deleted`
   - `customer.subscription.updated`
5. Copy **Signing secret**
6. Add to WordPress: **Settings** → **Stripe Payments** → **Webhook Secret**

### Set Up Customer Portal

Allow customers to manage subscriptions:

1. Go to **Stripe Dashboard** → **Settings** → **Customer portal**
2. Enable portal
3. Configure cancellation settings
4. Add portal link to user dashboard:

```php
// In user dashboard template
$customer_id = get_user_meta(get_current_user_id(), 'stripe_customer_id', true);
if ($customer_id) {
    echo '<a href="https://billing.stripe.com/p/login/YOUR_ID">Manage Subscription</a>';
}
```

---

## Production Checklist

Before going live:

- [ ] Test complete signup flow with test cards
- [ ] Test payment cancellation flow
- [ ] Verify all emails are being sent
- [ ] Check all user roles upgrade correctly
- [ ] Verify database tokens are being cleaned up
- [ ] Switch Stripe from Test Mode to Live Mode
- [ ] Create Live Mode payment links
- [ ] Update payment links in WordPress settings
- [ ] Test with real credit card (small amount)
- [ ] Set up proper SMTP for emails
- [ ] Enable SSL/HTTPS on entire site
- [ ] Configure backup for `wp_event_rsvp_payment_tokens` table
- [ ] Set up monitoring for failed payments
- [ ] Create refund policy page
- [ ] Add terms of service and privacy policy
- [ ] Test subscription management/cancellation
- [ ] Document customer support procedures

---

## Support Resources

**Stripe Documentation:**
- [Payment Links Guide](https://stripe.com/docs/payment-links)
- [Test Cards](https://stripe.com/docs/testing)
- [Webhooks](https://stripe.com/docs/webhooks)
- [Customer Portal](https://stripe.com/docs/billing/subscriptions/integrating-customer-portal)

**WordPress Functions:**
- `wp_create_user()` - Create new user
- `wp_update_user()` - Update user data
- `update_user_meta()` - Store user metadata
- `wp_send_json_success()` - Send AJAX success response

**Database Functions:**
- `$wpdb->insert()` - Insert database record
- `$wpdb->update()` - Update database record
- `$wpdb->prepare()` - Prepare SQL query (prevents injection)

---

## Quick Reference

### User Roles

| Role | Capabilities | Plan |
|------|-------------|------|
| subscriber | Browse events, RSVP | Free Attendee |
| event_host | Create events + subscriber | Event Host ($19/mo) |
| vendor | Post ads + subscriber | Vendor ($29/mo) |
| pro | Events + ads + subscriber | Pro ($39/mo) |

### User Meta Keys

```php
get_user_meta($user_id, 'event_rsvp_plan', true);
// Returns: attendee, event_host, vendor, or pro

get_user_meta($user_id, 'event_rsvp_subscription_status', true);
// Returns: active, cancelled, suspended, or empty

get_user_meta($user_id, 'event_rsvp_payment_date', true);
// Returns: MySQL datetime or empty

get_user_meta($user_id, 'event_rsvp_payment_pending', true);
// Returns: 1 or empty (deleted after payment)
```

### Important URLs

```
Signup: /signup/?plan=event_host
Success: /signup-success/?payment_success=1&token=XXX&plan=event_host
Cancelled: /payment-cancelled/?plan=event_host
Pricing: /pricing/
Login: /login/
Host Dashboard: /host-dashboard/
Vendor Dashboard: /vendor-dashboard/
```

### Database Queries

**Check pending tokens:**
```sql
SELECT * FROM wp_event_rsvp_payment_tokens 
WHERE status = 'pending' 
ORDER BY created_at DESC;
```

**Find user's plan:**
```sql
SELECT user_id, meta_key, meta_value 
FROM wp_usermeta 
WHERE meta_key LIKE 'event_rsvp%' 
AND user_id = 123;
```

**Count users by plan:**
```sql
SELECT meta_value as plan, COUNT(*) as count 
FROM wp_usermeta 
WHERE meta_key = 'event_rsvp_plan' 
GROUP BY meta_value;
```

---

## Need Help?

If you're still experiencing issues after following this guide:

1. **Enable WordPress debug logging:**
   ```php
   // Add to wp-config.php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```

2. **Check logs:**
   - `wp-content/debug.log` (PHP errors)
   - Browser console (JavaScript errors)
   - Stripe Dashboard → **Logs** (API calls)

3. **Provide this information when asking for help:**
   - WordPress version
   - PHP version
   - Stripe account region (US/EU/etc)
   - Error messages from logs
   - Screenshots of Stripe payment link configuration
   - Database table structure of `wp_event_rsvp_payment_tokens`
   - User meta values for a test user

---

**Last Updated:** December 2024
**Version:** 2.0
**System:** WordPress + Stripe Payment Links Integration

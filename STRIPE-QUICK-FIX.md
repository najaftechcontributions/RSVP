# 🚨 QUICK FIX: Payment Works But User Stays Subscriber

## Problem

Payment is processed successfully in Stripe, but the user role doesn't upgrade from Subscriber to Event Host/Vendor/Pro.

## Most Common Causes (in order of likelihood)

### 1. ❌ Stripe Success URL Not Configured Correctly

**Check This First:**

1. Log in to [Stripe Dashboard](https://dashboard.stripe.com)
2. Go to **Payment links**
3. Click on your Event Host payment link
4. Check the **After payment** section

**WRONG Configuration:**
- Shows: "Show confirmation page on Stripe"
- Or: Success URL is blank
- Or: Success URL doesn't include your domain

**CORRECT Configuration:**
- After payment: **Redirect to your website**
- Success URL: `https://yoursite.com/signup-success/`
- Cancellation URL: `https://yoursite.com/payment-cancelled/`

**How to Fix:**
1. Edit the payment link
2. Under "After payment" select "Redirect to your website"
3. Enter: `https://YOURSITE.com/signup-success/` (use your actual domain)
4. Enter cancel URL: `https://YOURSITE.com/payment-cancelled/`
5. Save changes
6. **Repeat for all three payment links** (Event Host, Vendor, Pro)

---

### 2. ❌ Token Parameter Not Passing Through URL

**How to Check:**

After completing a test payment, look at the URL you're redirected to:

**Should look like:**
```
https://yoursite.com/signup-success/?payment_success=1&token=abcd1234xyz&plan=event_host
```

**Problem if it looks like:**
```
https://yoursite.com/signup-success/
```
(No parameters at all)

**How to Fix:**

The system should automatically append the token. If it's not appearing:

1. Go to **WordPress Admin** → **Settings** → **Stripe Payments**
2. Re-save your payment links (click Save Changes even if they look correct)
3. Try a new signup

If still not working, check the code in `rsvpplugin/includes/simple-stripe-payments.php` around line 67:

```php
// This code should exist:
$success_url = add_query_arg(array(
    'payment_success' => '1',
    'token' => $token,
    'plan' => $plan_slug
), home_url('/signup-success/'));
```

---

### 3. ❌ JavaScript Not Running on Success Page

**How to Check:**

1. Complete a test signup
2. On the `/signup-success/` page, press **F12** (opens browser console)
3. Look for errors in the Console tab

**Common Errors:**
- "Nonce verification failed"
- "AJAX request failed"
- "Token is undefined"

**How to Fix:**

**If you see nonce error:**
- Clear your browser cache
- Try in incognito/private window
- Check if you're logged in (you should be auto-logged in after signup)

**If AJAX is blocked:**
- Check if admin-ajax.php is accessible: `yoursite.com/wp-admin/admin-ajax.php`
- Verify no security plugins are blocking AJAX
- Check .htaccess for rewrite rules blocking admin-ajax

**If token is undefined:**
- Go back to Fix #2 above

---

### 4. ❌ Database Table Missing

**How to Check:**

Go to **phpMyAdmin** → Select your WordPress database → Look for table:
```
wp_event_rsvp_payment_tokens
```

**If table doesn't exist:**

Run this SQL query:

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 5. ❌ Required Pages Missing or Wrong Template

**How to Check:**

1. Go to **WordPress Admin** → **Pages**
2. Find page: **Signup Success**
3. Click **Edit**
4. Check **Page Attributes** → **Template**
5. Should say: **Signup Success Page**

**If wrong template or page doesn't exist:**

1. Create new page
2. Title: `Signup Success`
3. Slug: `signup-success`
4. Template: Select "Signup Success Page" from dropdown
5. Publish
6. **Repeat for:**
   - Payment Cancelled (slug: `payment-cancelled`, template: Payment Cancelled)

---

### 6. ❌ User Not Auto-Logged In After Account Creation

**How to Check:**

After filling signup form and clicking submit:
1. Open browser DevTools (F12)
2. Go to **Application** tab (Chrome) or **Storage** tab (Firefox)
3. Look for **Cookies** → your domain
4. Look for `wordpress_logged_in_...` cookie

**If cookie doesn't exist:**
- User wasn't auto-logged in
- Verification will fail because user is not authenticated

**How to Fix:**

Check `rsvpplugin/includes/simple-stripe-payments.php` → function `create_attendee_account()`:

This code MUST exist around line 120-125:

```php
// Auto-login the user
$user = get_user_by('id', $user_id);
if ($user) {
    wp_set_current_user($user_id, $user->user_login);
    wp_set_auth_cookie($user_id, true);
    do_action('wp_login', $user->user_login, $user);
}
```

If missing, add it before the return statement.

---

## Quick Test Procedure

### Complete Test Flow (5 minutes)

1. **Clear Everything:**
   - Clear browser cache
   - Open incognito window
   - Clear any test users from **WordPress Admin** → **Users**

2. **Start Fresh Signup:**
   - Go to `/pricing/` on your site
   - Click a paid plan (e.g., Event Host)
   - Fill signup form with NEW email (not used before)
   - Click submit

3. **Check Account Creation:**
   - Open new tab → **WordPress Admin** → **Users**
   - Find the new user
   - Role should be: **Subscriber**
   - User meta: `event_rsvp_payment_pending` should be `1`

4. **Complete Payment:**
   - Use Stripe test card: `4242 4242 4242 4242`
   - Expiry: any future date (e.g., 12/34)
   - CVC: any 3 digits (e.g., 123)
   - Click pay

5. **Watch Redirect:**
   - Should go to: `/signup-success/?payment_success=1&token=XXX&plan=event_host`
   - Should see spinner: "Processing Your Payment..."
   - Watch browser console (F12) for AJAX call
   - After 2-3 seconds: should show green checkmark
   - Should redirect to: `/host-dashboard/`

6. **Verify Upgrade:**
   - **WordPress Admin** → **Users** → Find user
   - Role should be: **Event Host** (not Subscriber)
   - User meta: `event_rsvp_plan` = `event_host`
   - User meta: `event_rsvp_subscription_status` = `active`
   - User meta: `event_rsvp_payment_pending` = (should be deleted/empty)

7. **Check Database:**
   - **phpMyAdmin** → `wp_event_rsvp_payment_tokens`
   - Find the record
   - `status` should be: `completed`
   - `completed_at` should have timestamp

---

## Immediate Manual Fix (If Automated Fails)

If you have users stuck as Subscribers who already paid, manually upgrade them:

### Option 1: WordPress Admin (Easiest)

1. Go to **WordPress Admin** → **Users**
2. Find the user
3. Click **Edit**
4. **Role:** Change from "Subscriber" to "Event Host" (or Vendor/Pro)
5. Scroll down to **Custom Fields**
6. Add/Update these meta fields:
   - `event_rsvp_plan` → `event_host` (or `vendor` or `pro`)
   - `event_rsvp_subscription_status` → `active`
   - Delete `event_rsvp_payment_pending` if it exists
7. Click **Update User**

### Option 2: SQL Query (Faster for Multiple Users)

**Get the user ID first**, then run:

```sql
-- Update user role
UPDATE wp_users 
SET user_login = user_login  -- dummy update to enable join
WHERE ID = 123; -- replace 123 with actual user ID

UPDATE wp_usermeta 
SET meta_value = 'a:1:{s:10:"event_host";b:1;}' 
WHERE user_id = 123 AND meta_key = 'wp_capabilities';

-- Update plan meta
UPDATE wp_usermeta 
SET meta_value = 'event_host' 
WHERE user_id = 123 AND meta_key = 'event_rsvp_plan';

-- Add subscription status
INSERT INTO wp_usermeta (user_id, meta_key, meta_value) 
VALUES (123, 'event_rsvp_subscription_status', 'active')
ON DUPLICATE KEY UPDATE meta_value = 'active';

-- Remove payment pending
DELETE FROM wp_usermeta 
WHERE user_id = 123 AND meta_key = 'event_rsvp_payment_pending';
```

**For Vendor role, use:**
```sql
SET meta_value = 'a:1:{s:6:"vendor";b:1;}'
```

**For Pro role, use:**
```sql
SET meta_value = 'a:1:{s:3:"pro";b:1;}'
```

### Option 3: PHP Function (Add to functions.php temporarily)

```php
// Add this to functions.php
add_action('init', function() {
    // Only allow admins and only via specific URL
    if (!current_user_can('administrator')) {
        return;
    }
    
    if (isset($_GET['upgrade_user_now'])) {
        $user_id = intval($_GET['user_id']);
        $plan = sanitize_text_field($_GET['plan']); // event_host, vendor, or pro
        
        if (!$user_id || !in_array($plan, ['event_host', 'vendor', 'pro'])) {
            wp_die('Invalid parameters');
        }
        
        // Update role
        wp_update_user(array(
            'ID' => $user_id,
            'role' => $plan
        ));
        
        // Update meta
        update_user_meta($user_id, 'event_rsvp_plan', $plan);
        update_user_meta($user_id, 'event_rsvp_subscription_status', 'active');
        update_user_meta($user_id, 'event_rsvp_payment_date', current_time('mysql'));
        delete_user_meta($user_id, 'event_rsvp_payment_pending');
        
        echo '<h1>✅ User #' . $user_id . ' upgraded to ' . $plan . '</h1>';
        echo '<p><a href="/wp-admin/users.php">Back to Users</a></p>';
        exit;
    }
});
```

**Usage (replace values):**
```
https://yoursite.com/?upgrade_user_now=1&user_id=123&plan=event_host
```

---

## Checklist: What to Check Right Now

Copy this checklist and check each item:

```
[ ] Stripe payment links have success URL set to: yoursite.com/signup-success/
[ ] Stripe payment links have cancel URL set to: yoursite.com/payment-cancelled/
[ ] After payment redirect is set to "your website" (not Stripe confirmation page)
[ ] WordPress page /signup-success/ exists with correct template
[ ] WordPress page /payment-cancelled/ exists with correct template
[ ] Database table wp_event_rsvp_payment_tokens exists
[ ] Payment links are saved in WP Admin → Settings → Stripe Payments
[ ] Test user was auto-logged in after signup (check for login cookie)
[ ] URL after payment includes: ?payment_success=1&token=xxx&plan=xxx
[ ] Browser console shows AJAX call to admin-ajax.php
[ ] AJAX response shows success: true
[ ] Database token status changes from 'pending' to 'completed'
[ ] User role changes from 'subscriber' to 'event_host' (or vendor/pro)
```

---

## Enable Debug Logging

Add this to `wp-config.php` to see detailed logs:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
@ini_set('display_errors', 0);
```

Logs will be saved to: `wp-content/debug.log`

Watch for lines like:
```
=== TOKEN VERIFICATION START ===
Token: abcd1234xyz
Plan: event_host
Found 1 pending tokens
TOKEN MATCH! User ID: 123
User upgraded successfully
=== TOKEN VERIFICATION END ===
```

If you see errors, that's your smoking gun.

---

## Still Not Working?

### Verify These Files Exist and Are Loaded:

1. `rsvpplugin/includes/simple-stripe-payments.php`
2. `rsvpplugin/includes/simple-stripe-ajax.php`
3. `rsvpplugin/event-rsvp-plugin.php` (should require the above files)

### Check Plugin is Active:

Go to **Plugins** → ensure plugin is activated

### Check for Conflicts:

1. Deactivate all other plugins except Event RSVP plugin
2. Try signup again
3. If it works, reactivate plugins one by one to find the conflict

### Common Conflicting Plugins:

- **Security plugins** that block AJAX (Wordfence, Sucuri, etc.)
- **Caching plugins** that cache AJAX requests
- **Membership plugins** that override user creation (MemberPress, Paid Memberships Pro)
- **Other payment plugins** that register conflicting AJAX actions

---

## Need More Help?

If you've tried everything above and it's still not working:

1. **Export this info:**
   - WordPress version
   - PHP version (Hosting → Server Info)
   - Active plugins list
   - Theme name and version

2. **Provide logs:**
   - Copy content from `wp-content/debug.log`
   - Copy browser console errors (F12 → Console tab)
   - Screenshot of Stripe payment link configuration

3. **Database info:**
   ```sql
   -- Run these and provide results:
   
   SELECT COUNT(*) FROM wp_event_rsvp_payment_tokens;
   
   SELECT * FROM wp_event_rsvp_payment_tokens 
   WHERE status = 'pending' 
   ORDER BY created_at DESC LIMIT 5;
   
   SELECT user_id, meta_key, meta_value 
   FROM wp_usermeta 
   WHERE meta_key LIKE 'event_rsvp%' 
   AND user_id = 123; -- replace with test user ID
   ```

---

**Remember:** The system creates a FREE account FIRST (subscriber), then upgrades after payment. This is by design so users don't lose access if payment fails. If payment works but upgrade doesn't, the issue is in the token verification step.

**Quick Summary:**
1. ✅ Payment processed in Stripe → Working
2. ✅ User account created as Subscriber → Working
3. ❌ User role not upgraded after payment → THIS is the problem
4. 🎯 Solution: Fix the redirect URL and token verification

Start with **Fix #1** above - that solves 80% of cases.

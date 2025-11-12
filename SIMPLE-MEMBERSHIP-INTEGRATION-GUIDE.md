# Simple Membership Integration Guide
## Event RSVP Platform + Simple Membership Plugin

**Version:** 1.0  
**Last Updated:** December 2024  
**Plugin:** Simple Membership v4.6.8

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Installation Steps](#installation-steps)
4. [Simple Membership Setup](#simple-membership-setup)
5. [Membership Level Configuration](#membership-level-configuration)
6. [Payment Gateway Setup](#payment-gateway-setup)
7. [Integration with Event RSVP](#integration-with-event-rsvp)
8. [Signup Flow Modification](#signup-flow-modification)
9. [Role Assignment After Payment](#role-assignment-after-payment)
10. [Testing Checklist](#testing-checklist)
11. [Troubleshooting](#troubleshooting)
12. [Advanced Customization](#advanced-customization)

---

## 🎯 Overview

This guide will help you integrate the **Simple Membership** plugin with your Event RSVP Platform to handle:
- ✅ User registration and payments
- ✅ Subscription management
- ✅ Role assignment after successful payment
- ✅ Stripe payment integration (replacing custom integration)
- ✅ Automated account creation and activation

### What Simple Membership Provides

- Complete membership management system
- Multiple payment gateway support (Stripe, PayPal, etc.)
- Subscription and one-time payment options
- Member management dashboard
- Content protection and access control
- Email templates and automation
- Member login/registration forms

---

## ✅ Prerequisites

### Required Plugins

1. **Simple Membership** (v4.6.8 or higher)
   - Download: WordPress Plugin Directory
   - Author: smp7, wp.insider

2. **Stripe Payment Gateway Add-on** (for Simple Membership)
   - Required for Stripe payments
   - Integrated with Simple Membership

### Required Information

- [ ] Stripe API Keys (Secret & Publishable)
- [ ] Your website domain
- [ ] Admin email address
- [ ] Membership pricing structure

---

## 📦 Installation Steps

### Step 1: Install Simple Membership Plugin

```bash
1. Log into WordPress Admin
2. Navigate to: Plugins > Add New
3. Search: "Simple Membership"
4. Click: Install Now (by smp7, wp.insider)
5. Click: Activate
```

### Step 2: Install Stripe Add-on

```bash
1. Navigate to: Simple Membership > Payment Settings
2. Click on "Stripe" tab
3. Follow the installation prompt if needed
4. Or download from: https://simple-membership-plugin.com/
```

### Step 3: Verify Installation

```bash
✓ Check that "Simple Membership" appears in admin menu
✓ Navigate to: Simple Membership > Settings
✓ Verify settings page loads correctly
```

---

## ⚙️ Simple Membership Setup

### General Settings

Navigate to: **Simple Membership > Settings > General**

```
✓ Enable Free Membership: NO (we handle free attendees separately)
✓ Enable Registration: YES
✓ Enable More Tag Protection: NO
✓ Enable Comment Protection: NO
✓ Enable Debug: NO (only enable for troubleshooting)
```

### Pages Configuration

Navigate to: **Simple Membership > Settings > Pages**

Create and assign these pages:

| Page Purpose | Template | Shortcode |
|-------------|----------|-----------|
| Registration | page-signup.php | `[swpm_registration_form]` |
| Login | page-login.php | `[swpm_login_form]` |
| Profile | page-profile.php | `[swpm_profile_form]` |
| Password Reset | page-reset.php | `[swpm_reset_form]` |
| Join Us | page-join.php | `[swpm_payment_button]` |

**Important:** Our custom signup page will integrate with Simple Membership forms.

### Email Settings

Navigate to: **Simple Membership > Settings > Email**

Configure these email templates:

1. **Registration Complete Email**
   - Subject: `Welcome to {site_name} - Account Created!`
   - Enable: ✓ YES

2. **Email Activation**
   - Subject: `Activate Your Account at {site_name}`
   - Enable: ✓ YES (if using email verification)

3. **Upgrade Notification**
   - Subject: `Your Subscription Has Been Upgraded`
   - Enable: ✓ YES

---

## 🎫 Membership Level Configuration

Navigate to: **Simple Membership > Membership Levels**

### Create 4 Membership Levels

#### 1. Free Attendee (Level ID: 1)

```
Membership Level Name: Free Attendee
Default WordPress Role: Subscriber
Subscription Duration: No Expiry
Subscription Period: (leave empty for lifetime)
Access Starts: Immediately After Registration
```

**Features:**
- Browse events
- RSVP to events
- Receive QR codes
- No payment required

---

#### 2. Event Host (Level ID: 2)

```
Membership Level Name: Event Host
Default WordPress Role: event_host (custom role)
Subscription Duration: 1
Subscription Period: Months
Access Starts: Immediately After Payment
Price: $19.00 USD
```

**Payment Settings:**
- Payment Button Type: Subscription
- Billing Cycle: Monthly
- Trial Period: 0 days (optional: offer 7-day trial)

**Features:**
- Create unlimited events
- Manage attendees
- Export data
- Analytics dashboard

---

#### 3. Vendor (Level ID: 3)

```
Membership Level Name: Vendor
Default WordPress Role: vendor (custom role)
Subscription Duration: 1
Subscription Period: Months
Access Starts: Immediately After Payment
Price: $29.00 USD
```

**Payment Settings:**
- Payment Button Type: Subscription
- Billing Cycle: Monthly
- Trial Period: 0 days

**Features:**
- Post advertisements
- Ad analytics
- Multiple ad slots
- Target audiences

---

#### 4. Pro (Both) (Level ID: 4)

```
Membership Level Name: Pro (Host + Vendor)
Default WordPress Role: pro (custom role)
Subscription Duration: 1
Subscription Period: Months
Access Starts: Immediately After Payment
Price: $39.00 USD
```

**Payment Settings:**
- Payment Button Type: Subscription
- Billing Cycle: Monthly
- Trial Period: 0 days

**Features:**
- Everything in Event Host
- Everything in Vendor
- Priority support
- Custom branding

---

## 💳 Payment Gateway Setup

### Stripe Configuration

Navigate to: **Simple Membership > Payment Settings > Stripe**

#### Test Mode Configuration

```
Mode: Test Mode
Test Publishable Key: pk_test_xxxxxxxxxxxxx
Test Secret Key: sk_test_xxxxxxxxxxxxx
```

**Get Test Keys:**
1. Go to: https://dashboard.stripe.com/test/apikeys
2. Copy: Publishable key (starts with pk_test_)
3. Copy: Secret key (starts with sk_test_)
4. Paste into Simple Membership settings
5. Click: Save Changes

#### Live Mode Configuration

```
Mode: Live Mode
Live Publishable Key: pk_live_xxxxxxxxxxxxx
Live Secret Key: sk_live_xxxxxxxxxxxxx
```

**Get Live Keys:**
1. Go to: https://dashboard.stripe.com/apikeys
2. Copy: Publishable key (starts with pk_live_)
3. Copy: Secret key (starts with sk_live_)
4. Paste into Simple Membership settings
5. Click: Save Changes

#### Webhook Configuration

Simple Membership will automatically create Stripe webhooks. If manual setup is needed:

```
Webhook URL: https://yourdomain.com/?swpm_process_ipn=1&stripe=1
Events to Send:
  - customer.subscription.created
  - customer.subscription.updated
  - customer.subscription.deleted
  - invoice.payment_succeeded
  - invoice.payment_failed
  - checkout.session.completed
```

**Setup Webhook in Stripe:**
1. Go to: https://dashboard.stripe.com/webhooks
2. Click: Add endpoint
3. Enter webhook URL above
4. Select events listed above
5. Click: Add endpoint
6. Copy: Signing secret (starts with whsec_)
7. Paste in Simple Membership Stripe settings

---

## 🔌 Integration with Event RSVP

### Custom User Roles Setup

Your theme already has custom roles. Ensure they're created before assigning them in Simple Membership.

**Verify Roles Exist:**

Navigate to: **Users > Roles** (with Members plugin)

Required roles:
- ✓ event_host
- ✓ vendor
- ✓ pro
- ✓ subscriber (default WordPress role)

If roles don't exist, they're created automatically by your theme's `user-roles.php` file.

### Map Simple Membership Levels to WordPress Roles

| Membership Level | WordPress Role | Capabilities |
|-----------------|----------------|--------------|
| Free Attendee | subscriber | Read, browse events, RSVP |
| Event Host | event_host | Create events, manage attendees |
| Vendor | vendor | Post ads, view analytics |
| Pro (Both) | pro | All event_host + vendor capabilities |

This mapping is configured in the Membership Level settings (see previous section).

---

## 📝 Signup Flow Modification

### Updated Signup Process

**Old Flow (Custom Stripe):**
```
1. User fills signup form
2. AJAX creates pending registration
3. Redirects to custom Stripe checkout
4. Webhook creates account
5. Email sent with credentials
```

**New Flow (Simple Membership):**
```
1. User selects plan on pricing page
2. Redirects to signup page with plan parameter
3. User fills Simple Membership registration form
4. For paid plans:
   a. Redirects to Stripe checkout (via Simple Membership)
   b. Payment processed by Stripe
   c. Webhook updates membership level
   d. Role assigned automatically
   e. Activation email sent
5. For free plan:
   a. Account created immediately
   b. Role set to subscriber
   c. Welcome email sent
```

### Modified Signup Page Integration

The new signup page (`page-signup-swpm.php`) integrates Simple Membership shortcodes with custom styling.

**Key Features:**
- Uses `[swpm_registration_form]` shortcode
- Detects plan from URL parameter
- Pre-selects membership level
- Custom styling matches your theme
- Auto-redirect after successful registration

**URL Format:**
```
Free Plan: /signup/?plan=attendee
Event Host: /signup/?plan=event_host
Vendor: /signup/?plan=vendor
Pro: /signup/?plan=pro
```

---

## ✅ Role Assignment After Payment

### Automatic Role Assignment

Simple Membership handles role assignment automatically through webhooks:

**Payment Flow:**
```
1. User completes Stripe payment
2. Stripe sends webhook to Simple Membership
3. Simple Membership receives checkout.session.completed event
4. Membership level is activated
5. WordPress role is assigned based on level settings
6. User gets access to features immediately
```

### Manual Role Verification

To manually verify or update user roles:

**Navigate to:** Simple Membership > Members

**Check User Details:**
```
1. Find user in members list
2. Click: Edit
3. Verify:
   - Membership Level: Shows correct level
   - Account State: Active
   - Subscription Status: Active
   - WordPress Role: Matches membership level
```

### Role Update Hooks

Your custom code can also hook into Simple Membership events:

**File:** `rsvpplugin/includes/swpm-integration.php`

```php
// Hook into membership level change
add_action('swpm_membership_level_changed', 'event_rsvp_update_role_after_payment', 10, 2);

function event_rsvp_update_role_after_payment($user_id, $new_level) {
    // Map membership levels to roles
    $level_role_map = array(
        1 => 'subscriber',      // Free Attendee
        2 => 'event_host',      // Event Host
        3 => 'vendor',          // Vendor
        4 => 'pro',             // Pro (Both)
    );
    
    if (isset($level_role_map[$new_level])) {
        $user = new WP_User($user_id);
        $user->set_role($level_role_map[$new_level]);
        
        // Update user meta
        update_user_meta($user_id, 'event_rsvp_plan', array_search($level_role_map[$new_level], array(
            'attendee' => 'subscriber',
            'event_host' => 'event_host',
            'vendor' => 'vendor',
            'pro' => 'pro',
        )));
    }
}
```

---

## 🧪 Testing Checklist

### Pre-Launch Testing

#### Test Free Registration
- [ ] Visit `/signup/?plan=attendee`
- [ ] Fill out registration form
- [ ] Submit form
- [ ] Verify account created with subscriber role
- [ ] Check welcome email received
- [ ] Login with new credentials
- [ ] Verify dashboard access

#### Test Paid Registration - Event Host
- [ ] Visit `/signup/?plan=event_host`
- [ ] Fill out registration form
- [ ] Click payment button
- [ ] Complete Stripe test payment (card: 4242 4242 4242 4242)
- [ ] Verify redirect to success page
- [ ] Check membership level is "Event Host"
- [ ] Check WordPress role is "event_host"
- [ ] Verify welcome email received
- [ ] Login and test event creation

#### Test Paid Registration - Vendor
- [ ] Visit `/signup/?plan=vendor`
- [ ] Complete registration and payment
- [ ] Verify role is "vendor"
- [ ] Test ad posting functionality

#### Test Paid Registration - Pro
- [ ] Visit `/signup/?plan=pro`
- [ ] Complete registration and payment
- [ ] Verify role is "pro"
- [ ] Test both event creation and ad posting

#### Test Payment Failures
- [ ] Use declined test card (4000 0000 0000 0002)
- [ ] Verify error message displayed
- [ ] Check account not created or not activated
- [ ] Verify user can retry payment

#### Test Subscription Management
- [ ] Login as paid member
- [ ] Access Stripe Customer Portal
- [ ] Test subscription cancellation
- [ ] Verify role downgraded to subscriber
- [ ] Test subscription reactivation

---

## 🔧 Troubleshooting

### Common Issues

#### Issue 1: Payment Button Not Showing

**Symptoms:**
- Registration form displays but no payment button
- User can't proceed to checkout

**Solutions:**
```
1. Check: Simple Membership > Membership Levels
   - Verify price is set
   - Verify payment button is configured
   
2. Check: Simple Membership > Payment Settings
   - Verify Stripe is enabled
   - Verify API keys are correct
   
3. Clear WordPress cache
4. Test with different browser/incognito mode
```

#### Issue 2: Role Not Assigned After Payment

**Symptoms:**
- Payment succeeds but user has wrong role
- User stuck as subscriber

**Solutions:**
```
1. Check: Simple Membership > Members
   - Find user
   - Verify membership level is correct
   
2. Check: Membership Level Settings
   - Verify "Default WordPress Role" is set correctly
   
3. Check webhook logs:
   - Simple Membership > Settings > Advanced
   - Enable logging
   - Check for webhook errors
   
4. Manually update role:
   - Users > All Users
   - Find user
   - Change role manually
   - Then check Stripe webhook setup
```

#### Issue 3: Webhooks Not Working

**Symptoms:**
- Payment succeeds but membership not activated
- User not receiving emails
- Subscription not showing in Simple Membership

**Solutions:**
```
1. Verify webhook URL in Stripe Dashboard:
   - Should be: https://yourdomain.com/?swpm_process_ipn=1&stripe=1
   
2. Check webhook signing secret:
   - Copy from Stripe Dashboard
   - Paste in Simple Membership > Payment Settings > Stripe
   
3. Test webhook manually:
   - Stripe Dashboard > Webhooks
   - Click on your webhook
   - Click "Send test webhook"
   - Select "checkout.session.completed"
   - Check response
   
4. Check server logs:
   - Look for webhook processing errors
   - Verify HTTPS is working (webhooks require SSL)
```

#### Issue 4: Email Not Sending

**Symptoms:**
- Registration completes but no welcome email
- User doesn't receive credentials

**Solutions:**
```
1. Check: Simple Membership > Settings > Email
   - Verify emails are enabled
   - Check email templates
   
2. Test WordPress email:
   - Install WP Mail SMTP plugin
   - Send test email
   - Configure SMTP if default mail() fails
   
3. Check spam folder
4. Verify email address is valid
```

#### Issue 5: Existing Users Can't Upgrade

**Symptoms:**
- Logged-in user tries to upgrade
- Payment processes but level doesn't change

**Solutions:**
```
1. Simple Membership > Members
   - Find user
   - Manually change membership level
   
2. Create upgrade buttons on pricing page:
   - Use [swpm_payment_button id=X] shortcode
   - Allows logged-in users to purchase upgrades
   
3. Check: Simple Membership > Settings > Advanced
   - Enable "Allow Account Deletion"
   - This allows downgrades too
```

---

## 🚀 Advanced Customization

### Custom Registration Form Fields

Add custom fields to Simple Membership registration:

**Navigate to:** Simple Membership > Settings > Fields

**Add Custom Fields:**
```
Field Name: Phone Number
Field Type: Text
Required: No

Field Name: Company Name (for vendors)
Field Type: Text
Required: No (only for vendor/pro levels)
```

### Custom Redirect After Registration

**File:** `functions.php` or `rsvpplugin/includes/swpm-integration.php`

```php
add_filter('swpm_registration_complete_redirect_url', 'event_rsvp_custom_registration_redirect', 10, 2);

function event_rsvp_custom_registration_redirect($redirect_url, $user_data) {
    // Get membership level
    $level_id = $user_data['membership_level'];
    
    // Custom redirects based on level
    switch ($level_id) {
        case 1: // Free Attendee
            return home_url('/browse-events/');
            
        case 2: // Event Host
            return home_url('/host-dashboard/?welcome=1');
            
        case 3: // Vendor
            return home_url('/vendor-dashboard/?welcome=1');
            
        case 4: // Pro
            return home_url('/pro-dashboard/?welcome=1');
            
        default:
            return $redirect_url;
    }
}
```

### Custom Email Templates

**Navigate to:** Simple Membership > Settings > Email Misc.

**Enable Custom Templates:**
```
✓ Use Custom Email Template
```

**Create Custom Template:**

**File:** `wp-content/themes/your-theme/swpm-email-template.php`

```php
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        .header { background: #667eea; color: white; padding: 20px; }
        .content { padding: 20px; }
        .button { background: #667eea; color: white; padding: 12px 24px; 
                  text-decoration: none; border-radius: 4px; display: inline-block; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{site_name}</h1>
    </div>
    <div class="content">
        {email_body}
        
        <p>
            <a href="{login_url}" class="button">Login to Your Account</a>
        </p>
        
        <p>Need help? Contact us at {admin_email}</p>
    </div>
</body>
</html>
```

### Integration with Existing Event RSVP Features

**Modify Event Creation Permissions:**

**File:** `rsvpplugin/includes/post-types.php`

```php
// Check Simple Membership level before allowing event creation
add_action('admin_init', 'event_rsvp_check_swpm_permissions');

function event_rsvp_check_swpm_permissions() {
    if (!is_admin()) return;
    
    global $pagenow;
    
    // Check if user is trying to create/edit event
    if ($pagenow === 'post-new.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'event') {
        
        // Check Simple Membership level
        if (function_exists('SwpmMemberUtils::get_logged_in_members_level')) {
            $level = SwpmMemberUtils::get_logged_in_members_level();
            
            // Only levels 2 (Event Host) and 4 (Pro) can create events
            if ($level != 2 && $level != 4) {
                wp_die('You need an Event Host or Pro membership to create events. <a href="' . 
                       home_url('/pricing/') . '">Upgrade your account</a>');
            }
        }
    }
}
```

### Add Membership Badge to User Profile

**File:** `rsvpplugin/includes/swpm-integration.php`

```php
add_action('show_user_profile', 'event_rsvp_show_swpm_badge');
add_action('edit_user_profile', 'event_rsvp_show_swpm_badge');

function event_rsvp_show_swpm_badge($user) {
    if (!function_exists('SwpmMemberUtils::get_member_field_by_id')) return;
    
    $member_id = SwpmMemberUtils::get_member_id_from_user_id($user->ID);
    
    if (!$member_id) {
        echo '<h3>Membership Status</h3>';
        echo '<p>Not a Simple Membership member</p>';
        return;
    }
    
    $level = SwpmMemberUtils::get_membership_level($member_id);
    $status = SwpmMemberUtils::get_account_state($member_id);
    
    echo '<h3>Membership Status</h3>';
    echo '<table class="form-table">';
    echo '<tr><th>Level:</th><td>' . esc_html($level['alias']) . '</td></tr>';
    echo '<tr><th>Status:</th><td>' . esc_html($status) . '</td></tr>';
    echo '</table>';
}
```

---

## 📊 Migration from Custom Stripe Integration

### Deactivate Old Custom Integration

**Step 1: Backup Database**
```bash
# Via command line
mysqldump -u username -p database_name > backup.sql

# Or via plugin: Install "UpdraftPlus" or "BackupBuddy"
```

**Step 2: Disable Custom Stripe Files**

**File:** `rsvpplugin/event-rsvp-plugin.php`

```php
// Comment out these lines:
// require_once EVENT_RSVP_PLUGIN_DIR . '/includes/stripe-integration.php';
// require_once EVENT_RSVP_PLUGIN_DIR . '/includes/stripe-ajax-handlers.php';

// Add this instead:
require_once EVENT_RSVP_PLUGIN_DIR . '/includes/swpm-integration.php';
```

**Step 3: Migrate Existing Users**

Create a migration script to transfer users to Simple Membership:

**File:** `rsvpplugin/includes/swpm-migration.php`

```php
<?php
/**
 * Migrate users from custom Stripe to Simple Membership
 * Run once via: yoursite.com/?run_swpm_migration=1&key=your_secret_key
 */

add_action('init', 'event_rsvp_run_swpm_migration');

function event_rsvp_run_swpm_migration() {
    if (!isset($_GET['run_swpm_migration']) || $_GET['key'] !== 'your_secret_key_here') {
        return;
    }
    
    // Verify Simple Membership is active
    if (!class_exists('SwpmMemberUtils')) {
        die('Simple Membership plugin is not active');
    }
    
    // Get all users with custom stripe subscription
    $users = get_users(array(
        'meta_key' => 'event_rsvp_stripe_customer_id',
        'meta_compare' => 'EXISTS'
    ));
    
    $migrated = 0;
    $errors = array();
    
    foreach ($users as $user) {
        $plan = get_user_meta($user->ID, 'event_rsvp_plan', true);
        $stripe_customer = get_user_meta($user->ID, 'event_rsvp_stripe_customer_id', true);
        
        // Map plan to membership level
        $level_map = array(
            'event_host' => 2,
            'vendor' => 3,
            'pro' => 4,
        );
        
        if (!isset($level_map[$plan])) {
            $level_id = 1; // Default to free
        } else {
            $level_id = $level_map[$plan];
        }
        
        // Create Simple Membership record
        global $wpdb;
        
        $member_data = array(
            'user_name' => $user->user_login,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->user_email,
            'membership_level' => $level_id,
            'account_state' => 'active',
            'subscr_id' => $stripe_customer,
            'join_date' => $user->user_registered,
        );
        
        $inserted = $wpdb->insert(
            $wpdb->prefix . 'swpm_members_tbl',
            $member_data
        );
        
        if ($inserted) {
            $migrated++;
        } else {
            $errors[] = $user->user_email;
        }
    }
    
    echo "Migration complete!<br>";
    echo "Migrated: " . $migrated . " users<br>";
    
    if (!empty($errors)) {
        echo "Errors:<br>";
        echo implode('<br>', $errors);
    }
    
    die();
}
```

**Run Migration:**
```
1. Upload swpm-migration.php
2. Replace 'your_secret_key_here' with a random string
3. Visit: yoursite.com/?run_swpm_migration=1&key=your_secret_key_here
4. Wait for completion message
5. Verify users in Simple Membership > Members
```

---

## 📧 Email Configuration

### Recommended Email Setup

**Option 1: WP Mail SMTP (Recommended)**

1. Install WP Mail SMTP plugin
2. Configure with your email service:
   - SendGrid (free tier available)
   - Mailgun (free tier available)
   - Amazon SES
   - Gmail/G Suite

**Option 2: Simple Membership Built-in**

Simple Membership uses WordPress `wp_mail()` function which can be unreliable. Always test emails!

### Email Template Variables

Simple Membership supports these variables in email templates:

```
{site_name} - Your site name
{site_url} - Your site URL
{member_id} - Unique member ID
{user_name} - Username
{first_name} - First name
{last_name} - Last name
{email} - Email address
{membership_level} - Level name
{account_state} - Active/Inactive/Expired
{login_url} - Login page URL
{password_reset_url} - Password reset URL
{admin_email} - Site admin email
```

---

## 🎓 Best Practices

### Security

1. **Always use HTTPS** - Required for Stripe webhooks
2. **Strong passwords** - Enforce via Simple Membership settings
3. **Regular backups** - Before any major changes
4. **Test in staging** - Don't test payments on live site
5. **Monitor webhook logs** - Check for suspicious activity

### User Experience

1. **Clear pricing page** - Show what each plan includes
2. **Email verification** - Optional but recommended
3. **Welcome emails** - Set up automated welcome series
4. **Trial periods** - Consider offering trials for paid plans
5. **Cancellation flow** - Make it easy but collect feedback

### Performance

1. **Cache membership checks** - Use transients for role checks
2. **Optimize webhook processing** - Keep webhook handlers fast
3. **Database cleanup** - Regularly clean expired memberships
4. **Monitor Stripe quota** - Be aware of API rate limits

---

## 📞 Support Resources

### Simple Membership Documentation
- Official Docs: https://simple-membership-plugin.com/
- Support Forum: https://wordpress.org/support/plugin/simple-membership/
- Video Tutorials: Check YouTube for "Simple Membership Tutorial"

### Stripe Documentation
- Stripe Dashboard: https://dashboard.stripe.com/
- Stripe Docs: https://stripe.com/docs
- Testing: https://stripe.com/docs/testing

### WordPress Resources
- Codex: https://codex.wordpress.org/
- Developer Handbook: https://developer.wordpress.org/

---

## ✅ Final Checklist

Before going live, complete this checklist:

### Pre-Launch
- [ ] Simple Membership plugin installed and activated
- [ ] All 4 membership levels created
- [ ] WordPress roles mapped correctly
- [ ] Stripe connected (test mode)
- [ ] Test registration completed for each level
- [ ] Webhooks configured and tested
- [ ] Email templates customized
- [ ] WP Mail SMTP configured (recommended)
- [ ] Custom signup pages created
- [ ] Pricing page updated
- [ ] Terms of Service and Privacy Policy pages exist

### Testing Phase
- [ ] Test free registration
- [ ] Test paid registration (all levels)
- [ ] Test failed payments
- [ ] Test webhook processing
- [ ] Test email delivery
- [ ] Test role assignment
- [ ] Test feature access (events, ads)
- [ ] Test subscription cancellation
- [ ] Test subscription reactivation
- [ ] Mobile testing

### Launch Day
- [ ] Switch Stripe to live mode
- [ ] Update Stripe webhook to live endpoint
- [ ] Test one live transaction (small amount)
- [ ] Monitor webhook logs for 24 hours
- [ ] Monitor user registrations
- [ ] Check email deliverability
- [ ] Backup database

### Post-Launch
- [ ] Monitor Stripe dashboard daily (first week)
- [ ] Check Simple Membership members list daily
- [ ] Review error logs
- [ ] Collect user feedback
- [ ] Document any issues
- [ ] Plan for ongoing maintenance

---

## 🎉 Congratulations!

You've successfully integrated Simple Membership with your Event RSVP Platform!

**Next Steps:**
1. Monitor your first week of registrations closely
2. Collect user feedback
3. Iterate on email templates
4. Consider adding more membership levels
5. Explore Simple Membership add-ons for additional features

**Questions?**
- Check the troubleshooting section
- Review Simple Membership docs
- Check Stripe dashboard for payment issues
- Review your server error logs

---

**Document Version:** 1.0  
**Last Updated:** December 2024  
**Maintained By:** Event RSVP Platform Development Team

---

## Appendix A: Quick Reference

### Membership Level IDs
```
1 = Free Attendee (subscriber)
2 = Event Host (event_host)
3 = Vendor (vendor)
4 = Pro (pro)
```

### Important URLs
```
Signup: /signup/?plan={plan_slug}
Login: /login/
Pricing: /pricing/
Dashboard: /host-dashboard/ or /vendor-dashboard/
Stripe Portal: https://billing.stripe.com/p/login/{customer_id}
```

### Shortcodes
```
[swpm_registration_form]
[swpm_login_form]
[swpm_profile_form]
[swpm_payment_button id=X]
[swpm_reset_form]
```

### Key Files Modified
```
page-signup-swpm.php - New signup page with SWPM integration
rsvpplugin/includes/swpm-integration.php - SWPM hooks
rsvpplugin/event-rsvp-plugin.php - Updated to load SWPM integration
page-pricing.php - Updated with SWPM payment buttons
```

---

**END OF GUIDE**

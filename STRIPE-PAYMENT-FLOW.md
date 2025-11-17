# Stripe Payment Flow - Complete Guide

## Overview

The system now properly handles user registration with Stripe payment integration. Users are **always** registered as **subscriber (attendee)** first, then redirected to Stripe for payment. After successful payment, their role is upgraded.

## Fixed Issues

### Before (Problems)
- ❌ Users were directly assigned premium roles without payment
- ❌ No payment redirect happening
- ❌ Conflicting AJAX handlers
- ❌ No failure/cancellation handling

### After (Solutions)
- ✅ Users always register as subscriber first
- ✅ Proper redirect to Stripe payment
- ✅ Role upgraded only after payment success
- ✅ Payment success and failure pages created
- ✅ Removed conflicting handler

## User Registration Flow

### For Free Attendee Plan

1. User fills signup form
2. Account created as **subscriber** role
3. User auto-logged in
4. Redirected to browse events
5. Welcome email sent

### For Paid Plans (Event Host, Vendor, Pro)

1. User fills signup form on `/signup/?plan=event_host` (or vendor/pro)
2. **Account created immediately as subscriber** role
3. User auto-logged in
4. Payment token generated and saved
5. User redirected to Stripe Payment Link
6. **Two possible outcomes:**

#### A. Payment Success
1. Stripe redirects to `/signup-success/?payment_success=1&token=XXX&plan=event_host`
2. JavaScript automatically verifies token via AJAX
3. Backend upgrades role from subscriber to event_host/vendor/pro
4. User meta updated (plan, subscription status, payment date)
5. Upgrade confirmation email sent
6. User redirected to dashboard
7. Token marked as completed

#### B. Payment Cancelled/Failed
1. Stripe redirects to `/payment-cancelled/?plan=event_host`
2. User sees friendly message
3. Account remains as free subscriber
4. User can:
   - Try payment again
   - Login to free account
   - Browse events
   - Upgrade later from pricing page

## Key Files Modified

### 1. `rsvpplugin/includes/simple-stripe-ajax.php`
**Purpose:** Main AJAX handler for registration
- Handles free and paid plan signups
- Creates subscriber account for paid plans
- Redirects to Stripe payment

### 2. `rsvpplugin/includes/simple-stripe-payments.php`
**Changes:**
- Added `cancel_url` to Stripe payment link
- Added auto-login after account creation
- Now properly creates subscriber first, upgrades after payment

### 3. `rsvpplugin/includes/ajax-handlers.php`
**Changes:**
- Removed conflicting `event_rsvp_register_user` handler
- This handler was bypassing payment flow

### 4. `page-payment-cancelled.php` (NEW)
**Purpose:** Handle payment cancellation/failure
- Shows friendly error message
- Explains free account is created
- Offers options to retry or use free account

### 5. `page-signup-success.php` (EXISTING)
**Purpose:** Verify payment and upgrade account
- Shows loading spinner
- Verifies payment token
- Upgrades role automatically
- Redirects to dashboard

## Database Structure

### `wp_event_rsvp_payment_tokens` Table
```sql
id              - Auto increment
user_id         - WordPress user ID
token           - Hashed secure token
plan_slug       - event_host, vendor, or pro
status          - pending or completed
created_at      - Timestamp
completed_at    - Timestamp (NULL until verified)
```

## User Meta Fields

```php
event_rsvp_plan                  - attendee, event_host, vendor, or pro
event_rsvp_payment_pending       - 1 (deleted after payment success)
event_rsvp_subscription_status   - active (set after payment)
event_rsvp_payment_date          - Timestamp of successful payment
```

## Stripe Configuration

### Required Setup in WordPress Admin

1. Go to **Settings → Stripe Payments**
2. Enter three payment links:
   - Event Host Plan Link
   - Vendor Plan Link
   - Pro Plan Link

### Stripe Dashboard Setup

For each plan, create a Payment Link:

1. **Event Host** - $19/month recurring
   - Product name: Event Host Subscription
   - Stripe Payment Link: `https://buy.stripe.com/test_xxxxx`

2. **Vendor** - $29/month recurring
   - Product name: Vendor Subscription
   - Stripe Payment Link: `https://buy.stripe.com/test_xxxxx`

3. **Pro** - $39/month recurring
   - Product name: Pro Subscription (Host + Vendor)
   - Stripe Payment Link: `https://buy.stripe.com/test_xxxxx`

### Important Stripe Settings

The system automatically appends success and cancel URLs to your payment links:
- Success URL: `{your-site}/signup-success/?payment_success=1&token={token}&plan={plan}`
- Cancel URL: `{your-site}/payment-cancelled/?plan={plan}`

You don't need to configure these in Stripe Dashboard - they're added programmatically.

## Testing the Flow

### Test Paid Plan Registration

1. Go to `/pricing/`
2. Click "Start Hosting" on Event Host plan
3. Fill out signup form
4. Click "Create Account & Proceed to Payment"
5. Should redirect to Stripe payment page
6. Use test card: `4242 4242 4242 4242`
7. Complete payment
8. Should redirect back and verify automatically
9. Check user role is upgraded

### Test Payment Cancellation

1. Follow steps 1-4 above
2. On Stripe payment page, click "Back" or close window
3. Should redirect to `/payment-cancelled/`
4. Check user account exists as subscriber
5. User can login with created credentials

### Test Free Plan

1. Go to `/signup/?plan=attendee` or `/signup/`
2. Fill out form
3. Click "Create Free Account"
4. Should redirect to browse events
5. Check user role is subscriber

## Role Hierarchy

| Role | Capabilities |
|------|-------------|
| **subscriber** (attendee) | Browse events, RSVP, receive QR codes |
| **event_host** | Everything subscriber + create/manage events |
| **vendor** | Everything subscriber + post ads |
| **pro** | Everything event_host + everything vendor |

## Email Notifications

### Account Created Email
Sent immediately when account is created (before payment)
- Username and email
- Login link
- Note about completing payment for premium features

### Payment Success Email
Sent after successful payment and role upgrade
- Congratulations message
- List of unlocked features
- Dashboard link

## Security Features

1. **Nonce verification** on all AJAX requests
2. **Hashed tokens** in database (using `wp_hash_password`)
3. **Token expiration** - only pending tokens checked
4. **Auto-login** - user logged in immediately to prevent issues
5. **SQL injection protection** - uses `$wpdb->prepare()`

## Troubleshooting

### Payment successful but role not upgraded

**Check:**
1. Database table `wp_event_rsvp_payment_tokens` exists
2. Token record exists with status 'pending'
3. PHP error logs for verification errors
4. User should still have free subscriber access

### Not redirecting to Stripe

**Check:**
1. Stripe Payment Links configured in Settings
2. Plan slug matches (event_host, vendor, pro)
3. JavaScript console for errors
4. Network tab shows AJAX call success

### User can't access premium features

**Check:**
1. User role: `wp_users.ID` should show correct role
2. User meta `event_rsvp_plan` should match
3. `event_rsvp_subscription_status` should be 'active'
4. Token status should be 'completed'

## WordPress Pages Required

Make sure these pages exist with correct templates:

- `/signup/` - Template: Signup Page
- `/signup-success/` - Template: Signup Success Page  
- `/payment-cancelled/` - Template: Payment Cancelled
- `/pricing/` - Template: Pricing Page
- `/login/` - Template: Login Page
- `/host-dashboard/` - Template: Host Dashboard
- `/ads-manager/` - Template: Vendor Dashboard
- `/browse-events/` - Template: Browse Events

## Summary

The payment flow now works correctly:
1. ✅ User always registers as subscriber
2. ✅ Redirects to Stripe for payment
3. ✅ Role upgraded only after payment verification
4. ✅ Failed payments handled gracefully
5. ✅ User always gets an account (free or paid)
6. ✅ No orphaned registrations or incomplete flows

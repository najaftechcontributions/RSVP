# Simple Stripe Payment Setup Guide

This platform uses **Stripe Payment Links** for subscription management. This is the simplest way to accept payments without needing plugins or complex integrations.

## How It Works

1. **User Signs Up**: User fills out signup form and selects a plan
2. **Account Created**: System creates a FREE attendee account immediately
3. **Redirect to Stripe**: User is redirected to Stripe Payment Link
4. **Payment Completed**: After successful payment, user returns with a token
5. **Account Upgraded**: System verifies token and upgrades user role

### Key Benefits

- ✅ No plugin dependencies (no WooCommerce, no Simple Membership)
- ✅ User always gets an account (free attendee if payment fails)
- ✅ Stripe handles all payment processing and PCI compliance
- ✅ Easy to manage subscriptions via Stripe Dashboard
- ✅ Customers can manage billing via Stripe Customer Portal

## Setup Instructions

### Step 1: Create Stripe Account

1. Go to [Stripe.com](https://stripe.com) and create an account
2. Complete your business profile
3. Switch between Test Mode and Live Mode as needed

### Step 2: Create Payment Links

For each plan, create a Payment Link in Stripe:

#### Event Host Plan ($19/month)

1. Go to Stripe Dashboard → **Products** → **Payment Links**
2. Click **New** button
3. Configure:
   - **Product name**: Event Host Subscription
   - **Description**: Create and manage unlimited events
   - **Pricing**: $19.00 USD
   - **Billing period**: Monthly recurring
4. Click **Create link**
5. Copy the payment link URL (e.g., `https://buy.stripe.com/test_xxxxx`)

#### Vendor Plan ($29/month)

1. Repeat above steps with:
   - **Product name**: Vendor Subscription
   - **Description**: Advertise your business or services
   - **Pricing**: $29.00 USD
   - **Billing period**: Monthly recurring

#### Pro Plan ($39/month)

1. Repeat above steps with:
   - **Product name**: Pro Subscription (Host + Vendor)
   - **Description**: Host events AND advertise - Best value!
   - **Pricing**: $39.00 USD
   - **Billing period**: Monthly recurring

### Step 3: Configure WordPress

1. Log in to WordPress admin
2. Go to **Settings** → **Stripe Payments**
3. Paste each Payment Link URL in the corresponding field:
   - Event Host Plan Link
   - Vendor Plan Link
   - Pro Plan Link
4. Click **Save Changes**

### Step 4: Test the Flow

1. Use Stripe Test Mode for testing
2. Create a test signup with a paid plan
3. Use test card: `4242 4242 4242 4242`
4. Verify account is created as attendee first
5. Complete payment on Stripe
6. Verify account is upgraded after payment success

## User Roles

The system uses the following role hierarchy:

| Role | Capabilities | How Assigned |
|------|-------------|--------------|
| **Subscriber** (Attendee) | Browse events, RSVP | Default for free accounts or failed payments |
| **Event Host** | Create events + Attendee capabilities | After Event Host payment |
| **Vendor** | Post ads + Attendee capabilities | After Vendor payment |
| **Pro** | Create events + Post ads + Attendee | After Pro payment |
| **Administrator** | Full access | Manual assignment |

## Payment Failure Handling

If payment fails or is cancelled:

1. User account is already created as FREE attendee (subscriber role)
2. User can log in and browse events
3. User can upgrade anytime from pricing page
4. No orphaned accounts or incomplete registrations

## Customer Subscription Management

Customers can manage their subscriptions via Stripe Customer Portal:

1. Log in to Stripe Dashboard
2. Go to **Settings** → **Customer Portal**
3. Enable customer portal features
4. Customers can access portal at: `https://billing.stripe.com/p/login/YOUR_ID`

Features available:
- Update payment method
- View invoice history
- Cancel subscription
- Resume subscription

## Ads Management - Vendor Access Control

### Vendor Dashboard (Custom Page)

Vendors see ONLY their own ads on the custom vendor dashboard page (`/vendor-dashboard/`):

```php
// In page-vendor-dashboard.php
$args = array(
    'post_type' => 'vendor_ad',
    'author' => $user_id,  // Filters by current user
);

// Admins bypass filter
if (current_user_can('administrator')) {
    unset($args['author']);
}
```

### WordPress Admin Dashboard

Vendors see ONLY their own ads in WordPress admin (edit.php?post_type=vendor_ad):

```php
// Automatic filter in post-types.php
function event_rsvp_filter_vendor_ads_by_author($query) {
    // Admins see all ads
    if (current_user_can('administrator')) {
        return;
    }
    
    // Vendors only see their own ads
    $query->set('author', get_current_user_id());
}
```

### Administrator Access

Administrators see ALL ads in both locations:
- Custom vendor dashboard page shows all ads
- WordPress admin shows all ads
- Can manage, approve, and delete any ad

## Troubleshooting

### Payment successful but account not upgraded

1. Check if token table exists: `wp_event_rsvp_payment_tokens`
2. Verify token was created in database
3. Check PHP error logs for verification errors
4. User should still have free attendee access

### Payment links not working

1. Verify links are configured in **Settings** → **Stripe Payments**
2. Check that links are from correct Stripe account (Test vs Live)
3. Ensure links are for **recurring** subscriptions, not one-time payments

### Customers can't manage subscriptions

1. Enable Stripe Customer Portal in Stripe Dashboard
2. Ensure customers are using the correct login email
3. Provide customer portal link: `https://billing.stripe.com/p/login`

## Files Structure

```
rsvpplugin/
├── includes/
│   ├── simple-stripe-payments.php   # Main payment integration
│   ├── simple-stripe-ajax.php        # AJAX handlers
│   ├── post-types.php                # Includes vendor_ad author filter
│   └── [deprecated files]            # Old integration files (empty placeholders)
├── page-signup.php                   # Main signup page
├── page-signup-success.php           # Payment verification page
└── page-pricing.php                  # Pricing plans page
```

## Migration from Old System

If migrating from WooCommerce or Simple Membership:

1. Existing users keep their current roles
2. Configure Stripe Payment Links as above
3. New signups use new system automatically
4. Old subscription management can be phased out

## Support

For issues or questions:
- Check Stripe Dashboard for payment status
- Review PHP error logs
- Test with Stripe test mode first
- Verify all Payment Links are configured correctly

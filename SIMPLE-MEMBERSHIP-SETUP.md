# Simple Membership Integration - Complete Setup Package

## ✅ What Has Been Created

This package includes everything you need to integrate Simple Membership plugin with your Event RSVP Platform.

---

## 📦 Files Created

### 1. **SIMPLE-MEMBERSHIP-INTEGRATION-GUIDE.md** (Main Guide)
   - **Purpose:** Complete, detailed integration guide
   - **Pages:** 300+ lines of comprehensive documentation
   - **Includes:**
     - Installation instructions
     - Payment gateway setup
     - Membership level configuration
     - Role assignment workflow
     - Troubleshooting guide
     - Advanced customization
     - Migration from custom Stripe
   - **When to use:** Reference guide for all integration aspects

### 2. **SIMPLE-MEMBERSHIP-QUICK-START.md** (Fast Setup)
   - **Purpose:** Get running in 15 minutes
   - **Includes:**
     - 5-step quick setup
     - Copy-paste configurations
     - Test checklist
     - Go-live checklist
   - **When to use:** When you need to set up quickly

### 3. **rsvpplugin/includes/swpm-integration.php** (Integration Code)
   - **Purpose:** Core integration between Simple Membership and Event RSVP
   - **Features:**
     - Automatic role assignment after payment
     - Webhook handling
     - Custom redirects based on membership level
     - Permission checks for events and ads
     - Email notifications
     - Migration utilities
   - **Hooks into:**
     - Simple Membership events
     - WordPress user system
     - Event RSVP features

### 4. **page-signup-swpm.php** (New Signup Template)
   - **Purpose:** Signup page integrated with Simple Membership
   - **Features:**
     - Displays Simple Membership registration forms
     - Detects plan from URL parameter
     - Custom styling matching your theme
     - Pre-selects membership level
     - Payment flow visualization
   - **Usage:** Assign as template to your Signup page

### 5. **rsvpplugin/event-rsvp-plugin.php** (Updated Main Plugin)
   - **Purpose:** Updated to load Simple Membership integration
   - **Changes:**
     - Loads `swpm-integration.php` instead of custom Stripe files
     - Updated admin notices to check for Simple Membership
     - Custom Stripe integration commented out (not deleted)
     - Version updated to 2.1.0

---

## 🚀 Implementation Steps

### Phase 1: Backup (5 min)
```bash
1. Backup your database
2. Backup your theme files
3. Note current active plugins
```

### Phase 2: Install Simple Membership (3 min)
```bash
1. Go to: Plugins > Add New
2. Search: "Simple Membership"
3. Install and activate (by smp7, wp.insider)
```

### Phase 3: Create Membership Levels (10 min)

Go to: **Simple Membership > Membership Levels**

Create 4 levels as detailed in the Quick Start Guide:

| Level | Name | Role | Price | Duration |
|-------|------|------|-------|----------|
| 1 | Free Attendee | subscriber | $0 | No Expiry |
| 2 | Event Host | event_host | $19/mo | Monthly |
| 3 | Vendor | vendor | $29/mo | Monthly |
| 4 | Pro (Both) | pro | $39/mo | Monthly |

### Phase 4: Connect Stripe (5 min)

1. Get Stripe test keys from: https://dashboard.stripe.com/test/apikeys
2. Go to: **Simple Membership > Payment Settings > Stripe**
3. Enter keys and save

### Phase 5: Setup Webhooks (5 min)

1. Copy webhook URL from Simple Membership
2. Create webhook in Stripe Dashboard
3. Copy signing secret back to Simple Membership

### Phase 6: Update Signup Page (2 min)

1. Go to: **Pages > All Pages**
2. Edit your Signup page
3. Change template to: **Signup Page (Simple Membership)**
4. Save

### Phase 7: Test Everything (15 min)

Follow the test checklist in the Quick Start Guide:
- Test free registration
- Test paid registration (all 3 paid plans)
- Test role assignment
- Test webhook processing
- Test email delivery

---

## 🎯 How It Works

### Old Flow (Custom Stripe)
```
User → Signup Form → AJAX Handler → Custom Stripe Checkout 
→ Webhook → Create Account → Assign Role → Send Email
```

### New Flow (Simple Membership)
```
User → Signup Form (SWPM) → Simple Membership Handler
→ Stripe Checkout (if paid) → SWPM Webhook → Assign Role
→ SWPM Email + Custom Welcome Email
```

### Role Assignment Workflow
```
1. User completes payment in Stripe
2. Stripe sends webhook to Simple Membership
3. Simple Membership receives checkout.session.completed
4. SWPM creates/activates membership
5. Our swpm-integration.php hooks into membership_changed
6. WordPress role is assigned based on level
7. User meta updated with plan info
8. Welcome email sent
9. User redirected to dashboard
```

---

## 🔧 Integration Points

### Simple Membership Hooks Used

```php
// When membership level changes (after payment)
add_action('swpm_membership_changed', 'handle_membership_changed', 10, 2);

// After front-end registration (free plan)
add_action('swpm_front_end_registration_complete', 'handle_registration_complete', 10, 2);

// When payment is processed
add_action('swpm_payment_ipn_processed', 'handle_payment_processed', 10, 1);

// When subscription is cancelled
add_action('swpm_subscription_canceled', 'handle_subscription_canceled', 10, 1);

// When subscription expires
add_action('swpm_subscription_expired', 'handle_subscription_expired', 10, 1);
```

### Custom Redirects

```php
// After registration, redirect based on level
add_filter('swpm_registration_complete_redirect_url', 'custom_registration_redirect', 10, 2);

// After login, redirect based on role
add_filter('swpm_after_login_redirect_url', 'custom_login_redirect', 10, 2);
```

### Permission Checks

```php
// Check if user can create events
add_action('admin_init', 'check_permissions');

// Only event_host and pro can create events
function user_can_create_events($user_id)

// Only vendor and pro can post ads
function user_can_post_ads($user_id)
```

---

## 📧 Email Flow

### Emails Sent by Simple Membership
1. **Registration confirmation** (free plans)
2. **Email verification** (if enabled)
3. **Payment confirmation**
4. **Subscription renewal reminder**

### Emails Sent by Integration (swpm-integration.php)
1. **Custom welcome email** with plan details
2. **Cancellation notification**
3. **Expiration warning**

### Email Configuration

Recommended: Install **WP Mail SMTP** plugin
- Use SendGrid (free 100 emails/day)
- Or Mailgun (free 5,000 emails/month)
- Or Amazon SES (cheapest for high volume)

---

## 🎨 Customization Options

### Change Pricing

Edit in: **Simple Membership > Membership Levels**
- Update price for each level
- Changes reflect immediately
- Existing subscriptions unaffected

### Custom Email Templates

Edit in: **Simple Membership > Settings > Email**
- Customize subject lines
- Customize message content
- Use template variables

### Custom Redirects

Edit in: `rsvpplugin/includes/swpm-integration.php`
- Modify `custom_registration_redirect()`
- Modify `custom_login_redirect()`

### Custom Welcome Emails

Edit in: `rsvpplugin/includes/swpm-integration.php`
- Modify `send_welcome_email()`
- Modify `send_cancellation_email()`
- Modify `send_expiration_email()`

---

## 🔐 Security Features

### Built-in Protection
- WordPress nonce verification
- Role capability checks
- Stripe webhook signature verification
- SQL injection prevention (prepared statements)
- XSS protection (esc_ functions)

### Payment Security
- PCI compliant via Stripe
- No credit card data stored
- SSL/HTTPS required
- Webhook signing secrets

---

## 📊 Subscription Management

### For Users
- Manage via Stripe Customer Portal
- Update payment method
- Cancel subscription
- View billing history

### For Admins
- View all members: **Simple Membership > Members**
- Manual role assignment: **Users > All Users**
- Payment history: Stripe Dashboard
- Webhook logs: **Simple Membership > Settings > Advanced**

---

## 🆘 Common Issues & Solutions

### Issue: "Simple Membership plugin not found"
**Solution:** Install and activate Simple Membership plugin

### Issue: Payment button not showing
**Solution:** 
- Verify Stripe keys are entered
- Check membership level has payment button configured
- Clear cache

### Issue: Role not assigned after payment
**Solution:**
- Check webhook is active and receiving events
- Verify webhook secret matches
- Check error logs

### Issue: Emails not sending
**Solution:**
- Install WP Mail SMTP
- Test email delivery
- Check spam folder

### Issue: Existing users need to be migrated
**Solution:**
- See migration section in main guide
- Use provided migration script
- Test with one user first

---

## 📈 Monitoring & Analytics

### What to Monitor

**Daily (First Week):**
- New registrations
- Payment success rate
- Webhook processing
- Email delivery
- Error logs

**Weekly:**
- Revenue in Stripe Dashboard
- Active subscriptions
- Cancellation rate
- Support requests

**Monthly:**
- MRR (Monthly Recurring Revenue)
- Churn rate
- Upgrade/downgrade trends
- Popular plans

### Tools

**Stripe Dashboard:**
- Revenue tracking
- Subscription analytics
- Failed payment alerts
- Customer portal

**WordPress:**
- Simple Membership > Members
- Simple Membership > Reports
- User role distribution

---

## 🚀 Going Live

### Before You Go Live

1. **Test Everything in Test Mode**
   - All 4 membership levels
   - Payment success scenarios
   - Payment failure scenarios
   - Webhook processing
   - Email delivery
   - Role assignment

2. **Legal Pages Ready**
   - Terms of Service
   - Privacy Policy (mention Stripe, payment processing)
   - Refund Policy
   - Cancellation Policy

3. **Stripe Account Approved**
   - Business profile complete
   - Bank account added
   - Identity verified
   - Account activated

### Going Live Steps

1. **Get Live Stripe Keys**
   - Go to: https://dashboard.stripe.com/apikeys
   - Copy Publishable Key (pk_live_...)
   - Copy Secret Key (sk_live_...)

2. **Update Simple Membership**
   - Go to: Simple Membership > Payment Settings > Stripe
   - Change Mode to: **Live Mode**
   - Enter live keys
   - Save

3. **Update Webhook**
   - Create new webhook in Stripe (live mode)
   - Use same events as test mode
   - Copy signing secret
   - Update in Simple Membership

4. **Test One Transaction**
   - Use real card (small amount)
   - Verify everything works
   - Issue refund if needed

5. **Monitor Closely**
   - Watch first 10 transactions
   - Check webhook processing
   - Verify emails sending
   - Test cancellation flow

---

## 📞 Support Resources

### Documentation
- This guide: `SIMPLE-MEMBERSHIP-INTEGRATION-GUIDE.md`
- Quick start: `SIMPLE-MEMBERSHIP-QUICK-START.md`
- Simple Membership docs: https://simple-membership-plugin.com/documentation/
- Stripe docs: https://stripe.com/docs

### Community
- Simple Membership forum: https://wordpress.org/support/plugin/simple-membership/
- Stripe Discord: https://discord.gg/stripe

### Direct Support
- Simple Membership: support@simple-membership-plugin.com
- Stripe: https://support.stripe.com/

---

## ✅ Final Checklist

Before marking this as complete:

### Setup
- [ ] Simple Membership installed and activated
- [ ] 4 membership levels created
- [ ] Stripe connected (test mode initially)
- [ ] Webhooks configured and active
- [ ] Signup page template updated

### Testing
- [ ] Free registration tested
- [ ] Event Host registration tested
- [ ] Vendor registration tested  
- [ ] Pro registration tested
- [ ] Role assignment verified for all levels
- [ ] Emails received for all scenarios
- [ ] Event creation restricted to correct roles
- [ ] Ad posting restricted to correct roles

### Documentation
- [ ] Team trained on new system
- [ ] Support documentation updated
- [ ] User guides created (if needed)

### Production
- [ ] Database backed up
- [ ] Test mode thoroughly tested
- [ ] Live Stripe keys ready
- [ ] Legal pages updated
- [ ] Monitoring in place

---

## 🎉 Success Criteria

You'll know it's working when:

1. ✅ Free users can register instantly
2. ✅ Paid users see Stripe checkout
3. ✅ Roles assigned automatically after payment
4. ✅ Welcome emails sent to all new users
5. ✅ Event creation restricted to Event Host & Pro
6. ✅ Ad posting restricted to Vendor & Pro
7. ✅ Subscriptions shown in Stripe Dashboard
8. ✅ Webhooks processing without errors
9. ✅ Users can manage their subscriptions
10. ✅ Revenue tracking works in Stripe

---

## 💡 Pro Tips

1. **Start in Test Mode**
   - Use test mode for at least 1 week
   - Test all scenarios multiple times
   - Don't rush to live mode

2. **Monitor Webhooks**
   - Enable logging in Simple Membership
   - Check webhook status daily (first week)
   - Set up Stripe webhook alerts

3. **Email Deliverability**
   - Use WP Mail SMTP from day one
   - Test emails to different providers
   - Monitor spam complaints

4. **User Experience**
   - Make plan selection obvious
   - Explain payment flow clearly
   - Show what's included in each plan
   - Make cancellation easy (builds trust)

5. **Revenue Optimization**
   - Consider offering annual plans (discount)
   - Test different price points
   - Offer limited-time promotions
   - Track which plans convert best

---

## 📝 Migration Notes

### If You Have Existing Custom Stripe Integration

**Important:** Don't delete custom Stripe files yet!

The integration is designed to coexist:
- Custom files are commented out, not deleted
- Can switch back if needed (uncomment in plugin file)
- Migrate users gradually using provided tools
- Test thoroughly before full migration

**Migration Path:**
1. Set up Simple Membership in parallel
2. Test with new users first
3. Migrate existing users using migration script
4. Verify all existing subscriptions work
5. Monitor for 1 week
6. Only then remove custom files

---

## 🔄 Version History

**v2.1.0** - Simple Membership Integration
- Added: Simple Membership integration
- Added: Role assignment automation
- Added: Custom email notifications
- Added: Permission checks
- Added: Comprehensive documentation
- Modified: Main plugin file
- Modified: Signup page template
- Deprecated: Custom Stripe integration (still available)

---

## 📄 File Structure

```
your-theme/
���── SIMPLE-MEMBERSHIP-INTEGRATION-GUIDE.md (Main guide)
├── SIMPLE-MEMBERSHIP-QUICK-START.md (Quick setup)
├── SIMPLE-MEMBERSHIP-SETUP.md (This file)
├── page-signup-swpm.php (New signup template)
└── rsvpplugin/
    ├── event-rsvp-plugin.php (Updated v2.1.0)
    └── includes/
        ├── swpm-integration.php (New integration)
        ├── stripe-integration.php (Old - commented out)
        └── stripe-ajax-handlers.php (Old - commented out)
```

---

## 🎯 Next Steps

### Immediate (Today)
1. Read the Quick Start Guide
2. Install Simple Membership
3. Create membership levels
4. Connect Stripe (test mode)

### This Week
1. Test all membership levels
2. Customize email templates
3. Update pricing page
4. Test webhook processing

### Before Launch
1. Complete all testing
2. Get Stripe account approved
3. Switch to live mode
4. Test with real transaction
5. Launch! 🚀

---

**Questions?** 
- Read the full integration guide
- Check troubleshooting sections
- Review Simple Membership documentation

**Ready to start?**
- Begin with: `SIMPLE-MEMBERSHIP-QUICK-START.md`

---

**Document Version:** 1.0  
**Last Updated:** December 2024  
**Integration Status:** Ready for Implementation

---

**🎉 Congratulations!**

You now have everything needed to integrate Simple Membership with your Event RSVP Platform. The custom Stripe integration has been successfully replaced with a professional, battle-tested membership solution.

**Good luck with your launch! 🚀**

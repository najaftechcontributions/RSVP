# Simple Membership - Quick Start Guide
## Event RSVP Platform Integration

**⚡ Fast Setup in 15 Minutes**

---

## 📋 Pre-Flight Checklist

Before you begin, make sure you have:

- [ ] WordPress Admin Access
- [ ] Stripe Account (get one at stripe.com - free)
- [ ] SSL Certificate installed (HTTPS required for payments)
- [ ] Backup of your database

---

## 🚀 5-Step Setup

### Step 1: Install Simple Membership Plugin (3 min)

1. Log into WordPress Admin
2. Go to: **Plugins > Add New**
3. Search for: **"Simple Membership"**
4. Click: **Install Now** (by smp7, wp.insider)
5. Click: **Activate**

✅ **Verify:** You should see "Simple Membership" in your admin menu.

---

### Step 2: Create Membership Levels (5 min)

Go to: **Simple Membership > Membership Levels > Add New**

Create these 4 levels exactly as shown:

#### Level 1: Free Attendee
```
Membership Level Name: Free Attendee
Default WordPress Role: Subscriber
Access Starts: Immediately After Registration
Subscription Duration: No Expiry
Price: 0
```
Click **Save**

#### Level 2: Event Host
```
Membership Level Name: Event Host
Default WordPress Role: event_host
Access Starts: Immediately After Payment
Subscription Duration: 1
Subscription Period: Months
Price: $19.00
Payment Button: Subscription (Stripe)
```
Click **Save**

#### Level 3: Vendor
```
Membership Level Name: Vendor
Default WordPress Role: vendor
Access Starts: Immediately After Payment
Subscription Duration: 1
Subscription Period: Months
Price: $29.00
Payment Button: Subscription (Stripe)
```
Click **Save**

#### Level 4: Pro
```
Membership Level Name: Pro (Host + Vendor)
Default WordPress Role: pro
Access Starts: Immediately After Payment
Subscription Duration: 1
Subscription Period: Months
Price: $39.00
Payment Button: Subscription (Stripe)
```
Click **Save**

✅ **Verify:** You should have 4 membership levels created.

---

### Step 3: Connect Stripe (3 min)

#### Get Your Stripe Keys

1. Go to: https://dashboard.stripe.com/test/apikeys
2. Copy your **Publishable key** (starts with `pk_test_`)
3. Copy your **Secret key** (starts with `sk_test_`)

#### Add Keys to Simple Membership

1. Go to: **Simple Membership > Payment Settings**
2. Click the **Stripe** tab
3. Paste your **Publishable Key**
4. Paste your **Secret Key**
5. Mode: Select **Test Mode**
6. Click **Save Changes**

✅ **Verify:** You should see "Stripe settings saved successfully."

---

### Step 4: Setup Stripe Webhooks (2 min)

#### In Simple Membership:
1. Go to: **Simple Membership > Payment Settings > Stripe**
2. Copy the **Webhook URL** (should be: `https://yourdomain.com/?swpm_process_ipn=1&stripe=1`)

#### In Stripe Dashboard:
1. Go to: https://dashboard.stripe.com/test/webhooks
2. Click: **Add endpoint**
3. Paste the Webhook URL
4. Click: **Select events**
5. Choose these events:
   - `checkout.session.completed`
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
6. Click: **Add events**
7. Click: **Add endpoint**
8. Copy the **Signing secret** (starts with `whsec_`)

#### Back in Simple Membership:
1. Paste the **Signing secret** in the Webhook Secret field
2. Click **Save Changes**

✅ **Verify:** Webhook should show "Active" in Stripe dashboard.

---

### Step 5: Update Signup Page (2 min)

1. Go to: **Pages > All Pages**
2. Find your **Signup** page
3. Click **Edit**
4. In the **Page Attributes** box on the right, change **Template** to: **Signup Page (Simple Membership)**
5. Click **Update**

✅ **Verify:** Visit your signup page - you should see the new registration form.

---

## 🧪 Test Your Setup

### Test Free Registration

1. Open a **private/incognito browser window**
2. Go to: `yoursite.com/signup/?plan=attendee`
3. Fill out the form
4. Click **Register**
5. ✅ Account should be created immediately
6. ✅ Welcome email should arrive
7. ✅ You should be able to login

### Test Paid Registration

1. Open a **private/incognito browser window**
2. Go to: `yoursite.com/signup/?plan=event_host`
3. Fill out the form
4. Click **Register**
5. You'll be redirected to Stripe checkout
6. Use test card: `4242 4242 4242 4242`
7. Expiry: Any future date
8. CVC: Any 3 digits
9. Click **Subscribe**
10. ✅ You should be redirected back to your site
11. ✅ Account should have "event_host" role
12. ✅ Welcome email should arrive

---

## 🎯 Quick Reference

### Test Credit Cards
```
Success: 4242 4242 4242 4242
Decline: 4000 0000 0000 0002
Requires Authentication: 4000 0025 0000 3155
```

### Important URLs
```
Signup Free: /signup/?plan=attendee
Signup Host: /signup/?plan=event_host
Signup Vendor: /signup/?plan=vendor
Signup Pro: /signup/?plan=pro
Pricing Page: /pricing/
Login Page: /login/
```

### Admin Shortcuts
```
Members: Simple Membership > Members
Levels: Simple Membership > Membership Levels
Settings: Simple Membership > Settings
Stripe: Simple Membership > Payment Settings > Stripe
```

---

## ✅ Go Live Checklist

Before accepting real payments:

### In Stripe Dashboard
1. [ ] Complete business profile
2. [ ] Add bank account for payouts
3. [ ] Submit activation request
4. [ ] Wait for approval
5. [ ] Get **live** API keys from: https://dashboard.stripe.com/apikeys

### In Simple Membership
1. [ ] Change Stripe mode to: **Live Mode**
2. [ ] Replace test keys with **live keys**
3. [ ] Update webhook URL to live endpoint
4. [ ] Test one small transaction ($1)
5. [ ] Monitor for 24 hours

### In WordPress
1. [ ] Backup database
2. [ ] Test all 4 membership levels
3. [ ] Verify emails are sending
4. [ ] Check SSL certificate is active
5. [ ] Update Terms of Service
6. [ ] Update Privacy Policy

---

## 🆘 Quick Troubleshooting

### Problem: Payment button not showing
**Solution:** 
- Check Stripe keys are entered correctly
- Verify membership level has price set
- Clear WordPress cache

### Problem: Role not assigned after payment
**Solution:**
- Check webhook is active in Stripe
- Verify webhook secret is correct
- Check WordPress error logs
- Manually assign role from: Users > All Users

### Problem: Emails not sending
**Solution:**
- Install WP Mail SMTP plugin
- Configure with SendGrid or Mailgun (free tiers available)
- Send test email

### Problem: Webhook errors
**Solution:**
- Verify webhook URL is correct
- Check SSL certificate is valid
- Test webhook in Stripe dashboard
- Enable logging in Simple Membership settings

---

## 📚 Full Documentation

For detailed information, see: `SIMPLE-MEMBERSHIP-INTEGRATION-GUIDE.md`

---

## 💡 Tips for Success

1. **Always test in Test Mode first** - Never use live keys until you're ready
2. **Use incognito windows for testing** - Prevents login conflicts
3. **Monitor the first week** - Check registrations and payments daily
4. **Setup email notifications** - Know when payments succeed/fail
5. **Keep Stripe dashboard open** - Watch transactions in real-time

---

## 🎉 You're Done!

Your Event RSVP Platform is now integrated with Simple Membership!

**What you can do now:**
- ✅ Accept payments via Stripe
- ✅ Manage subscriptions automatically
- ✅ Assign roles based on membership
- ✅ Handle upgrades and downgrades
- ✅ Track revenue in Stripe dashboard

**Next Steps:**
- Test all 4 membership levels
- Customize email templates
- Update your pricing page
- Promote your platform!

---

**Need Help?**
- Read the full guide: `SIMPLE-MEMBERSHIP-INTEGRATION-GUIDE.md`
- Simple Membership docs: https://simple-membership-plugin.com/
- Stripe docs: https://stripe.com/docs
- WordPress support: https://wordpress.org/support/

---

**Last Updated:** December 2024  
**Version:** 1.0

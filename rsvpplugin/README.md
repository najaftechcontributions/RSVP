# Event RSVP Platform Plugin

A complete, portable event management system for WordPress with RSVP functionality, QR code check-ins, attendee management, vendor advertising, and more.

## Features

✅ **Event Management** - Create and manage events with dates, venues, capacity limits  
✅ **RSVP System** - Accept RSVPs via forms with automated confirmation emails  
✅ **QR Code Check-ins** - Generate unique QR codes for each attendee  
✅ **Attendee Management** - Track attendees, check-in status, and export data  
✅ **Vendor Advertising** - Display ads in various locations with analytics  
✅ **User Roles** - Event Host, Vendor, Pro, and Event Staff roles  
✅ **Email Notifications** - Automated QR code delivery via WP SMTP  
✅ **Export to CSV** - Export attendee lists for reporting  
✅ **Custom Post Types** - Events, Attendees, Products, Vendor Ads  
✅ **ACF Integration** - Advanced custom fields for all features  

## Installation

### Quick Setup (3 Steps)

1. **Copy the Plugin Folder**
   - Copy the entire `rsvpplugin` folder to your theme directory

2. **Add to functions.php**
   - Add this single line to your theme's `functions.php`:
   ```php
   require_once get_template_directory() . '/rsvpplugin/event-rsvp-plugin.php';
   ```

3. **Install Required Plugins**
   - Install and activate:
     - Advanced Custom Fields (ACF)
     - Members (for role management)
     - Contact Form 7 (optional, for CF7 integration)
     - WP SMTP (for email delivery)

That's it! The plugin is now active.

## Required Plugins

| Plugin | Required | Purpose |
|--------|----------|---------|
| **Advanced Custom Fields** | Yes | Manages event/attendee fields |
| **Members** | Yes | User role management |
| **Contact Form 7** | Optional | Form integration (can use built-in forms) |
| **WP SMTP** | Recommended | Email delivery for QR codes |

## Configuration

### 1. Create Required Pages

Create these WordPress pages with the following templates:

- **Browse Events** - Template: `page-browse-events.php`
- **Host Dashboard** - Template: `page-host-dashboard.php`  
- **Event Create** - Template: `page-event-create.php`
- **Check-in** - Template: `page-check-in.php`
- **QR View** - Template: `page-qr-view.php`
- **RSVP** - Template: `page-rsvp.php`
- **Login** - Template: `page-login.php`
- **Signup** - Template: `page-signup.php`
- **Vendor Dashboard** - Template: `page-vendor-dashboard.php`
- **Ads Management** - Template: `page-ads-management.php`

### 2. Configure WP SMTP

1. Install WP SMTP plugin
2. Go to **WP SMTP Settings**
3. Configure your email provider (Gmail, SendGrid, etc.)
4. Test email delivery

### 3. Configure User Roles

The plugin automatically creates these roles:

- **Event Host** - Can create events and manage attendees
- **Vendor** - Can manage products and vendor ads
- **Pro** - Combined Event Host + Vendor capabilities
- **Event Staff** - View-only access for check-ins

### 4. Set Up ACF Fields

ACF fields are automatically registered. To verify:

1. Go to **Custom Fields** in WordPress admin
2. You should see field groups for:
   - Event Fields
   - Attendee Fields
   - Product Fields
   - Vendor Ad Fields

## Usage

### Creating an Event

1. Go to **Events → Add New**
2. Fill in event details:
   - Title and description
   - Event date and end date
   - Venue address
   - Max attendees (capacity)
   - QR code email schedule (days before event)
3. Publish the event

### Managing RSVPs

**Option 1: Built-in Forms**
- Use the RSVP page template
- Attendees fill out the form
- QR codes are automatically sent via email

**Option 2: Shortcode**
```php
[event_rsvp_form event_id="123"]
```

**Option 3: Contact Form 7**
- Create a CF7 form with these fields:
  - `attendee-name`
  - `attendee-email`
  - `attendee-phone`
  - `rsvp-status`
  - `event-id` (hidden)
  - `event-rsvp` (hidden, value="1")

### QR Code Check-in

1. Go to **Check-in** page
2. Use QR scanner or manual search
3. Scan attendee QR code
4. Attendee is marked as checked-in

### Exporting Attendees

From the Host Dashboard:
1. View your event
2. Click "Export Attendees to CSV"
3. Download includes: Name, Email, Phone, RSVP Status, Check-in Status

### Vendor Advertising

**Create an Ad:**
1. Go to **Vendor Ads → Add New**
2. Upload ad image
3. Set start/end dates
4. Choose slot location (sidebar, footer, homepage)
5. Add click URL

**Display Ads:**
```php
// Automatic display by location
<?php echo event_rsvp_display_vendor_ad('sidebar'); ?>

// Shortcode
[vendor_ad location="sidebar"]

// Specific ad
[ad id="123"]
```

## Shortcodes

```php
// RSVP Form
[event_rsvp_form event_id="123"]

// Upcoming Events List
[upcoming_events limit="5"]

// Vendor Ad by Location
[vendor_ad location="sidebar"]

// Single Ad by ID
[ad id="123"]
```

## File Structure

```
rsvpplugin/
├── event-rsvp-plugin.php       # Main plugin file
├── assets/
│   ├── css/
│   │   ├── event-rsvp.css      # Main styles
│   │   ├── event-templates.css  # Event page styles
│   │   ├── vendor-ads-display.css
│   │   ├── vendor-dashboard.css
│   │   └── ads-management.css
│   └── js/
│       ├── event-rsvp.js        # QR scanner & check-ins
│       └── vendor-ads.js        # Ad click tracking
└── includes/
    ├── post-types.php           # Custom post types
    ├── user-roles.php           # User role management
    ├── acf-fields.php           # ACF field groups
    ├── helper-functions.php     # Utility functions
    ├── ajax-handlers.php        # AJAX endpoints
    ├── form-handlers.php        # Form submissions
    ├── email-functions.php      # Email templates
    ├── shortcodes.php           # Shortcode handlers
    └── admin-functions.php      # Admin features
```

## Helper Functions

```php
// Get event statistics
event_rsvp_get_event_stats($event_id);

// Check if event is full
event_rsvp_is_event_full($event_id);

// Get available spots
event_rsvp_get_available_spots($event_id);

// Get attendees by event
event_rsvp_get_attendees_by_event($event_id, $status);

// Get upcoming events
event_rsvp_get_upcoming_events($limit);

// Get user's events
event_rsvp_get_user_events($user_id);

// Display vendor ad
event_rsvp_display_vendor_ad($location);

// Generate QR code
event_rsvp_generate_qr_code($data);
```

## Email Customization

Edit `rsvpplugin/includes/email-functions.php` to customize:
- Email template HTML
- Email subject lines
- From name/email
- Scheduling logic

## Security

- All AJAX requests use WordPress nonces
- User capabilities are checked for sensitive operations
- QR codes include verification hashes
- SQL injection protection via WordPress sanitization
- XSS protection with esc_* functions

## Performance

- QR codes can use external API or plugin
- Database queries are optimized with proper indexes
- Assets are conditionally loaded based on page templates
- Ad impressions/clicks are tracked efficiently

## Troubleshooting

**QR codes not sending?**
- Check WP SMTP configuration
- Verify email sending works
- Check WordPress cron is running

**ACF fields not showing?**
- Make sure ACF plugin is active
- Check field group locations are set correctly

**User roles missing?**
- Deactivate and reactivate theme
- Or manually trigger: `event_rsvp_add_custom_roles()`

**Permissions errors?**
- Verify user has correct role
- Check capability requirements in code

## Uninstallation

To remove the plugin:

1. Remove this line from `functions.php`:
   ```php
   require_once get_template_directory() . '/rsvpplugin/event-rsvp-plugin.php';
   ```

2. Optionally delete the `rsvpplugin` folder

3. To remove data:
   - Delete all Events, Attendees, Products, Vendor Ads posts
   - Remove custom roles via Members plugin
   - Uninstall ACF, Members, CF7, WP SMTP if not needed

## Support & Credits

- **Package**: Event RSVP Platform Plugin
- **Version**: 2.0.0
- **License**: GPL-2.0-or-later
- **Compatibility**: WordPress 5.0+ with PHP 7.4+

Built for easy portability and implementation in any WordPress theme.

---

For questions or issues, refer to the code documentation within each file.

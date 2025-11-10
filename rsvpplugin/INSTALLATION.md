# Quick Installation Guide

## 1. Copy Plugin Folder
Copy the `rsvpplugin` folder to your WordPress theme directory.

## 2. Add to functions.php
Add this line to your theme's `functions.php`:
```php
require_once get_template_directory() . '/rsvpplugin/event-rsvp-plugin.php';
```

## 3. Install Required Plugins
- Advanced Custom Fields (ACF)
- Members
- WP SMTP (for email delivery)
- Contact Form 7 (optional)

## 4. Done!
The plugin is now active. See README.md for full documentation.

# Ad Shortcodes - Quick Reference Guide

## 🎯 Available Shortcodes

### 1. Location-Based Ad Display
Shows a **random** active ad from the specified location.

```
[vendor_ad location="LOCATION_NAME"]
```

#### Available Locations:
| Location Code | Description |
|--------------|-------------|
| `home_1` | Homepage Slot 1 |
| `home_2` | Homepage Slot 2 |
| `home_3` | Homepage Slot 3 |
| `sidebar_1` | Sidebar Slot 1 |
| `sidebar_2` | Sidebar Slot 2 |
| `sidebar_3` | Sidebar Slot 3 |
| `sidebar_4` | Sidebar Slot 4 |
| `events_1` | Events Page Slot 1 |
| `events_2` | Events Page Slot 2 |
| `events_3` | Events Page Slot 3 |
| `events_4` | Events Page Slot 4 |

#### Examples:
```
[vendor_ad location="home_1"]
[vendor_ad location="sidebar_2"]
[vendor_ad location="events_1"]
```

---

### 2. Show ALL Ads in a Location
Displays **all** active ads from a location (stacked vertically).

```
[vendor_ad location="LOCATION_NAME" show_all="true"]
```

#### Example:
```
[vendor_ad location="home_1" show_all="true"]
```

---

### 3. Display Specific Ad by ID
Shows a particular ad regardless of location.

```
[ad id="123"]
```

Replace `123` with the actual ad ID (found in the Ads Management table).

#### Example:
```
[ad id="45"]
```

---

### 4. Preview Mode
Display an ad in preview mode (for testing, doesn't track impressions).

```
[ad id="123" preview="true"]
[vendor_ad location="home_1" preview="true"]
```

---

## 📍 Where to Use Shortcodes

### WordPress Page/Post Editor
Simply paste the shortcode in the content editor:

```
Some content here...

[vendor_ad location="home_1"]

More content...
```

### Gutenberg (Block Editor)
1. Add a **Shortcode** block
2. Paste your shortcode
3. Preview or publish

### Elementor
1. Add a **Shortcode** widget
2. Enter your shortcode in the widget settings
3. Style as needed

### Theme Template Files
Use PHP's `do_shortcode()` function:

```php
<?php echo do_shortcode('[vendor_ad location="sidebar_1"]'); ?>
```

### Sidebar Widgets
1. Add a **Text** or **HTML** widget to your sidebar
2. Paste the shortcode
3. Save

---

## 🔧 PHP Functions (For Developers)

### Display Ad by Location
```php
<?php 
// Random ad from location
echo event_rsvp_display_vendor_ad('home_1'); 

// All ads from location
echo event_rsvp_display_vendor_ad('home_1', false, true); 

// Preview mode
echo event_rsvp_display_vendor_ad('home_1', true); 
?>
```

### Get Active Ads (returns array of posts)
```php
<?php
$ads = event_rsvp_get_active_vendor_ads('sidebar_1');

foreach ($ads as $ad) {
    echo get_the_title($ad->ID);
}
?>
```

### Get All Ad Locations
```php
<?php
$locations = event_rsvp_get_ad_locations();

foreach ($locations as $key => $label) {
    echo "$key: $label<br>";
}
?>
```

---

## 📋 Common Use Cases

### Homepage Hero Ad
Place at the top of your homepage:
```
[vendor_ad location="home_1"]
```

### Sidebar Ad Widget
Add to your sidebar widget area:
```
[vendor_ad location="sidebar_1"]
```

### Between Blog Posts
In archive or blog templates:
```php
<?php if (have_posts()) : ?>
    <?php while (have_posts()) : the_post(); ?>
        <?php the_content(); ?>
    <?php endwhile; ?>
    
    <?php echo do_shortcode('[vendor_ad location="home_2"]'); ?>
    
<?php endif; ?>
```

### Events Archive Page
At the top of events listing:
```
[vendor_ad location="events_1"]
```

At the bottom:
```
[vendor_ad location="events_4"]
```

### Show Multiple Homepage Ads
```
[vendor_ad location="home_1" show_all="true"]
```

---

## ⚙️ Shortcode Parameters Reference

### `[vendor_ad]` Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `location` | string | `sidebar_1` | Location code (required) |
| `preview` | boolean | `false` | Enable preview mode |
| `show_all` | boolean | `true` | Show all ads vs random |

### `[ad]` Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `id` | integer | `0` | Ad post ID (required) |
| `preview` | boolean | `false` | Enable preview mode |

---

## 🎨 Styling Ad Displays

All ads have CSS classes you can target:

```css
/* Target all ads */
.vendor-ad-wrapper { }

/* Target ads by location */
.vendor-ad-home_1 { }
.vendor-ad-sidebar_1 { }
.vendor-ad-events_1 { }

/* Target the container */
.vendor-ad-container { }

/* Target the image */
.vendor-ad-image img { }

/* Target the overlay */
.vendor-ad-overlay { }

/* Target multiple ads container */
.vendor-ads-multiple { }
```

### Example Custom Styling
```css
/* Make sidebar ads square */
.vendor-ad-sidebar_1 .vendor-ad-image {
    aspect-ratio: 1 / 1;
}

/* Larger homepage ads */
.vendor-ad-home_1 .vendor-ad-container {
    max-width: 1400px;
}

/* Add spacing between multiple ads */
.vendor-ads-multiple {
    gap: 40px;
}
```

---

## 🚀 Performance Tips

1. **Use specific locations** rather than displaying many random ads
2. **Limit show_all** to avoid too many ads on one page
3. **Optimize ad images** before uploading (compress, resize)
4. **Use lazy loading** (automatically applied to ad images)
5. **Monitor page load times** when adding multiple ads

---

## ✅ Checklist Before Going Live

- [ ] Ad has been **approved** by admin
- [ ] Ad status is set to **active**
- [ ] Start date is **today or earlier**
- [ ] End date is **in the future**
- [ ] Ad has a **featured image** uploaded
- [ ] Shortcode **location** matches ad's assigned location
- [ ] Click URL is **valid** and tested
- [ ] Preview looks good on **mobile** and desktop

---

## 🆘 Quick Troubleshooting

**Ad not showing?**
```
Check: Active ✓ | Approved ✓ | Has Image ✓ | Dates Valid ✓
```

**Wrong ad showing?**
- Verify the `location` parameter matches the ad's assigned location
- Check if multiple ads exist for that location (random rotation)

**No ads in location?**
- Create an ad and assign it to that location
- Approve and activate the ad
- Verify the date range includes today

---

## 📞 Getting Ad IDs

To find an ad's ID for use in `[ad id="X"]`:

1. Go to **Ads Management** dashboard
2. Look in the **Details** column
3. ID is shown as: `ID: 123`
4. Or check the **Shortcode** column for the ready-to-use shortcode

---

## 🎉 Examples Library

### Simple Homepage Banner
```
[vendor_ad location="home_1"]
```

### Multiple Sidebar Ads
```
[vendor_ad location="sidebar_1"]

Some content...

[vendor_ad location="sidebar_2"]
```

### Events Page with Top & Bottom Ads
```
[vendor_ad location="events_1"]

<!-- Events listing here -->

[vendor_ad location="events_4"]
```

### Show All Active Home Ads
```
[vendor_ad location="home_1" show_all="true"]
```

### Specific Ad Anywhere
```
[ad id="42"]
```

### Preview Before Publishing
```
[vendor_ad location="home_2" preview="true"]
```

---

**Copy and paste these shortcodes directly into your content!**

Last Updated: [Current Date]

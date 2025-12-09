# Email Campaign System - Fixes and Improvements

## ✅ Issues Fixed

### 1. Event Host Name Display
**Problem**: Campaign preview was showing campaign creator name instead of event host name.

**Solution**: 
- Updated `event_rsvp_ajax_preview_email_template()` to correctly retrieve event host name
- Updated `event_rsvp_ajax_get_campaign_preview()` to use event host name
- Updated `event_rsvp_send_campaign_email()` to use event host name

**Logic**:
1. First checks for `event_host` custom field (works with both ACF and non-ACF)
2. If event host is not set, falls back to event creator (post author)
3. If event creator is not found, uses site name as final fallback

### 2. Default Template Preview
**Problem**: Default template (template_id = 0) was not previewing.

**Solution**: 
- Modified `event_rsvp_ajax_preview_email_template()` to handle template_id = 0
- When template_id is 0 or not set, uses `event_rsvp_get_default_email_html()` function
- Preview modal now shows "Default HTML Template" when no template is selected

### 3. Template Change in Manage Campaign
**Problem**: Unable to change email template when editing/managing a campaign.

**Solution**:
- Added template dropdown to the Settings tab in manage campaign modal
- Updated `event_rsvp_ajax_get_campaign_settings()` to return current template_id
- Updated `event_rsvp_ajax_update_campaign_settings()` to save template_id changes
- Added `loadCampaignSettings()` function to load and display current template

### 4. Image Field Visibility
**Problem**: Image upload field wasn't showing when template requires custom image.

**Solution**:
- Added `updateImageFieldVisibility()` JavaScript function
- Image field shows when:
  - Template HTML contains `{{custom_image}}` placeholder
  - Template name contains the word "image"
- Added event handlers for template dropdown changes
- Added new "Image Banner Template" that uses custom images

### 5. Campaign Preview Tab
**Problem**: Preview tab in manage campaign modal wasn't loading preview.

**Solution**:
- Added `loadCampaignPreview()` JavaScript function
- Preview automatically loads when switching to Preview tab
- Added "Refresh Preview" button to reload preview after making changes
- Preview uses saved campaign data including custom image

## 🎨 New Features

### Image Banner Template
Added a new email template specifically designed for custom event images:
- **Name**: "Image Banner Template"
- **Features**: 
  - Custom image banner at the top
  - Professional gradient header
  - Event details in styled box
  - Fully responsive design
  - Uses `{{custom_image}}` placeholder

### Custom Image Upload
- Image URL input field with preview
- Upload button (prompts for URL)
- Remove image button
- Live preview of selected image
- Image field automatically shows/hides based on template selection

## 📝 How to Test

### Test 1: Event Host Name Display
1. Create or edit an event
2. Set the "Event Host" field to a custom name (e.g., "John Smith")
3. Create a new email campaign for this event
4. Click "Preview Template" - should show "John Smith has invited you to..."
5. Send test email - should show "John Smith" as the host

### Test 2: Default Template Preview
1. Create new campaign
2. Select "Use Default HTML Template" from template dropdown
3. Click "Preview Template" - should show the default template
4. Preview should display event host name correctly

### Test 3: Change Template in Manage Campaign
1. Create a campaign with any template
2. Click "Edit" on the campaign
3. Go to "Settings" tab
4. Change the "Email Template" dropdown to a different template
5. Click "Save Settings"
6. Go to "Preview" tab and click "Refresh Preview"
7. Preview should show the new template

### Test 4: Image Field Visibility
1. Create new campaign
2. Select "Image Banner Template" from dropdown
3. Image field should appear automatically
4. Enter an image URL (e.g., https://via.placeholder.com/600x300)
5. Image preview should appear below the input field
6. Switch to a different template - image field should hide (if template doesn't use images)

### Test 5: Campaign Preview Refresh
1. Create and save a campaign
2. Click "Edit" on the campaign
3. Go to "Settings" tab
4. Change campaign name, subject, or image
5. Click "Save Settings"
6. Go to "Preview" tab
7. Click "Refresh Preview" - should show updated information

## 🔧 Technical Details

### Modified Files

1. **rsvpplugin/includes/email-invitation-ajax.php**
   - Fixed `event_rsvp_ajax_preview_email_template()` to handle default template
   - Updated to return `template_needs_image` flag
   - Enhanced `event_rsvp_ajax_get_campaign_settings()` response

2. **page-email-campaigns.php**
   - Added template change handlers
   - Added preview loading functionality
   - Added image field visibility logic
   - Updated campaign settings save to include template_id
   - Added custom image preview handlers

3. **rsvpplugin/includes/email-invitation-db.php**
   - Added new "Image Banner Template" with `{{custom_image}}` placeholder

### Key Functions Added

- `loadCampaignPreview(campaignId)` - Loads campaign preview via AJAX
- `updateImageFieldVisibility(templateSelector, imageGroupSelector, forceShow)` - Shows/hides image field
- Enhanced `loadCampaignSettings(campaignId)` - Loads and sets template selection

## ✨ Available Email Templates

1. **Default HTML Template** (template_id = 0)
   - Basic invitation template
   - Always available as fallback

2. **Modern Invitation**
   - Clean and modern design
   - Gradient header
   - Professional styling

3. **Simple & Clean**
   - Minimalist design
   - Clean typography
   - Black and white theme

4. **Colorful & Fun**
   - Vibrant and energetic
   - Perfect for social events
   - Gradient backgrounds

5. **Professional Event**
   - Corporate styling
   - Formal invitation format
   - Traditional design

6. **Image Banner Template** ⭐ NEW
   - Custom image banner support
   - Uses `{{custom_image}}` placeholder
   - Responsive image display

## 🎯 Template Placeholders

All templates support these placeholders:
- `{{event_name}}` - Event title
- `{{event_date}}` - Formatted event date
- `{{event_time}}` - Event time (12-hour format)
- `{{event_location}}` - Venue address
- `{{event_description}}` - Event description/excerpt
- `{{host_name}}` - Event host name (from event_host field or event creator)
- `{{recipient_name}}` - Recipient's name
- `{{tracking_url}}` - Trackable event link
- `{{unsubscribe_url}}` - Unsubscribe link
- `{{custom_image}}` - Custom event image URL (Image Banner Template only)

## 📌 Notes

- Event host name is now consistently pulled from the `event_host` custom field across all email functions
- Default template is always available and can be previewed
- Template changes are saved and reflected in campaign previews
- Image field visibility is intelligent and template-aware
- All changes are backward compatible with existing campaigns

## 🚀 Next Steps

Consider these future enhancements:
1. WordPress media library integration for image uploads
2. Drag-and-drop template customization
3. Template preview thumbnails
4. A/B testing different templates
5. Custom template creation interface

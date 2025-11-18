# Venue Map URL & Past Event Updates

## Summary of Changes

This document outlines the updates made to handle Google Maps iframe embeds and display "Event is Over" messages for past events.

## 1. Venue Map URL Field Enhancement

### What Changed
- Updated the `venue_map_url` ACF field from a simple URL field to a textarea field
- Added automatic extraction of the `src` attribute from Google Maps iframe code
- Users can now paste the entire iframe code or just the URL

### Files Modified
1. **rsvpplugin/includes/acf-fields.php**
   - Changed field type from `url` to `textarea`
   - Added instructions and placeholder text
   - Added ACF filter to automatically extract src from iframe on save

2. **single-event.php**
   - Added PHP logic to extract iframe src before displaying the map
   - Added `referrerpolicy="no-referrer-when-downgrade"` attribute for better compatibility

### How It Works
**Before:** Users had to manually extract the src URL from Google Maps iframe code.

**Now:** Users can paste the entire iframe code like:
```html
<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d31529.720228510032!2d73.1676672!3d33.6166912!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x38dfeb9cd333e521%3A0xde1ea4986b7188c!2sGhauri%20Town%20Phase%205-A%2C%20Street%204A%2C%20Phase%205%20Ghauri%20Town%2C%20Islamabad%2C%20Pakistan!5e1!3m2!1sen!2s!4v1763452045354!5m2!1sen!2s" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
```

And the system will automatically extract and save:
```
https://www.google.com/maps/embed?pb=!1m18!1m12...
```

## 2. Past Event Handling

### What Changed
- Events that have passed now show "Event is Over" message instead of the RSVP form
- Similar to how full events display a capacity message

### Files Modified
1. **single-event.php**
   - Added check for `$is_past` before checking `$is_full`
   - Displays "⏰ This event is over. RSVPs are no longer accepted." message
   - Prevents RSVP form from showing for past events

2. **template-parts/event-card.php**
   - Updated button logic to prioritize past events over full events
   - Past events show "📄 View Details" button instead of RSVP button
   - Added CSS classes for styling: `event-action-past` and `event-action-full`

### Priority Order
The system now checks in this order:
1. **Is Past?** → Show "Event is Over" message
2. **Is Full?** → Show "Event at Full Capacity" message
3. **Normal** → Show RSVP form

## Technical Details

### ACF Filter Implementation
```php
function event_rsvp_extract_map_url($value, $post_id, $field) {
    if (empty($value)) {
        return $value;
    }

    // Extract src from iframe if present
    if (strpos($value, '<iframe') !== false) {
        preg_match('/src=["\']([^"\']+)["\']/', $value, $matches);
        if (!empty($matches[1])) {
            return $matches[1];
        }
    }

    return $value;
}
add_filter('acf/update_value/name=venue_map_url', 'event_rsvp_extract_map_url', 10, 3);
```

### Display Logic (single-event.php)
```php
<?php if ($venue_map_url) : 
    $map_src = $venue_map_url;
    // Extract src if full iframe was provided
    if (strpos($venue_map_url, '<iframe') !== false) {
        preg_match('/src=["\']([^"\']+)["\']/', $venue_map_url, $matches);
        if (!empty($matches[1])) {
            $map_src = $matches[1];
        }
    }
?>
    <div class="event-map-section">
        <h3>Event Location</h3>
        <div class="map-embed">
            <iframe src="<?php echo esc_url($map_src); ?>" ...></iframe>
        </div>
    </div>
<?php endif; ?>
```

### Past Event Check
```php
<?php if ($is_past) : ?>
    <div class="rsvp-full-message">
        ⏰ This event is over. RSVPs are no longer accepted.
    </div>
<?php elseif ($is_full) : ?>
    <div class="rsvp-full-message">
        ⚠ This event is at full capacity. Please check back later for cancellations.
    </div>
<?php else : ?>
    <!-- RSVP Form -->
<?php endif; ?>
```

## User Experience Improvements

### For Event Hosts
1. **Easier Map Embedding**: Just copy-paste from Google Maps without manual URL extraction
2. **Automatic Processing**: The system handles iframe parsing automatically
3. **Backward Compatible**: Still works with direct URLs

### For Event Attendees
1. **Clear Past Event Status**: Immediately see when an event has passed
2. **No Confusion**: Can't attempt to RSVP for past events
3. **Consistent UX**: Same message style as the "full event" notification

## Testing Recommendations

1. **Test Map URL Field**:
   - Paste full Google Maps iframe → verify map displays correctly
   - Paste just the src URL → verify map displays correctly
   - Edit existing events → verify existing maps still work

2. **Test Past Event Display**:
   - Create an event with past date → verify "Event is Over" message shows
   - Verify RSVP form does NOT show for past events
   - Verify event cards show "View Details" for past events
   - Verify past events have `event-past` CSS class applied

3. **Test Priority Order**:
   - Past + Full event → should show "Event is Over" (past takes priority)
   - Future + Full event → should show "Full Capacity" message
   - Future + Not Full → should show RSVP form

## Future Enhancements (Optional)

1. Add support for other map providers (Apple Maps, OpenStreetMap, etc.)
2. Add visual preview of extracted map in event creation form
3. Add timezone handling for more accurate past event detection
4. Add optional "grace period" for late RSVPs to past events
5. Add analytics for past event views

## Conclusion

These updates improve the user experience for both event hosts and attendees by:
- Simplifying the map embedding process
- Providing clear feedback about event status
- Preventing confusion with past events
- Maintaining consistency with existing UI patterns

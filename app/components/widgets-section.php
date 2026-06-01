<?php
/**
 * UI COMPONENT
 *
 * Responsibility:
 * - Render HTML for a single component type
 * - Use only provided $data array
 *
 * Rules:
 * - MUST NOT access CMS
 * - MUST NOT access cache
 * - MUST NOT perform routing or data fetching
 *
 * This file is PURE PRESENTATION LAYER.
 */
 
 
/**
 * app/components/widgets-section.php
 *
 * Home Page Widgets Section
 * Displays Facebook feed and Google Calendar embed side-by-side.
 */

$calendarId = config('google_calendar_id', '');

?>

<div class="row g-4">

    <div class="col-md-6">
        <h5>Facebook Updates</h5>

        <iframe
            src="https://www.facebook.com/plugins/page.php?href=<?= urlencode(config('facebook_page_id', '')) ?>&tabs=timeline&width=500&height=600"
            width="100%"
            height="600"
            style="border:none;overflow:hidden"
            scrolling="no"
            frameborder="0"
            allowfullscreen="true">
        </iframe>
    </div>

    <div class="col-md-6">
        <h5>Upcoming Events</h5>

        <?php if (!empty($calendarId)): ?>
            <iframe
                src="https://calendar.google.com/calendar/embed?src=<?= urlencode($calendarId) ?>&mode=AGENDA"
                width="100%"
                height="600"
                frameborder="0"
                scrolling="no">
            </iframe>
        <?php else: ?>
            <p class="text-muted">Calendar not configured.</p>
        <?php endif; ?>
    </div>

</div>

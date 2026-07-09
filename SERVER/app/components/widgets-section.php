<?php
/**
 * UI COMPONENT: Widgets Section (Side-by-Side)
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
 *
 * app/components/widgets-section.php
 *
 * Home Page Widgets Section
 * Displays Facebook feed and Google Calendar embed side-by-side.
 */

$calendarId = $google_calendar_id ?? '';
$schoolFacebookUrl = $school_facebook_url ?? '';

// DEBUG: View Page Source to see these
echo "<!-- Debug: FB URL is: " . htmlspecialchars($schoolFacebookUrl) . " -->";
echo "<!-- Debug: CAL ID is: " . htmlspecialchars($calendarId) . " -->";

if (empty($calendarId) && empty($schoolFacebookUrl)) {
    echo "<!-- Widgets Hidden: Both fields empty -->";
    return;
}
?>

<div class="row g-4 my-4">
    <?php if (!empty($schoolFacebookUrl)): ?>
        <div class="<?= empty($calendarId) ? 'col-12' : 'col-md-6' ?>">
            <h5 class="mb-3 border-bottom pb-2">Facebook Updates</h5>
            <div class="fb-container bg-light rounded shadow-sm overflow-hidden" style="height: 600px; min-width: 280px;">
                <iframe
                    src="https://www.facebook.com/plugins/page.php?href=<?= urlencode($schoolFacebookUrl) ?>&tabs=timeline&width=500&height=600&small_header=false&adapt_container_width=true&hide_cover=false&show_facepile=true"
                    width="100%" 
                    height="600" 
                    style="border:none;overflow:hidden" 
                    scrolling="no" 
                    frameborder="0" 
                    allowfullscreen="true" 
                    allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share">
                </iframe>
            </div>
        </div>
    <?php endif; ?>
	<?php if (!empty($calendarId)): ?>
		<div class="<?= empty($schoolFacebookUrl) ? 'col-12' : 'col-md-6' ?>">
			<h5 class="mb-3 border-bottom pb-2">Upcoming Events</h5>
			<div class="calendar-container rounded shadow-sm overflow-hidden border" style="height: 600px;">
				<iframe
					src="https://calendar.google.com/calendar/embed?src=<?= urlencode($calendarId) ?>&mode=AGENDA&wkst=1&bgcolor=%23ffffff&ctz=America%2FChicago"
					width="100%" height="600" frameborder="0" scrolling="no">
				</iframe>
			</div>
		</div>
	<?php endif; ?>
</div>
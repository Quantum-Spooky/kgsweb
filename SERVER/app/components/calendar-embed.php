<?php
/**
 * UI COMPONENT: Calendar Embed
 * app/components/calendar-embed.php
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

$calId = $calendar_id ?? config('google_calendar_id');
$title = $title ?? ''; // Passed from Smart Grid
if (empty($calId)) {
    echo "<!-- Calendar Hidden: No calendar ID set in config -->";
    return;
}
?>

<div class="kgs-widget-container">
    <?php if (!empty($title) && $title !== 'None'): ?>
        <h5 class="rich-text-title">
            <span><?= htmlspecialchars($title) ?></span>
            <a href="<?= config('base_url') ?>calendar/" class="btn btn-sm btn-outline-success">View All</a>
        </h5>
    <?php endif; ?>

    <div class="ratio ratio-4x3 shadow-sm rounded border overflow-hidden bg-white">
        <iframe src="https://calendar.google.com/calendar/embed?src=<?= urlencode($calId) ?>&wkst=1&bgcolor=%23ffffff&ctz=America%2FChicago&mode=AGENDA" style="border: 0" frameborder="0" scrolling="no"></iframe>
    </div>
    
    <div class="mt-3 text-end d-flex gap-2 justify-content-end">
        <a href="https://calendar.google.com/calendar/render?cid=<?= urlencode($calId) ?>" 
           target="_blank" class="btn btn-xs btn-light border small text-muted" style="font-size:0.75rem;">
           <i class="fa-solid fa-calendar-plus"></i> Add to My Google Calendar
        </a>
    </div>
</div>
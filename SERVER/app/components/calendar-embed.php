<?php
/**
 * UI COMPONENT: Calendar Embed (Advanced Integrated Calendar)
 * app/components/calendar-embed.php
 *
 * Responsibility:
 * - Render HTML for an advanced calendar with Google Calendar, Agenda view, and static PDFs.
 * - Use provided data and retrieve files from GoogleDriveCache.
 *
 * Rules:
 * - Pure Presentation Layer.
 */

$calId = $calendar_id ?? $data['calendar_id'] ?? config('google_calendar_id');
if (empty($calId)) {
    $calId = 'c_35c7f773dea0cc46099f7607201bed993a0a29d94d5456aa00594ed16ffb5071@group.calendar.google.com';
}
$title = $title ?? $data['title'] ?? ''; 
$primaryColor = config('color_primary', '#015BA7');
$isCalendarPage = str_contains($_SERVER['REQUEST_URI'] ?? '', '/calendar');

// Helper function to find folder in cache tree
if (!function_exists('kgs_find_folder_in_tree')) {
    function kgs_find_folder_in_tree($items, string $targetId) {
        if (!is_array($items)) return null;
        foreach ($items as $item) {
            if (!is_array($item)) continue;
            if (($item['id'] ?? '') === $targetId) return $item['children'] ?? [];
            if (!empty($item['children'])) {
                $found = kgs_find_folder_in_tree($item['children'], $targetId);
                if ($found !== null) return $found;
            }
        }
        return null;
    }
}

// Helper function to get the latest file in a Google Drive folder
if (!function_exists('kgs_get_latest_file_from_folder')) {
    function kgs_get_latest_file_from_folder($folderId) {
        if (empty($folderId) || str_starts_with($folderId, '@')) {
            return null;
        }
        $masterRootId = config('master_root_folder_id', '1o0X89kQL_cXEUpPOSZXcCUFz8bzCtmAp');
        $masterTree = GoogleDriveCache::get('drive-trees', 'tree_' . $masterRootId, 0);
        $items = kgs_find_folder_in_tree($masterTree, $folderId);
        if (empty($items)) {
            $items = GoogleDriveCache::get('drive-trees', 'tree_' . $folderId, 0);
        }
        if (empty($items)) return null;

        usort($items, function($a, $b) {
            $da = function_exists('kgs_fl_extract_sort_date') ? kgs_fl_extract_sort_date($a['name'] ?? '') : null;
            $db = function_exists('kgs_fl_extract_sort_date') ? kgs_fl_extract_sort_date($b['name'] ?? '') : null;
            if ($da && $db) return strcmp($db, $da); 
            return strcmp($b['modifiedTime'] ?? '', $a['modifiedTime'] ?? '');
        });
        return $items[0] ?? null;
    }
}

// Fetch files dynamically from Google Drive for PDF/Image tab
$academicFolderId = config('academic_calendar_folder_id');
$academicFile = kgs_get_latest_file_from_folder($academicFolderId);
$academicExt = $academicFile ? strtolower(pathinfo($academicFile['name'], PATHINFO_EXTENSION)) : '';
$academicIsImage = in_array($academicExt, ['jpg', 'jpeg', 'png', 'gif']);
$academicUrl = $academicFile ? $academicFile['webViewLink'] : '#';
$academicEmbedUrl = '';
if ($academicFile) {
    if ($academicIsImage) {
        $academicEmbedUrl = get_drive_url($academicFile['id'], 1200);
    } else {
        $academicEmbedUrl = "https://drive.google.com/file/d/" . $academicFile['id'] . "/preview?view=FitH";
    }
}

$monthlyFolderId = config('monthly_calendar_folder_id');
$monthlyFile = kgs_get_latest_file_from_folder($monthlyFolderId);
$monthlyExt = $monthlyFile ? strtolower(pathinfo($monthlyFile['name'], PATHINFO_EXTENSION)) : '';
$monthlyIsImage = in_array($monthlyExt, ['jpg', 'jpeg', 'png', 'gif']);
$monthlyUrl = $monthlyFile ? $monthlyFile['webViewLink'] : '#';
$monthlyEmbedUrl = '';
if ($monthlyFile) {
    if ($monthlyIsImage) {
        $monthlyEmbedUrl = get_drive_url($monthlyFile['id'], 1200);
    } else {
        $monthlyEmbedUrl = "https://drive.google.com/file/d/" . $monthlyFile['id'] . "/preview?view=FitH";
    }
}

$categories = [
    ['key' => 'kgs', 'label' => 'School Events', 'color' => '#015BA7'],
    ['key' => 'board', 'label' => 'School Board Meetings', 'color' => '#6f42c1'],
    ['key' => 'athletics', 'label' => 'Athletics & Sports', 'color' => '#198754'],
    ['key' => 'pto', 'label' => 'PTO Events', 'color' => '#fd7e14']
];

$schoolEvents = [
    ['id' => 'e-1', 'title' => 'KGS Back-to-School Picnic', 'date' => '2026-08-15', 'time' => '5:00 PM - 7:00 PM', 'location' => 'KGS Playground', 'category' => 'pto'],
    ['id' => 'e-2', 'title' => 'First Day of School (Early Dismissal)', 'date' => '2026-08-18', 'time' => '8:00 AM - 11:30 AM', 'location' => 'Kell Grade School Campus', 'category' => 'kgs'],
    ['id' => 'e-3', 'title' => 'Regular School Board Meeting', 'date' => '2026-08-20', 'time' => '6:00 PM', 'location' => 'KGS Main Library', 'category' => 'board'],
    ['id' => 'e-4', 'title' => 'KGS Boys Baseball vs. Iuka', 'date' => '2026-08-24', 'time' => '4:30 PM', 'location' => 'Iuka Athletic Fields', 'category' => 'athletics'],
    ['id' => 'e-5', 'title' => 'Regular School Board Meeting - Budget Hearing', 'date' => '2026-09-17', 'time' => '6:00 PM', 'location' => 'KGS Main Library', 'category' => 'board'],
    ['id' => 'e-6', 'title' => 'KGS Girls Volleyball vs. Raccoon', 'date' => '2026-09-22', 'time' => '5:00 PM', 'location' => 'KGS Gymnasium', 'category' => 'athletics'],
    ['id' => 'e-7', 'title' => 'Fall PTO Cookie Dough Fundraiser Begins', 'date' => '2026-10-02', 'time' => 'All Day', 'location' => 'KGS District', 'category' => 'pto']
];
?>

<style>
/* Header view switcher styles */
.kgs-cal-tab-btn {
    color: #6c757d;
    background: transparent;
    border: none;
    transition: all 0.2s ease-in-out;
}
.kgs-cal-tab-btn:hover {
    color: #212529;
}
.kgs-cal-tab-btn.active {
    color: #212529 !important;
    background-color: #ffffff !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05) !important;
    font-weight: 700 !important;
}

/* Event filter buttons */
.kgs-cal-filter-btn {
    transition: all 0.2s ease-in-out;
}
.kgs-cal-filter-btn:hover {
    background-color: #f8f9fa !important;
}
.kgs-cal-filter-btn.active {
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

/* PDF select buttons */
.kgs-pdf-select-btn {
    border-radius: 12px;
    border: 1px solid #e9ecef;
    box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    background: #ffffff;
}
.kgs-pdf-select-btn:hover {
    border-color: #ced4da;
    background-color: #f8f9fa;
}
.kgs-pdf-select-btn.active {
    border-color: <?= $primaryColor ?> !important;
    background-color: <?= $primaryColor ?>05 !important;
    box-shadow: 0 0 0 3px <?= $primaryColor ?>15 !important;
}

/* Event Row hover */
.kgs-cal-event-row {
    transition: background-color 0.15s ease-in-out;
    border-radius: 8px;
    padding: 8px;
    margin-bottom: 4px;
}
.kgs-cal-event-row:hover {
    background-color: rgba(0,0,0,0.015);
}

/* Collapsible accordion filters on mobile */
@media (max-width: 991.98px) {
    .kgs-filter-collapsible-body {
        display: none;
        position: absolute;
        left: 0;
        right: 0;
        top: 100%;
        margin-top: 8px;
        background: #ffffff;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        padding: 16px;
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
        z-index: 1050;
    }
    .kgs-filter-collapsible-body.show {
        display: block !important;
    }
}
@media (min-width: 992px) {
    .kgs-filter-collapsible-body {
        display: block !important;
    }
}
</style>

<?php if (!$isCalendarPage): ?>
    <!-- ========================================== -->
    <!-- SIMPLIFIED WIDGET VIEW (For Home / Other Pages) -->
    <!-- ========================================== -->
    <div class="container py-4 kgs-calendar-container-wrapper mb-4" id="calendar-widget-module">
        <!-- Main Widget Title with "View All" on Homepage only -->
        <?php if (!empty($title) && $title !== 'None'): ?>
            <h5 class="rich-text-title d-flex justify-content-between align-items-center mb-3">
                <span><?= htmlspecialchars($title) ?></span>
                <a href="<?= config('base_url') ?>calendar/" class="btn btn-sm btn-outline-success">View All</a>
            </h5>
        <?php endif; ?>

        <!-- Simplified Accordion Dropdown Filter BELOW the Title -->
        <div class="position-relative mb-3" style="z-index: 100;">
            <div class="card border border-light-subtle shadow-sm bg-white p-3 rounded-3 cursor-pointer" id="kgs-widget-filter-toggle" style="cursor: pointer;">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-uppercase text-muted fw-bold tracking-wider" style="font-size: 11px;">
                        <i class="fa-solid fa-filter me-2 text-primary" style="color: <?= $primaryColor ?>;"></i> Filter Calendars
                    </span>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-light text-muted border" id="kgs-widget-filter-badge" style="font-size: 10px;">All Active</span>
                        <i class="fa-solid fa-chevron-down text-muted" id="kgs-widget-filter-chevron"></i>
                    </div>
                </div>
            </div>
            
            <!-- Absolute dropdown menu -->
            <div class="d-none position-absolute start-0 end-0 mt-1 bg-white shadow-lg border border-light-subtle rounded-3 p-3" id="kgs-widget-filters-collapse" style="z-index: 1050;">
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($categories as $cat): ?>
                        <button type="button" class="btn w-100 d-flex align-items-center justify-content-between p-2.5 rounded-3 border text-start kgs-cal-filter-btn active" data-category="<?= $cat['key'] ?>" data-primary-color="<?= $cat['color'] ?>" style="font-size: 11.5px; background: #ffffff; border-color: <?= $cat['color'] ?>; background-color: <?= $cat['color'] ?>08;">
                            <div class="d-flex align-items-center gap-2.5 min-w-0">
                                <span class="rounded-circle d-inline-block shrink-0" style="width: 8px; height: 8px; background-color: <?= $cat['color'] ?>;"></span>
                                <span class="text-dark fw-bold" style="font-size: 11.5px;"><?= htmlspecialchars($cat['label']) ?></span>
                            </div>
                            <input type="checkbox" class="form-check-input m-0 pointer-events-none" checked style="width: 14px; height: 14px;">
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Agenda List (Limited to 10 events maximum) -->
        <div class="card border border-light-subtle shadow-sm bg-white p-3 p-md-4 rounded-3">
            <div id="kgs-cal-events-list">
                <?php 
                // Show up to 10 events
                $limitedEvents = array_slice($schoolEvents, 0, 10);
                foreach ($limitedEvents as $evt):
                    $timestamp = strtotime($evt['date']);
                    $month = strtoupper(date('M', $timestamp));
                    $day = date('d', $timestamp);
                    $catInfo = null;
                    foreach ($categories as $cat) {
                        if ($cat['key'] === $evt['category']) {
                            $catInfo = $cat;
                            break;
                        }
                    }
                ?>
                    <div class="py-2.5 d-flex gap-3 align-items-start kgs-cal-event-row border-bottom" data-category="<?= htmlspecialchars($evt['category']) ?>">
                        <!-- Date badge -->
                        <div class="d-flex flex-column align-items-center justify-content-center bg-light border rounded-3 text-center shrink-0" style="width: 46px; height: 50px; min-width: 46px; border-color: #e9ecef;">
                            <span class="text-muted fw-bold uppercase" style="font-size: 9px; line-height: 1;"><?= $month ?></span>
                            <span class="text-dark fw-black fs-6 mt-0.5" style="font-weight: 900; line-height: 1;"><?= $day ?></span>
                        </div>

                        <!-- Event Details -->
                        <div class="flex-grow-1 min-w-0">
                            <div class="mb-0.5">
                                <span class="badge rounded-pill text-white" style="font-size: 8px; font-weight: 800; background-color: <?= $catInfo['color'] ?>; padding: 2px 6px;">
                                    <?= htmlspecialchars($catInfo['label']) ?>
                                </span>
                            </div>
                            <h6 class="fw-bold text-dark m-0 mb-0.5" style="font-size: 13px;">
                                <?= htmlspecialchars($evt['title']) ?>
                            </h6>
                            <div class="d-flex flex-wrap align-items-center gap-x-3 gap-y-1 text-muted" style="font-size: 11px;">
                                <span class="d-flex align-items-center gap-1">
                                    <i class="fa-regular fa-clock text-muted"></i> <?= htmlspecialchars($evt['time']) ?>
                                </span>
                                <span class="d-flex align-items-center gap-1">
                                    <i class="fa-solid fa-map-pin text-muted"></i> <?= htmlspecialchars($evt['location']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center py-4 d-none text-muted" id="kgs-cal-no-events" style="font-size: 12px;">
                No active events matching your filter. Use the filter dropdown above to enable categories.
            </div>
            
            <!-- Link to Full Page -->
            <div class="text-center mt-3 pt-3 border-top">
                <a href="<?= config('base_url') ?>calendar/" class="btn btn-sm btn-outline-primary px-4 rounded-pill fw-bold" style="font-size: 12px; border-color: <?= $primaryColor ?>; color: <?= $primaryColor ?>;">
                    <i class="fa-solid fa-calendar-days me-1"></i> View Full Calendar & PDFs
                </a>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- ========================================== -->
    <!-- FULL CALENDAR PAGE VIEW                     -->
    <!-- ========================================== -->
    <div class="container py-4 kgs-calendar-container-wrapper mb-4" id="calendar-module">
        <!-- Main Widget Title -->
        <?php if (!empty($title) && $title !== 'None'): ?>
            <h5 class="rich-text-title d-flex justify-content-between align-items-center mb-3">
                <span><?= htmlspecialchars($title) ?></span>
            </h5>
        <?php endif; ?>

        <!-- View Switcher Panel -->
        <div class="card shadow-sm border border-light-subtle mb-4 p-3 bg-white rounded-3">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                <!-- Left Title and Icon -->
                <div class="d-flex align-items-center gap-3">
                    <div class="p-2 rounded bg-opacity-10" style="background-color: <?= $primaryColor ?>15; color: <?= $primaryColor ?>;">
                        <i class="fa-solid fa-calendar-days fs-4"></i>
                    </div>
                    <div>
                        <h4 class="m-0 fw-bold text-dark fs-5" style="font-size: 1.15rem !important;">School Calendars & Schedules</h4>
                        <p class="m-0 text-muted small" style="font-size: 11px;">View upcoming events or access static calendar PDFs</p>
                    </div>
                </div>
                
                <!-- Right View Switcher buttons - Agenda List default active -->
                <div class="d-flex bg-light p-1 rounded-3 border gap-1 align-self-start align-self-sm-auto">
                    <button class="btn btn-sm py-1.5 px-3 rounded-2 fw-semibold kgs-cal-tab-btn active" data-view="agenda" style="font-size: 11px;">
                        <i class="fa-solid fa-list-ul me-1"></i> Agenda List
                    </button>
                    <button class="btn btn-sm py-1.5 px-3 rounded-2 fw-semibold kgs-cal-tab-btn" data-view="embed" style="font-size: 11px;">
                        <i class="fa-solid fa-globe me-1"></i> Google Calendar
                    </button>
                    <button class="btn btn-sm py-1.5 px-3 rounded-2 fw-semibold kgs-cal-tab-btn" data-view="pdf" style="font-size: 11px;">
                        <i class="fa-regular fa-file-pdf me-1"></i> PDF Calendars
                    </button>
                </div>
            </div>
        </div>

        <!-- 1. Agenda List View (Visible by default) -->
        <div class="kgs-cal-view-container" id="kgs-cal-view-agenda">
            <div class="row g-4">
                <!-- Agenda Grid Feed on left (takes 9 columns) -->
                <div class="col-12 col-lg-9">
                    <div class="card border border-light-subtle shadow-sm bg-white p-4 rounded-3">
                        <h5 class="fw-bold text-dark d-flex align-items-center gap-2 mb-4" style="font-size: 15px;">
                            <i class="fa-solid fa-layer-group text-primary" style="color: <?= $primaryColor ?>;"></i>
                            Upcoming District Schedule (2026)
                        </h5>

                        <div id="kgs-cal-events-list">
                            <?php 
                            foreach ($schoolEvents as $evt):
                                $timestamp = strtotime($evt['date']);
                                $month = strtoupper(date('M', $timestamp));
                                $day = date('d', $timestamp);
                                $catInfo = null;
                                foreach ($categories as $cat) {
                                    if ($cat['key'] === $evt['category']) {
                                        $catInfo = $cat;
                                        break;
                                    }
                                }
                            ?>
                                <div class="py-3 d-flex gap-3 align-items-start kgs-cal-event-row border-bottom" data-category="<?= htmlspecialchars($evt['category']) ?>">
                                    <!-- Colored Date badge -->
                                    <div class="d-flex flex-column align-items-center justify-content-center bg-light border rounded-3 text-center shrink-0" style="width: 50px; height: 55px; min-width: 50px; border-color: #e9ecef;">
                                        <span class="text-muted fw-bold uppercase" style="font-size: 10px; line-height: 1;"><?= $month ?></span>
                                        <span class="text-dark fw-black fs-5 mt-1" style="font-weight: 900; line-height: 1;"><?= $day ?></span>
                                    </div>

                                    <!-- Event Details -->
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="mb-1">
                                            <span class="badge rounded-pill text-white" style="font-size: 8px; font-weight: 800; background-color: <?= $catInfo['color'] ?>; padding: 3px 8px;">
                                                <?= htmlspecialchars($catInfo['label']) ?>
                                            </span>
                                        </div>
                                        <h6 class="fw-bold text-dark m-0 mb-1 fs-6 kgs-cal-event-title">
                                            <?= htmlspecialchars($evt['title']) ?>
                                        </h6>
                                        <div class="d-flex flex-wrap align-items-center gap-x-3 gap-y-1 text-muted" style="font-size: 11px;">
                                            <span class="d-flex align-items-center gap-1">
                                                <i class="fa-regular fa-clock text-muted"></i> <?= htmlspecialchars($evt['time']) ?>
                                            </span>
                                            <span class="d-flex align-items-center gap-1">
                                                <i class="fa-solid fa-map-pin text-muted"></i> <?= htmlspecialchars($evt['location']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Lazy Loading / Load More container -->
                        <div class="text-center mt-3 pt-3 border-top d-none" id="kgs-load-more-container">
                            <button class="btn btn-sm btn-outline-primary px-4 rounded-pill fw-bold" id="kgs-load-more-btn" style="font-size: 12px; border-color: <?= $primaryColor ?>; color: <?= $primaryColor ?>;">
                                <i class="fa-solid fa-circle-chevron-down me-1"></i> Load More Events
                            </button>
                        </div>

                        <div class="text-center py-5 d-none text-muted" id="kgs-cal-no-events">
                            No active events matching your filter. Choose at least one calendar on the sidebar.
                        </div>
                    </div>
                </div>

                <!-- Filters Sidebar on right (takes 3 columns). On mobile, drops below the feed. -->
                <div class="col-12 col-lg-3">
                    <div class="card border border-light-subtle shadow-sm bg-white p-3 rounded-3 mb-3 position-relative" style="z-index: 99;">
                        <!-- Mobile Trigger: Dropdown accordion on mobile -->
                        <div class="d-flex justify-content-between align-items-center cursor-pointer d-lg-none" id="kgs-filter-accordion-toggle" style="cursor: pointer;">
                            <h5 class="text-uppercase text-muted fw-bold mb-0 tracking-wider d-flex align-items-center gap-2" style="font-size: 11px;">
                                <i class="fa-solid fa-filter text-muted"></i> Filter Calendars
                            </h5>
                            <i class="fa-solid fa-chevron-down text-muted" id="kgs-filter-accordion-chevron"></i>
                        </div>

                        <!-- Desktop Header -->
                        <h5 class="text-uppercase text-muted fw-bold mb-3 tracking-wider d-none d-lg-flex align-items-center gap-2" style="font-size: 11px;">
                            <i class="fa-solid fa-filter text-muted"></i> Filter Calendars
                        </h5>

                        <!-- Filter Options: Collapsible body on mobile, always visible on desktop -->
                        <div class="kgs-filter-collapsible-body mt-2 mt-lg-0" id="kgs-filter-accordion-body">
                            <div class="d-flex flex-column gap-2" id="kgs-cal-filters-group">
                                <?php foreach ($categories as $cat): ?>
                                    <button type="button" class="btn w-100 d-flex align-items-center justify-content-between p-2 rounded-3 border text-start kgs-cal-filter-btn active" data-category="<?= $cat['key'] ?>" data-primary-color="<?= $cat['color'] ?>" style="font-size: 12px; background: #ffffff; border-color: <?= $cat['color'] ?>; background-color: <?= $cat['color'] ?>08;">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: <?= $cat['color'] ?>;"></span>
                                            <span class="text-dark fw-bold"><?= htmlspecialchars($cat['label']) ?></span>
                                        </div>
                                        <input type="checkbox" class="form-check-input m-0 pointer-events-none" checked style="width: 14px; height: 14px;">
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-3 p-3 rounded-3 border text-muted" style="font-size: 11px; background-color: #f8f9fa; border-color: #e9ecef; line-height: 1.4;">
                                <strong>Tip:</strong> Keep categories enabled to see conflicts and plan your family's weekly schedule.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Google Calendar Embed View (Hidden by default) -->
        <div class="kgs-cal-view-container d-none" id="kgs-cal-view-embed">
            <div class="card border border-light-subtle shadow-sm rounded-3 overflow-hidden bg-white">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                        <span class="text-uppercase text-muted fw-bold small tracking-wider" style="font-size: 10px;">Live Google Calendar</span>
                        <span class="badge bg-light text-muted border font-monospace fw-normal" style="font-size: 10px;">America/Chicago</span>
                    </div>
                    <div class="ratio ratio-16x9 bg-light rounded-3 border overflow-hidden" style="min-height: 550px;">
                        <iframe src="https://calendar.google.com/calendar/embed?src=<?= urlencode($calId) ?>&wkst=1&bgcolor=%23ffffff&ctz=America%2FChicago" style="border: 0" frameborder="0" scrolling="no"></iframe>
                    </div>
                    <div class="mt-3 text-end">
                        <a href="https://calendar.google.com/calendar/render?cid=<?= urlencode($calId) ?>" 
                           target="_blank" class="btn btn-sm btn-light border small text-muted" style="font-size: 11px;">
                           <i class="fa-solid fa-calendar-plus me-1"></i> Add to My Google Calendar
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. PDF Calendars View (Hidden by default) -->
        <div class="kgs-cal-view-container d-none" id="kgs-cal-view-pdf">
            <div class="row g-4 mb-4">
                <!-- Academic Calendar Selection Card -->
                <div class="col-12 col-md-6">
                    <button type="button" class="w-100 text-start p-4 rounded-3 border kgs-pdf-select-btn active" data-doc-type="academic">
                        <div class="d-flex justify-content-between align-items-start w-100 mb-2">
                            <h5 class="fw-bold text-dark fs-6 mb-0">Academic Year Calendar</h5>
                            <i class="fa-solid fa-eye text-primary kgs-pdf-eye-icon" style="font-size: 14px;"></i>
                        </div>
                        <p class="text-muted small mb-3" style="font-size: 11px; line-height: 1.4;">
                            Official school year schedule detailing holidays, teacher institutes, early dismissal days, and parent-teacher conference cycles.
                        </p>
                        <div class="d-flex gap-2">
                            <?php if ($academicFile): ?>
                                <a href="<?= htmlspecialchars($academicUrl) ?>" target="_blank" class="btn btn-sm btn-light border fw-bold text-muted px-3 py-1" style="font-size: 10px;" onclick="event.stopPropagation();">
                                    <i class="fa-solid fa-download me-1"></i> Download File
                                </a>
                            <?php else: ?>
                                <span class="text-muted small" style="font-size: 10px; font-style: italic;">No file available</span>
                            <?php endif; ?>
                        </div>
                    </button>
                </div>

                <!-- Monthly Calendar Selection Card -->
                <div class="col-12 col-md-6">
                    <button type="button" class="w-100 text-start p-4 rounded-3 border kgs-pdf-select-btn" data-doc-type="monthly">
                        <div class="d-flex justify-content-between align-items-start w-100 mb-2">
                            <h5 class="fw-bold text-dark fs-6 mb-0">Monthly Event Calendar</h5>
                            <i class="fa-solid fa-eye text-muted kgs-pdf-eye-icon" style="font-size: 14px;"></i>
                        </div>
                        <p class="text-muted small mb-3" style="font-size: 11px; line-height: 1.4;">
                            Regularly updated monthly schedule listing physical sports matches, board meeting agendas, student activities, and cafeteria menu cycles.
                        </p>
                        <div class="d-flex gap-2">
                            <?php if ($monthlyFile): ?>
                                <a href="<?= htmlspecialchars($monthlyUrl) ?>" target="_blank" class="btn btn-sm btn-light border fw-bold text-muted px-3 py-1" style="font-size: 10px;" onclick="event.stopPropagation();">
                                    <i class="fa-solid fa-download me-1"></i> Download File
                                </a>
                            <?php else: ?>
                                <span class="text-muted small" style="font-size: 10px; font-style: italic;">No file available</span>
                            <?php endif; ?>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Interactive full-width viewer -->
            <div class="card border border-light-subtle shadow-sm rounded-3 overflow-hidden bg-white">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                        <span class="text-uppercase text-muted fw-bold small tracking-wider" id="kgs-pdf-viewer-title" style="font-size: 10px;">Viewing: Academic Calendar</span>
                        <span class="badge bg-light text-muted border font-monospace fw-normal" id="kgs-pdf-viewer-badge" style="font-size: 10px;"><?= $academicIsImage ? 'Image Viewer' : 'PDF Viewer' ?></span>
                    </div>
                    
                    <div class="w-100 bg-light rounded-3 border overflow-hidden d-flex align-items-center justify-content-center kgs-pdf-viewport" style="min-height: 700px; height: 800px;">
                        <!-- Academic Document View -->
                        <div class="w-100 h-100 kgs-pdf-view-frame" id="kgs-pdf-frame-academic">
                            <?php if ($academicFile): ?>
                                <?php if ($academicIsImage): ?>
                                    <div class="w-100 h-100 p-2 d-flex align-items-center justify-content-center bg-white overflow-auto">
                                        <img src="<?= $academicEmbedUrl ?>" alt="School Calendar View" class="img-fluid d-block mx-auto rounded shadow-sm" style="max-height: 100%; object-fit: scale-down;">
                                    </div>
                                <?php else: ?>
                                    <iframe src="<?= $academicEmbedUrl ?>" class="w-100 h-100" title="School Calendar Doc View" style="border: none;"></iframe>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="text-center py-5 text-muted">No academic calendar document found in Google Drive.</div>
                            <?php endif; ?>
                        </div>

                        <!-- Monthly Document View -->
                        <div class="w-100 h-100 kgs-pdf-view-frame d-none" id="kgs-pdf-frame-monthly">
                            <?php if ($monthlyFile): ?>
                                <?php if ($monthlyIsImage): ?>
                                    <div class="w-100 h-100 p-2 d-flex align-items-center justify-content-center bg-white overflow-auto">
                                        <img src="<?= $monthlyEmbedUrl ?>" alt="School Calendar View" class="img-fluid d-block mx-auto rounded shadow-sm" style="max-height: 100%; object-fit: scale-down;">
                                    </div>
                                <?php else: ?>
                                    <iframe src="<?= $monthlyEmbedUrl ?>" class="w-100 h-100" title="School Calendar Doc View" style="border: none;"></iframe>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="text-center py-5 text-muted">No monthly calendar document found in Google Drive.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
(function() {
    // 1. Setup Wrapper Selection to avoid global collisions
    const wrapper = document.querySelector('#calendar-module') || document.querySelector('#calendar-widget-module');
    if (!wrapper) return;

    // 2. Simplified Widget Filter Accordion Toggle
    const widgetFilterToggle = wrapper.querySelector('#kgs-widget-filter-toggle');
    const widgetCollapse = wrapper.querySelector('#kgs-widget-filters-collapse');
    if (widgetFilterToggle && widgetCollapse) {
        widgetFilterToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            widgetCollapse.classList.toggle('d-none');
            const chevron = wrapper.querySelector('#kgs-widget-filter-chevron');
            if (chevron) {
                if (widgetCollapse.classList.contains('d-none')) {
                    chevron.className = 'fa-solid fa-chevron-down text-muted';
                } else {
                    chevron.className = 'fa-solid fa-chevron-up text-muted';
                }
            }
        });
        
        // Close on click outside
        document.addEventListener('click', function(e) {
            if (!widgetFilterToggle.contains(e.target) && !widgetCollapse.contains(e.target)) {
                widgetCollapse.classList.add('d-none');
                const chevron = wrapper.querySelector('#kgs-widget-filter-chevron');
                if (chevron) {
                    chevron.className = 'fa-solid fa-chevron-down text-muted';
                }
            }
        });
    }

    // 3. Mobile Filter Accordion Toggle (Full View)
    const mobileFilterToggle = wrapper.querySelector('#kgs-filter-accordion-toggle');
    const mobileFilterBody = wrapper.querySelector('#kgs-filter-accordion-body');
    if (mobileFilterToggle && mobileFilterBody) {
        mobileFilterToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            mobileFilterBody.classList.toggle('show');
            const chevron = wrapper.querySelector('#kgs-filter-accordion-chevron');
            if (chevron) {
                if (mobileFilterBody.classList.contains('show')) {
                    chevron.className = 'fa-solid fa-chevron-up text-muted';
                } else {
                    chevron.className = 'fa-solid fa-chevron-down text-muted';
                }
            }
        });
        
        // Close on click outside
        document.addEventListener('click', function(e) {
            if (!mobileFilterToggle.contains(e.target) && !mobileFilterBody.contains(e.target)) {
                mobileFilterBody.classList.remove('show');
                const chevron = wrapper.querySelector('#kgs-filter-accordion-chevron');
                if (chevron) {
                    chevron.className = 'fa-solid fa-chevron-down text-muted';
                }
            }
        });
    }

    // 4. Tab Switching
    wrapper.querySelectorAll('.kgs-cal-tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const view = this.getAttribute('data-view');
            
            // Toggle active button
            wrapper.querySelectorAll('.kgs-cal-tab-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Toggle view container
            wrapper.querySelectorAll('.kgs-cal-view-container').forEach(c => c.classList.add('d-none'));
            const activeContainer = wrapper.querySelector('#kgs-cal-view-' + view);
            if (activeContainer) {
                activeContainer.classList.remove('d-none');
            }
        });
    });

    // 5. Pagination & Lazy Loading States
    let currentLimit = 10;
    const defaultLimit = 10;

    // Filter Updates handler
    function updateAgendaList() {
        // Get all active categories
        const activeCats = Array.from(wrapper.querySelectorAll('.kgs-cal-filter-btn.active'))
                                .map(b => b.getAttribute('data-category'));

        // Filter and Paginate rows
        let totalMatchedCount = 0;
        let visibleCount = 0;
        
        wrapper.querySelectorAll('.kgs-cal-event-row').forEach(row => {
            const cat = row.getAttribute('data-category');
            if (activeCats.includes(cat)) {
                totalMatchedCount++;
                // If it is on the main calendar page, apply lazy load pagination limit
                const isCalendarPage = <?= $isCalendarPage ? 'true' : 'false' ?>;
                if (!isCalendarPage || totalMatchedCount <= currentLimit) {
                    row.classList.remove('d-none');
                    visibleCount++;
                } else {
                    row.classList.add('d-none');
                }
            } else {
                row.classList.add('d-none');
            }
        });

        // Toggle "Load More" button
        const loadMoreContainer = wrapper.querySelector('#kgs-load-more-container');
        if (loadMoreContainer) {
            if (totalMatchedCount > currentLimit) {
                loadMoreContainer.classList.remove('d-none');
            } else {
                loadMoreContainer.classList.add('d-none');
            }
        }

        // Show no events message if none visible
        const noEvents = wrapper.querySelector('#kgs-cal-no-events');
        if (noEvents) {
            if (totalMatchedCount === 0) {
                noEvents.classList.remove('d-none');
            } else {
                noEvents.classList.add('d-none');
            }
        }

        // Update badge text if on widget view
        const filterBadge = wrapper.querySelector('#kgs-widget-filter-badge');
        if (filterBadge) {
            const allCount = wrapper.querySelectorAll('.kgs-cal-filter-btn').length;
            if (activeCats.length === allCount) {
                filterBadge.textContent = 'All Active';
            } else if (activeCats.length === 0) {
                filterBadge.textContent = 'Filtered All';
            } else {
                filterBadge.textContent = activeCats.length + ' Active';
            }
        }
    }

    // 6. Agenda Filtering click handlers
    wrapper.querySelectorAll('.kgs-cal-filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            this.classList.toggle('active');
            const checkbox = this.querySelector('input[type="checkbox"]');
            if (checkbox) {
                checkbox.checked = this.classList.contains('active');
            }
            
            // Update button styles based on active status
            const catColor = this.getAttribute('data-primary-color');
            if (this.classList.contains('active')) {
                this.style.borderColor = catColor;
                this.style.backgroundColor = catColor + '08';
            } else {
                this.style.borderColor = '#e9ecef';
                this.style.backgroundColor = '#ffffff';
            }

            // Reset pagination on filter change to allow consistent UX
            currentLimit = defaultLimit;
            updateAgendaList();
        });
    });

    // 7. Load More Button Handler
    const loadMoreBtn = wrapper.querySelector('#kgs-load-more-btn');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            currentLimit += 10; // Load next 10 matched items
            updateAgendaList();
        });
    }

    // 8. PDF Select buttons
    wrapper.querySelectorAll('.kgs-pdf-select-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const docType = this.getAttribute('data-doc-type');
            
            // Toggle selection buttons
            wrapper.querySelectorAll('.kgs-pdf-select-btn').forEach(b => {
                b.classList.remove('active');
                const icon = b.querySelector('.kgs-pdf-eye-icon');
                if (icon) {
                    icon.className = 'fa-solid fa-eye text-muted kgs-pdf-eye-icon';
                }
            });
            
            this.classList.add('active');
            const eyeIcon = this.querySelector('.kgs-pdf-eye-icon');
            if (eyeIcon) {
                eyeIcon.className = 'fa-solid fa-eye text-primary kgs-pdf-eye-icon';
            }

            // Toggle frames
            wrapper.querySelectorAll('.kgs-pdf-view-frame').forEach(f => f.classList.add('d-none'));
            const activeFrame = wrapper.querySelector('#kgs-pdf-frame-' + docType);
            if (activeFrame) {
                activeFrame.classList.remove('d-none');
            }

            // Update viewer title & badge
            const viewerTitle = wrapper.querySelector('#kgs-pdf-viewer-title');
            if (viewerTitle) {
                viewerTitle.textContent = 'Viewing: ' + (docType === 'academic' ? 'Academic Calendar' : 'Monthly Calendar');
            }

            const viewerBadge = wrapper.querySelector('#kgs-pdf-viewer-badge');
            if (viewerBadge) {
                const isImg = activeFrame.querySelector('img') !== null;
                viewerBadge.textContent = isImg ? 'Image Viewer' : 'PDF Viewer';
            }
        });
    });

    // Run initial agenda list sizing
    updateAgendaList();
})();
</script>

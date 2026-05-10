<?php
// cfg/config.php

// === BASE URL (Change this if you move the site) ===
define('BASE_URL', '/kgs2026/apptegyclone/public/');

// === SCHOOL COLORS ===
define('COLOR_PRIMARY',   '#015BA7');   // Kell Indian Blue
define('COLOR_SECONDARY', '#002366');   // Deep Royal Blue (best contrast)
define('COLOR_ACCENT',    '#87d3f8');   // Bright Pale Cyan Blue
define('COLOR_WHITE',     '#FFFFFF');
define('COLOR_BLACK',     '#000000');

define('SITE_NAME', 'Kell Grade School');
define('DISTRICT_NAME', 'Kell Consolidated School District #2');
define('ADDRESS', '207 N Johnson St, Kell, IL 62853');
define('PHONE', '(618) 822-6234');
define('EMAIL', 'contact@kellgradeschool.com');
define('PRINCIPAL', 'Patrick Keeney');

define('FACEBOOK_PAGE', 'https://www.facebook.com/KellCSD2');
define('REPORT_CARD_URL', 'https://www.illinoisreportcard.com/School.aspx?schoolId=130580020032001');

define('NAV_BG', 'https://kellgradeschool.com/wp-content/uploads/2024/11/feathers_3.png');

define('HERO_IMAGE', 'https://kellgradeschool.com/wp-content/uploads/2026/01/kell-school-front-blue.png');
define('HERO_HEADLINE', '');
define('HERO_SUBHEADLINE', '');

// Live Feed - Add more as needed
$live_posts = [
    ['date' => 'May 8, 2026', 'text' => 'Today’s lunch: Pizza, salad, and fruit.'],
    ['date' => 'May 7, 2026', 'text' => 'Reminder: Parent-Teacher Conferences next week.'],
    ['date' => 'May 6, 2026', 'text' => 'Spring Picture Day is this Friday!'],
];


// Google Configs (replace with real IDs)
$config = [
    'google_calendar_id' => 'c_35c7f773dea0cc46099f7607201bed993a0a29d94d5456aa00594ed16ffb5071',
    'facebook_page_id' => 'KellCSD2',
    'staff_sheet_id' => '', // Google Sheet ID for staff
    // Passthrough URLs - leave empty to hide section
    'ks_site_url' => '',           // Kindergarten
    'gr1_site_url' => '',
    // ... add all others
    'baseball_site_url' => '',
    // etc.
];

// Live Feed - simple array or connect to Google Sheet later
$live_posts = [
    ['date' => 'May 8, 2026', 'text' => 'Today’s lunch: Pizza, salad, and fruit.'],
    // Add more
];
?>
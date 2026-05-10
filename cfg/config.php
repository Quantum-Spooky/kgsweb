<?php
/**
 * cfg/config.php
 *
 * Kell Grade School Website Configuration
 * ---------------------------------------
 * Central configuration file for the entire website.
 *
 * PURPOSE
 * -------
 * This file stores:
 * - Branding settings
 * - Contact information
 * - Google integration IDs
 * - External links
 * - Dynamic page toggles
 * - Google Site passthrough URLs
 * - Document folder IDs
 * - Live feed configuration
 * - Navigation visibility controls
 *
 * IMPORTANT
 * ---------
 * Any config value left empty ('') should generally be treated
 * as disabled/hidden by the frontend.
 *
 * Example:
 *
 * if (!empty($config['baseball_site_url'])) {
 *     // Show baseball navigation link/page
 * }
 *
 * FUTURE EXPANSION
 * ----------------
 * Recommended future upgrade:
 * Move helper functions into:
 *
 * /kgs-core/helpers/config-helper.php
 *
 * Suggested helper:
 *
 * function config($key, $default = '') {
 *     global $config;
 *     return $config[$key] ?? $default;
 * }
 *
 * Example usage:
 *
 * echo config('site_name');
 * echo config('google_calendar_id');
 *
 * BENEFITS
 * --------
 * - Cleaner templates
 * - Easier module development
 * - Easier admin panel integration
 * - Easier JSON/API export
 * - Easier future CMS migration
 */

///////////////////////////////////////////////////////////////////////////
// BASE URL
///////////////////////////////////////////////////////////////////////////

/**
 * Base public URL for the site.
 *
 * IMPORTANT:
 * Must include trailing slash.
 *
 * Local Example:
 * /kgs2026/apptegyclone/public/
 *
 * Production Example:
 * /
 */

define('BASE_URL', '/kgs2026/apptegyclone/public/');

///////////////////////////////////////////////////////////////////////////
// SCHOOL BRANDING COLORS
///////////////////////////////////////////////////////////////////////////

define('COLOR_PRIMARY',   '#015BA7'); // Kell Indian Blue
define('COLOR_SECONDARY', '#002366'); // Deep Royal Blue
define('COLOR_ACCENT',    '#87d3f8'); // Accent Cyan
define('COLOR_WHITE',     '#FFFFFF');
define('COLOR_BLACK',     '#000000');

///////////////////////////////////////////////////////////////////////////
// SITE SETTINGS
///////////////////////////////////////////////////////////////////////////

define('SITE_NAME',     'Kell Grade School');
define('DISTRICT_NAME', 'Kell Consolidated School District #2');

///////////////////////////////////////////////////////////////////////////
// CONTACT INFORMATION
///////////////////////////////////////////////////////////////////////////

define('ADDRESS',   '207 N Johnson St, Kell, IL 62853');
define('PHONE',     '618-822-6234');
define('FAX',       '618-822-6733');
define('EMAIL',     'contact@kellgradeschool.com');

define('PRINCIPAL', 'Patrick Keeney');
define('SECRETARY', 'Kendra Koch');

///////////////////////////////////////////////////////////////////////////
// EXTERNAL LINKS
///////////////////////////////////////////////////////////////////////////

define('FACEBOOK_PAGE', 'https://www.facebook.com/KellCSD2');

define(
    'REPORT_CARD_URL',
    'https://www.illinoisreportcard.com/School.aspx?schoolId=130580020032001'
);

define('SAFE2HELP_URL', 'https://safe2helpil.com');
define('ABLE_URL',      'https://ablenrc.org');
define('LIFELINE_URL',  'https://988lifeline.org');

define('TEACHEREASE_URL', '');

///////////////////////////////////////////////////////////////////////////
// DESIGN / IMAGES
///////////////////////////////////////////////////////////////////////////

define(
    'NAV_BG',
    'https://kellgradeschool.com/wp-content/uploads/2024/11/feathers_3.png'
);

define(
    'HERO_IMAGE',
    'https://kellgradeschool.com/wp-content/uploads/2026/01/kell-school-front-blue.png'
);

define('HERO_HEADLINE', '');
define('HERO_SUBHEADLINE', '');

///////////////////////////////////////////////////////////////////////////
// WEATHER
///////////////////////////////////////////////////////////////////////////

/**
 * Used by homepage weather widgets.
 */

define('WEATHER_LOCATION', 'Kell, IL');

///////////////////////////////////////////////////////////////////////////
// LIVE FEED
///////////////////////////////////////////////////////////////////////////

/**
 * Temporary local live feed array.
 *
 * FUTURE PLAN:
 * Replace with:
 * Google Form → Google Sheet → Dynamic frontend feed
 *
 * Related future config:
 * 'live_feed_sheet_id'
 */

$live_posts = [
    [
        'date' => 'May 8, 2026',
        'text' => 'Today’s lunch: Pizza, salad, and fruit.'
    ],
    [
        'date' => 'May 7, 2026',
        'text' => 'Reminder: Parent-Teacher Conferences next week.'
    ],
    [
        'date' => 'May 6, 2026',
        'text' => 'Spring Picture Day is this Friday!'
    ],
];

///////////////////////////////////////////////////////////////////////////
// MAIN CONFIG ARRAY
///////////////////////////////////////////////////////////////////////////

/**
 * CENTRAL CONFIG ARRAY
 * --------------------
 * Most dynamic site settings belong here.
 *
 * WHY?
 * ----
 * Arrays scale better than constants for:
 * - modules
 * - loops
 * - admin systems
 * - API exports
 * - conditional navigation
 * - future CMS integrations
 */

$config = [

    ///////////////////////////////////////////////////////////////////////
    // CORE SITE SETTINGS
    ///////////////////////////////////////////////////////////////////////

    'base_url' => BASE_URL,

    'site_name'     => SITE_NAME,
    'district_name' => DISTRICT_NAME,

    ///////////////////////////////////////////////////////////////////////
    // GOOGLE / SOCIAL
    ///////////////////////////////////////////////////////////////////////

    'google_calendar_id' =>
        'c_35c7f773dea0cc46099f7607201bed993a0a29d94d5456aa00594ed16ffb5071@group.calendar.google.com',

    'facebook_page_id' => 'KellCSD2',

    ///////////////////////////////////////////////////////////////////////
    // ABOUT / DISTRICT
    ///////////////////////////////////////////////////////////////////////

    'about_text_doc_id'     => '',
    'board_docs_folder_id'  => '',
    'legal_docs_folder_id'  => '',
    'employment_sheet_id'   => '',
    'staff_sheet_id'        => '',
    'contact_sheet_id'      => '',

    ///////////////////////////////////////////////////////////////////////
    // BOARD / DOCUMENT STORAGE
    ///////////////////////////////////////////////////////////////////////

    'board_agenda_folder_id'   => '',
    'board_minutes_folder_id'  => '',
    'photo_gallery_folder_id'  => '',

    ///////////////////////////////////////////////////////////////////////
    // LIVE FEED / NEWS
    ///////////////////////////////////////////////////////////////////////

    'news_sheet_id'       => '',
    'live_feed_sheet_id'  => '',

    ///////////////////////////////////////////////////////////////////////
    // ACADEMICS — ELEMENTARY
    ///////////////////////////////////////////////////////////////////////

    'prek_site_url' => '',
    'kg_site_url'   => '',
    'gr1_site_url'  => '',
    'gr2_site_url'  => '',
    'gr3_site_url'  => '',
    'gr4_site_url'  => '',
    'gr5_site_url'  => '',

    ///////////////////////////////////////////////////////////////////////
    // ACADEMICS — JUNIOR HIGH
    ///////////////////////////////////////////////////////////////////////

    'jh_ela_site_url'     => '',
    'jh_math_site_url'    => '',
    'jh_science_site_url' => '',
    'jh_ss_site_url'      => '',

    ///////////////////////////////////////////////////////////////////////
    // SCHOOLWIDE PROGRAMS
    ///////////////////////////////////////////////////////////////////////

    'sped_site_url'   => '',
    'title1_site_url' => '',

    ///////////////////////////////////////////////////////////////////////
    // CALENDAR
    ///////////////////////////////////////////////////////////////////////

    'monthly_cal_folder_id'  => '',		// calendar page displays the most recent image or pdf in this folder
    'academic_cal_folder_id' => '',		// calendar page displays the most recent image or pdf in this folder

    ///////////////////////////////////////////////////////////////////////
    // DINING
    ///////////////////////////////////////////////////////////////////////

    'breakfast_menu_folder_id' => '',	// dining page displays the most recent image or pdf in this folder
    'lunch_menu_folder_id'     => '',   // dining page displays the most recent image or pdf in this folder

    ///////////////////////////////////////////////////////////////////////
    // ACTIVITIES — SPORTS
    ///////////////////////////////////////////////////////////////////////

    'baseball_site_url'   => '',
    'basketball_site_url' => '',
    'bowling_site_url'    => '',
    'cheer_site_url'      => '',
    'xc_site_url'         => '',
    'volleyball_site_url' => '',

    ///////////////////////////////////////////////////////////////////////
    // ACTIVITIES — CLUBS
    ///////////////////////////////////////////////////////////////////////

    'stuco_site_url'       => '',
    'yearbook_site_url'    => '',
    'bookclub_site_url'    => '',
    'cooking_site_url'     => '',
    'braingames_site_url'  => '',
    'scholarbowl_site_url' => '',

    ///////////////////////////////////////////////////////////////////////
    // FAMILY
    ///////////////////////////////////////////////////////////////////////

    'pto_site_url' => '',

    ///////////////////////////////////////////////////////////////////////
    // SITEWIDE / FOOTER / UTILITIES
    ///////////////////////////////////////////////////////////////////////

    'weather_location' => 'Kell, IL',

    'teacher_ease_url' => '',

    'facebook_url' => 'https://facebook.com/KellCSD2',

    'safe2help_url' => 'https://safe2helpil.com',

    'able_url' => 'https://ablenrc.org',

    'lifeline_url' => 'https://988lifeline.org',

    'google_maps_embed' => '',
];

///////////////////////////////////////////////////////////////////////////
// OPTIONAL HELPER FUNCTION
///////////////////////////////////////////////////////////////////////////

/**
 * OPTIONAL CONFIG HELPER
 * ----------------------
 * Recommended for future development.
 *
 * Example:
 *
 * echo config_value('site_name');
 *
 * instead of:
 *
 * echo $config['site_name'];
 *
 * BENEFITS
 * --------
 * - Prevents undefined index warnings
 * - Allows default fallback values
 * - Cleaner templates
 * - Easier debugging
 */
 
///////////////////////////////////////////////////////////////////////////
 
/**
 * OTHER NOTES
 * --------
 * Footer links and Navigation links should be editable via a google sheet.
 *
 */

/////////////////////////////////////////////////////////////////////////// 
 
 
 
 */////////////////////////

function config_value($key, $default = '')
{
    global $config;

    return $config[$key] ?? $default;
}
?>

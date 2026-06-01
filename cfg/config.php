<?php
/**
 * cfg/config.php
 * Kell Grade School Website Configuration
 */

///////////////////////////////////////////////////////////////////////////
// ROOT PATH (SAFE GUARD)
///////////////////////////////////////////////////////////////////////////

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/');
}

///////////////////////////////////////////////////////////////////////////
// BASE URL
// IMPORTANT: used for all internal routing and asset generation
///////////////////////////////////////////////////////////////////////////

// normalize base URL so trailing slash is guaranteed
define('BASE_URL', '/kgs2026/ac/public/');

///////////////////////////////////////////////////////////////////////////
// SCHOOL BRANDING COLORS
///////////////////////////////////////////////////////////////////////////

define('COLOR_PRIMARY',   '#015BA7');
define('COLOR_SECONDARY', '#002366');
define('COLOR_ACCENT',    '#87d3f8');
define('COLOR_WHITE',     '#FFFFFF');
define('COLOR_BLACK',     '#000000');

///////////////////////////////////////////////////////////////////////////
// SITE SETTINGS
///////////////////////////////////////////////////////////////////////////

define('SITE_NAME', 'Kell Grade School');
define('DISTRICT_NAME', 'Kell Consolidated School District #2');

///////////////////////////////////////////////////////////////////////////
// CONTACT INFORMATION
///////////////////////////////////////////////////////////////////////////

define('ADDRESS', '207 N Johnson St, Kell, IL 62853');
define('PHONE', '618-822-6234');
define('FAX', '618-822-6733');
define('EMAIL', 'contact@kellgradeschool.com');

define('PRINCIPAL', 'Patrick Keeney');
define('SECRETARY', 'Kendra Koch');

///////////////////////////////////////////////////////////////////////////
// EXTERNAL LINKS (canonicalized naming)
///////////////////////////////////////////////////////////////////////////

define('FACEBOOK_URL', 'https://www.facebook.com/KellCSD2');

define('REPORT_CARD_URL', 'https://www.illinoisreportcard.com/School.aspx?schoolId=130580020032001');
define('SAFE2HELP_URL', 'https://safe2helpil.com');
define('ABLE_URL', 'https://ablenrc.org');
define('LIFELINE_URL', 'https://988lifeline.org');

define('TEACHEREASE_URL', '');

///////////////////////////////////////////////////////////////////////////
// DESIGN / IMAGES
///////////////////////////////////////////////////////////////////////////

define('NAV_BG_IMAGE', 'https://kellgradeschool.com/wp-content/uploads/2024/11/feathers_3.png');
define('HERO_IMAGE', 'https://kellgradeschool.com/wp-content/uploads/2026/01/kell-school-front-blue.png');

define('HERO_HEADLINE', '');
define('HERO_SUBHEADLINE', '');

///////////////////////////////////////////////////////////////////////////
// WEATHER
///////////////////////////////////////////////////////////////////////////

define('WEATHER_LOCATION', 'Kell, IL');

///////////////////////////////////////////////////////////////////////////
// LIVE FEED (TEMP STATIC DATA - WILL BE REPLACED BY SHEET/DRIVE)
///////////////////////////////////////////////////////////////////////////

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
// MAIN CONFIG ARRAY (LEGACY + INTEGRATION LAYER)
///////////////////////////////////////////////////////////////////////////

$config = [

    // CORE
    'base_url' => BASE_URL,
    'site_name' => SITE_NAME,
    'district_name' => DISTRICT_NAME,

    // CMS ROOT
    'drive_root_folder_id' => '',

    // ROUTING / ALIASES
    'route_aliases_sheet_id' => '',

    // GOOGLE
    'google_service_account_json' => '
	{
	  "type": "service_account",
	  "project_id": "kgs-web-project",
	  "private_key_id": "REDACTED",
	  "private_key": "-----BEGIN PRIVATE KEY-----\nREDACTED\n-----END PRIVATE KEY-----\n",
	  "client_email": "kgs-web-service-account@kgs-web-project.iam.gserviceaccount.com",
	  "client_id": "118348104168435635958",
	  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
	  "token_uri": "https://oauth2.googleapis.com/token",
	  "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
	  "client_x509_cert_url": "https://www.googleapis.com/robot/v1/metadata/x509/kgs-web-service-account%40kgs-web-project.iam.gserviceaccount.com",
	  "universe_domain": "googleapis.com"
	}
	
	',
	'google_calendar_id' => '',
    'facebook_page_id' => 'KellCSD2',


    // ABOUT
    'about_text_doc_id' => '',
    'board_docs_folder_id' => '',
    'legal_docs_folder_id' => '',
    'employment_sheet_id' => '',
    'staff_sheet_id' => '',
    'contact_sheet_id' => '',

    // BOARD
    'board_agenda_folder_id' => '',
    'board_minutes_folder_id' => '',
    'photo_gallery_folder_id' => '',

    // NEWS
    'news_sheet_id' => '',
    'live_feed_sheet_id' => '',

    // ELEMENTARY
    'prek_site_url' => '',
    'kg_site_url' => '',
    'gr1_site_url' => '',
    'gr2_site_url' => '',
    'gr3_site_url' => '',
    'gr4_site_url' => '',
    'gr5_site_url' => '',

    // JUNIOR HIGH
    'jh_ela_site_url' => '',
    'jh_math_site_url' => '',
    'jh_science_site_url' => '',
    'jh_ss_site_url' => '',

    // PROGRAMS
    'sped_site_url' => '',
    'title1_site_url' => '',

    // CALENDAR
    'monthly_cal_folder_id' => '',
    'academic_cal_folder_id' => '',

    // DINING
    'breakfast_menu_folder_id' => '',
    'lunch_menu_folder_id' => '',

    // ATHLETICS
    'baseball_site_url' => '',
    'basketball_site_url' => '',
    'bowling_site_url' => '',
    'cheer_site_url' => '',
    'xc_site_url' => '',
    'volleyball_site_url' => '',

    // CLUBS
    'stuco_site_url' => '',
    'yearbook_site_url' => '',
    'bookclub_site_url' => '',
    'cooking_site_url' => '',
    'braingames_site_url' => '',
    'scholarbowl_site_url' => '',

    // FAMILY
    'pto_site_url' => '',

    // FOOTER
    'weather_location' => 'Kell, IL',
    'teacher_ease_url' => '',
    'facebook_url' => 'https://facebook.com/KellCSD2',
    'safe2help_url' => 'https://safe2helpil.com',
    'able_url' => 'https://ablenrc.org',
    'lifeline_url' => 'https://988lifeline.org',
    'google_maps_embed' => '',
];

///////////////////////////////////////////////////////////////////////////
// HELPERS
///////////////////////////////////////////////////////////////////////////

function config_value($key, $default = '')
	{
		global $config;
		return $config[$key] ?? $default;
	}

define('RUN_CACHE_REPAIR', true);
<?php
/**
 * cfg/config.php
 * Kell Grade School - Environment & Identity Configuration
 * 
 * PURPOSE: 
 * This file defines the "Static" identity of the site. It contains server paths,
 * branding colors, and hardcoded fallback content. 
 * 
 * ARCHITECTURAL ROLE:
 * 1. Defines PHP Constants for high-level system paths.
 * 2. Provides the secondary fallback level for the config() helper.
 */

/*
|--------------------------------------------------------------------------
| SYSTEM PATHS (Immutable)
|--------------------------------------------------------------------------
| These use define() because they should never change during a request 
| and are required for the application to physically find its own files.
*/

if (!defined('ROOT_PATH')) {
    // The physical location on the server disk
    define('ROOT_PATH', dirname(__DIR__) . '/');
}

// The URL path (e.g., /public/ or /kgs2026/ac/public/)
define('BASE_URL', '/kgs2026/ac/public/');

/*
|--------------------------------------------------------------------------
| 2. LEGACY CONSTANTS (Move here to prevent Undefined Constant errors)
|--------------------------------------------------------------------------
| EVERYTHING IN THIS SECTION CAN BE DELETED AFTER THE GOOGLE SHEET 
| CONFIG IS FULLY WORKING. These are currently needed to populate the 
| fallback $config array below.
|--------------------------------------------------------------------------
*/

// Site Identity
define('SITE_NAME', 'Kell Grade School');
define('DISTRICT_NAME', 'Kell Consolidated School District #2');

// School Branding (UI Colors)
define('COLOR_PRIMARY',   '#015BA7'); // Kell Blue
define('COLOR_SECONDARY', '#002366'); // Midnight Navy
define('COLOR_ACCENT',    '#87d3f8'); // Sky Blue
define('COLOR_WHITE',     '#FFFFFF');
define('COLOR_BLACK',     '#000000');

/*
|--------------------------------------------------------------------------
| CONFIG FALLBACK ARRAY
|--------------------------------------------------------------------------
| This array is searched by config() if a key is not found in the 
| Google Sheet cache. 
*/

$config = [
    // --- Identity ---
    'site_name'     => SITE_NAME,
    'district_name' => DISTRICT_NAME,
    'school_name'   => 'Kell Grade School',
    'principal'     => 'Patrick Keeney',
    'secretary'     => 'Kendra Koch',

    // --- Contact Info ---
    'address' => '207 N Johnson St, Kell, IL 62853',
    'phone'   => '618-822-6234',
    'fax'     => '618-822-6733',
    'email'   => 'contact@kellgradeschool.com',

    // --- External Links ---
    'facebook_url'    => 'https://www.facebook.com/KellCSD2',
    'report_card_url' => 'https://www.illinoisreportcard.com/School.aspx?schoolId=130580020032001',
    'safe2help_url'   => 'https://safe2helpil.com',
    'able_url'        => 'https://ablenrc.org',
    'lifeline_url'    => 'https://988lifeline.org',
    'teacherease_url' => 'https://www.teacherease.com/common/login.aspx',

    // --- Design Assets ---
    'nav_bg_image'    => 'https://kellgradeschool.com/wp-content/uploads/2024/11/feathers_3.png',
    'hero_image'      => 'https://kellgradeschool.com/wp-content/uploads/2026/01/kell-school-front-blue.png',
    'weather_location'=> 'Kell, IL',
];

/*
|--------------------------------------------------------------------------
| Global flag to enable cache self-healing on boot
|--------------------------------------------------------------------------
*/

// Global flag to enable cache self-healing on boot
define('RUN_CACHE_REPAIR', true);



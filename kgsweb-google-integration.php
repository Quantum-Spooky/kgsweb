<?php
/**
 * Plugin Name: KGSWeb Google Integration
 * Plugin URI: https://kellgradeschool.com
 * Description: Google Drive/Calendar/Slides/Sheets integration + secure uploads for Kell Grade School.
 * Version: 1.0.0
 * Author: Travis Donoho
 * License: GPLv2 or later
 * Text Domain: kgsweb
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'KGSWEB_PLUGIN_VERSION', '1.0.0' );
define( 'KGSWEB_PLUGIN_FILE', __FILE__ );
define( 'KGSWEB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'KGSWEB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'KGSWEB_SETTINGS_OPTION', 'kgsweb_settings' );

// Optional: configure in wp-config.php
// define( 'KGSWEB_UPLOAD_PASS_HASH', 'sha256:...' );

require_once KGSWEB_PLUGIN_DIR . 'includes/class-kgsweb-google-integration.php';
require_once KGSWEB_PLUGIN_DIR . 'includes/class-kgsweb-google-admin.php';
require_once KGSWEB_PLUGIN_DIR . 'includes/class-kgsweb-google-rest-api.php';
require_once KGSWEB_PLUGIN_DIR . 'includes/class-kgsweb-google-shortcodes.php';
require_once KGSWEB_PLUGIN_DIR . 'includes/class-kgsweb-google-secure-upload.php';
require_once KGSWEB_PLUGIN_DIR . 'includes/class-kgsweb-google-drive-docs.php';
require_once KGSWEB_PLUGIN_DIR . 'includes/class-kgsweb-google-ticker.php';
require_once KGSWEB_PLUGIN_DIR . 'includes/class-kgsweb-google-upcoming-events.php';
require_once KGSWEB_PLUGIN_DIR . 'includes/class-kgsweb-google-helpers.php';



register_activation_hook( KGSWEB_PLUGIN_FILE, function() {
    // Seed defaults
    $defaults = [
        'service_account_json'     => '',
        'public_docs_root_id'      => '',
        'upload_root_id'           => '',
        'menu_breakfast_folder_id' => '',
        'menu_lunch_folder_id'     => '',
        'ticker_file_id'           => '',
        'calendar_id'              => '',
        'slides_file_id'           => '',
        'sheets_file_id'           => '',
        'sheets_default_range'     => 'A1:Z100',
        'upload_auth_mode'         => 'password', // password|google_group
        'upload_google_group'      => '',
        'upload_destination'       => 'drive', // drive|wordpress
        'wp_upload_root_path'      => '',
        'upload_password_plaintext'=> '',
        'debug_enabled'            => false,
        'calendar_page_url'        => '',
    ];
    $existing = get_option( KGSWEB_SETTINGS_OPTION, [] );
    update_option( KGSWEB_SETTINGS_OPTION, array_merge( $defaults, $existing ) );

    // Schedule hourly cron if not scheduled
    if ( ! wp_next_scheduled( 'kgsweb_hourly_cache_refresh' ) ) {
        wp_schedule_event( time() + 60, 'hourly', 'kgsweb_hourly_cache_refresh' );
    }
});

register_deactivation_hook( KGSWEB_PLUGIN_FILE, function() {
    $ts = wp_next_scheduled( 'kgsweb_hourly_cache_refresh' );
    if ( $ts ) wp_unschedule_event( $ts, 'kgsweb_hourly_cache_refresh' );
    // Do not delete options/transients automatically; preserve last-known-good cache.
    // Consider purging temporary transients if needed.
});

add_action( 'plugins_loaded', function() {
    KGSweb_Google_Integration::init();
    KGSweb_Google_Admin::init();
    KGSweb_Google_REST_API::init();
    KGSweb_Google_Shortcodes::init();
    KGSweb_Google_Secure_Upload::init();
    KGSweb_Google_Drive_Docs::init();
    KGSweb_Google_Ticker::init();
    KGSweb_Google_Upcoming_Events::init();
    KGSweb_Google_Helpers::init();
});

add_action( 'wp_enqueue_scripts', [ 'KGSweb_Google_Shortcodes', 'register_assets' ] );	


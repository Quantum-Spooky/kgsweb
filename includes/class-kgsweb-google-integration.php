<?php
// includes/class-kgsweb-google-integration.php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class KGSweb_Google_Integration {
    private static $instance = null;

    // Google service clients
    private static $drive   = null;
    private static $calendar= null;
    private static $sheets  = null;
    private static $slides  = null;

    public static function init() {
        if ( self::$instance ) return;
        self::$instance = new self();

        add_action( 'init', [ __CLASS__, 'register_assets' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin' ] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_frontend' ] );
		/* add_action( 'init', [ 'KGSweb_Google_Integration', 'register_shortcodes' ] ); */
        add_action( 'kgsweb_hourly_cache_refresh', [ __CLASS__, 'cron_refresh_all_caches' ] );
    }

    public static function get_settings() {
        return get_option( KGSWEB_SETTINGS_OPTION, [] );
    }

    public static function register_assets() {
        // CSS
        wp_register_style( 'kgsweb-style', KGSWEB_PLUGIN_URL . 'css/kgsweb-style.css', [], KGSWEB_PLUGIN_VERSION );
        // JS (front-end)
        wp_register_script( 'kgsweb-helpers',  KGSWEB_PLUGIN_URL . 'js/kgsweb-helpers.js', [], KGSWEB_PLUGIN_VERSION, true );
        wp_register_script( 'kgsweb-cache',    KGSWEB_PLUGIN_URL . 'js/kgsweb-cache.js',    ['kgsweb-helpers'], KGSWEB_PLUGIN_VERSION, true );
        wp_register_script( 'kgsweb-datetime', KGSWEB_PLUGIN_URL . 'js/kgsweb-datetime.js', ['kgsweb-helpers'], KGSWEB_PLUGIN_VERSION, true );
        wp_register_script( 'kgsweb-ticker',   KGSWEB_PLUGIN_URL . 'js/kgsweb-ticker.js',   ['kgsweb-helpers','kgsweb-cache'], KGSWEB_PLUGIN_VERSION, true );
        wp_register_script( 'kgsweb-calendar', KGSWEB_PLUGIN_URL . 'js/kgsweb-calendar.js', ['kgsweb-helpers','kgsweb-cache'], KGSWEB_PLUGIN_VERSION, true );
        wp_register_script( 'kgsweb-folders',  KGSWEB_PLUGIN_URL . 'js/kgsweb-folders.js',  ['kgsweb-helpers','kgsweb-cache'], KGSWEB_PLUGIN_VERSION, true );
        wp_register_script( 'kgsweb-menus',    KGSWEB_PLUGIN_URL . 'js/kgsweb-menus.js',    ['kgsweb-helpers','kgsweb-cache'], KGSWEB_PLUGIN_VERSION, true );
        wp_register_script( 'kgsweb-upload',   KGSWEB_PLUGIN_URL . 'js/kgsweb-upload.js',   ['kgsweb-helpers'], KGSWEB_PLUGIN_VERSION, true );
        // JS (admin)
        wp_register_script( 'kgsweb-admin',    KGSWEB_PLUGIN_URL . 'js/kgsweb-admin.js',    ['jquery'], KGSWEB_PLUGIN_VERSION, true );

        // Localize common config
        $cfg = [
            'rest' => [
                'root' => esc_url_raw( rest_url( 'kgsweb/v1' ) ),
                'nonce'=> wp_create_nonce( 'wp_rest' ),
            ],
            'assets' => [
                'fontawesome' => true, // assume loaded theme-side or enqueue in admin
            ],
        ];
        wp_localize_script( 'kgsweb-helpers', 'KGSWEB_CFG', $cfg );
    }

    public static function enqueue_admin( $hook ) {
        wp_enqueue_style( 'kgsweb-style' );
        wp_enqueue_script( 'kgsweb-admin' );
    }

    public static function enqueue_frontend() {
        // Enqueue as needed; shortcodes can also enqueue conditionally.
        wp_enqueue_style( 'kgsweb-style' );
        // JS enqueues are performed by shortcode renderers per presence.
    }

    // Build Google clients from stored service account JSON (do not expose to front-end)
    public static function get_drive() {
        if ( self::$drive ) return self::$drive;
        $json = self::get_settings()['service_account_json'] ?? '';
        if ( empty( $json ) ) return null;
        // TODO: Initialize Google Drive client with service account JSON (server-to-server)
        // self::$drive = new Google_Service_Drive($client);
        return self::$drive;
    }
    public static function get_calendar() { /* TODO */ return self::$calendar; }
    public static function get_sheets()   { /* TODO */ return self::$sheets; }
    public static function get_slides()   { /* TODO */ return self::$slides; }
	
	
	// Get Cached Documents Tree
	public static function get_cached_documents_tree( $root_id ) {
		$cache_key = 'kgsweb_docs_tree_' . md5( $root_id );
		$cached = get_transient( $cache_key );

		if ( $cached !== false ) {
			return $cached;
		}

		$tree = KGSweb_Google_Drive_Docs::build_documents_tree( $root_id );
		$tree = self::filter_empty_branches( $tree );

		set_transient( $cache_key, $tree, HOUR_IN_SECONDS );
		return $tree;
	}

    // Cron: refresh caches safely
    public static function cron_refresh_all_caches() {
        // Defer to module classes for each cache
		
		KGSweb_Google_Ticker::refresh_cache_cron();
		update_option( 'kgsweb_cache_last_refresh_ticker', time() );        
		
		KGSweb_Google_Upcoming_Events::refresh_cache_cron();
		update_option( 'kgsweb_cache_last_refresh_events', time() );       
		
		KGSweb_Google_Drive_Docs::refresh_cache_cron(); // includes menus, documents tree
		update_option( 'kgsweb_cache_last_refresh_drive_docs', time() );       
		
		// Slides/Sheets cache refresh can be on-demand or included here if configured
        update_option( 'kgsweb_cache_last_refresh_global', time() );
	}

    // Namespaced transient helpers
    public static function get_transient( $key ) { return get_transient( $key ); }
    public static function set_transient( $key, $val, $ttl ) { return set_transient( $key, $val, $ttl ); }
    public static function delete_transient( $key ) { return delete_transient( $key ); }
	
	
	
	
	
	
	public static function get_drive_client() {
    // Load Google API PHP Client
    $client = new Google_Client();
    $client->setAuthConfig( self::get_settings()['google_credentials_path'] );
    $client->addScope( Google_Service_Drive::DRIVE_READONLY );
    $client->setAccessType('offline');
    // Optionally refresh token here
    return $client;
	
	
	}
	
	
	
	
	
	
	
}
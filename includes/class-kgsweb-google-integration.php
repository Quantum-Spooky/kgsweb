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

	// Build and return a Google_Service_Drive instance (cached)
	public static function get_drive() {
		if ( self::$drive ) return self::$drive;

		$client = self::get_drive_client();
		if ( ! $client ) {
			error_log( 'KGSWeb: get_drive -> no Google client available.' );
			return null;
		}

		try {
			self::$drive = new Google_Service_Drive( $client );
			return self::$drive;
		} catch ( Exception $e ) {
			error_log( 'KGSWeb: Failed initializing Google_Service_Drive: ' . $e->getMessage() );
			return null;
		}
	}

	/**
	 * Create and return a configured Google_Client or null on error.
	 */
	
	public static function get_drive_client() {
		static $cached_client = null;
		if ($cached_client) return $cached_client;

		if (!class_exists('Google_Client')) {
			$autoload = __DIR__ . '/../vendor/autoload.php';
			if (file_exists($autoload)) {
				require_once $autoload;
			} else {
				error_log('KGSWeb: Google API client autoload missing at: ' . $autoload);
				return null;
			}
		}

		$settings = self::get_settings();
		$client   = new Google_Client();
		$client->setApplicationName('KGSWeb');
		$client->setScopes([
			Google_Service_Drive::DRIVE_READONLY,
			Google_Service_Drive::DRIVE_METADATA_READONLY,
		]);
		$client->setAccessType('offline');

		// Option 1: credentials file path
		if (!empty($settings['google_credentials_path'])) {
			$path = $settings['google_credentials_path'];
			if (!file_exists($path)) {
				$try = __DIR__ . '/../' . ltrim($path, '/\\');
				if (file_exists($try)) $path = $try;
			}
			if (file_exists($path)) {
				try {
					$client->setAuthConfig($path);
					return $cached_client = $client;
				} catch (Exception $e) {
					error_log('KGSWeb: setAuthConfig(path) failed: ' . $e->getMessage());
					return null;
				}
			} else {
				error_log('KGSWeb: google_credentials_path not found: ' . $settings['google_credentials_path']);
			}
		}

		// Option 2: service account JSON
		if (!empty($settings['service_account_json'])) {
			$json_normalized = str_replace('\\n', "\n", $settings['service_account_json']);
			$service_account = json_decode($json_normalized, true);

			if (!is_array($service_account) || empty($service_account['private_key']) || empty($service_account['client_email'])) {
				error_log('KGSWeb: service_account_json invalid after normalization.');
				return null;
			}

			try {
				$client->setAuthConfig($service_account);
				return $cached_client = $client;
			} catch (Exception $e) {
				error_log('KGSWeb: setAuthConfig(json) failed: ' . $e->getMessage());
				return null;
			}
		}

		error_log('KGSWeb: No Google credentials configured (google_credentials_path or service_account_json).');
		return null;
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
	
	
	
	
	
	

	
	
	
	
	
}

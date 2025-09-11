<?php
// includes/class-kgsweb-google-integration.php
if (!defined('ABSPATH')) { exit; }

use Google\Client;
use Google\Service\Drive;
use Google\Service\Calendar;
use Google\Service\Sheets;
use Google\Service\Slides;

class KGSweb_Google_Integration {

    /*******************************
     * Singleton Instance
     *******************************/
    private static ?self $instance = null;

    /*******************************
     * Google Service Clients
     *******************************/
    private ?Client $client   = null;
    private ?KGSweb_Google_Drive_Docs $drive = null;
    private ?Calendar $calendar = null;
    private ?Sheets $sheets = null;
    private ?Slides $slides = null;

    /*******************************
     * Plugin Paths
     *******************************/
    private string $plugin_url;
    private string $plugin_path;

    /*******************************
     * Configurable
     *******************************/
    private int $lockout_time = 86400; // 24 hours
    private int $max_attempts = 50;

    /*******************************
     * Singleton Init
     *******************************/
    public static function init(): self {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->register_hooks();
        }
        return self::$instance;
    }


    /*******************************
     * Constructor
     *******************************/
    private function __construct() {
        // $this->plugin_url  = plugin_dir_url(__FILE__);
        // $this->plugin_path = plugin_dir_path(__FILE__);
		$this->plugin_url  = plugin_dir_url(dirname(__FILE__, 1)); // plugin root
		$this->plugin_path = plugin_dir_path(dirname(__FILE__, 1));
    }

    /*******************************
     * WP Hooks
     *******************************/
    private function register_hooks(): void {
        add_action('init', [$this, 'register_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend']);

        // Shortcodes registration handled in KGSweb_Google_Shortcodes
        add_action('kgsweb_hourly_cache_refresh', [$this, 'cron_refresh_all_caches']);
        if (!wp_next_scheduled('kgsweb_hourly_cache_refresh')) {
            wp_schedule_event(time(), 'hourly', 'kgsweb_hourly_cache_refresh');
        }
    }

    /*******************************
     * Settings
     *******************************/
	public static function get_settings(): array {
		$settings = get_option(KGSWEB_SETTINGS_OPTION, []);
		// Individual Google/Plugin options
		$settings['service_account_json'] = get_option('kgsweb_service_account_json', '');
		$settings['public_docs_root_id']   = get_option('kgsweb_root_folder_id', '');
		$settings['upload_root_id']        = get_option('kgsweb_upload_root_folder_id', '');
		$settings['menu_breakfast_folder_id'] = get_option('kgsweb_breakfast_folder_id', '');
		$settings['menu_lunch_folder_id']     = get_option('kgsweb_lunch_folder_id', '');
		$settings['ticker_file_id']        = get_option('kgsweb_ticker_folder_id', '');
		$settings['calendar_id']           = get_option('kgsweb_calendar_ids', '');

		// Secure Upload options
		$secure_upload = get_option('kgsweb_secure_upload_options', []);
		$settings['upload_auth_mode']   = $secure_upload['upload_auth_mode'] ?? 'password';
		$settings['upload_password']    = $secure_upload['upload_password'] ?? '';
		$settings['google_groups']      = $secure_upload['google_groups'] ?? [];
		$settings['upload_destination'] = $secure_upload['upload_destination'] ?? 'drive';
		$settings['wp_upload_root_path']= $secure_upload['wp_upload_root'] ?? '';

		// Optional: debug log
		// error_log(print_r($settings, true));

		return $settings;
	}



	/*******************************
	 * Google Service Accessors
	 *******************************/

	/**
	 * Google Drive wrapper
	 */
	 public static function get_drive(): ?KGSweb_Google_Drive_Docs {
			$instance = self::init(); // <-- replace get_instance() with init()

			if (!$instance->client) {
				$instance->init_google_client(); // call non-static method properly
			}

			if ($instance->client && !$instance->drive) {
				$instance->drive = new KGSweb_Google_Drive_Docs($instance->client);
			}

			return $instance->drive;
		}

	/**
	 * Calendar service
	 */
	public static function get_calendar(): ?Calendar {
		$instance = self::init();
		if (!$instance->calendar) {
			$instance->init_google_client();
		}
		return $instance->calendar;
	}

	/**
	 * Sheets service
	 */
	public static function get_sheets(): ?Sheets {
		$instance = self::init();
		if (!$instance->sheets) {
			$instance->init_google_client();
		}
		return $instance->sheets;
	}

	/**
	 * Slides service
	 */
	public static function get_slides(): ?Slides {
		$instance = self::init();
		if (!$instance->slides) {
			$instance->init_google_client();
		}
		return $instance->slides;
	}

	/*******************************
	 * Google Client Lazy Loader
	 *******************************/
	private function init_google_client(): ?Client {
		if ($this->client) return $this->client;

		$settings = self::get_settings();
		$json = $settings['service_account_json'] ?? '';
		if (!$json) {
			error_log('KGSWEB: No service account JSON found.');
			return null;
		}
		
		if (!$json) {
			error_log('KGSWEB: No service account JSON found.');
		} else {
			error_log('KGSWEB: Service account JSON found, attempting client init.');
		}

		try {
			$client = new Client();
			$client->setAuthConfig(json_decode($json, true));
			$client->setScopes([
				Drive::DRIVE_READONLY,
				Drive::DRIVE_FILE,
				Drive::DRIVE_METADATA_READONLY,
				Calendar::CALENDAR_READONLY,
				Sheets::SPREADSHEETS_READONLY,
				Slides::PRESENTATIONS_READONLY,
				'profile',
				'email',
			]);
			$client->setAccessType('offline');

			// Assign services only if client initialized successfully
			$this->client   = $client;
			$this->calendar = new Calendar($client);
			$this->sheets   = new Sheets($client);
			$this->slides   = new Slides($client);
			$this->drive    = new KGSweb_Google_Drive_Docs($client);

			return $client;
		} catch (Exception $e) {
			error_log('KGSWEB: Google Client Init Error - ' . $e->getMessage());
			return null;
		}
	}


    /*******************************
     * Cached Documents Tree
     *******************************/
    public static function get_cached_documents_tree(string $root_id) {
        $cache_key = 'kgsweb_docs_tree_' . md5($root_id);
        $cached = get_transient($cache_key);
        if ($cached !== false) return $cached;
        $tree = KGSweb_Google_Drive_Docs::build_documents_tree($root_id);
        $tree = KGSweb_Google_Drive_Docs::filter_empty_branches($tree);
        set_transient($cache_key, $tree, HOUR_IN_SECONDS);
        return $tree;
    }

    /*******************************
     * Cron: Refresh All Caches
     *******************************/
    public function cron_refresh_all_caches(): void {
        KGSweb_Google_Ticker::refresh_cache_cron();
        update_option('kgsweb_cache_last_refresh_ticker', current_time('timestamp'));

        KGSweb_Google_Upcoming_Events::refresh_cache_cron();
        update_option('kgsweb_cache_last_refresh_events', current_time('timestamp'));

        KGSweb_Google_Drive_Docs::refresh_cache_cron();
        update_option('kgsweb_cache_last_refresh_drive_docs', current_time('timestamp'));

        update_option('kgsweb_cache_last_refresh_global', current_time('timestamp'));
        update_option('kgsweb_last_refresh', current_time('timestamp'));
    }

    /*******************************
     * Helpers: Transients
     *******************************/
    public static function get_transient(string $key) { return get_transient($key); }
    public static function set_transient(string $key, $val, int $ttl) { return set_transient($key, $val, $ttl); }
    public static function delete_transient(string $key) { return delete_transient($key); }

    /*******************************
     * Assets Registration
     *******************************/
    public function register_assets(): void {
        wp_register_style(
            'kgsweb-style',
            $this->plugin_url . 'css/kgsweb-style.css',
            [],
            filemtime($this->plugin_path . 'css/kgsweb-style.css')
        );

        wp_register_script(
            'kgsweb-helpers',
            $this->plugin_url . 'js/kgsweb-helpers.js',
            [],
            filemtime($this->plugin_path . 'js/kgsweb-helpers.js'),
            true
        );

        $modules = ['cache', 'datetime', 'ticker', 'calendar', 'folders', 'menus', 'upload', 'sheets', 'slides'];
        foreach ($modules as $mod) {
            wp_register_script(
                "kgsweb-$mod",
                $this->plugin_url . "js/kgsweb-$mod.js",
                ['kgsweb-helpers'],
                filemtime($this->plugin_path . "js/kgsweb-$mod.js"),
                true
            );
        }

        wp_register_script(
            'kgsweb-admin',
            $this->plugin_url . 'js/kgsweb-admin.js',
            ['jquery'],
            filemtime($this->plugin_path . 'js/kgsweb-admin.js'),
            true
        );

        // Localize helpers
        wp_localize_script('kgsweb-helpers', 'KGSWEB_CFG', [
            'rest' => [
                'root'  => esc_url_raw(rest_url('kgsweb/v1')),
                'nonce' => wp_create_nonce('wp_rest'),
            ],
            'ajax' => [
                'list_folders' => esc_url(admin_url('admin-ajax.php?action=kgsweb_list_folders')),
            ],
            'assets' => ['fontawesome' => true],
        ]);
    }

    /*******************************
     * Assets Enqueue (Frontend)
     *******************************/
	public function enqueue_frontend(): void {
		wp_enqueue_style('kgsweb-style');

		// Enqueue the folders JS (and other modules)
		wp_enqueue_script('kgsweb-folders', $this->plugin_url . 'js/kgsweb-folders.js', ['kgsweb-helpers'], filemtime($this->plugin_path . 'js/kgsweb-folders.js'), true);

		// Localize REST URL and default root ID for folders JS
		$settings = self::get_settings();
		wp_localize_script('kgsweb-folders', 'KGSwebFolders', [
			'restUrl' => esc_url_raw(rest_url('kgsweb/v1/documents')),
			'rootId'  => sanitize_text_field($settings['public_docs_root_id'] ?? ''),
		]);

		// Enqueue other JS modules as usual
		$frontend_js = ['cache','datetime','ticker','calendar','folders','menus','upload','sheets','slides','helpers'];
			foreach ($frontend_js as $mod) {
				wp_enqueue_script("kgsweb-$mod");
		}
	}
	
	
}

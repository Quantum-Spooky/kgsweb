<?php
// includes/class-kgsweb-google-integration.php
if (!defined("ABSPATH")) {
    exit();
}

use Google\Client;
use Google\Service\Drive;
use Google\Service\Docs;
use Google\Service\Calendar;
use Google\Service\Sheets;
use Google\Service\Slides;
		
class KGSweb_Google_Integration
{
    /*******************************
     * Singleton Instance
     *******************************/
    private static ?self $instance = null;

    /*******************************
     * Google Service Clients
     *******************************/
    private ?Client $client = null;
    private ?KGSweb_Google_Drive_Docs $drive = null;
	private ?Docs $docsService = null;
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
    public static function init(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->register_hooks();
        }
        return self::$instance;
    }

    /*******************************
     * Constructor
     *******************************/
    private function __construct()
    {
        $this->plugin_url = plugin_dir_url(dirname(__FILE__, 1)); // plugin root
        $this->plugin_path = plugin_dir_path(dirname(__FILE__, 1));
    }

    /*******************************
     * WP Hooks
     *******************************/
    private function register_hooks(): void
    {
        add_action("init", [$this, "register_assets"]);
        add_action("wp_enqueue_scripts", [$this, "enqueue_frontend"]);

        // Shortcodes registration handled in KGSweb_Google_Shortcodes
        add_action("kgsweb_hourly_cache_refresh", [
            $this,
            "cron_refresh_all_caches",
        ]);
        if (!wp_next_scheduled("kgsweb_hourly_cache_refresh")) {
            wp_schedule_event(time(), "hourly", "kgsweb_hourly_cache_refresh");
        }
    }

    /*******************************
     * Settings
     *******************************/

    public static function get_settings(): array
    {
        $settings = get_option(KGSWEB_SETTINGS_OPTION, []);

        // Individual Google/Plugin options
        $settings["service_account_json"] = get_option(
            "kgsweb_service_account_json",
            ""
        );
        $settings["public_docs_root_id"] = get_option(
            "kgsweb_root_folder_id",
            ""
        ); // Keep raw
        $settings["upload_root_id"] = get_option(
            "kgsweb_upload_root_folder_id",
            ""
        ); // Keep raw
        $settings["menu_breakfast_folder_id"] = get_option(
            "kgsweb_breakfast_folder_id",
            ""
        ); // Keep raw
        $settings["menu_lunch_folder_id"] = get_option(
            "kgsweb_lunch_folder_id",
            ""
        ); // Keep raw
        $settings["ticker_file_id"] = get_option("kgsweb_ticker_file_id", ""); // Keep raw
        $settings["calendar_id"] = get_option("kgsweb_calendar_ids", ""); // Keep raw

        // Secure Upload options
        $secure_upload = get_option("kgsweb_secure_upload_options", []);
        $settings["upload_auth_mode"] =
            $secure_upload["upload_auth_mode"] ?? "password";
        $settings["upload_password"] = $secure_upload["upload_password"] ?? "";
        $settings["google_groups"] = $secure_upload["google_groups"] ?? [];
        $settings["upload_destination"] =
            $secure_upload["upload_destination"] ?? "drive";
        $settings["wp_upload_root_path"] =
            $secure_upload["wp_upload_root"] ?? "";

        return $settings;
    }

    /*******************************
     * Google Service Accessors
     *******************************/

	/**
	 * Return Google Drive service for direct API access
	 */
	public static function get_drive_service(): ?Drive
	{
		$client = self::get_google_client();
		if (!$client) {
			error_log("[KGSweb] get_drive_service: Google Client not available.");
			return null;
		}
		return new Drive($client);
	}
	
	/**
     * Google Drive wrapper
     */
    public static function get_drive(): ?KGSweb_Google_Drive_Docs
    {
        $instance = self::init();
        if (!$instance->client) {
            $instance->init_google_client();
        }
        if ($instance->client && !$instance->drive) {
            $instance->drive = new KGSweb_Google_Drive_Docs($instance->client);
        }
		 return $instance->drive;
    }
		
	
	public static function get_docs_service() {
		$instance = self::init();
		if ($instance->docsService !== null) {
			return $instance->docsService;
		}

		if (!class_exists('\Google\Service\Docs')) {
			error_log('KGSWEB: Google Docs client class not available.');
			return null;
		}

		try {
			$client = self::get_google_client();
			if (!$client instanceof Client) {
				error_log('KGSWEB: get_docs_service - google client not available');
				return null;
			}
			$instance->docsService = new Docs($client);
			return $instance->docsService;
		} catch (Exception $e) {
			error_log('KGSWEB: Failed to initialize Docs service - ' . $e->getMessage());
			return null;
		}
	}


    /**
     * Calendar service
     */
    public static function get_calendar(): ?Calendar
    {
        $instance = self::init();
        if (!$instance->calendar) {
            $instance->init_google_client();
        }
        return $instance->calendar;
    }

    /**
     * Sheets service
     */
    public static function get_sheets(): ?Sheets
    {
        $instance = self::init();
        if (!$instance->sheets) {
            $instance->init_google_client();
        }
        return $instance->sheets;
    }

    /**
     * Slides service
     */
    public static function get_slides(): ?Slides
    {
        $instance = self::init();
        if (!$instance->slides) {
            $instance->init_google_client();
        }
        return $instance->slides;
    }

    /**
     * Return underlying Google Client instance (initializes if needed).
     */
    public static function get_google_client(): ?Client
    {
        $instance = self::init();
        if (!$instance->client) {
            $instance->init_google_client();
        }
        return $instance->client ?? null;
    }

    /*******************************
     * Events Cache Helper
     *******************************/
    public static function get_cached_events(string $calendar_id): ?array
    {
        $key = "kgsweb_calendar_events_" . md5($calendar_id);
        $cached = get_transient($key);
        if ($cached !== false) {
            return $cached;
        }
        $service = self::get_calendar();
        if (!$service) {
            return null;
        }
        try {
            $now = date("c");
            $events = $service->events->listEvents($calendar_id, [
                "timeMin" => $now,
                "orderBy" => "startTime",
                "singleEvents" => true,
                "maxResults" => 10,
            ]);

            $data = [];
            foreach ($events->getItems() as $event) {
                $data[] = [
                    "id" => $event->getId(),
                    "summary" => $event->getSummary(),
                    "location" => $event->getLocation(),
                    "start" =>
                        $event->getStart()->getDateTime() ??
                        $event->getStart()->getDate(),
                    "end" =>
                        $event->getEnd()->getDateTime() ??
                        $event->getEnd()->getDate(),
                ];
            }
            set_transient($key, $data, 15 * MINUTE_IN_SECONDS);
            return $data;
        } catch (Exception $e) {
            error_log("KGSWEB: Calendar fetch error - " . $e->getMessage());
            return null;
        }
	}
	
	 /*******************************
	 * Cron: Refresh All Caches
	 *******************************/
	public function cron_refresh_all_caches(): void
	{
		
		// HAS A refresh_cache_cron() METHOD IN ITS FEATURE CLASS AND IT IS USED HERE
		
		// --- Refresh Ticker ---
		if (KGSweb_Google_Ticker::refresh_cache_cron()) {
			update_option("kgsweb_cache_last_refresh_ticker", current_time("timestamp"));
		}
		
		// --- Refresh Drive Docs (Downloads) ---
		if (KGSweb_Google_Drive_Docs::refresh_cache_cron()) {
			update_option("kgsweb_cache_last_refresh_drive_docs", current_time("timestamp"));
		}

	// HAS A refresh_cache_cron() METHOD IN ITS FEATURE CLASS BUT IT ISN'T USED HERE

		// --- Refresh Upcoming Events ---
		$calendar_id = self::get_settings()["calendar_id"] ?? "";
		if ($calendar_id) {
			delete_transient("kgsweb_calendar_events_" . md5($calendar_id));
			self::get_cached_events($calendar_id);
			update_option("kgsweb_cache_last_refresh_events", current_time("timestamp"));
		}

		// --- Refresh Menus ---
		foreach (["breakfast", "lunch"] as $type) {
			KGSweb_Google_Menus::refresh_menu_cache($type);
		}
		
		// HAS NO METHOD CALLED refresh_cache_cron() IN ITS FEATURE CLASS 	
		
		// --- Refresh Upload Folder Tree ---
		$upload_root = get_option('kgsweb_upload_root_folder_id', '');
		if ($upload_root) {
			KGSweb_Google_Drive_Docs::cache_upload_folders($upload_root);
		}

		// --- Refresh Slides ---
		$slides_file = self::get_settings()["slides_file_id"] ?? "";
		if ($slides_file) {
			KGSweb_Google_Slides::refresh_cache($slides_file);
		}

		// --- Refresh Sheets ---
		$sheets_file = self::get_settings()["sheets_file_id"] ?? "";
		if ($sheets_file) {
			KGSweb_Google_Sheets::refresh_cache($sheets_file, "A1:Z100");
		}
		
		// GLOBAL CACHE REFRESH

		// --- Global refresh timestamp ---
		update_option("kgsweb_cache_last_refresh_global", current_time("timestamp"));
		update_option("kgsweb_last_refresh", current_time("timestamp"));
	}

    /*******************************
     * Helpers: Transients
     *******************************/
    public static function get_transient(string $key)
    {
        return get_transient($key);
    }
    public static function set_transient(string $key, $val, int $ttl)
    {
        return set_transient($key, $val, $ttl);
    }
    public static function delete_transient(string $key)
    {
        return delete_transient($key);
    }

    /*******************************
     * Assets Registration
     *******************************/
    public static function register_assets()
    {
        $base = plugin_dir_path(KGSWEB_PLUGIN_FILE);
        $baseurl = plugins_url("", KGSWEB_PLUGIN_FILE);

        // Helper function to get file modification time
        $ver = function ($relpath) use ($base) {
            $path = $base . ltrim($relpath, "/");
            return file_exists($path) ? filemtime($path) : time();
        };

        wp_register_style(
            "kgsweb-style",
            $baseurl . "/css/kgsweb-style.css",
            [],
            $ver("/css/kgsweb-style.css")
        );

        $scripts = [
            "helpers" => "/js/kgsweb-helpers.js",
            "cache" => "/js/kgsweb-cache.js",
            "ticker" => "/js/kgsweb-ticker.js",
            "calendar" => "/js/kgsweb-calendar.js",
            "menus" => "/js/kgsweb-menus.js",
            "upload" => "/js/kgsweb-upload.js",
            "sheets" => "/js/kgsweb-sheets.js",
            "slides" => "/js/kgsweb-slides.js",
        ];

        foreach ($scripts as $handle => $relpath) {
            wp_register_script(
                "kgsweb-$handle",
                $baseurl . $relpath,
                ["jquery"],
                $ver($relpath),
                true
            );
        }
		
        // Localize scripts
        wp_localize_script("kgsweb-helpers", "KGSWEB_CFG", [
            "rest" => [
                "root" => esc_url_raw(rest_url("kgsweb/v1")),
                "nonce" => wp_create_nonce("wp_rest"),
            ],
            "ajax" => [
                "list_folders" => esc_url(
                    admin_url("admin-ajax.php?action=kgsweb_list_folders")
                ),
            ],
            "assets" => ["fontawesome" => true],
        ]);
		
		wp_localize_script('kgsweb-upload', 'kgsweb_ajax', [
		  'ajax_url' => admin_url('admin-ajax.php'),
		  'uploadRootId' => get_option('kgsweb_upload_root_folder_id', ''),
		  'nonce' => wp_create_nonce('kgsweb_upload_nonce')
		]);
		

    }
    /*******************************
     * Assets Enqueue (Frontend)
     *******************************/
    public function enqueue_frontend(): void
    {
        $baseurl = $this->plugin_url;
        $base = $this->plugin_path;

        // Explicit load order for scripts
        $frontend_js = [
            "helpers",
            "format",
            "documents",
            "cache",
            "ticker",
            "calendar",
            "menus",
            "upload",
            "sheets",
        ];
        foreach ($frontend_js as $mod) {
            $path = $base . "js/kgsweb-$mod.js";
            if (file_exists($path)) {
                wp_enqueue_script(
                    "kgsweb-$mod",
                    $baseurl . "js/kgsweb-$mod.js",
                    ["jquery"],
                    filemtime($path),
                    true
                );
            }
        }

        // --- Localize KGSWEB_CFG for helpers ---
        if (wp_script_is("kgsweb-helpers", "enqueued")) {
            wp_localize_script("kgsweb-helpers", "KGSWEB_CFG", [
                "rest" => [
                    "root" => esc_url_raw(rest_url("kgsweb/v1")),
                    "nonce" => wp_create_nonce("wp_rest"),
                ],
                "ajax" => [
                    "list_folders" => esc_url(
                        admin_url("admin-ajax.php?action=kgsweb_list_folders")
                    ),
                ],
                "assets" => ["fontawesome" => true],
            ]);
            // Conditionally enqueue FontAwesome
            wp_enqueue_style(
                "font-awesome",
                "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css",
                [],
                "6.5.1"
            );
        }
        // --- User-Accessible Cache Refresh ---	
		if (wp_script_is("kgsweb-cache", "enqueued")) { 
			$secret = get_option('kgsweb_cache_refresh_secret', '');
			wp_localize_script("kgsweb-cache", "KGSwebCache", [
				"secret" => $secret,
				"restUrl" => esc_url_raw(rest_url("kgsweb/v1/cache-refresh"))
		]);
}

        // Localize folders for documents script
        if (wp_script_is("kgsweb-documents", "enqueued")) {
            $settings = self::get_settings();
            wp_localize_script("kgsweb-documents", "KGSwebFolders", [
                "restUrl" => esc_url_raw(rest_url("kgsweb/v1/documents")),
                "rootId" => $settings["public_docs_root_id"] ?? "",
            ]);
        }

        // Styles
        $style_path = $base . "css/kgsweb-style.css";
        if (file_exists($style_path)) {
            wp_enqueue_style(
                "kgsweb-frontend",
                $baseurl . "css/kgsweb-style.css",
                [],
                filemtime($style_path)
            );
        }
        // Conditionally load slides assets if shortcode exists
        if (
            is_singular() &&
            has_shortcode(get_post()->post_content ?? "", "kgsweb_slides")
        ) {
            $slides_js = $base . "js/kgsweb-slides.js";
            $slides_css = $base . "css/kgsweb-slides.css";

            if (file_exists($slides_js)) {
                wp_enqueue_script(
                    "kgsweb-slides",
                    $baseurl . "js/kgsweb-slides.js",
                    ["jquery"],
                    filemtime($slides_js),
                    true
                );
            }

            if (file_exists($slides_css)) {
                wp_enqueue_style(
                    "kgsweb-slides",
                    $baseurl . "css/kgsweb-slides.css",
                    [],
                    filemtime($slides_css)
                );
            }
        }
    }



    /*******************************
     * Google Client Lazy Loader
     *******************************/
    private function init_google_client(): ?Client
    {
        if ($this->client) {
            return $this->client;
        }

        $settings = self::get_settings();
        $json = $settings["service_account_json"] ?? "";
        if (!$json) {
            error_log("KGSWEB: No service account JSON found.");
            return null;
        }

        error_log(
            "KGSWEB: Service account JSON found, attempting client init."
        );

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
                "profile",
                "email",
            ]);
            $client->setAccessType("offline");

            // Assign services only if client initialized successfully
            $this->client = $client;
            $this->calendar = new Calendar($client);
            $this->sheets = new Sheets($client);
            $this->slides = new Slides($client);
            $this->drive = new KGSweb_Google_Drive_Docs($client);

            return $client;
        } catch (Exception $e) {
            error_log("KGSWEB: Google Client Init Error - " . $e->getMessage());
            return null;
        }
    }

    /*******************************
     * Cached Documents Tree
     *******************************/
    public static function get_cached_documents_tree(string $root_id)
    {
        $cache_key = "kgsweb_docs_tree_" . md5($root_id);
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
        $tree = KGSweb_Google_Drive_Docs::build_documents_tree($root_id);
        $tree = KGSweb_Google_Drive_Docs::filter_empty_branches($tree);
        set_transient($cache_key, $tree, HOUR_IN_SECONDS);
        return $tree;
    }
}



						// TEST
						/* add_action('admin_init', function() {
						if (!current_user_can('manage_options')) return;

						$upload_root_id = get_option('kgsweb_upload_root_folder_id', '');
						if (!$upload_root_id) {
							error_log("KGSWEB DEBUG: No upload root folder ID set.");
							return;
						}

						$drive_service = KGSweb_Google_Integration::get_drive_service();
						if (!$drive_service) {
							error_log("KGSWEB DEBUG: Drive service not initialized.");
							return;
						}

						function log_folder_tree($drive_service, $folder_id, $prefix = '') {
							try {
								$response = $drive_service->files->listFiles([
									'q' => "'$folder_id' in parents and trashed = false",
									'fields' => 'files(id, name, mimeType)',
								]);

								if (empty($response->files)) {
									error_log("KGSWEB DEBUG: {$prefix}[empty folder]");
								} else {
									foreach ($response->files as $file) {
										$type = ($file->mimeType === 'application/vnd.google-apps.folder') ? 'Folder' : 'File';
										error_log("KGSWEB DEBUG: {$prefix}{$type} - Name: {$file->name}, ID: {$file->id}");

										if ($file->mimeType === 'application/vnd.google-apps.folder') {
											log_folder_tree($drive_service, $file->id, $prefix . '  ');
										}
									}
								}
							} catch (Exception $e) {
								error_log("KGSWEB DEBUG: Drive API Error - " . $e->getMessage());
							}
						}

						error_log("KGSWEB DEBUG: Upload Root Folder ID = $upload_root_id");
						log_folder_tree($drive_service, $upload_root_id);
					}); */

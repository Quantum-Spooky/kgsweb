<?php
// includes/class-kgsweb-google-integration.php
if (!defined("ABSPATH")) {
    exit();
}

use Google\Client;
use Google\Service\Drive;
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
        // --- Refresh Ticker ---
        if (KGSweb_Google_Ticker::refresh_cache_cron()) {
            update_option(
                "kgsweb_cache_last_refresh_ticker",
                current_time("timestamp")
            );
        }

        // --- Refresh Upcoming Events ---
        $calendar_id = self::get_settings()["calendar_id"] ?? "";
        if ($calendar_id) {
            delete_transient("kgsweb_calendar_events_" . md5($calendar_id));
            self::get_cached_events($calendar_id);
            update_option(
                "kgsweb_cache_last_refresh_events",
                current_time("timestamp")
            );
        }

        // --- Refresh Drive Docs ---
        if (KGSweb_Google_Drive_Docs::refresh_cache_cron()) {
            update_option(
                "kgsweb_cache_last_refresh_drive_docs",
                current_time("timestamp")
            );
        }

        // --- Refresh Menus ---
        foreach (["breakfast", "lunch"] as $type) {
            KGSweb_Google_Menus::refresh_menu_cache($type);
        }

        //---  Refresh Slides ---
        $slides_file = self::get_settings()["slides_file_id"] ?? "";
        if ($slides_file) {
            KGSweb_Google_Slides::refresh_cache($slides_file);
        }

        // --- Refresh Sheets ---
        $sheets_file = self::get_settings()["sheets_file_id"] ?? "";
        if ($sheets_file) {
            KGSweb_Google_Sheets::refresh_cache($sheets_file, "A1:Z100");
        }

        // --- Global refresh timestamp ---
        update_option(
            "kgsweb_cache_last_refresh_global",
            current_time("timestamp")
        );
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
     * Standardized Google Drive Helpers
     *******************************/

    /**
     * List files in a folder
     * @param string $folder_id
     * @param array $options Optional keys: 'mimeType', 'orderBy', 'pageSize'
     * @return array List of files with keys: id, name, mimeType, modifiedTime
     */
    public static function list_files_in_folder(
        string $folder_id,
        array $options = []
    ): array {
        $drive = self::get_drive();
        if (!$drive) {
            return [];
        }

        // drive->list_files_in_folder currently accepts only folder_id; pass options if implemented later
        return $drive->list_files_in_folder($folder_id);
    }

    /**
     * Get contents of a Google file (Docs or plain text)
     * @param string $file_id
     * @param string|null $mimeType Optional, if known
     * @return string|null File contents or null on error
     */
    public static function get_file_contents(
        string $file_id,
        ?string $mimeType = null
    ): ?string {
        $drive = self::get_drive();
        if (!$drive) {
            return null;
        }

        return $drive->get_file_contents($file_id, $mimeType);
    }

    /**
     * Get the latest file in a folder.
     *
     * Strategy:
     * 1) Try to use cached documents tree (transient 'kgsweb_docs_tree_' . md5($folder_id))
     *    - If present, traverse it to find the newest file by modifiedTime.
     * 2) If cache missing or empty, call Drive API directly with orderBy modifiedTime desc, pageSize=1.
     *
     * Returns array with keys: id, name, mimeType, modifiedTime OR null if none found.
     */
    public static function get_latest_file_from_folder(
        string $folder_id
    ): ?array {
        if (empty($folder_id)) {
            return null;
        }

        $drive = self::get_drive();
        if (!$drive) {
            return null;
        }

        // 1) Try cached tree
        $cache_key = "kgsweb_docs_tree_" . md5($folder_id);
        $tree = self::get_transient($cache_key);

        $latest = null;

        if ($tree !== false && !empty($tree) && is_array($tree)) {
            // tree is an array of nodes; traverse recursively
            $walker = function ($nodes) use (&$walker, &$latest) {
                foreach ((array) $nodes as $n) {
                    if (!is_array($n)) {
                        continue;
                    }
                    if (($n["type"] ?? "") === "file") {
                        $mt = $n["modifiedTime"] ?? "";
                        // store minimal file info
                        $file = [
                            "id" => $n["id"] ?? "",
                            "name" => $n["name"] ?? "",
                            "mimeType" => $n["mime"] ?? ($n["mimeType"] ?? ""),
                            "modifiedTime" => $mt,
                        ];
                        if (
                            empty($latest) ||
                            strcmp(
                                $file["modifiedTime"],
                                $latest["modifiedTime"]
                            ) > 0
                        ) {
                            $latest = $file;
                        }
                    }
                    if (!empty($n["children"]) && is_array($n["children"])) {
                        $walker($n["children"]);
                    }
                }
            };
            $walker($tree);
            if ($latest) {
                return $latest;
            }
        }

        // 2) Fallback: call Drive API directly to get newest file
        $client = self::get_google_client();
        if (!$client instanceof Client) {
            error_log(
                "KGSWEB: get_latest_file_from_folder - google client not available"
            );
            return null;
        }

        try {
            $service = new Drive($client);

            // Query for docs or plain text files; include other types if needed
            $q = sprintf("'%s' in parents and trashed = false", $folder_id);

            // Try to prioritize docs & text. The Drive API won't accept OR with complex grouping easily,
            // so we will not restrict MIME types here to allow any file; but we will order by modifiedTime.
            $params = [
                "q" => $q,
                "orderBy" => "modifiedTime desc",
                "pageSize" => 50,
                "fields" => "files(id,name,mimeType,modifiedTime)",
            ];

            $response = $service->files->listFiles($params);
            $files = $response->getFiles() ?: [];

            // Prefer google-docs or text/plain first, but still return first modified file if none match.
            $preferred = null;
            foreach ($files as $f) {
                $meta = [
                    "id" => $f->getId(),
                    "name" => $f->getName(),
                    "mimeType" => $f->getMimeType(),
                    "modifiedTime" => $f->getModifiedTime() ?? "",
                ];
                if (
                    in_array(
                        $meta["mimeType"],
                        ["application/vnd.google-apps.document", "text/plain"],
                        true
                    )
                ) {
                    $preferred = $meta;
                    break;
                }
                // keep first as fallback
                if ($preferred === null) {
                    $preferred = $meta;
                }
            }

            if ($preferred) {
                return $preferred;
            }

            return null;
        } catch (Exception $e) {
            error_log(
                "KGSWEB: get_latest_file_from_folder - Drive API error: " .
                    $e->getMessage()
            );
            return null;
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

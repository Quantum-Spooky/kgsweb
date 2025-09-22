<?php
// includes/class-kgsweb-google-drive-docs.php
if (!defined('ABSPATH')) exit;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Docs;

class KGSweb_Google_Drive_Docs {

    /** @var Client */
    private Client $client;

    /** @var Docs|null */
    private ?Docs $docsService = null;

    /*******************************
     * Initialization
     *******************************/
    public static function init() {
        add_shortcode('kgsweb_documents', [__CLASS__, 'shortcode_render']);
        add_action('rest_api_init', [__CLASS__, 'register_rest_routes']);
    }

    public function __construct(Client $client) {
        $this->client = $client;
    }

    /*******************************
     * REST Routes
     *******************************/
    public static function register_rest_routes() {
        register_rest_route('kgsweb/v1', '/documents', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'rest_get_documents'],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function rest_get_documents(WP_REST_Request $request) {
        $root = $request->get_param('root');
        $sort_by = $request->get_param('sort_by') ?: 'alpha-asc';
        $collapsed = $request->get_param('collapsed') ?: 'false';

        return self::get_documents_tree_payload($root, $sort_by, $collapsed);
    }

    /*******************************
     * Root IDs
     *******************************/
    public static function get_public_root_id(): string {
        return KGSweb_Google_Integration::get_settings()['public_docs_root_id'] ?? '';
    }

    public static function get_upload_root_id(): string {
        return KGSweb_Google_Integration::get_settings()['upload_root_id'] ?? '';
    }

    /*******************************
     * Shortcode
     *******************************/
    public static function shortcode_render($atts = []) {
        $atts = shortcode_atts([
            'sort_by' => 'alpha-asc',
            'collapsed' => 'false',
        ], $atts, 'kgsweb_documents');

        $payload = self::get_documents_tree_payload('', $atts['sort_by'], $atts['collapsed']);
        if (is_wp_error($payload)) {
            return '<p>' . esc_html($payload->get_error_message()) . '</p>';
        }

        return KGSweb_Google_Helpers::render_tree_html($payload['tree'], $atts['collapsed']);
    }

    /*******************************
     * Document Tree Handling
     *******************************/
    public static function get_documents_tree_payload(string $folder_id = '', string $sort_by = 'alpha-asc', string $collapsed = 'false') {
        $root = $folder_id ?: self::get_public_root_id();
        if (empty($root)) {
            return new WP_Error('no_root', __('No document root configured.', 'kgsweb'), ['status' => 404]);
        }

        return KGSweb_Google_Helpers::get_cached_documents_tree($root);
    }

    public static function rebuild_documents_tree_cache(string $root): void {
        if (empty($root)) return;

        delete_transient('kgsweb_docs_tree_' . md5($root));
        KGSweb_Google_Helpers::get_cached_documents_tree($root);
    }

    /*******************************
     * Upload Tree Helpers
     *******************************/
    public static function folder_exists_in_upload_tree(string $folder_id): bool {
        $root = self::get_upload_root_id();
        $tree = get_transient('kgsweb_cache_upload_tree_' . $root);

        if ($tree === false) {
            $tree = KGSweb_Google_Helpers::get_cached_documents_tree($root);
        }

        return self::search_tree_for_id($tree, $folder_id);
    }

    private static function search_tree_for_id(array $nodes, string $id): bool {
        foreach ($nodes as $n) {
            if (($n['id'] ?? '') === $id) return true;
            if (!empty($n['children']) && self::search_tree_for_id($n['children'], $id)) return true;
        }
        return false;
    }

    public static function folder_path_from_id(string $folder_id): string {
        return sanitize_title($folder_id);
    }

																				   /*******************************
																					 * CRON Refresh
																					 * Rebuilds cached trees & menus
																					 *******************************/
																					public static function refresh_cache_cron() {
																						$integration = KGSweb_Google_Integration::init();

																						// Rebuild public docs tree
																						self::rebuild_documents_tree_cache(self::get_public_root_id());

																					}

    /*******************************
     * Force-refresh file cache
     *******************************/
    public static function force_refresh_file_cache(string $file_id): ?string {
        $content = KGSweb_Google_Helpers::get_file_contents($file_id);
        if ($content !== null) {
            $cache_key = 'kgsweb_cache_file_' . $file_id;
            KGSweb_Google_Integration::set_transient($cache_key, $content, MINUTE_IN_SECONDS * 5);
            error_log("KGSWEB: Force-refreshed cache for file {$file_id}, length=" . strlen($content));
        } else {
            error_log("KGSWEB: Failed to force-refresh cache for {$file_id}");
        }
        return $content;
    }

    /*******************************
     * Menus (Breakfast/Lunch)
     *******************************/
    public static function get_menu_payload(string $type) {
        $key = 'kgsweb_cache_menu_' . $type;
        $data = get_transient($key);
        if ($data === false) {
            $data = [
                'type' => $type,
                'image_url' => '',
                'width' => 0,
                'height' => 0,
                'updated_at' => current_time('timestamp')
            ];
            set_transient($key, $data, HOUR_IN_SECONDS);
        }

        if (empty($data['image_url'])) {
            return new WP_Error('no_menu', __('Menu not available.', 'kgsweb'), ['status' => 404]);
        }

        return $data;
    }

    public static function refresh_menu_cache(string $type): void {
        $data = [
            'type' => $type,
            'image_url' => '',
            'width' => 0,
            'height' => 0,
            'updated_at' => current_time('timestamp')
        ];
        set_transient('kgsweb_cache_menu_' . $type, $data, HOUR_IN_SECONDS);
        update_option('kgsweb_cache_last_refresh_menu_' . $type, current_time('timestamp'));
    }

    /*******************************
     * Docs API Helpers
     *******************************/
    private function get_docs_service(): ?Docs {
        if ($this->docsService !== null) return $this->docsService;

        $client = KGSweb_Google_Integration::get_google_client();
        if (!$client instanceof Client) return null;

        try {
            $this->docsService = new Docs($client);
            return $this->docsService;
        } catch (Exception $e) {
            error_log('KGSWEB: Failed to initialize Docs service - ' . $e->getMessage());
            return null;
        }
    }

    public function get_file_contents(string $file_id, ?string $mime_type = null): string {
        return KGSweb_Google_Helpers::get_file_contents($file_id, $mime_type) ?? '';
    }
}
